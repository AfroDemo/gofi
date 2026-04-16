<?php

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Enums\DeviceIncidentSeverity;
use App\Enums\DeviceIncidentStatus;
use App\Enums\DeviceStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\PayoutStatus;
use App\Enums\TenantStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use App\Models\DeviceIncident;
use App\Models\HotspotDevice;
use App\Models\HotspotSession;
use App\Models\Payout;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $isPlatformAdmin = $scope['is_platform_admin'];
        $tenantScope = $scope['tenant'];
        $tenantIds = $scope['tenant_ids'];

        $transactions = Transaction::query()->whereIn('tenant_id', $tenantIds);
        $sessions = HotspotSession::query()->whereIn('tenant_id', $tenantIds);
        $devices = HotspotDevice::query()->whereIn('tenant_id', $tenantIds);
        $branches = Branch::query()->whereIn('tenant_id', $tenantIds);
        $payouts = Payout::query()->whereIn('tenant_id', $tenantIds);
        $escalations = $this->buildEscalations($tenantIds);

        return Inertia::render('dashboard', [
            'viewer' => $scope['viewer'],
            'summary' => [
                'revenue' => (float) (clone $transactions)
                    ->where('status', TransactionStatus::Successful)
                    ->sum('amount'),
                'active_sessions' => (clone $sessions)
                    ->where('status', HotspotSessionStatus::Active)
                    ->count(),
                'tenants' => $tenantScope
                    ? 1
                    : ($isPlatformAdmin ? Tenant::query()->where('status', TenantStatus::Active)->count() : 0),
                'branches' => (clone $branches)->count(),
                'pending_payouts' => (float) (clone $payouts)
                    ->whereIn('status', [PayoutStatus::Pending, PayoutStatus::Processing])
                    ->sum('amount'),
                'pending_transactions' => (clone $transactions)
                    ->where('status', TransactionStatus::Pending)
                    ->count(),
                'escalations' => $escalations['summary']['total'],
            ],
            'deviceStatus' => [
                'online' => (clone $devices)->where('status', DeviceStatus::Online)->count(),
                'offline' => (clone $devices)->where('status', DeviceStatus::Offline)->count(),
                'provisioning' => (clone $devices)->where('status', DeviceStatus::Provisioning)->count(),
            ],
            'escalations' => $escalations,
            'revenueRows' => $this->buildRevenueRows($isPlatformAdmin, $tenantScope),
            'recentTransactions' => (clone $transactions)
                ->with(['tenant:id,name', 'branch:id,name'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'tenant' => $transaction->tenant?->name,
                    'branch' => $transaction->branch?->name,
                    'amount' => (float) $transaction->amount,
                    'source' => $transaction->source->value,
                    'status' => $transaction->status->value,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                ]),
            'activeSessions' => (clone $sessions)
                ->with(['branch:id,name', 'accessPackage:id,name'])
                ->where('status', HotspotSessionStatus::Active)
                ->orderBy('expires_at')
                ->limit(6)
                ->get()
                ->map(fn (HotspotSession $session) => [
                    'id' => $session->id,
                    'mac_address' => $session->device_mac_address,
                    'ip_address' => $session->device_ip_address,
                    'branch' => $session->branch?->name,
                    'package' => $session->accessPackage?->name,
                    'expires_at' => $session->expires_at?->toIso8601String(),
                    'data_used_mb' => (int) $session->data_used_mb,
                ]),
        ]);
    }

    protected function buildRevenueRows(bool $isPlatformAdmin, ?Tenant $tenantScope): array
    {
        if ($tenantScope) {
            return Branch::query()
                ->where('tenant_id', $tenantScope->id)
                ->withSum([
                    'transactions as successful_revenue' => fn (Builder $query) => $query
                        ->where('status', TransactionStatus::Successful),
                ], 'amount')
                ->withCount([
                    'sessions as active_sessions_count' => fn (Builder $query) => $query
                        ->where('status', HotspotSessionStatus::Active),
                ])
                ->orderByDesc('successful_revenue')
                ->limit(6)
                ->get()
                ->map(fn (Branch $branch) => [
                    'name' => $branch->name,
                    'location' => $branch->location,
                    'revenue' => (float) ($branch->successful_revenue ?? 0),
                    'active_sessions' => $branch->active_sessions_count,
                    'kind' => 'branch',
                ])
                ->all();
        }

        if (! $isPlatformAdmin) {
            return [];
        }

        return Tenant::query()
            ->withSum([
                'transactions as successful_revenue' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Successful),
            ], 'amount')
            ->withCount([
                'sessions as active_sessions_count' => fn (Builder $query) => $query
                    ->where('status', HotspotSessionStatus::Active),
            ])
            ->orderByDesc('successful_revenue')
            ->limit(6)
            ->get()
            ->map(fn (Tenant $tenant) => [
                'name' => $tenant->name,
                'location' => $tenant->timezone,
                'revenue' => (float) ($tenant->successful_revenue ?? 0),
                'active_sessions' => $tenant->active_sessions_count,
                'kind' => 'tenant',
            ])
            ->all();
    }

    protected function buildEscalations(array $tenantIds): array
    {
        $severityPriority = [
            DeviceIncidentSeverity::Critical->value => 100,
            DeviceIncidentSeverity::High->value => 92,
            DeviceIncidentSeverity::Medium->value => 82,
            DeviceIncidentSeverity::Low->value => 72,
        ];

        $branchAlerts = Branch::query()
            ->whereIn('tenant_id', $tenantIds)
            ->whereIn('status', [BranchStatus::Maintenance, BranchStatus::Inactive])
            ->with(['tenant:id,name'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Branch $branch) => [
                'type' => 'branch_availability',
                'level' => $branch->status === BranchStatus::Inactive ? 'high' : 'medium',
                'priority' => $branch->status === BranchStatus::Inactive ? 90 : 80,
                'title' => $branch->status === BranchStatus::Inactive
                    ? $branch->name.' is inactive'
                    : $branch->name.' is in maintenance',
                'description' => $branch->status === BranchStatus::Inactive
                    ? 'This branch is out of service. New access sales should stay blocked until operators reactivate it.'
                    : 'This branch is in maintenance mode, so new checkout and voucher access are intentionally suppressed.',
                'tenant' => $branch->tenant?->name,
                'branch' => $branch->name,
                'occurred_at' => $branch->updated_at?->toIso8601String(),
                'href' => route('branches.index', [
                    'attention' => 'unavailable',
                    'search' => $branch->name,
                ]),
                'action_label' => 'Review branch',
            ]);

        $incidentAlerts = DeviceIncident::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('status', DeviceIncidentStatus::Open)
            ->with([
                'tenant:id,name',
                'branch:id,name',
                'device:id,name,identifier',
            ])
            ->latest('opened_at')
            ->get()
            ->map(fn (DeviceIncident $incident) => [
                'type' => 'device_incident',
                'level' => $incident->severity->value,
                'priority' => $severityPriority[$incident->severity->value] ?? 70,
                'title' => $incident->title,
                'description' => sprintf(
                    '%s at %s needs operator follow-up.',
                    $incident->device?->name ?? $incident->device?->identifier ?? 'This device',
                    $incident->branch?->name ?? 'the branch'
                ),
                'tenant' => $incident->tenant?->name,
                'branch' => $incident->branch?->name,
                'occurred_at' => $incident->opened_at?->toIso8601String(),
                'href' => route('devices.index', [
                    'attention' => 'open_incidents',
                    'search' => $incident->device?->identifier ?? $incident->device?->name,
                ]),
                'action_label' => 'Review device',
            ]);

        $stalePendingAlerts = Transaction::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('status', TransactionStatus::Pending)
            ->where('created_at', '<=', now()->subMinutes(5))
            ->with(['tenant:id,name', 'branch:id,name'])
            ->latest()
            ->get()
            ->map(fn (Transaction $transaction) => [
                'type' => 'payment_followup',
                'level' => 'medium',
                'priority' => 78,
                'title' => 'Pending payment needs review',
                'description' => sprintf(
                    '%s at %s has been waiting for provider confirmation longer than expected.',
                    $transaction->reference,
                    $transaction->branch?->name ?? $transaction->tenant?->name ?? 'this workspace'
                ),
                'tenant' => $transaction->tenant?->name,
                'branch' => $transaction->branch?->name,
                'occurred_at' => $transaction->created_at?->toIso8601String(),
                'href' => route('transactions.index', [
                    'attention' => 'stale_pending',
                    'search' => $transaction->reference,
                ]),
                'action_label' => 'Review payment',
            ]);

        $unfulfilledConfirmedAlerts = Transaction::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('status', TransactionStatus::Successful)
            ->whereDoesntHave('hotspotSessions')
            ->with(['tenant:id,name', 'branch:id,name'])
            ->latest('confirmed_at')
            ->get()
            ->filter(fn (Transaction $transaction) => data_get($transaction->metadata, 'payment.branch_unavailable_at_confirmation') === true)
            ->map(fn (Transaction $transaction) => [
                'type' => 'payment_followup',
                'level' => 'high',
                'priority' => 96,
                'title' => 'Confirmed payment could not activate access',
                'description' => sprintf(
                    '%s was paid successfully, but %s was unavailable when confirmation arrived.',
                    $transaction->reference,
                    $transaction->branch?->name ?? 'the branch'
                ),
                'tenant' => $transaction->tenant?->name,
                'branch' => $transaction->branch?->name,
                'occurred_at' => ($transaction->confirmed_at ?? $transaction->updated_at)?->toIso8601String(),
                'href' => route('transactions.index', [
                    'attention' => 'review',
                    'search' => $transaction->reference,
                ]),
                'action_label' => 'Inspect payment',
            ]);

        $items = $branchAlerts
            ->concat($incidentAlerts)
            ->concat($stalePendingAlerts)
            ->concat($unfulfilledConfirmedAlerts)
            ->sort(function (array $left, array $right) {
                if ($left['priority'] !== $right['priority']) {
                    return $right['priority'] <=> $left['priority'];
                }

                return strcmp((string) ($right['occurred_at'] ?? ''), (string) ($left['occurred_at'] ?? ''));
            })
            ->take(8)
            ->values()
            ->map(function (array $item) {
                unset($item['priority']);

                return $item;
            })
            ->all();

        return [
            'summary' => [
                'total' => $branchAlerts->count() + $incidentAlerts->count() + $stalePendingAlerts->count() + $unfulfilledConfirmedAlerts->count(),
                'unavailable_branches' => $branchAlerts->count(),
                'open_incidents' => $incidentAlerts->count(),
                'payment_followups' => $stalePendingAlerts->count() + $unfulfilledConfirmedAlerts->count(),
            ],
            'items' => $items,
        ];
    }
}
