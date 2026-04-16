<?php

namespace App\Actions\Ops;

use Illuminate\Database\Eloquent\Model;

class ReleaseOperatorFollowUp
{
    public function execute(Model $followable, ?string $actorName, ?int $actorId): void
    {
        $followUp = $followable->operatorFollowUp;

        if (! $followUp) {
            return;
        }

        $followable->operatorNotes()->create([
            'tenant_id' => $followUp->tenant_id,
            'branch_id' => $followUp->branch_id,
            'user_id' => $actorId,
            'note' => sprintf('%s released follow-up ownership.', $actorName ?? 'An operator'),
        ]);

        $followUp->delete();
    }
}
