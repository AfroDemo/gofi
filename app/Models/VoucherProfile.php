<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'access_package_id',
        'name',
        'code_prefix',
        'price',
        'duration_minutes',
        'data_limit_mb',
        'speed_limit_kbps',
        'expires_in_days',
        'mac_lock_on_first_use',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'mac_lock_on_first_use' => 'bool',
            'is_active' => 'bool',
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

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
