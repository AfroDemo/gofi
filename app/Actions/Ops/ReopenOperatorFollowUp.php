<?php

namespace App\Actions\Ops;

use App\Enums\OperatorFollowUpStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReopenOperatorFollowUp
{
    public function execute(Model $followable, User $actor): void
    {
        $followUp = $followable->operatorFollowUp;

        if (! $followUp) {
            return;
        }

        $followUp->update([
            'status' => OperatorFollowUpStatus::NeedsFollowUp,
            'resolved_by_user_id' => null,
            'resolved_at' => null,
            'acknowledged_by_user_id' => null,
            'acknowledged_at' => null,
        ]);

        $followable->operatorNotes()->create([
            'tenant_id' => $followUp->tenant_id,
            'branch_id' => $followUp->branch_id,
            'user_id' => $actor->id,
            'note' => sprintf('%s reopened this follow-up.', $actor->name),
        ]);
    }
}
