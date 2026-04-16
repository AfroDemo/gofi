<?php

namespace App\Http\Controllers;

use App\Enums\HotspotSessionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => in_array($request->string('status')->toString(), ['all', 'active', 'pending', 'expired', 'terminated'], true)
                ? $request->string('status')->toString()
                : 'all',
        ];

        $sessions = HotspotSession::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('device_mac_address', 'like', $search)
                        ->orWhere('device_ip_address', 'like', $search)
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('name', 'like', $search))
                        ->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $search))
                        ->orWhereHas('accessPackage', fn (Builder $package) => $package->where('name', 'like', $search))
                        ->orWhereHas('voucher', fn (Builder $voucher) => $voucher->where('code', 'like', $search))
                        ->orWhereHas('transaction', fn (Builder $transaction) => $transaction->where('reference', 'like', $search));
                });
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->with([
                'tenant:id,name,currency',
                'branch:id,name,location',
                'accessPackage:id,name',
                'voucher:id,code',
                'transaction:id,reference',
            ])
            ->latest('started_at')
            ->get();

        return Inertia::render('operations/sessions', [
            'viewer' => $scope['viewer'],
            'filters' => $filters,
            'summary' => [
                'total' => $sessions->count(),
                'active' => $sessions->where('status', HotspotSessionStatus::Active)->count(),
                'pending' => $sessions->where('status', HotspotSessionStatus::Pending)->count(),
                'expired' => $sessions->where('status', HotspotSessionStatus::Expired)->count(),
                'data_in_use_mb' => (int) $sessions->sum('data_used_mb'),
            ],
            'sessions' => $sessions->map(fn (HotspotSession $session) => [
                'id' => $session->id,
                'tenant' => $session->tenant?->name,
                'branch' => $session->branch?->name,
                'location' => $session->branch?->location,
                'package' => $session->accessPackage?->name,
                'voucher' => $session->voucher?->code,
                'transaction_reference' => $session->transaction?->reference,
                'status' => $session->status->value,
                'mac_address' => $session->device_mac_address,
                'ip_address' => $session->device_ip_address,
                'duration_minutes' => $session->duration_minutes,
                'data_limit_mb' => $session->data_limit_mb,
                'data_used_mb' => (int) $session->data_used_mb,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
