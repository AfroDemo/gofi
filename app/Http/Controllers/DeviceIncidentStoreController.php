<?php

namespace App\Http\Controllers;

use App\Enums\DeviceIncidentSeverity;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\DeviceIncident;
use App\Models\HotspotDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceIncidentStoreController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $device = HotspotDevice::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with(['tenant:id', 'branch:id'])
            ->findOrFail($device->id);

        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', Rule::in(array_map(fn (DeviceIncidentSeverity $severity) => $severity->value, DeviceIncidentSeverity::cases()))],
            'details' => ['nullable', 'string', 'max:5000'],
        ]);

        DeviceIncident::query()->create([
            'tenant_id' => $device->tenant_id,
            'branch_id' => $device->branch_id,
            'hotspot_device_id' => $device->id,
            'reported_by_user_id' => $request->user()?->id,
            'title' => $payload['title'],
            'details' => $payload['details'] ?? null,
            'severity' => $payload['severity'],
            'opened_at' => now(),
        ]);

        return to_route('devices.show', $device)->with('success', 'Device incident logged successfully.');
    }
}
