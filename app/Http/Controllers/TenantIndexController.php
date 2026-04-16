<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);

        $tenants = Tenant::query()
            ->whereIn('id', $scope['tenant_ids'])
            ->with(['owner:id,name,email', 'creator:id,name'])
            ->withCount(['branches', 'users', 'packages'])
            ->withSum([
                'transactions as successful_revenue' => fn (Builder $query) => $query
                    ->where('status', TransactionStatus::Successful),
            ], 'amount')
            ->orderBy('name')
            ->get();

        return Inertia::render('operations/tenants', [
            'viewer' => $scope['viewer'],
            'capabilities' => [
                'can_create' => $scope['is_platform_admin'],
            ],
            'summary' => [
                'total' => $tenants->count(),
                'active' => $tenants->where('status', 'active')->count(),
                'branches' => (int) $tenants->sum('branches_count'),
                'packages' => (int) $tenants->sum('packages_count'),
                'successful_revenue' => (float) $tenants->sum(fn (Tenant $tenant) => (float) ($tenant->successful_revenue ?? 0)),
            ],
            'tenants' => $tenants->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status->value,
                'currency' => $tenant->currency,
                'country_code' => $tenant->country_code,
                'timezone' => $tenant->timezone,
                'owner' => $tenant->owner?->name,
                'owner_email' => $tenant->owner?->email,
                'creator' => $tenant->creator?->name,
                'branches_count' => $tenant->branches_count,
                'users_count' => $tenant->users_count,
                'packages_count' => $tenant->packages_count,
                'successful_revenue' => (float) ($tenant->successful_revenue ?? 0),
            ])->values(),
        ]);
    }
}
