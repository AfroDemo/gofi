<?php

namespace App\Models;

use App\Enums\OperatorFollowUpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperatorFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'assigned_user_id',
        'assigned_by_user_id',
        'resolved_by_user_id',
        'acknowledged_by_user_id',
        'assigned_at',
        'status',
        'resolved_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'status' => OperatorFollowUpStatus::class,
            'resolved_at' => 'datetime',
            'acknowledged_at' => 'datetime',
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }
}
