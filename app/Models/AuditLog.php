<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'description',
    ];

    protected $casts = ['old_values' => 'array', 'new_values' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Static helper to record an audit event */
    public static function record(
        string $action,
        string $description,
        ?Model $model = null,
        array  $oldValues = [],
        array  $newValues = []
    ): self {
        return static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'model_type'  => $model ? class_basename($model) : null,
            'model_id'    => $model?->getKey(),
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
            'ip_address'  => request()->ip(),
            'description' => $description,
        ]);
    }
}
