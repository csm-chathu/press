<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpenBundle;
use App\Models\Product;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenBundleController extends Controller
{
    // List open bundles for a product (or all products in branch)
    public function index(Request $request)
    {
        $user = $request->user();

        $query = OpenBundle::with(['product:id,name,sku,base_unit,bundle_size', 'openedBy:id,name', 'jobCard:id,job_number'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->product_id, fn($q, $id) => $q->where('product_id', $id))
            ->when($request->status ?? 'open', fn($q, $s) => $q->where('status', $s))
            ->latest('opened_at');

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    // Open a new bundle (decrement stock_quantity by 1 bundle, add open_bundle record)
    public function open(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'job_card_id' => 'nullable|exists:job_cards,id',
            'bundle_ref'  => 'nullable|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $this->authorizeBranch($product->branch_id);

        if (!$product->hasBundles()) {
            return response()->json(['message' => 'This product does not have a bundle size configured.'], 422);
        }

        // stock_quantity is in sheets — opening a bundle = consuming bundle_size sheets from sealed stock
        if ($product->stock_quantity < $product->bundle_size) {
            return response()->json([
                'message' => "Insufficient stock. Need {$product->bundle_size} sheets but only {$product->stock_quantity} available.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Deduct bundle_size sheets from sealed stock
            $product->decrement('stock_quantity', $product->bundle_size);
            $product->refresh();

            $bundle = OpenBundle::create([
                'branch_id'        => $request->user()->branch_id,
                'product_id'       => $product->id,
                'job_card_id'      => $data['job_card_id'] ?? null,
                'opened_by'        => $request->user()->id,
                'bundle_ref'       => $data['bundle_ref'] ?? null,
                'bundle_size'      => $product->bundle_size,
                'sheets_used'      => 0,
                'sheets_remaining' => $product->bundle_size,
                'status'           => 'open',
                'opened_at'        => now(),
                'notes'            => $data['notes'] ?? null,
            ]);

            StockLedger::record(
                $product,
                'OUT',
                $product->bundle_size,
                $request->user()->id,
                $request->user()->branch_id,
                'BUNDLE_OPEN',
                $bundle->id,
                "Bundle opened — {$product->bundle_size} sheets removed from sealed stock"
            );

            DB::commit();
            return response()->json($bundle->load('product:id,name,sku,bundle_size', 'openedBy:id,name'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Use sheets from an open bundle (called when paper is consumed in production)
    public function use(Request $request, OpenBundle $bundle)
    {
        $this->authorizeBranch($bundle->branch_id);

        $data = $request->validate([
            'sheets_used' => 'required|integer|min:1',
            'notes'       => 'nullable|string',
        ]);

        if ($bundle->status !== 'open') {
            return response()->json(['message' => 'Bundle is already empty/closed.'], 422);
        }

        if ($data['sheets_used'] > $bundle->sheets_remaining) {
            return response()->json([
                'message' => "Only {$bundle->sheets_remaining} sheets remaining in this bundle.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $bundle->sheets_used      += $data['sheets_used'];
            $bundle->sheets_remaining -= $data['sheets_used'];

            if ($bundle->sheets_remaining === 0) {
                $bundle->status     = 'empty';
                $bundle->closed_at  = now();
            }
            $bundle->save();

            DB::commit();
            return response()->json($bundle->fresh(['product:id,name,sku,bundle_size']));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Manually close/discard the rest of an open bundle (wastage)
    public function close(Request $request, OpenBundle $bundle)
    {
        $this->authorizeBranch($bundle->branch_id);

        if ($bundle->status !== 'open') {
            return response()->json(['message' => 'Bundle is already closed.'], 422);
        }

        $remaining = $bundle->sheets_remaining;

        DB::beginTransaction();
        try {
            $bundle->update([
                'status'    => 'empty',
                'closed_at' => now(),
                'notes'     => ($bundle->notes ? $bundle->notes . ' | ' : '') . 'Manually closed — ' . ($request->reason ?? 'wastage'),
            ]);

            // Log wasted sheets to stock ledger (they were already deducted when bundle was opened)
            if ($remaining > 0) {
                $product = $bundle->product;
                StockLedger::record(
                    $product,
                    'OUT',
                    $remaining,
                    $request->user()->id,
                    $bundle->branch_id,
                    'BUNDLE_WASTE',
                    $bundle->id,
                    "Bundle closed with {$remaining} sheets wasted"
                );
            }

            DB::commit();
            return response()->json($bundle->fresh());
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Summary: sheets in sealed stock + sheets in open bundles per product
    public function summary(Request $request)
    {
        $user = $request->user();

        $bundles = OpenBundle::with('product:id,name,sku,base_unit,bundle_size,stock_quantity')
            ->where('status', 'open')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->selectRaw('product_id, count(*) as open_bundle_count, sum(sheets_remaining) as open_sheets, sum(sheets_used) as used_sheets')
            ->groupBy('product_id')
            ->get();

        return response()->json($bundles);
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
