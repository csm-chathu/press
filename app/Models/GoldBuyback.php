<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldBuyback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'buyback_number', 'customer_id', 'user_id', 'branch_id', 'gold_rate_id',
        'description', 'item_type', 'gross_weight', 'deduction_weight', 'net_weight',
        'declared_karat', 'assay_method', 'assay_karat', 'xrf_reading',
        'melt_loss_percent', 'assay_notes', 'rate_per_gram', 'buying_price_per_gram',
        'offered_total', 'final_price', 'payment_method', 'kyc_verified', 'status', 'notes',
    ];

    protected $casts = [
        'gross_weight'          => 'float',
        'deduction_weight'      => 'float',
        'net_weight'            => 'float',
        'assay_karat'           => 'float',
        'xrf_reading'           => 'float',
        'melt_loss_percent'     => 'float',
        'rate_per_gram'         => 'float',
        'buying_price_per_gram' => 'float',
        'offered_total'         => 'float',
        'final_price'           => 'float',
        'kyc_verified'          => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function goldRate()
    {
        return $this->belongsTo(GoldRate::class);
    }

    public function scrapItem()
    {
        return $this->hasOne(ScrapItem::class, 'buyback_id');
    }

    // Auto-generate buyback number
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->buyback_number)) {
                $model->buyback_number = 'BB-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }
}
