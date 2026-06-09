<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\JobCard;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today     = today();
        $thisMonth = now()->startOfMonth();
        $user      = request()->user();

        $productsQuery  = Product::query();
        $customersQuery = Customer::query();
        $salesQuery     = Sale::query();
        $purchasesQuery = Purchase::query();
        $jobCardsQuery  = JobCard::query();

        if (!$user->isAdmin()) {
            $productsQuery->where('branch_id', $user->branch_id);
            $customersQuery->where('branch_id', $user->branch_id);
            $salesQuery->where('branch_id', $user->branch_id);
            $purchasesQuery->where('branch_id', $user->branch_id);
            $jobCardsQuery->where('branch_id', $user->branch_id);
        }

        $completedSales = (clone $salesQuery)->where('status', 'completed');

        // Press-specific production stats
        $activeJobs    = (clone $jobCardsQuery)->whereIn('status', ['designing', 'proof_approval', 'plate_making', 'printing', 'finishing', 'quality_check'])->count();
        $waitingJobs   = (clone $jobCardsQuery)->where('status', 'waiting')->count();
        $readyJobs     = (clone $jobCardsQuery)->where('status', 'ready')->count();
        $pendingQuotes = \App\Models\Quotation::when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereIn('status', ['draft', 'sent'])->count();

        // Customer outstanding
        $outstanding = (clone $completedSales)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->sum(DB::raw('total - amount_paid'));

        // Low stock (reorder alerts)
        $lowStockCount = (clone $productsQuery)
            ->whereRaw('stock_quantity <= reorder_level AND reorder_level > 0')
            ->count();

        return response()->json([
            // ── Press KPIs ──────────────────────────────────────────
            'totals' => [
                'revenue_today'     => (clone $completedSales)->whereDate('sold_at', $today)->sum('total'),
                'revenue_month'     => (clone $completedSales)->where('sold_at', '>=', $thisMonth)->sum('total'),
                'orders_today'      => (clone $salesQuery)->whereDate('sold_at', $today)->count(),
                'pending_quotes'    => $pendingQuotes,
                'active_jobs'       => $activeJobs,
                'waiting_jobs'      => $waitingJobs,
                'ready_for_dispatch'=> $readyJobs,
                'customer_outstanding' => round((float) $outstanding, 2),
                'low_stock_count'   => $lowStockCount,
                'purchases_month'   => (clone $purchasesQuery)->where('purchased_at', '>=', $thisMonth)->sum('total'),
                'customers'         => (clone $customersQuery)->count(),
            ],

            // ── Production status breakdown ──────────────────────────
            'production_status' => (clone $jobCardsQuery)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->whereNotIn('status', ['delivered'])
                ->groupBy('status')
                ->get(),

            // ── 30-day revenue trend ─────────────────────────────────
            'sales_chart' => (clone $completedSales)
                ->select(
                    DB::raw('DATE(sold_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('sold_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            // ── 6-month revenue bar ──────────────────────────────────
            'monthly_revenue' => (clone $completedSales)
                ->select(
                    DB::raw("DATE_FORMAT(sold_at, '%Y-%m') as month"),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as order_count')
                )
                ->where('sold_at', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get(),

            // ── Payment method breakdown ─────────────────────────────
            'payment_methods' => (clone $completedSales)
                ->select(
                    'payment_method',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total) as revenue')
                )
                ->where('sold_at', '>=', $thisMonth)
                ->groupBy('payment_method')
                ->orderByDesc('revenue')
                ->get(),

            // ── Jobs by machine this month ───────────────────────────
            'machine_utilization' => DB::table('production_jobs')
                ->join('press_machines', 'press_machines.id', '=', 'production_jobs.machine_id')
                ->when(!$user->isAdmin(), fn($q) => $q->where('production_jobs.branch_id', $user->branch_id))
                ->where('production_jobs.created_at', '>=', $thisMonth)
                ->select(
                    'press_machines.name',
                    DB::raw('COUNT(*) as job_count'),
                    DB::raw('SUM(production_jobs.output_quantity) as total_output')
                )
                ->groupBy('press_machines.id', 'press_machines.name')
                ->orderByDesc('job_count')
                ->get(),

            // ── Active job cards (upcoming due dates) ────────────────
            'upcoming_jobs' => (clone $jobCardsQuery)
                ->with(['customer:id,name', 'machine:id,name'])
                ->whereNotIn('status', ['delivered'])
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->take(8)
                ->get(['id', 'job_number', 'title', 'customer_id', 'status', 'due_date', 'machine_id']),

            // ── Low stock alerts ─────────────────────────────────────
            'low_stock' => (clone $productsQuery)
                ->with('category:id,name')
                ->whereRaw('stock_quantity <= reorder_level AND reorder_level > 0')
                ->take(10)
                ->get(['id', 'name', 'sku', 'stock_quantity', 'reorder_level', 'category_id']),

            // ── Recent orders ────────────────────────────────────────
            'recent_sales' => (clone $salesQuery)
                ->with('customer:id,name')
                ->latest('sold_at')
                ->take(6)
                ->get(['id', 'invoice_number', 'customer_id', 'total', 'payment_status', 'order_status', 'sold_at', 'status']),
        ]);
    }
}
