<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenBundle extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'job_card_id',
        'opened_by',
        'bundle_ref',
        'bundle_size',
        'sheets_used',
        'sheets_remaining',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'bundle_size'       => 'integer',
        'sheets_used'       => 'integer',
        'sheets_remaining'  => 'integer',
        'opened_at'         => 'datetime',
        'closed_at'         => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
