<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceNoteStoreController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, HotspotDevice $device): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $device = HotspotDevice::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($device->id);

        $payload = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $device->operatorNotes()->create([
            'tenant_id' => $device->tenant_id,
            'branch_id' => $device->branch_id,
            'user_id' => $request->user()?->id,
            'note' => $payload['note'],
        ]);

        return to_route('devices.show', $device)->with('success', 'Operator follow-up note saved.');
    }
}
