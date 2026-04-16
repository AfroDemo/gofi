<?php

namespace App\Models;

use App\Enums\PackageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'package_type',
        'description',
        'price',
        'currency',
        'duration_minutes',
        'data_limit_mb',
        'speed_limit_kbps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'package_type' => PackageType::class,
            'price' => 'decimal:2',
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

    public function voucherProfiles(): HasMany
    {
        return $this->hasMany(VoucherProfile::class);
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
