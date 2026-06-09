<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturnItem extends Model
{
    protected $fillable = [
        'supplier_return_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'total' => 'float',
    ];

    public function supplierReturn()
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
