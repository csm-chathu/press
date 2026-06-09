<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryPayment extends Model
{
    protected $fillable = [
        'branch_id',
        'employee_id',
        'period_month',
        'paid_at',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'user_id',
        'journal_entry_id',
    ];

    protected $casts = [
        'period_month' => 'date',
        'paid_at' => 'date',
        'amount' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

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
