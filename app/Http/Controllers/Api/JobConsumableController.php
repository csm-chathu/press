<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\JobConsumable;
use App\Models\OpenBundle;
use App\Models\Product;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobConsumableController extends Controller
{
    public function index(JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);
        return response()->json($jobCard->consumables()->with('product:id,name,sku,base_unit')->latest()->get());
    }

    public function store(Request $request, JobCard $jobCard)
    {
        $this->authorizeBranch($jobCard->branch_id);

        $data = $request->validate([
            'type'        => 'required|in:plate,ink,paper,other',
            'description' => 'required|string|max:255',
            'product_id'  => 'nullable|exists:products,id',
            'quantity'    => 'required|numeric|min:0',
            'unit'        => 'required|string|max:20',
            'unit_cost'   => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $data['total_cost'] = round($data['quantity'] * $data['unit_cost'], 2);

        DB::beginTransaction();
        try {
            $consumable = $jobCard->consumables()->create([
                ...$data,
                'branch_id'  => $request->user()->branch_id,
                'created_by' => $request->user()->id,
            ]);

            if (!empty($data['product_id'])) {
                $product = Product::findOrFail($data['product_id']);
                $sheetsNeeded = (int) $data['quantity'];

                if ($product->hasBundles() && $data['type'] === 'paper') {
                    // Deduct from open bundles (oldest first), then from sealed stock if needed
                    $this->deductFromBundles($product, $sheetsNeeded, $request->user()->branch_id);
                } else {
                    $product->decrement('stock_quantity', $data['quantity']);
                    $product->refresh();

                    StockLedger::record(
                        $product,
                        'OUT',
                        $data['quantity'],
                        $request->user()->id,
                        $request->user()->branch_id,
                        'JOB_CONSUMABLE',
                        $consumable->id,
                        "Used in job card #{$jobCard->job_number} — {$data['type']}"
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($consumable->load('product'), 201);
    }

    public function destroy(JobConsumable $consumable)
    {
        $this->authorizeBranch($consumable->branch_id);

        DB::beginTransaction();
        try {
            if ($consumable->product_id) {
                $product = Product::find($consumable->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $consumable->quantity);
                    $product->refresh();

                    StockLedger::record(
                        $product,
                        'IN',
                        $consumable->quantity,
                        request()->user()->id,
                        $consumable->branch_id,
                        'JOB_CONSUMABLE_REVERSAL',
                        $consumable->id,
                        "Reversed — consumable deleted from job card"
                    );
                }
            }

            $consumable->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Deleted']);
    }

    // Deduct sheets from open bundles (FIFO), auto-open from sealed stock if no open bundle has enough
    private function deductFromBundles(Product $product, int $sheets, int $branchId): void
    {
        $remaining = $sheets;
        $userId    = request()->user()->id;

        // Pull sheets from existing open bundles (oldest first)
        $openBundles = OpenBundle::where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->where('status', 'open')
            ->orderBy('opened_at')
            ->get();

        foreach ($openBundles as $bundle) {
            if ($remaining <= 0) break;

            $take = min($remaining, $bundle->sheets_remaining);
            $bundle->sheets_used      += $take;
            $bundle->sheets_remaining -= $take;

            if ($bundle->sheets_remaining === 0) {
                $bundle->status    = 'empty';
                $bundle->closed_at = now();
            }
            $bundle->save();
            $remaining -= $take;
        }

        // If still sheets needed, auto-open a new bundle from sealed stock
        while ($remaining > 0 && $product->stock_quantity >= $product->bundle_size) {
            $product->decrement('stock_quantity', $product->bundle_size);
            $product->refresh();

            $take   = min($remaining, $product->bundle_size);
            $leftIn = $product->bundle_size - $take;

            $bundle = OpenBundle::create([
                'branch_id'        => $branchId,
                'product_id'       => $product->id,
                'opened_by'        => $userId,
                'bundle_size'      => $product->bundle_size,
                'sheets_used'      => $take,
                'sheets_remaining' => $leftIn,
                'status'           => $leftIn > 0 ? 'open' : 'empty',
                'opened_at'        => now(),
                'closed_at'        => $leftIn > 0 ? null : now(),
                'notes'            => 'Auto-opened by job consumable',
            ]);

            StockLedger::record(
                $product,
                'OUT',
                $product->bundle_size,
                $userId,
                $branchId,
                'BUNDLE_OPEN',
                $bundle->id,
                "Bundle auto-opened — {$product->bundle_size} sheets deducted from sealed stock"
            );

            $remaining -= $take;
        }
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
