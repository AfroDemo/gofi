<?php

namespace App\Models;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'access_package_id',
        'voucher_id',
        'revenue_share_rule_id',
        'initiated_by_user_id',
        'source',
        'status',
        'reference',
        'provider_reference',
        'phone_number',
        'amount',
        'gateway_fee',
        'currency',
        'paid_at',
        'confirmed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source' => TransactionSource::class,
            'status' => TransactionStatus::class,
            'amount' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
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

    public function accessPackage(): BelongsTo
    {
        return $this->belongsTo(AccessPackage::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function revenueShareRule(): BelongsTo
    {
        return $this->belongsTo(RevenueShareRule::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(PaymentCallback::class);
    }

    public function revenueAllocation(): HasOne
    {
        return $this->hasOne(RevenueAllocation::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function hotspotSessions(): HasMany
    {
        return $this->hasMany(HotspotSession::class);
    }

    public function operatorNotes(): MorphMany
    {
        return $this->morphMany(OperatorNote::class, 'noteable');
    }
}
