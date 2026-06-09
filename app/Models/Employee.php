<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'branch_id',
        'employee_code',
        'name',
        'role',
        'phone',
        'base_salary',
        'is_active',
    ];

    protected $casts = [
        'base_salary' => 'float',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(EmployeeSalaryPayment::class);
    }
}
