<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldRate extends Model
{
    protected $fillable = ['date', 'rate_per_gram', 'created_by'];

    protected $casts = [
        'date'         => 'date',
        'rate_per_gram'=> 'float',
    ];

    /** Karat purity multipliers relative to 24K */
    public static array $karatPurity = [
        '9k'  => 9  / 24,
        '14k' => 14 / 24,
        '18k' => 18 / 24,
        '22k' => 22 / 24,
        '24k' => 24 / 24,
    ];

    /** Calculate LKR value for given weight (grams) and karat string */
    public function calculate(float $weightGrams, string $karat): float
    {
        $purity = self::$karatPurity[strtolower($karat)] ?? 1.0;
        return round($this->rate_per_gram * $weightGrams * $purity, 2);
    }

    public static function today(): ?self
    {
        return static::where('date', today()->toDateString())->first();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
