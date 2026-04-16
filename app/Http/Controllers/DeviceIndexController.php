<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => in_array($request->string('status')->toString(), ['all', 'online', 'offline', 'provisioning'], true)
                ? $request->string('status')->toString()
                : 'all',
        ];

        $devices = HotspotDevice::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', $search)
                        ->orWhere('identifier', 'like', $search)
                        ->orWhere('integration_driver', 'like', $search)
                        ->orWhere('ip_address', 'like', $search)
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('name', 'like', $search))
                        ->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $search));
                });
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->with(['tenant:id,name,currency', 'branch:id,name,location'])
            ->orderBy('name')
            ->get();

        return Inertia::render('operations/devices', [
            'viewer' => $scope['viewer'],
            'filters' => $filters,
            'summary' => [
                'total' => $devices->count(),
                'online' => $devices->where('status', DeviceStatus::Online)->count(),
                'offline' => $devices->where('status', DeviceStatus::Offline)->count(),
                'provisioning' => $devices->where('status', DeviceStatus::Provisioning)->count(),
                'branches_covered' => $devices->pluck('branch_id')->filter()->unique()->count(),
            ],
            'devices' => $devices->map(fn (HotspotDevice $device) => [
                'id' => $device->id,
                'tenant' => $device->tenant?->name,
                'branch' => $device->branch?->name,
                'location' => $device->branch?->location,
                'name' => $device->name,
                'identifier' => $device->identifier,
                'status' => $device->status->value,
                'integration_driver' => $device->integration_driver,
                'ip_address' => $device->ip_address,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'metadata' => $device->metadata,
            ])->values(),
        ]);
    }
}
