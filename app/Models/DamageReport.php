<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    protected $fillable = [
        'branch_id',
        'reference_number',
        'product_id',
        'quantity',
        'reason',
        'staff_name',
        'notes',
        'photo_path',
        'estimated_loss',
        'status',
        'user_id',
        'occurred_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'estimated_loss' => 'float',
        'occurred_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
