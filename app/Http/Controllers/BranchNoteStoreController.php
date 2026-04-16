<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchNoteStoreController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $branch = Branch::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($branch->id);

        $payload = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $branch->operatorNotes()->create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'user_id' => $request->user()?->id,
            'note' => $payload['note'],
        ]);

        return to_route('branches.show', $branch)->with('success', 'Operator follow-up note saved.');
    }
}
