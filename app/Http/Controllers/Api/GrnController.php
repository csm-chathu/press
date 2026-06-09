<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Support\AccountingService;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Grn::with(['supplier:id,name', 'purchase:id,purchase_number', 'user:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q, $s) => $q->where('grn_number', 'like', "%$s%"))
            ->latest('received_at');

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'supplier_invoice_number' => 'nullable|string|max:120',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.purchase_item_id' => 'nullable|exists:purchase_items,id',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.free_quantity' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:120',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        $purchase = Purchase::with('items')->findOrFail($data['purchase_id']);
        if (!$request->user()->isAdmin() && $purchase->branch_id !== $request->user()->branch_id) {
            abort(403, 'Forbidden for this branch.');
        }
        if (!in_array($purchase->status, ['draft', 'approved', 'sent', 'partial_received'])) {
            return response()->json(['message' => 'Cannot create a GRN against a purchase with status: ' . $purchase->status], 422);
        }

        DB::beginTransaction();
        try {
            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $lineTotal = 0;

            $grn = Grn::create([
                'branch_id' => $request->user()->branch_id,
                'grn_number' => 'GRN-' . now()->format('Ymd') . '-' . str_pad(Grn::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'status' => 'received',
                'discount' => $discount,
                'tax' => $tax,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
                'received_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (float) $item['quantity_received'];
                $free = (float) ($item['free_quantity'] ?? 0);
                $unitCost = (float) $item['unit_cost'];
                $taxAmount = (float) ($item['tax_amount'] ?? 0);
                $total = ($qty * $unitCost) + $taxAmount;
                $lineTotal += $total;

                GrnItem::create([
                    'grn_id' => $grn->id,
                    'purchase_item_id' => $item['purchase_item_id'] ?? null,
                    'product_id' => $product->id,
                    'quantity_received' => $qty,
                    'free_quantity' => $free,
                    'unit_cost' => $unitCost,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $product->increment('stock_quantity', $qty + $free);

                $priceUpdate = ['purchase_price' => $unitCost];
                if (!empty($item['selling_price'])) {
                    $priceUpdate['selling_price'] = (int) $item['selling_price'];
                }
                Product::where('id', $product->id)->update($priceUpdate);

                $product->refresh();

                StockLedger::record(
                    $product,
                    'IN',
                    $qty + $free,
                    $request->user()->id,
                    $request->user()->branch_id,
                    'GRN',
                    $grn->id,
                    'Stock received against GRN',
                    [
                        'purchase_id' => $purchase->id,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]
                );
            }

            $grn->update(['total' => max(0, $lineTotal - $discount + $tax)]);
            AccountingService::postGrn($grn->fresh());

            $expected = $purchase->items->sum('quantity');
            $received = GrnItem::whereIn('grn_id', Grn::where('purchase_id', $purchase->id)->pluck('id'))
                ->sum(DB::raw('quantity_received + free_quantity'));

            $purchase->status = $received >= $expected ? 'completed' : 'partial_received';
            $purchase->save();

            DB::commit();
            return response()->json($grn->load('items.product', 'supplier', 'purchase', 'user'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
