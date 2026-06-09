<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku', 'description', 'source_type', 'buyback_id', 'product_id',
        'gold_rate_id', 'branch_id', 'user_id', 'karat', 'weight_g',
        'estimated_value', 'status', 'refinery_name', 'refinery_notes', 'notes',
    ];

    protected $casts = [
        'weight_g'        => 'float',
        'estimated_value' => 'float',
    ];

    public function buyback()
    {
        return $this->belongsTo(GoldBuyback::class, 'buyback_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function goldRate()
    {
        return $this->belongsTo(GoldRate::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->sku)) {
                $model->sku = 'SCR-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }
}
