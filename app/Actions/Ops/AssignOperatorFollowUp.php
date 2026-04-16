<?php

namespace App\Actions\Ops;

use App\Models\Branch;
use App\Models\HotspotDevice;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AssignOperatorFollowUp
{
    public function execute(Model $followable, User $assignedUser, User $actor): void
    {
        $followUp = $followable->operatorFollowUp()->updateOrCreate([], [
            'tenant_id' => $followable->tenant_id,
            'branch_id' => $this->resolveBranchId($followable),
            'assigned_user_id' => $assignedUser->id,
            'assigned_by_user_id' => $actor->id,
            'assigned_at' => now(),
        ]);

        $message = $assignedUser->is($actor)
            ? sprintf('%s took ownership of this follow-up.', $actor->name)
            : sprintf('%s assigned this follow-up to %s.', $actor->name, $assignedUser->name);

        $followable->operatorNotes()->create([
            'tenant_id' => $followUp->tenant_id,
            'branch_id' => $followUp->branch_id,
            'user_id' => $actor->id,
            'note' => $message,
        ]);
    }

    protected function resolveBranchId(Model $followable): ?int
    {
        return match (true) {
            $followable instanceof Branch => $followable->id,
            $followable instanceof HotspotDevice, $followable instanceof Transaction => $followable->branch_id,
            default => null,
        };
    }
}
