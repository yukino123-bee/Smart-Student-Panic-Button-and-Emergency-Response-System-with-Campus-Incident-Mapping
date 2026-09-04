<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    public const TYPE_CRITICAL = 'Critical Emergency';

    public const TYPE_MEDICAL = 'Medical Emergency';

    public const TYPE_PUBLIC_SAFETY = 'Public Safety Emergency';

    public const EMERGENCY_TYPES = [
        self::TYPE_CRITICAL,
        self::TYPE_MEDICAL,
        self::TYPE_PUBLIC_SAFETY,
    ];

    protected $fillable = [
        'device_id', 'emergency_type', 'reported_at', 'status', 'remarks', 'resolved_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['Pending', 'Acknowledged', 'Responding']);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'Resolved');
    }

    public function scopeClinicRelevant(Builder $query): Builder
    {
        return $query->whereHas('notifications', function ($q) {
            $q->where('recipient', 'Clinic');
        });
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
