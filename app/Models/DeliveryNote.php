<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $fillable = [
        'branch_id',
        'delivery_number',
        'customer_id',
        'order_id',
        'delivery_date',
        'delivery_method',
        'vehicle_details',
        'tracking_number',
        'status',
        'total_quantity',
        'delivered_quantity',
        'delivery_address',
        'notes',
        'dispatched_by',
        'dispatched_at',
        'delivered_at',
    ];

    protected $casts = [
        'delivery_date'      => 'date',
        'dispatched_at'      => 'datetime',
        'delivered_at'       => 'datetime',
        'total_quantity'     => 'integer',
        'delivered_quantity' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'order_id');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'DN-' . now()->format('Ymd') . '-';
        $last   = static::whereDate('created_at', today())
            ->where('delivery_number', 'like', $prefix . '%')
            ->max('delivery_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
