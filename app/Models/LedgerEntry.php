<?php

namespace App\Models;

use App\Enums\LedgerDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'payout_id',
        'direction',
        'entry_type',
        'amount',
        'currency',
        'balance_after',
        'description',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => LedgerDirection::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }
}
