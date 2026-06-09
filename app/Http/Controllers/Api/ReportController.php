<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayEndReport;
use App\Models\GoldRate;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Metal Balance Report: grams by karat across all/branch products */
    public function metalBalance(Request $request)
    {
        $user = $request->user();

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotNull('karat')
            ->where('stock_quantity', '>', 0)
            ->get(['karat', 'weight', 'stock_quantity', 'purchase_price', 'selling_price', 'material', 'branch_id', 'name']);

        $goldRate = GoldRate::today();

        $karatPurity = GoldRate::$karatPurity;

        // Group by karat
        $byKarat = $products->groupBy('karat')->map(function ($items, $karat) use ($goldRate, $karatPurity) {
            $totalWeight = $items->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity);
            $purity      = $karatPurity[strtolower($karat)] ?? 1;
            $goldValueLkr = $goldRate ? $goldRate->rate_per_gram * $totalWeight * $purity : null;

            return [
                'karat'         => $karat,
                'purity'        => round($purity * 100, 2),
                'item_count'    => $items->count(),
                'piece_count'   => $items->sum('stock_quantity'),
                'total_weight_g'=> round($totalWeight, 3),
                'gold_value_lkr'=> $goldValueLkr ? round($goldValueLkr, 2) : null,
                'cost_value_lkr'=> round($items->sum(fn($p) => $p->purchase_price * $p->stock_quantity), 2),
                'sell_value_lkr'=> round($items->sum(fn($p) => $p->selling_price * $p->stock_quantity), 2),
            ];
        })->values();

        $totals = [
            'total_weight_g'=> round($byKarat->sum('total_weight_g'), 3),
            'gold_value_lkr'=> $goldRate ? round($byKarat->sum('gold_value_lkr'), 2) : null,
            'cost_value_lkr'=> round($byKarat->sum('cost_value_lkr'), 2),
            'sell_value_lkr'=> round($byKarat->sum('sell_value_lkr'), 2),
        ];

        return response()->json([
            'by_karat'  => $byKarat,
            'totals'    => $totals,
            'gold_rate' => $goldRate,
            'date'      => today()->toDateString(),
        ]);
    }

    /** Profit/Loss on rate fluctuations (unrealized P&L) */
    public function ratePnl(Request $request)
    {
        $user    = $request->user();
        $goldRate = GoldRate::today();

        if (!$goldRate) {
            return response()->json(['message' => 'No gold rate set for today'], 404);
        }

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotNull('karat')
            ->whereNotNull('weight')
            ->where('stock_quantity', '>', 0)
            ->get();

        $rows = $products->map(function ($p) use ($goldRate) {
            $purity       = GoldRate::$karatPurity[strtolower($p->karat)] ?? 1;
            $currentGoldV = $goldRate->rate_per_gram * ($p->weight ?? 0) * $purity;
            $costBasis    = $p->purchase_price;
            $unrealized   = ($currentGoldV - $costBasis) * $p->stock_quantity;

            return [
                'id'              => $p->id,
                'name'            => $p->name,
                'karat'           => $p->karat,
                'weight_g'        => $p->weight,
                'stock'           => $p->stock_quantity,
                'cost_per_unit'   => $costBasis,
                'gold_value_now'  => round($currentGoldV, 2),
                'unrealized_pnl'  => round($unrealized, 2),
                'pnl_percent'     => $costBasis > 0 ? round((($currentGoldV - $costBasis) / $costBasis) * 100, 2) : null,
            ];
        });

        return response()->json([
            'products'       => $rows,
            'total_unrealized_pnl' => round($rows->sum('unrealized_pnl'), 2),
            'gold_rate'      => $goldRate,
            'date'           => today()->toDateString(),
        ]);
    }

    /** Day-end: get system stock snapshot + previous reports */
    public function dayEnd(Request $request)
    {
        $user = $request->user();

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->with('category:id,name')
            ->get(['id','name','sku','karat','weight','stock_quantity','purchase_price','selling_price','category_id','branch_id']);

        $goldRate = GoldRate::today();

        $karatBreakdown = $products->whereNotNull('karat')
            ->groupBy('karat')
            ->map(fn($items, $karat) => [
                'karat'      => $karat,
                'pieces'     => $items->sum('stock_quantity'),
                'weight_g'   => round($items->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity), 3),
            ])->values();

        $reports = DayEndReport::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest('report_date')->take(10)->get();

        return response()->json([
            'products'        => $products,
            'karat_breakdown' => $karatBreakdown,
            'gold_rate'       => $goldRate,
            'recent_reports'  => $reports,
            'date'            => today()->toDateString(),
        ]);
    }

    /** Save a day-end physical count */
    public function storeDayEnd(Request $request)
    {
        $data = $request->validate([
            'report_date'           => 'required|date',
            'physical_gold_weight'  => 'required|numeric|min:0',
            'item_counts'           => 'required|array',
            'item_counts.*.product_id' => 'required|exists:products,id',
            'item_counts.*.physical_qty' => 'required|integer|min:0',
            'notes'                 => 'nullable|string',
            'status'                => 'required|in:draft,submitted',
        ]);

        $user     = $request->user();
        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->get(['id','weight','karat','stock_quantity','purchase_price']);

        $goldRate = GoldRate::today();
        $systemWeight = round($products->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity), 3);

        $report = DayEndReport::updateOrCreate(
            ['report_date' => $data['report_date']],
            [
                'created_by'           => $user->id,
                'branch_id'            => $user->branch_id ?? $products->first()?->branch_id,
                'system_gold_weight'   => $systemWeight,
                'physical_gold_weight' => $data['physical_gold_weight'],
                'variance_weight'      => round($data['physical_gold_weight'] - $systemWeight, 3),
                'system_stock_value'   => round($products->sum(fn($p) => $p->purchase_price * $p->stock_quantity), 2),
                'karat_breakdown'      => $products->whereNotNull('karat')->groupBy('karat')
                    ->map(fn($items, $k) => [
                        'karat'    => $k,
                        'pieces'   => $items->sum('stock_quantity'),
                        'weight_g' => round($items->sum(fn($p) => ($p->weight ?? 0) * $p->stock_quantity), 3),
                    ])->values(),
                'item_counts'          => $data['item_counts'],
                'notes'                => $data['notes'] ?? null,
                'status'               => $data['status'],
            ]
        );

        return response()->json($report, 201);
    }

    /** Sales summary report */
    public function salesSummary(Request $request)
    {
        $user = $request->user();

        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $sales = Sale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('
                COUNT(*) as count,
                SUM(total) as total_revenue,
                SUM(gold_value_total) as gold_value,
                SUM(gemstone_value_total) as gemstone_value,
                SUM(making_charges_total) as making_charges,
                SUM(wastage_total) as wastage,
                SUM(tax) as total_tax,
                SUM(discount) as total_discount
            ')
            ->first();

        return response()->json([
            'from'   => $from,
            'to'     => $to,
            'totals' => $sales,
        ]);
    }

    /** Top selling products by quantity and revenue */
    public function topProducts(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->when(!$user->isAdmin(), fn($q) => $q->where('sales.branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->select(
                'products.id',
                'products.name',
                'categories.name as category',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as order_count')
            )
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        return response()->json(['from' => $from, 'to' => $to, 'products' => $rows]);
    }

    /** Revenue breakdown by category */
    public function categorySales(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->when(!$user->isAdmin(), fn($q) => $q->where('sales.branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('COALESCE(categories.name, "Uncategorized") as category'),
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as order_count')
            )
            ->groupBy('categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json(['from' => $from, 'to' => $to, 'categories' => $rows]);
    }

    /** Revenue per table number */
    public function tablePerformance(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rows = Sale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', 'completed')
            ->selectRaw('
                COALESCE(table_number, "Walk-in") as table_label,
                COUNT(*) as bill_count,
                SUM(total) as total_revenue,
                AVG(total) as avg_bill,
                SUM(discount) as total_discount
            ')
            ->groupBy('table_label')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json(['from' => $from, 'to' => $to, 'tables' => $rows]);
    }

    /** Payment method breakdown */
    public function paymentMethods(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rows = Sale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', 'completed')
            ->selectRaw('
                payment_method,
                payment_status,
                COUNT(*) as bill_count,
                SUM(total) as total_revenue,
                SUM(amount_paid) as total_collected
            ')
            ->groupBy('payment_method', 'payment_status')
            ->orderByDesc('total_revenue')
            ->get();

        $grandTotal = $rows->sum('total_revenue');

        return response()->json(['from' => $from, 'to' => $to, 'methods' => $rows, 'grand_total' => $grandTotal]);
    }

    /** Sales performance by cashier/staff */
    public function cashierPerformance(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rows = DB::table('sales')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->when(!$user->isAdmin(), fn($q) => $q->where('sales.branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->where('sales.status', 'completed')
            ->whereNull('sales.deleted_at')
            ->select(
                'users.id',
                'users.name',
                'users.role',
                DB::raw('COUNT(*) as bill_count'),
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('AVG(sales.total) as avg_bill'),
                DB::raw('SUM(sales.discount) as total_discount')
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json(['from' => $from, 'to' => $to, 'staff' => $rows]);
    }

    /** Pending / partial bills */
    public function pendingBills(Request $request)
    {
        $user = $request->user();

        $bills = Sale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereIn('payment_status', ['pending', 'partial'])
            ->where('status', 'completed')
            ->with('customer:id,name,phone')
            ->orderByDesc('created_at')
            ->get(['id','invoice_number','table_number','customer_id','total','amount_paid','payment_status','created_at']);

        $totalOutstanding = $bills->sum(fn($b) => $b->total - $b->amount_paid);

        return response()->json(['bills' => $bills, 'total_outstanding' => $totalOutstanding]);
    }

    /** Current stock / inventory summary */
    public function stockSummary(Request $request)
    {
        $user = $request->user();

        $products = Product::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->with('category:id,name')
            ->get(['id','name','sku','category_id','stock_quantity','min_stock_level','purchase_price','selling_price','branch_id']);

        $totalValue   = $products->sum(fn($p) => $p->purchase_price * $p->stock_quantity);
        $retailValue  = $products->sum(fn($p) => $p->selling_price * $p->stock_quantity);
        $lowStockCount = $products->filter(fn($p) => $p->stock_quantity <= $p->min_stock_level)->count();
        $outOfStock   = $products->filter(fn($p) => $p->stock_quantity == 0)->count();

        return response()->json([
            'products'       => $products,
            'total_cost_value'  => round($totalValue, 2),
            'total_retail_value'=> round($retailValue, 2),
            'low_stock_count'=> $lowStockCount,
            'out_of_stock'   => $outOfStock,
        ]);
    }

    /** Daily revenue trend for a date range */
    public function dailyRevenue(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->subDays(29)->toDateString();
        $to   = $request->date_to   ?? now()->toDateString();

        $rows = Sale::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as bill_count, SUM(total) as revenue, SUM(discount) as discount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['from' => $from, 'to' => $to, 'days' => $rows]);
    }
}
