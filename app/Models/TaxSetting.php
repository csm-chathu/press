<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = ['name', 'rate', 'applies_to', 'is_active', 'description'];

    protected $casts = ['rate' => 'float', 'is_active' => 'boolean'];

    public static function active(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->get();
    }
}
