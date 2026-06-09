<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    protected $fillable = [
        'branch_id',
        'return_number',
        'supplier_id',
        'grn_id',
        'status',
        'reason',
        'credit_note_number',
        'total_amount',
        'notes',
        'user_id',
        'returned_at',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'returned_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SupplierReturnItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }
}
