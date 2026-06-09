<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobConsumable extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_id', 'branch_id', 'type', 'description',
        'quantity', 'unit', 'unit_cost', 'total_cost', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'   => 'float',
        'unit_cost'  => 'float',
        'total_cost' => 'float',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
