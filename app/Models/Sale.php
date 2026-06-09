<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'invoice_number', 'customer_id', 'user_id', 'quotation_id',
        'subtotal', 'discount', 'tax', 'tax_rate', 'total', 'payment_method',
        'card_reference', 'payment_status', 'amount_paid', 'notes', 'sold_at',
        'status', 'order_status', 'order_type', 'table_number',
    ];

    protected $casts = [
        'sold_at'    => 'datetime',
        'subtotal'   => 'float',
        'discount'   => 'float',
        'tax'        => 'float',
        'total'      => 'float',
        'amount_paid'=> 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
