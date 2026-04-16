<?php

namespace App\Http\Controllers;

use App\Actions\Ops\AssignOperatorFollowUp;
use App\Actions\Ops\ReleaseOperatorFollowUp;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchFollowUpController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __construct(
        protected AssignOperatorFollowUp $assignOperatorFollowUp,
        protected ReleaseOperatorFollowUp $releaseOperatorFollowUp,
    ) {}

    public function store(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($branch->id);

        $this->assignOperatorFollowUp->execute($branch, $request->user(), $request->user());

        return to_route('branches.show', $branch)->with('success', 'Follow-up ownership assigned to you.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($branch->id);

        $this->releaseOperatorFollowUp->execute($branch, $request->user()?->name, $request->user()?->id);

        return to_route('branches.show', $branch)->with('success', 'Follow-up ownership released.');
    }
}
