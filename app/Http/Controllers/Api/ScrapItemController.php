<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GoldRate;
use App\Models\Product;
use App\Models\ScrapItem;
use Illuminate\Http\Request;

class ScrapItemController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = ScrapItem::with([
            'buyback:id,buyback_number,customer_id',
            'buyback.customer:id,name',
            'product:id,name,sku',
            'user:id,name',
        ])->orderByDesc('created_at');

        if (!$user->isAdmin() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        return $query->paginate($request->per_page ?? 20);
    }

    // Convert an existing product to scrap
    public function convertProduct(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'weight_g'    => 'required|numeric|min:0.001',
            'notes'       => 'nullable|string',
        ]);

        $product  = Product::findOrFail($data['product_id']);
        $goldRate = GoldRate::today();

        // Deduct from product stock
        if ($product->stock_quantity < 1) {
            return response()->json(['message' => 'Product has no stock to convert.'], 422);
        }
        $product->decrement('stock_quantity');

        $karatPurity = ['9k' => 9/24, '14k' => 14/24, '18k' => 18/24, '22k' => 22/24, '24k' => 24/24];
        $purity      = $karatPurity[$product->karat] ?? 1;
        $estValue    = $goldRate ? round($goldRate->rate_per_gram * $data['weight_g'] * $purity, 2) : 0;

        $scrap = ScrapItem::create([
            'description'     => "Converted from: {$product->name} ({$product->sku})",
            'source_type'     => 'converted_product',
            'product_id'      => $product->id,
            'gold_rate_id'    => $goldRate?->id,
            'branch_id'       => $request->user()->branch_id,
            'user_id'         => $request->user()->id,
            'karat'           => $product->karat ?? 'mixed',
            'weight_g'        => $data['weight_g'],
            'estimated_value' => $estValue,
            'status'          => 'available',
            'notes'           => $data['notes'] ?? null,
        ]);

        AuditLog::record('scrap_converted', "Product {$product->sku} converted to scrap {$scrap->sku}", $scrap, null, [
            'product_id' => $product->id,
            'weight_g'   => $data['weight_g'],
        ]);

        return response()->json($scrap, 201);
    }

    public function update(Request $request, ScrapItem $scrapItem)
    {
        $data = $request->validate([
            'status'         => 'sometimes|in:available,sent_for_refining,melted,sold',
            'refinery_name'  => 'nullable|string|max:255',
            'refinery_notes' => 'nullable|string',
            'notes'          => 'nullable|string',
            'estimated_value'=> 'nullable|numeric|min:0',
        ]);

        $old = $scrapItem->only(['status', 'estimated_value']);
        $scrapItem->update($data);

        AuditLog::record('scrap_updated', "Scrap {$scrapItem->sku} updated to status: {$scrapItem->status}", $scrapItem, $old, $data);

        return response()->json($scrapItem);
    }

    public function destroy(ScrapItem $scrapItem)
    {
        AuditLog::record('scrap_deleted', "Scrap {$scrapItem->sku} deleted", $scrapItem, $scrapItem->toArray(), null);
        $scrapItem->delete();
        return response()->noContent();
    }
}
