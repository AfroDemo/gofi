<?php

namespace App\Models;

use App\Enums\DeviceIncidentSeverity;
use App\Enums\DeviceIncidentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'hotspot_device_id',
        'reported_by_user_id',
        'resolved_by_user_id',
        'title',
        'details',
        'severity',
        'status',
        'opened_at',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'severity' => DeviceIncidentSeverity::class,
            'status' => DeviceIncidentStatus::class,
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(HotspotDevice::class, 'hotspot_device_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
