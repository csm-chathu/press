<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    protected $fillable = [
        'grn_id',
        'purchase_item_id',
        'product_id',
        'quantity_received',
        'free_quantity',
        'unit_cost',
        'tax_amount',
        'total',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'quantity_received' => 'float',
        'free_quantity' => 'float',
        'unit_cost' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
        'expiry_date' => 'date',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
