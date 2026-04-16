<?php

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Enums\DeviceIncidentStatus;
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
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => in_array($request->string('status')->toString(), ['all', 'active', 'maintenance', 'inactive'], true)
                ? $request->string('status')->toString()
                : 'all',
            'attention' => in_array($request->string('attention')->toString(), ['all', 'review', 'unavailable', 'open_incidents'], true)
                ? $request->string('attention')->toString()
                : 'all',
        ];
        $stalePendingCutoff = now()->subMinutes(5);

        $branches = Branch::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhere('location', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('name', 'like', $search))
                        ->orWhereHas('manager', fn (Builder $manager) => $manager->where('name', 'like', $search)->orWhere('email', 'like', $search));
                });
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['attention'] === 'unavailable', function (Builder $query) {
                $query->whereIn('status', [BranchStatus::Maintenance->value, BranchStatus::Inactive->value]);
            })
            ->when($filters['attention'] === 'open_incidents', function (Builder $query) {
                $query->whereHas('deviceIncidents', fn (Builder $incidents) => $incidents->where('status', DeviceIncidentStatus::Open));
            })
            ->when($filters['attention'] === 'review', function (Builder $query) use ($stalePendingCutoff) {
                $query->where(function (Builder $nested) use ($stalePendingCutoff) {
                    $nested
                        ->whereIn('status', [BranchStatus::Maintenance->value, BranchStatus::Inactive->value])
                        ->orWhereHas('deviceIncidents', fn (Builder $incidents) => $incidents->where('status', DeviceIncidentStatus::Open))
                        ->orWhereHas('transactions', function (Builder $transactions) use ($stalePendingCutoff) {
                            $transactions
                                ->where('status', TransactionStatus::Pending)
                                ->where('created_at', '<=', $stalePendingCutoff);
                        });
                });
            })
            ->with(['tenant:id,name,currency', 'manager:id,name,email'])
            ->withCount('devices')
            ->withCount([
                'devices as online_devices_count' => fn (Builder $query) => $query->where('status', DeviceStatus::Online),
                'sessions as active_sessions_count' => fn (Builder $query) => $query->where('status', HotspotSessionStatus::Active),
                'deviceIncidents as open_incidents_count' => fn (Builder $query) => $query->where('status', DeviceIncidentStatus::Open),
                'transactions as stale_pending_transactions_count' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Pending)
                    ->where('created_at', '<=', $stalePendingCutoff),
            ])
            ->withSum([
                'transactions as successful_revenue' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Successful),
            ], 'amount')
            ->orderBy('name')
            ->get();

        return Inertia::render('operations/branches', [
            'viewer' => $scope['viewer'],
            'filters' => $filters,
            'summary' => [
                'total' => $branches->count(),
                'active' => $branches->where('status', 'active')->count(),
                'unavailable' => $branches->filter(fn (Branch $branch) => in_array($branch->status, [BranchStatus::Maintenance, BranchStatus::Inactive], true))->count(),
                'online_devices' => (int) $branches->sum('online_devices_count'),
                'open_incidents' => (int) $branches->sum('open_incidents_count'),
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
                'open_incidents_count' => $branch->open_incidents_count,
                'active_sessions_count' => $branch->active_sessions_count,
                'stale_pending_transactions_count' => $branch->stale_pending_transactions_count,
                'successful_revenue' => (float) ($branch->successful_revenue ?? 0),
                'currency' => $branch->tenant?->currency,
                'attention_reason' => $this->attentionReason($branch),
            ])->values(),
        ]);
    }

    protected function attentionReason(Branch $branch): ?string
    {
        return match (true) {
            $branch->status === BranchStatus::Inactive => 'Branch is inactive and should not handle live access sales.',
            $branch->status === BranchStatus::Maintenance => 'Branch is in maintenance mode and customer access is intentionally suppressed.',
            (int) $branch->open_incidents_count > 0 => 'Open device incidents are still unresolved at this branch.',
            (int) $branch->stale_pending_transactions_count > 0 => 'This branch has payments that have stayed pending longer than expected.',
            default => null,
        };
    }
}
