<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'city',
        'country', 'date_of_birth', 'gender', 'notes', 'branch_id',
        'id_type', 'id_number', 'id_expiry', 'kyc_verified', 'kyc_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'id_expiry'     => 'date',
        'kyc_verified'  => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function buybacks()
    {
        return $this->hasMany(GoldBuyback::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function totalPurchasedAttribute(): float
    {
        return $this->sales()->where('payment_status', 'paid')->sum('total');
    }
}
