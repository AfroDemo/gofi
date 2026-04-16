<?php

namespace App\Actions\Ops;

use App\Enums\OperatorFollowUpStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ResolveOperatorFollowUp
{
    public function execute(Model $followable, User $actor): void
    {
        $followUp = $followable->operatorFollowUp;

        if (! $followUp) {
            return;
        }

        $followUp->update([
            'status' => OperatorFollowUpStatus::Resolved,
            'resolved_by_user_id' => $actor->id,
            'resolved_at' => now(),
        ]);

        $followable->operatorNotes()->create([
            'tenant_id' => $followUp->tenant_id,
            'branch_id' => $followUp->branch_id,
            'user_id' => $actor->id,
            'note' => sprintf('%s marked this follow-up as resolved.', $actor->name),
        ]);
    }
}
