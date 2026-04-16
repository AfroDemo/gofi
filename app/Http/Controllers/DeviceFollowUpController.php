<?php

namespace App\Http\Controllers;

use App\Actions\Ops\AssignOperatorFollowUp;
use App\Actions\Ops\ReleaseOperatorFollowUp;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceFollowUpController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __construct(
        protected AssignOperatorFollowUp $assignOperatorFollowUp,
        protected ReleaseOperatorFollowUp $releaseOperatorFollowUp,
    ) {}

    public function store(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($device->id);

        $this->assignOperatorFollowUp->execute($device, $request->user(), $request->user());

        return to_route('devices.show', $device)->with('success', 'Follow-up ownership assigned to you.');
    }

    public function destroy(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $device = HotspotDevice::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($device->id);

        $this->releaseOperatorFollowUp->execute($device, $request->user()?->name, $request->user()?->id);

        return to_route('devices.show', $device)->with('success', 'Follow-up ownership released.');
    }
}
