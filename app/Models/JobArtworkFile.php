<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobArtworkFile extends Model
{
    protected $fillable = [
        'job_card_id', 'branch_id',
        'original_name', 'stored_name', 'file_path',
        'mime_type', 'file_size', 'version', 'notes', 'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version'   => 'integer',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
