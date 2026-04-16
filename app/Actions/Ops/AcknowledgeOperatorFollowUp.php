<?php

namespace App\Actions\Ops;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AcknowledgeOperatorFollowUp
{
    public function execute(Model $followable, User $actor): bool
    {
        $followUp = $followable->operatorFollowUp;

        if (! $followUp || $followUp->assigned_user_id !== $actor->id) {
            return false;
        }

        $followUp->update([
            'acknowledged_by_user_id' => $actor->id,
            'acknowledged_at' => now(),
        ]);

        $followable->operatorNotes()->create([
            'tenant_id' => $followUp->tenant_id,
            'branch_id' => $followUp->branch_id,
            'user_id' => $actor->id,
            'note' => sprintf('%s acknowledged this follow-up and is actively working it.', $actor->name),
        ]);

        return true;
    }
}
