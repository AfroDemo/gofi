<?php

namespace App\Http\Controllers;

use App\Actions\Ops\AcknowledgeOperatorFollowUp;
use App\Actions\Ops\AssignOperatorFollowUp;
use App\Actions\Ops\ReleaseOperatorFollowUp;
use App\Actions\Ops\ReopenOperatorFollowUp;
use App\Actions\Ops\ResolveOperatorFollowUp;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceFollowUpController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __construct(
        protected AssignOperatorFollowUp $assignOperatorFollowUp,
        protected AcknowledgeOperatorFollowUp $acknowledgeOperatorFollowUp,
        protected ResolveOperatorFollowUp $resolveOperatorFollowUp,
        protected ReopenOperatorFollowUp $reopenOperatorFollowUp,
        protected ReleaseOperatorFollowUp $releaseOperatorFollowUp,
    ) {}

    public function store(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($device->id);
        $payload = $request->validate([
            'assigned_user_id' => ['nullable', 'integer'],
        ]);

        $assignedUserId = $payload['assigned_user_id'] ?? $request->user()->id;
        $allowedUserIds = User::query()
            ->whereHas('tenantMemberships', fn ($memberships) => $memberships->where('tenant_id', $device->tenant_id))
            ->pluck('id');

        if ($request->user()?->isPlatformAdmin()) {
            $allowedUserIds->push($request->user()->id);
        }

        abort_unless($allowedUserIds->contains($assignedUserId), 404);

        $assignedUser = User::query()->findOrFail($assignedUserId);

        $this->assignOperatorFollowUp->execute($device, $assignedUser, $request->user());

        return to_route('devices.show', $device)->with('success', 'Follow-up ownership updated.');
    }

    public function destroy(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($device->id);

        $this->releaseOperatorFollowUp->execute($device, $request->user()?->name, $request->user()?->id);

        return to_route('devices.show', $device)->with('success', 'Follow-up ownership released.');
    }

    public function resolve(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($device->id);

        $this->resolveOperatorFollowUp->execute($device, $request->user());

        return to_route('devices.show', $device)->with('success', 'Follow-up marked as resolved.');
    }

    public function acknowledge(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($device->id);

        $acknowledged = $this->acknowledgeOperatorFollowUp->execute($device, $request->user());

        return to_route('devices.show', $device)->with(
            $acknowledged ? 'success' : 'error',
            $acknowledged ? 'Follow-up acknowledged.' : 'Only the assigned operator can acknowledge this follow-up.'
        );
    }

    public function reopen(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($device->id);

        $this->reopenOperatorFollowUp->execute($device, $request->user());

        return to_route('devices.show', $device)->with('success', 'Follow-up reopened.');
    }
}
