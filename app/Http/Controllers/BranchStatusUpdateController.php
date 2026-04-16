<?php

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use App\Models\BranchStatusEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchStatusUpdateController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $branch = Branch::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($branch->id);

        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in(array_map(fn (BranchStatus $status) => $status->value, BranchStatus::cases()))],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($branch->status->value === $payload['status']) {
            return to_route('branches.show', $branch)->with('error', 'The branch is already in that status.');
        }

        BranchStatusEvent::query()->create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'changed_by_user_id' => $request->user()?->id,
            'from_status' => $branch->status->value,
            'to_status' => $payload['status'],
            'reason' => $payload['reason'],
        ]);

        $branch->update([
            'status' => $payload['status'],
        ]);

        return to_route('branches.show', $branch)->with('success', 'Branch status updated successfully.');
    }
}
