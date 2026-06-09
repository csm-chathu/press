<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'purchase_number', 'supplier_id', 'user_id', 'subtotal',
        'tax', 'total', 'status', 'notes', 'purchased_at',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'subtotal'     => 'float',
        'tax'          => 'float',
        'total'        => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
