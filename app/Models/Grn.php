<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grn extends Model
{
    protected $fillable = [
        'branch_id',
        'grn_number',
        'purchase_id',
        'supplier_id',
        'supplier_invoice_number',
        'status',
        'discount',
        'tax',
        'total',
        'notes',
        'user_id',
        'received_at',
    ];

    protected $casts = [
        'discount' => 'float',
        'tax' => 'float',
        'total' => 'float',
        'received_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(GrnItem::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
