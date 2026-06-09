<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobCard extends Model
{
    protected $fillable = [
        'branch_id',
        'job_number',
        'order_id',
        'quotation_id',
        'customer_id',
        'title',
        'product_description',
        'paper_type',
        'gsm',
        'size',
        'width_mm',
        'height_mm',
        'quantity_ordered',
        'color_count',
        'printing_method',
        'printing_instructions',
        'finishing_instructions',
        'delivery_instructions',
        'machine_id',
        'assigned_operator_id',
        'artwork_status',
        'artwork_file_path',
        'status',
        'is_priority',
        'order_date',
        'scheduled_date',
        'due_date',
        'completed_at',
        'qr_code',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'gsm'              => 'integer',
        'quantity_ordered' => 'integer',
        'is_priority'      => 'boolean',
        'width_mm'         => 'float',
        'height_mm'        => 'float',
        'order_date'       => 'date',
        'scheduled_date'   => 'date',
        'due_date'         => 'date',
        'completed_at'     => 'datetime',
    ];

    public static $statuses = [
        'waiting'        => 'Waiting',
        'designing'      => 'Designing',
        'proof_approval' => 'Proof Approval',
        'plate_making'   => 'Plate Making',
        'printing'       => 'Printing',
        'finishing'      => 'Finishing',
        'quality_check'  => 'Quality Check',
        'ready'          => 'Ready for Dispatch',
        'delivered'      => 'Delivered',
    ];

    public static $artworkStatuses = [
        'pending'   => 'Pending',
        'received'  => 'Received',
        'reviewing' => 'Reviewing',
        'approved'  => 'Approved',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'order_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(PressMachine::class, 'machine_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_operator_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function productionJobs(): HasMany
    {
        return $this->hasMany(ProductionJob::class);
    }

    public function prepressTask(): HasOne
    {
        return $this->hasOne(PrepressTask::class);
    }

    public function finishingTask(): HasOne
    {
        return $this->hasOne(FinishingTask::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(JobConsumable::class);
    }

    public function costing(): HasOne
    {
        return $this->hasOne(JobCosting::class);
    }

    public function artworkFiles(): HasMany
    {
        return $this->hasMany(JobArtworkFile::class)->orderByDesc('version');
    }

    public static function generateNumber(): string
    {
        $prefix = 'JC-' . now()->format('Ymd') . '-';
        $last   = static::whereDate('created_at', today())
            ->where('job_number', 'like', $prefix . '%')
            ->max('job_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && !in_array($this->status, ['ready', 'delivered']);
    }
}
