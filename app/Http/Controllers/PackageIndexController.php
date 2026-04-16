<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\AccessPackage;
use App\Models\Transaction;
use App\Models\VoucherProfile;
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

        $packages = AccessPackage::query()
            ->whereIn('tenant_id', $tenantIds)
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
            'summary' => [
                'total' => $packages->count(),
                'active' => $packages->where('is_active', true)->count(),
                'voucher_profiles' => VoucherProfile::query()->whereIn('tenant_id', $tenantIds)->count(),
                'branch_coverage' => $packages->pluck('branch_id')->filter()->unique()->count(),
                'successful_revenue' => (float) Transaction::query()
                    ->whereIn('tenant_id', $tenantIds)
                    ->where('status', TransactionStatus::Successful)
                    ->sum('amount'),
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
