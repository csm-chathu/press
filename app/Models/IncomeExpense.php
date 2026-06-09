<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeExpense extends Model
{
    protected $fillable = [
        'branch_id',
        'type',
        'entry_date',
        'category',
        'description',
        'amount',
        'payment_method',
        'reference',
        'account_code',
        'notes',
        'user_id',
        'journal_entry_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'float',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
