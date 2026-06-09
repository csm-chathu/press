<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StockMovement;

class StockLedger
{
    public static function record(
        Product $product,
        string $movementType,
        float $quantity,
        ?int $userId = null,
        ?int $branchId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?array $meta = null
    ): StockMovement {
        return StockMovement::create([
            'branch_id' => $branchId ?? $product->branch_id,
            'product_id' => $product->id,
            'user_id' => $userId,
            'movement_type' => $movementType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'balance_after' => $product->stock_quantity,
            'unit' => $product->base_unit ?? $product->unit_type,
            'notes' => $notes,
            'meta' => $meta,
        ]);
    }
}
