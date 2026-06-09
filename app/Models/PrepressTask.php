<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrepressTask extends Model
{
    protected $fillable = [
        'branch_id',
        'job_card_id',
        'artwork_file_path',
        'artwork_filename',
        'artwork_uploaded_at',
        'artwork_uploaded_by',
        'proof_sent_at',
        'proof_approved_at',
        'proof_approved_by',
        'revision_count',
        'revision_notes',
        'plate_status',
        'plate_completed_at',
        'plate_count',
        'status',
        'notes',
        'client_decision',
        'client_decision_at',
        'client_notes',
        'assigned_to',
    ];

    protected $casts = [
        'artwork_uploaded_at' => 'datetime',
        'proof_sent_at'       => 'datetime',
        'proof_approved_at'   => 'datetime',
        'plate_completed_at'  => 'datetime',
        'client_decision_at'  => 'datetime',
        'revision_count'      => 'integer',
        'plate_count'         => 'integer',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artwork_uploaded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proof_approved_by');
    }
}
