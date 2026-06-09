<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'branch_id',
        'entry_date',
        'source_type',
        'source_id',
        'reference',
        'description',
        'status',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'meta' => 'array',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
