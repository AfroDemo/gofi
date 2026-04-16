<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);

        $branches = Branch::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with(['tenant:id,name,currency', 'manager:id,name,email'])
            ->withCount('devices')
            ->withCount([
                'devices as online_devices_count' => fn (Builder $query) => $query->where('status', DeviceStatus::Online),
                'sessions as active_sessions_count' => fn (Builder $query) => $query->where('status', HotspotSessionStatus::Active),
            ])
            ->withSum([
                'transactions as successful_revenue' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Successful),
            ], 'amount')
            ->orderBy('name')
            ->get();

        return Inertia::render('operations/branches', [
            'viewer' => $scope['viewer'],
            'summary' => [
                'total' => $branches->count(),
                'active' => $branches->where('status', 'active')->count(),
                'online_devices' => (int) $branches->sum('online_devices_count'),
                'active_sessions' => (int) $branches->sum('active_sessions_count'),
                'successful_revenue' => (float) $branches->sum(fn (Branch $branch) => (float) ($branch->successful_revenue ?? 0)),
            ],
            'branches' => $branches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'tenant' => $branch->tenant?->name,
                'name' => $branch->name,
                'code' => $branch->code,
                'status' => $branch->status->value,
                'location' => $branch->location,
                'address' => $branch->address,
                'manager' => $branch->manager?->name,
                'manager_email' => $branch->manager?->email,
                'devices_count' => $branch->devices_count,
                'online_devices_count' => $branch->online_devices_count,
                'active_sessions_count' => $branch->active_sessions_count,
                'successful_revenue' => (float) ($branch->successful_revenue ?? 0),
                'currency' => $branch->tenant?->currency,
            ])->values(),
        ]);
    }
}
