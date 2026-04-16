<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class HotspotDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'identifier',
        'status',
        'integration_driver',
        'ip_address',
        'last_seen_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
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

    public function incidents(): HasMany
    {
        return $this->hasMany(DeviceIncident::class, 'hotspot_device_id');
    }

    public function operatorNotes(): MorphMany
    {
        return $this->morphMany(OperatorNote::class, 'noteable');
    }

    public function operatorFollowUp(): MorphOne
    {
        return $this->morphOne(OperatorFollowUp::class, 'followable');
    }
}
