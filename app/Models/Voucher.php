<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'voucher_profile_id',
        'access_package_id',
        'code',
        'status',
        'locked_mac_address',
        'redeemed_at',
        'expires_at',
        'created_by_user_id',
        'redeemed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function voucherProfile(): BelongsTo
    {
        return $this->belongsTo(VoucherProfile::class);
    }

    public function accessPackage(): BelongsTo
    {
        return $this->belongsTo(AccessPackage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
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
