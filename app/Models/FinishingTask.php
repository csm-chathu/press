<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishingTask extends Model
{
    protected $fillable = [
        'branch_id',
        'job_card_id',
        'cutting',
        'folding',
        'binding',
        'lamination',
        'uv_coating',
        'foiling',
        'die_cutting',
        'packaging',
        'lamination_type',
        'binding_type',
        'other_instructions',
        'status',
        'completed_at',
        'completed_by',
        'notes',
    ];

    protected $casts = [
        'cutting'      => 'boolean',
        'folding'      => 'boolean',
        'binding'      => 'boolean',
        'lamination'   => 'boolean',
        'uv_coating'   => 'boolean',
        'foiling'      => 'boolean',
        'die_cutting'  => 'boolean',
        'packaging'    => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getOperationsAttribute(): array
    {
        $ops = [];
        foreach (['cutting', 'folding', 'binding', 'lamination', 'uv_coating', 'foiling', 'die_cutting', 'packaging'] as $op) {
            if ($this->$op) {
                $ops[] = str_replace('_', ' ', ucfirst($op));
            }
        }
        return $ops;
    }
}
