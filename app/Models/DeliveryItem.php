<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItem extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'job_card_id',
        'description',
        'quantity_ordered',
        'quantity_delivered',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered'   => 'integer',
        'quantity_delivered' => 'integer',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }
}
