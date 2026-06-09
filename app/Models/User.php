<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Press roles
    public const ROLES = [
        'admin'              => 'Admin',
        'owner'              => 'Owner',
        'sales'              => 'Sales',
        'estimator'          => 'Estimator',
        'designer'           => 'Designer',
        'production_manager' => 'Production Manager',
        'machine_operator'   => 'Machine Operator',
        'store_keeper'       => 'Store Keeper',
        'accountant'         => 'Accountant',
        'dispatch_officer'   => 'Dispatch Officer',
        'client'             => 'Client (Portal)',
        // Legacy roles kept for backward compat
        'manager'            => 'Manager',
        'cashier'            => 'Cashier',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'customer_id',
        'can_override_gold_rate',
        'can_delete_transactions',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'       => 'datetime',
        'password'                => 'hashed',
        'can_override_gold_rate'  => 'boolean',
        'can_delete_transactions' => 'boolean',
        'is_active'               => 'boolean',
        'branch_id'               => 'integer',
    ];

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'production_manager'], true);
    }

    public function isCashier(): bool
    {
        return in_array($this->role, ['cashier', 'accountant'], true);
    }

    public function isStoreKeeper(): bool
    {
        return $this->role === 'store_keeper';
    }

    public function isDesigner(): bool
    {
        return $this->role === 'designer';
    }

    public function isOperator(): bool
    {
        return $this->role === 'machine_operator';
    }

    public function isDispatch(): bool
    {
        return $this->role === 'dispatch_officer';
    }

    public function isSales(): bool
    {
        return in_array($this->role, ['sales', 'cashier'], true);
    }

    public function isEstimator(): bool
    {
        return in_array($this->role, ['estimator', 'sales'], true);
    }

    public function canSellItems(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'manager', 'cashier', 'sales', 'accountant'], true);
    }

    public function canAddStock(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'manager', 'store_keeper', 'production_manager'], true);
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }

    public function canCreateQuotations(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'estimator', 'sales', 'manager'], true);
    }

    public function canManageProduction(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'production_manager', 'machine_operator'], true);
    }

    public function canOverrideGoldRate(): bool
    {
        return $this->isAdmin() || $this->can_override_gold_rate;
    }

    public function canDeleteTransactions(): bool
    {
        return $this->isAdmin() || $this->can_delete_transactions;
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedJobs()
    {
        return $this->hasMany(JobCard::class, 'assigned_operator_id');
    }
}
