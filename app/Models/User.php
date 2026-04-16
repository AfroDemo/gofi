<?php

namespace App\Models;

use App\Enums\PlatformRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'platform_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'platform_role' => PlatformRole::class,
        ];
    }

    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->using(TenantMembership::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_user_id');
    }

    public function managedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'manager_user_id');
    }

    public function branchStatusEvents(): HasMany
    {
        return $this->hasMany(BranchStatusEvent::class, 'changed_by_user_id');
    }

    public function reportedDeviceIncidents(): HasMany
    {
        return $this->hasMany(DeviceIncident::class, 'reported_by_user_id');
    }

    public function resolvedDeviceIncidents(): HasMany
    {
        return $this->hasMany(DeviceIncident::class, 'resolved_by_user_id');
    }

    public function createdVouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'created_by_user_id');
    }

    public function redeemedVouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'redeemed_by_user_id');
    }

    public function initiatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'initiated_by_user_id');
    }

    public function authorizedSessions(): HasMany
    {
        return $this->hasMany(HotspotSession::class, 'authorized_by_user_id');
    }

    public function operatorNotes(): HasMany
    {
        return $this->hasMany(OperatorNote::class);
    }

    public function assignedOperatorFollowUps(): HasMany
    {
        return $this->hasMany(OperatorFollowUp::class, 'assigned_user_id');
    }

    public function delegatedOperatorFollowUps(): HasMany
    {
        return $this->hasMany(OperatorFollowUp::class, 'assigned_by_user_id');
    }

    public function resolvedOperatorFollowUps(): HasMany
    {
        return $this->hasMany(OperatorFollowUp::class, 'resolved_by_user_id');
    }

    public function acknowledgedOperatorFollowUps(): HasMany
    {
        return $this->hasMany(OperatorFollowUp::class, 'acknowledged_by_user_id');
    }

    public function isPlatformAdmin(): bool
    {
        return ($this->platform_role ?? PlatformRole::TenantUser) === PlatformRole::SuperAdmin;
    }
}
