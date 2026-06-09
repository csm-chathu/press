<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'user_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'balance_after',
        'unit',
        'notes',
        'meta',
        'moved_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'balance_after' => 'float',
        'moved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
