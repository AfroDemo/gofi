<?php

namespace App\Http\Controllers;

use App\Enums\DeviceIncidentStatus;
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
            'attention' => in_array($request->string('attention')->toString(), ['all', 'review', 'open_incidents', 'offline'], true)
                ? $request->string('attention')->toString()
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
            ->when($filters['attention'] === 'open_incidents', function (Builder $query) {
                $query->whereHas('incidents', fn (Builder $incidents) => $incidents->where('status', DeviceIncidentStatus::Open));
            })
            ->when($filters['attention'] === 'offline', function (Builder $query) {
                $query->where('status', DeviceStatus::Offline);
            })
            ->when($filters['attention'] === 'review', function (Builder $query) {
                $query->where(function (Builder $nested) {
                    $nested
                        ->where('status', DeviceStatus::Offline->value)
                        ->orWhereHas('incidents', fn (Builder $incidents) => $incidents->where('status', DeviceIncidentStatus::Open));
                });
            })
            ->with(['tenant:id,name,currency', 'branch:id,name,location,status'])
            ->withCount([
                'incidents as open_incidents_count' => fn (Builder $query) => $query->where('status', DeviceIncidentStatus::Open),
            ])
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
                'open_incidents' => (int) $devices->sum('open_incidents_count'),
            ],
            'devices' => $devices->map(fn (HotspotDevice $device) => [
                'id' => $device->id,
                'tenant' => $device->tenant?->name,
                'branch' => $device->branch?->name,
                'branch_status' => $device->branch?->status?->value,
                'location' => $device->branch?->location,
                'name' => $device->name,
                'identifier' => $device->identifier,
                'status' => $device->status->value,
                'integration_driver' => $device->integration_driver,
                'ip_address' => $device->ip_address,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'open_incidents_count' => (int) $device->open_incidents_count,
                'attention_reason' => $this->attentionReason($device),
                'metadata' => $device->metadata,
            ])->values(),
        ]);
    }

    protected function attentionReason(HotspotDevice $device): ?string
    {
        return match (true) {
            (int) $device->open_incidents_count > 0 => 'This device has unresolved incidents that may affect customer access.',
            $device->status === DeviceStatus::Offline => 'This device is offline and may block portal access for the branch.',
            default => null,
        };
    }
}
