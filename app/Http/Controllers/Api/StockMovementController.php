<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = StockMovement::with(['product:id,name,sku', 'user:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->product_id, fn($q, $p) => $q->where('product_id', $p))
            ->when($request->movement_type, fn($q, $t) => $q->where('movement_type', $t))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('moved_at', '>=', $d))
            ->when($request->date_to, fn($q, $d) => $q->whereDate('moved_at', '<=', $d))
            ->latest('moved_at');

        return response()->json($query->paginate($request->per_page ?? 50));
    }
}
