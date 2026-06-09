<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DayEndReport extends Model
{
    protected $fillable = [
        'report_date', 'created_by', 'branch_id',
        'system_gold_weight', 'physical_gold_weight', 'variance_weight',
        'system_stock_value', 'physical_stock_value',
        'karat_breakdown', 'item_counts', 'notes', 'status',
    ];

    protected $casts = [
        'report_date'   => 'date',
        'karat_breakdown' => 'array',
        'item_counts'   => 'array',
    ];

    public function branch()   { return $this->belongsTo(Branch::class); }
    public function createdBy(){ return $this->belongsTo(User::class, 'created_by'); }
}
