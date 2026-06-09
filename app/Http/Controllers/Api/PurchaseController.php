<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\StockLedger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $purchases = Purchase::with(['supplier:id,name', 'user:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where('purchase_number', 'like', "%$s%"))
            ->when(request('supplier_id'), fn($q, $s) => $q->where('supplier_id', $s))
            ->when(request('receivable'), fn($q) => $q->whereIn('status', ['draft', 'approved', 'sent', 'partial_received']))
            ->latest('purchased_at')
            ->paginate(request('per_page', 20));
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
            'tax'         => 'nullable|numeric|min:0',
            'status'      => 'required|in:draft,approved,sent,partial_received,completed,cancelled,received',
            'notes'       => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_cost'] * $i['quantity']);
            $tax      = $data['tax'] ?? 0;

            $purchase = Purchase::create([
                'branch_id'       => $request->user()->branch_id,
                'purchase_number' => 'PO-' . now()->format('Ymd') . '-' . str_pad(Purchase::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id'     => $data['supplier_id'],
                'user_id'         => $request->user()->id,
                'subtotal'        => $subtotal,
                'tax'             => $tax,
                'total'           => $subtotal + $tax,
                'status'          => $data['status'],
                'notes'           => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if (!$request->user()->isAdmin() && $product->branch_id !== $request->user()->branch_id) {
                    throw new \Exception("Product is not available for your branch: {$product->name}");
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total'       => $item['unit_cost'] * $item['quantity'],
                ]);
                if ($data['status'] === 'received') {
                    $product->increment('stock_quantity', $item['quantity']);
                    $product->refresh();
                    StockLedger::record(
                        $product,
                        'IN',
                        (float) $item['quantity'],
                        $request->user()->id,
                        $request->user()->branch_id,
                        'PO',
                        $purchase->id,
                        'Stock received directly from purchase',
                        ['purchase_number' => $purchase->purchase_number]
                    );
                }
            }

            DB::commit();
            return response()->json($purchase->load(['items.product', 'supplier', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        return response()->json($purchase->load(['items.product', 'supplier', 'user']));
    }

    public function downloadPdf(Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        $purchase->load(['items.product', 'supplier', 'user']);
        $pdf = Pdf::loadView('pdf.purchase_order', ['purchase' => $purchase])->setPaper('A4');
        return $pdf->download("PO-{$purchase->purchase_number}.pdf");
    }

    public function destroy(Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        $purchase->delete();
        return response()->json(['message' => 'Purchase deleted']);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
