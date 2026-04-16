<?php

namespace App\Http\Controllers;

use App\Enums\DeviceIncidentStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\DeviceIncident;
use App\Models\HotspotDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceIncidentResolveController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, HotspotDevice $device, DeviceIncident $incident): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $device = HotspotDevice::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($device->id);

        $incident = DeviceIncident::query()
            ->where('hotspot_device_id', $device->id)
            ->where('tenant_id', $device->tenant_id)
            ->findOrFail($incident->id);

        $payload = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:5000'],
        ]);

        if ($incident->status === DeviceIncidentStatus::Resolved) {
            return to_route('devices.show', $device)->with('error', 'This incident is already resolved.');
        }

        $incident->update([
            'status' => DeviceIncidentStatus::Resolved,
            'resolved_by_user_id' => $request->user()?->id,
            'resolved_at' => now(),
            'resolution_notes' => $payload['resolution_notes'],
        ]);

        return to_route('devices.show', $device)->with('success', 'Device incident resolved successfully.');
    }
}
