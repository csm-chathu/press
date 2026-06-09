<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BottleDeposit extends Model
{
    protected $fillable = [
        'branch_id',
        'sale_id',
        'customer_id',
        'supplier_id',
        'purchase_id',
        'product_id',
        'user_id',
        'type',
        'status',
        'quantity',
        'amount_per_bottle',
        'total_amount',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'amount_per_bottle' => 'float',
        'total_amount' => 'float',
        'processed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function purchase()
    {
        return $this->belongsTo(\App\Models\Purchase::class);
    }
}
