<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PressMachine extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'machine_type',
        'model_number',
        'manufacturer',
        'capacity_per_hour',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity_per_hour' => 'integer',
        'branch_id' => 'integer',
    ];

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'machine_id');
    }

    public function productionJobs(): HasMany
    {
        return $this->hasMany(ProductionJob::class, 'machine_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function types(): array
    {
        return ['printing', 'cutting', 'binding', 'lamination', 'uv', 'folding', 'die_cutting', 'other'];
    }
}
