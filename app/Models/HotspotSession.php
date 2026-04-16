<?php

namespace App\Models;

use App\Enums\HotspotSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'access_package_id',
        'voucher_id',
        'transaction_id',
        'authorized_by_user_id',
        'device_mac_address',
        'device_ip_address',
        'status',
        'duration_minutes',
        'data_limit_mb',
        'data_used_mb',
        'started_at',
        'expires_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => HotspotSessionStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public function accessPackage(): BelongsTo
    {
        return $this->belongsTo(AccessPackage::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
