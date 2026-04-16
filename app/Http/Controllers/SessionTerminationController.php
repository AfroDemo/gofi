<?php

namespace App\Http\Controllers;

use App\Enums\HotspotSessionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionTerminationController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, HotspotSession $session): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $session = HotspotSession::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($session->id);

        $payload = $request->validate([
            'termination_reason' => ['required', 'string', 'max:2000'],
        ]);

        if (! in_array($session->status, [HotspotSessionStatus::Active, HotspotSessionStatus::Pending], true)) {
            return to_route('sessions.show', $session)->with('error', 'Only active or pending sessions can be terminated from the workspace.');
        }

        $session->update([
            'status' => HotspotSessionStatus::Terminated,
            'ended_at' => $session->ended_at ?? now(),
            'terminated_by_user_id' => $request->user()?->id,
            'termination_reason' => $payload['termination_reason'],
        ]);

        return to_route('sessions.show', $session)->with('success', 'Session terminated successfully.');
    }
}
