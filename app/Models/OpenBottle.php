<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenBottle extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'sale_id',
        'opened_by',
        'opening_volume_ml',
        'remaining_volume_ml',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'opening_volume_ml' => 'float',
        'remaining_volume_ml' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
