<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShiftCashOut extends Model
{
    protected $fillable = ['shift_id', 'user_id', 'amount', 'reason'];

    protected $casts = ['amount' => 'float'];

    public function shift()
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
