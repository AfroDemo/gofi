<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'currency',
        'country_code',
        'timezone',
        'owner_user_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantMembership::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function branchStatusEvents(): HasMany
    {
        return $this->hasMany(BranchStatusEvent::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(HotspotDevice::class);
    }

    public function deviceIncidents(): HasMany
    {
        return $this->hasMany(DeviceIncident::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(AccessPackage::class);
    }

    public function voucherProfiles(): HasMany
    {
        return $this->hasMany(VoucherProfile::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(HotspotSession::class);
    }

    public function revenueShareRules(): HasMany
    {
        return $this->hasMany(RevenueShareRule::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
