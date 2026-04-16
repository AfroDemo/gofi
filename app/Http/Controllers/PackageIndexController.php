<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\AccessPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenantIds = $scope['tenant_ids'];
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => in_array($request->string('status')->toString(), ['all', 'active', 'inactive'], true)
                ? $request->string('status')->toString()
                : 'all',
            'type' => in_array($request->string('type')->toString(), ['all', 'time', 'data', 'mixed'], true)
                ? $request->string('type')->toString()
                : 'all',
        ];

        $packages = AccessPackage::query()
            ->whereIn('tenant_id', $tenantIds)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('name', 'like', $search))
                        ->orWhereHas('branch', fn (Builder $branch) => $branch
                            ->where('name', 'like', $search)
                            ->orWhere('location', 'like', $search));
                });
            })
            ->when($filters['status'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($filters['type'] !== 'all', fn (Builder $query) => $query->where('package_type', $filters['type']))
            ->with(['tenant:id,name', 'branch:id,name,location'])
            ->withCount('voucherProfiles')
            ->withCount([
                'transactions as successful_transactions_count' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Successful),
            ])
            ->withSum([
                'transactions as successful_revenue' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Successful),
            ], 'amount')
            ->orderByDesc('is_active')
            ->orderBy('price')
            ->get();

        $typeMix = $packages
            ->groupBy(fn (AccessPackage $package) => $package->package_type->value)
            ->map(fn ($group, $type) => [
                'type' => $type,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return Inertia::render('operations/packages', [
            'viewer' => $scope['viewer'],
            'filters' => $filters,
            'summary' => [
                'total' => $packages->count(),
                'active' => $packages->where('is_active', true)->count(),
                'voucher_profiles' => (int) $packages->sum('voucher_profiles_count'),
                'branch_coverage' => $packages->pluck('branch_id')->filter()->unique()->count(),
                'successful_revenue' => (float) $packages->sum(fn (AccessPackage $package) => (float) ($package->successful_revenue ?? 0)),
            ],
            'typeMix' => $typeMix,
            'packages' => $packages->map(fn (AccessPackage $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'tenant' => $package->tenant?->name,
                'branch' => $package->branch?->name,
                'location' => $package->branch?->location,
                'type' => $package->package_type->value,
                'price' => (float) $package->price,
                'currency' => $package->currency,
                'duration_minutes' => $package->duration_minutes,
                'data_limit_mb' => $package->data_limit_mb,
                'speed_limit_kbps' => $package->speed_limit_kbps,
                'is_active' => $package->is_active,
                'voucher_profiles_count' => $package->voucher_profiles_count,
                'successful_transactions_count' => $package->successful_transactions_count,
                'successful_revenue' => (float) ($package->successful_revenue ?? 0),
            ])->values(),
        ]);
    }
}
