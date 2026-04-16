<?php

namespace App\Models;

use App\Enums\BranchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'status',
        'location',
        'address',
        'manager_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => BranchStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(HotspotDevice::class);
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
}
