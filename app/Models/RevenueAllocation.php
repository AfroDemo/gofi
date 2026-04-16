<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'tenant_id',
        'model',
        'gross_amount',
        'gateway_fee',
        'platform_amount',
        'tenant_amount',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'platform_amount' => 'decimal:2',
            'tenant_amount' => 'decimal:2',
            'snapshot' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
