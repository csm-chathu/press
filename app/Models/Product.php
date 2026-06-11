<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\OpenBottle;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'sku', 'barcode', 'name', 'description', 'category_id', 'product_type',
        'brand', 'unit_type', 'base_unit', 'bundle_size', 'selling_variants', 'shot_variants',
        'purchase_price', 'selling_price',
        'stock_quantity', 'min_stock_level', 'image', 'image_public_id', 'is_active', 'supplier_id',
        'bottle_deposit_required', 'bottle_deposit_amount', 'tax_setting_id',
        // press-specific
        'material_type', 'gsm', 'paper_size', 'reorder_level',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'bottle_deposit_required' => 'boolean',
        'bottle_deposit_amount'   => 'float',
        'purchase_price'          => 'float',
        'selling_price'           => 'float',
        'shot_variants'           => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function taxSetting()
    {
        return $this->belongsTo(TaxSetting::class);
    }

    public function openBottles()
    {
        return $this->hasMany(OpenBottle::class);
    }

    public function openBundles()
    {
        return $this->hasMany(OpenBundle::class);
    }

    public function hasBundles(): bool
    {
        return (int) $this->bundle_size > 0;
    }

    // Total sheets across all currently open bundles
    public function openBundleSheetsRemaining(): int
    {
        return (int) $this->openBundles()->where('status', 'open')->sum('sheets_remaining');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    public function isStockTracked(): bool
    {
        return strtolower((string) $this->product_type) !== 'food';
    }

    public function getImageAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return asset('storage/' . ltrim($value, '/'));
    }
}
