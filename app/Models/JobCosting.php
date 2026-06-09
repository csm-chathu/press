<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCosting extends Model
{
    protected $table = 'job_costing';

    protected $fillable = [
        'job_card_id', 'branch_id',
        'paper_sheets', 'paper_rate_per_sheet', 'paper_cost',
        'ink_colours', 'ink_cost_per_colour', 'ink_cost',
        'plate_count', 'plate_cost_each', 'plate_cost',
        'machine_hours', 'machine_rate_per_hour', 'machine_cost',
        'labour_hours', 'labour_rate_per_hour', 'labour_cost',
        'electricity_cost', 'outsource_cost', 'outsource_description',
        'waste_percentage', 'waste_cost',
        'total_actual_cost',
        'revenue', 'profit', 'profit_margin',
        'notes',
    ];

    protected $casts = [
        'paper_sheets'          => 'integer',
        'paper_rate_per_sheet'  => 'float',
        'paper_cost'            => 'float',
        'ink_colours'           => 'integer',
        'ink_cost_per_colour'   => 'float',
        'ink_cost'              => 'float',
        'plate_count'           => 'integer',
        'plate_cost_each'       => 'float',
        'plate_cost'            => 'float',
        'machine_hours'         => 'float',
        'machine_rate_per_hour' => 'float',
        'machine_cost'          => 'float',
        'labour_hours'          => 'float',
        'labour_rate_per_hour'  => 'float',
        'labour_cost'           => 'float',
        'electricity_cost'      => 'float',
        'outsource_cost'        => 'float',
        'waste_percentage'      => 'float',
        'waste_cost'            => 'float',
        'total_actual_cost'     => 'float',
        'revenue'               => 'float',
        'profit'                => 'float',
        'profit_margin'         => 'float',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }
}
