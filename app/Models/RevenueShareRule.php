<?php

namespace App\Models;

use App\Enums\RevenueShareModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueShareRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'access_package_id',
        'name',
        'model',
        'platform_percentage',
        'platform_fixed_fee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'model' => RevenueShareModel::class,
            'platform_percentage' => 'decimal:2',
            'platform_fixed_fee' => 'decimal:2',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
