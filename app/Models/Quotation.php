<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    protected $fillable = [
        'branch_id',
        'quotation_number',
        'customer_id',
        'title',
        'product_type',
        'paper_type',
        'paper_product_id',
        'ink_product_id',
        'plate_product_id',
        'paper_sheets_needed',
        'gsm',
        'size',
        'width_mm',
        'height_mm',
        'quantity',
        'color_count',
        'printing_method',
        'plate_cost',
        'paper_cost',
        'ink_cost',
        'finishing_cost',
        'labour_cost',
        'wastage_percent',
        'profit_margin_percent',
        'subtotal',
        'tax_rate',
        'tax',
        'total',
        'status',
        'valid_until',
        'notes',
        'terms',
        'created_by',
        'sent_at',
        'approved_at',
    ];

    protected $casts = [
        'gsm'                  => 'integer',
        'quantity'             => 'integer',
        'color_count'          => 'integer',
        'plate_cost'           => 'float',
        'paper_cost'           => 'float',
        'ink_cost'             => 'float',
        'finishing_cost'       => 'float',
        'labour_cost'          => 'float',
        'wastage_percent'      => 'float',
        'profit_margin_percent'=> 'float',
        'subtotal'             => 'float',
        'tax_rate'             => 'float',
        'tax'                  => 'float',
        'total'                => 'float',
        'width_mm'             => 'float',
        'height_mm'            => 'float',
        'valid_until'          => 'date',
        'sent_at'              => 'datetime',
        'approved_at'          => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paperProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'paper_product_id');
    }

    public function inkProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ink_product_id');
    }

    public function plateProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'plate_product_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Sale::class, 'quotation_id');
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public static function generateNumber(): string
    {
        $prefix = 'QT-' . now()->format('Ymd') . '-';
        $last   = static::whereDate('created_at', today())
            ->where('quotation_number', 'like', $prefix . '%')
            ->max('quotation_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $baseCost = $this->plate_cost + $this->paper_cost + $this->ink_cost
            + $this->finishing_cost + $this->labour_cost;
        $wastage    = $baseCost * ($this->wastage_percent / 100);
        $costWithWastage = $baseCost + $wastage;
        $profit     = $costWithWastage * ($this->profit_margin_percent / 100);
        $subtotal   = $costWithWastage + $profit;
        $tax        = $subtotal * ($this->tax_rate / 100);

        $this->subtotal = round($subtotal, 2);
        $this->tax      = round($tax, 2);
        $this->total    = round($subtotal + $tax, 2);
    }
}
