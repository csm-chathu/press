<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationTemplate extends Model
{
    protected $fillable = [
        'branch_id', 'name',
        'product_type', 'paper_type', 'gsm', 'size', 'width_mm', 'height_mm',
        'color_count', 'printing_method',
        'plate_cost', 'paper_cost', 'ink_cost', 'finishing_cost', 'labour_cost',
        'wastage_percent', 'profit_margin_percent', 'tax_rate',
        'notes', 'terms', 'created_by',
    ];

    protected $casts = [
        'gsm'                  => 'integer',
        'color_count'          => 'integer',
        'width_mm'             => 'float',
        'height_mm'            => 'float',
        'plate_cost'           => 'float',
        'paper_cost'           => 'float',
        'ink_cost'             => 'float',
        'finishing_cost'       => 'float',
        'labour_cost'          => 'float',
        'wastage_percent'      => 'float',
        'profit_margin_percent'=> 'float',
        'tax_rate'             => 'float',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
