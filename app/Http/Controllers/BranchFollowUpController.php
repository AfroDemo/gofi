<?php

namespace App\Http\Controllers;

use App\Actions\Ops\AssignOperatorFollowUp;
use App\Actions\Ops\ReleaseOperatorFollowUp;
use App\Actions\Ops\ReopenOperatorFollowUp;
use App\Actions\Ops\ResolveOperatorFollowUp;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchFollowUpController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __construct(
        protected AssignOperatorFollowUp $assignOperatorFollowUp,
        protected ResolveOperatorFollowUp $resolveOperatorFollowUp,
        protected ReopenOperatorFollowUp $reopenOperatorFollowUp,
        protected ReleaseOperatorFollowUp $releaseOperatorFollowUp,
    ) {}

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($branch->id);
        $payload = $request->validate([
            'assigned_user_id' => ['nullable', 'integer'],
        ]);

        $assignedUserId = $payload['assigned_user_id'] ?? $request->user()->id;
        $allowedUserIds = User::query()
            ->whereHas('tenantMemberships', fn ($memberships) => $memberships->where('tenant_id', $branch->tenant_id))
            ->pluck('id');

        if ($request->user()?->isPlatformAdmin()) {
            $allowedUserIds->push($request->user()->id);
        }

        abort_unless($allowedUserIds->contains($assignedUserId), 404);

        $assignedUser = User::query()->findOrFail($assignedUserId);

        $this->assignOperatorFollowUp->execute($branch, $assignedUser, $request->user());

        return to_route('branches.show', $branch)->with('success', 'Follow-up ownership updated.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($branch->id);

        $this->releaseOperatorFollowUp->execute($branch, $request->user()?->name, $request->user()?->id);

        return to_route('branches.show', $branch)->with('success', 'Follow-up ownership released.');
    }

    public function resolve(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($branch->id);

        $this->resolveOperatorFollowUp->execute($branch, $request->user());

        return to_route('branches.show', $branch)->with('success', 'Follow-up marked as resolved.');
    }

    public function reopen(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($branch->id);

        $this->reopenOperatorFollowUp->execute($branch, $request->user());

        return to_route('branches.show', $branch)->with('success', 'Follow-up reopened.');
    }
}
