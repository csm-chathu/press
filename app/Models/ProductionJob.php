<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionJob extends Model
{
    protected $fillable = [
        'branch_id',
        'job_card_id',
        'machine_id',
        'operator_id',
        'start_time',
        'end_time',
        'output_quantity',
        'waste_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_time'      => 'datetime',
        'end_time'        => 'datetime',
        'output_quantity' => 'integer',
        'waste_quantity'  => 'integer',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(PressMachine::class, 'machine_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }
}
