<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Support\AccountingService;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReturnController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = SupplierReturn::with(['supplier:id,name', 'grn:id,grn_number'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->latest('returned_at');

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'grn_id' => 'nullable|exists:grns,id',
            'status' => 'required|in:draft,approved,sent,completed,cancelled',
            'reason' => 'nullable|string|max:120',
            'credit_note_number' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:120',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;

            $supplierReturn = SupplierReturn::create([
                'branch_id' => $request->user()->branch_id,
                'return_number' => 'SR-' . now()->format('Ymd') . '-' . str_pad(SupplierReturn::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $data['supplier_id'],
                'grn_id' => $data['grn_id'] ?? null,
                'status' => $data['status'],
                'reason' => $data['reason'] ?? null,
                'credit_note_number' => $data['credit_note_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'returned_at' => now(),
                'total_amount' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineTotal = $qty * $unitCost;

                if ($product->stock_quantity < $qty) {
                    throw new \Exception("Insufficient stock for return: {$product->name}");
                }

                $product->decrement('stock_quantity', $qty);
                $product->refresh();

                SupplierReturnItem::create([
                    'supplier_return_id' => $supplierReturn->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal,
                    'reason' => $item['reason'] ?? ($data['reason'] ?? null),
                ]);

                $totalAmount += $lineTotal;

                StockLedger::record(
                    $product,
                    'RETURN',
                    $qty,
                    $request->user()->id,
                    $request->user()->branch_id,
                    'SUPPLIER_RETURN',
                    $supplierReturn->id,
                    'Returned to supplier',
                    ['reason' => $item['reason'] ?? null]
                );
            }

            $supplierReturn->update(['total_amount' => $totalAmount]);
            AccountingService::postSupplierReturn($supplierReturn->fresh());

            DB::commit();
            return response()->json($supplierReturn->load('items.product', 'supplier', 'grn'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
