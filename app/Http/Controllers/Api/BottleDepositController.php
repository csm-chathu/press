<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BottleDeposit;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BottleDepositController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = BottleDeposit::with(['product:id,name,sku', 'customer:id,name', 'supplier:id,name', 'purchase:id,purchase_number'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->latest('processed_at');

        return response()->json($query->paginate($request->per_page ?? 30));
    }

    /**
     * Available empty bottles per product (returned by customers, not yet sent to supplier).
     * available = sum(refund+credit qty) − sum(supplier_return qty)
     */
    public function available(Request $request)
    {
        $branchId = $request->user()->branch_id;
        $isAdmin  = $request->user()->isAdmin();

        $received = BottleDeposit::query()
            ->when(!$isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->whereIn('type', ['refund', 'credit'])
            ->with('product:id,name,sku')
            ->select('product_id', DB::raw('SUM(quantity) as qty_received'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $returned = BottleDeposit::query()
            ->when(!$isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->where('type', 'supplier_return')
            ->select('product_id', DB::raw('SUM(quantity) as qty_returned'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $result = $received->map(function ($row) use ($returned) {
            $qty = (float) $row->qty_received - (float) ($returned[$row->product_id]->qty_returned ?? 0);
            return [
                'product_id'   => $row->product_id,
                'product'      => $row->product,
                'qty_available' => max(0, $qty),
            ];
        })->values()->filter(fn($r) => $r['qty_available'] > 0)->values();

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'required|in:collect,refund,credit',
            'quantity' => 'required|numeric|min:0.001',
            'amount_per_bottle' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $totalAmount = (float) $data['quantity'] * (float) $data['amount_per_bottle'];
        $status = $data['type'] === 'collect' ? 'collected' : ($data['type'] === 'refund' ? 'refunded' : 'credited');

        $deposit = BottleDeposit::create([
            'branch_id' => $request->user()->branch_id,
            'sale_id' => $data['sale_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'status' => $status,
            'quantity' => $data['quantity'],
            'amount_per_bottle' => $data['amount_per_bottle'],
            'total_amount' => $totalAmount,
            'notes' => $data['notes'] ?? null,
            'processed_at' => now(),
        ]);

        return response()->json($deposit->load('product', 'customer'), 201);
    }

    public function processReturn(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'amount_per_bottle' => 'required|numeric|min:0',
            'method' => 'required|in:refund,credit',
            'notes' => 'nullable|string',
        ]);

        $total = (float) $data['quantity'] * (float) $data['amount_per_bottle'];

        $deposit = BottleDeposit::create([
            'branch_id' => $request->user()->branch_id,
            'customer_id' => $data['customer_id'] ?? null,
            'product_id' => $data['product_id'],
            'user_id' => $request->user()->id,
            'type' => $data['method'],
            'status' => $data['method'] === 'refund' ? 'refunded' : 'credited',
            'quantity' => $data['quantity'],
            'amount_per_bottle' => $data['amount_per_bottle'],
            'total_amount' => $total,
            'notes' => $data['notes'] ?? null,
            'processed_at' => now(),
        ]);

        return response()->json($deposit->load('product', 'customer'), 201);
    }

    public function returnToSupplier(Request $request)
    {
        $data = $request->validate([
            'supplier_id'      => 'required|exists:suppliers,id',
            'purchase_id'      => 'nullable|exists:purchases,id',
            'product_id'       => 'required|exists:products,id',
            'quantity'         => 'required|numeric|min:0.001',
            'amount_per_bottle'=> 'required|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $user     = $request->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->isAdmin();

        // Calculate available qty for this product
        $qtyReceived = BottleDeposit::when(!$isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->where('product_id', $data['product_id'])
            ->whereIn('type', ['refund', 'credit'])
            ->sum('quantity');

        $qtyAlreadyReturned = BottleDeposit::when(!$isAdmin, fn($q) => $q->where('branch_id', $branchId))
            ->where('product_id', $data['product_id'])
            ->where('type', 'supplier_return')
            ->sum('quantity');

        $available = (float) $qtyReceived - (float) $qtyAlreadyReturned;

        if ((float) $data['quantity'] > $available) {
            return response()->json([
                'message' => "Only {$available} bottles available to return for this product.",
            ], 422);
        }

        $total = (float) $data['quantity'] * (float) $data['amount_per_bottle'];

        $deposit = BottleDeposit::create([
            'branch_id'        => $branchId,
            'supplier_id'      => $data['supplier_id'],
            'purchase_id'      => $data['purchase_id'] ?? null,
            'product_id'       => $data['product_id'],
            'user_id'          => $user->id,
            'type'             => 'supplier_return',
            'status'           => 'supplier_returned',
            'quantity'         => $data['quantity'],
            'amount_per_bottle'=> $data['amount_per_bottle'],
            'total_amount'     => $total,
            'notes'            => $data['notes'] ?? null,
            'processed_at'     => now(),
        ]);

        return response()->json($deposit->load('product', 'supplier', 'purchase'), 201);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $query = BottleDeposit::query();
        if (!$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $collected       = (clone $query)->where('type', 'collect')->sum('total_amount');
        $refunded        = (clone $query)->where('type', 'refund')->sum('total_amount');
        $credited        = (clone $query)->where('type', 'credit')->sum('total_amount');
        $supplierReturned= (clone $query)->where('type', 'supplier_return')->sum('total_amount');

        return response()->json([
            'collected'        => (float) $collected,
            'refunded'         => (float) $refunded,
            'credited'         => (float) $credited,
            'supplier_returned'=> (float) $supplierReturned,
            'outstanding'      => (float) ($collected - $refunded - $credited),
        ]);
    }
}
