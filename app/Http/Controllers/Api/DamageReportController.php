<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Product;
use App\Support\AccountingService;
use App\Support\StockLedger;
use Illuminate\Http\Request;

class DamageReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = DamageReport::with(['product:id,name,sku', 'user:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($inner) use ($search) {
                    $inner->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('product', fn($productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('occurred_at');

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:120',
            'staff_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:reported,approved,written_off',
        ]);

        $product = Product::findOrFail($data['product_id']);
        if ($product->stock_quantity < $data['quantity']) {
            return response()->json(['message' => 'Insufficient stock for damage write-off.'], 422);
        }

        $product->decrement('stock_quantity', $data['quantity']);
        $product->refresh();

        $damage = DamageReport::create([
            'branch_id' => $request->user()->branch_id,
            'reference_number' => 'DMG-' . now()->format('Ymd') . '-' . str_pad(DamageReport::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
            'reason' => $data['reason'],
            'staff_name' => $data['staff_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'estimated_loss' => (float) $data['quantity'] * (float) $product->purchase_price,
            'status' => $data['status'] ?? 'reported',
            'user_id' => $request->user()->id,
            'occurred_at' => now(),
        ]);

        StockLedger::record(
            $product,
            'DAMAGE',
            (float) $data['quantity'],
            $request->user()->id,
            $request->user()->branch_id,
            'DAMAGE',
            $damage->id,
            'Stock reduced due to damage',
            ['reason' => $data['reason']]
        );

        AccountingService::postDamage($damage);

        return response()->json($damage->load('product', 'user'), 201);
    }

    public function update(Request $request, DamageReport $damageReport)
    {
        if (!$request->user()->isAdmin() && $damageReport->branch_id !== $request->user()->branch_id) {
            abort(403, 'Forbidden for this branch.');
        }

        $data = $request->validate([
            'reason' => 'sometimes|string|max:120',
            'staff_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:reported,approved,written_off',
        ]);

        $damageReport->update($data);

        return response()->json($damageReport->fresh()->load('product:id,name,sku', 'user:id,name'));
    }
}
