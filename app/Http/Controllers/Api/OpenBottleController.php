<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpenBottle;
use App\Models\Product;
use App\Support\StockLedger;
use Illuminate\Http\Request;

class OpenBottleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = OpenBottle::with(['product:id,name,sku'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('opened_at');

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function available(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $user = $request->user();

        $bottles = OpenBottle::with(['product:id,name,base_unit'])
            ->where('product_id', $request->product_id)
            ->where('status', 'open')
            ->where('remaining_volume_ml', '>', 0)
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('opened_at')
            ->get();

        return response()->json($bottles);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'opening_volume_ml' => 'nullable|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        if ($product->stock_quantity < 1) {
            return response()->json(['message' => 'No stock left to open bottle.'], 422);
        }

        $volume = (float) ($data['opening_volume_ml'] ?? $this->extractMlFromUnit($product->base_unit));
        if ($volume <= 0) {
            $volume = 750;
        }

        $product->decrement('stock_quantity', 1);
        $product->refresh();

        $openBottle = OpenBottle::create([
            'branch_id' => $request->user()->branch_id,
            'product_id' => $product->id,
            'opened_by' => $request->user()->id,
            'opening_volume_ml' => $volume,
            'remaining_volume_ml' => $volume,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        StockLedger::record(
            $product,
            'OPEN_BOTTLE',
            1,
            $request->user()->id,
            $request->user()->branch_id,
            'OPEN_BOTTLE',
            $openBottle->id,
            'Bottle opened for peg service',
            ['opening_volume_ml' => $volume]
        );

        return response()->json($openBottle->load('product'), 201);
    }

    public function pour(Request $request, OpenBottle $openBottle)
    {
        $data = $request->validate([
            'volume_ml' => 'required|numeric|min:1',
        ]);

        $volume = (float) $data['volume_ml'];
        if ($openBottle->remaining_volume_ml < $volume) {
            return response()->json(['message' => 'Not enough remaining volume in this bottle.'], 422);
        }

        $openBottle->remaining_volume_ml -= $volume;
        if ($openBottle->remaining_volume_ml <= 0) {
            $openBottle->remaining_volume_ml = 0;
            $openBottle->status = 'empty';
            $openBottle->closed_at = now();
        }
        $openBottle->save();

        return response()->json($openBottle->fresh('product'));
    }

    public function close(OpenBottle $openBottle)
    {
        $openBottle->status = $openBottle->remaining_volume_ml > 0 ? 'closed' : 'empty';
        $openBottle->closed_at = now();
        $openBottle->save();

        return response()->json($openBottle->fresh('product'));
    }

    private function extractMlFromUnit(?string $value): float
    {
        if (!$value) {
            return 0;
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*ml/i', $value, $matches)) {
            return (float) $matches[1];
        }

        return 0;
    }
}
