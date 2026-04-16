<?php

namespace App\Http\Controllers;

use App\Enums\DeviceStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\PayoutStatus;
use App\Enums\TenantStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
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
            ],
            'deviceStatus' => [
                'online' => (clone $devices)->where('status', DeviceStatus::Online)->count(),
                'offline' => (clone $devices)->where('status', DeviceStatus::Offline)->count(),
                'provisioning' => (clone $devices)->where('status', DeviceStatus::Provisioning)->count(),
            ],
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
}
