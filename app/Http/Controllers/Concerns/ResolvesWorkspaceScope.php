<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\PlatformRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesWorkspaceScope
{
    /**
     * @return array{
     *     user: User,
     *     is_platform_admin: bool,
     *     tenant: ?Tenant,
     *     tenant_ids: array<int, int>,
     *     currency: ?string,
     *     viewer: array{id: int, scope: string, name: string, role: string, currency: ?string}
     * }
     */
    protected function resolveWorkspaceScope(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $tenantMembership = $user->tenantMemberships()->with('tenant')->orderByDesc('is_primary')->first();

        $isPlatformAdmin = $user->isPlatformAdmin();
        $tenantScope = $isPlatformAdmin ? null : $tenantMembership?->tenant;
        $tenantIds = match (true) {
            $isPlatformAdmin => Tenant::query()->pluck('id')->all(),
            $tenantScope !== null => [$tenantScope->id],
            default => [],
        };

        $currencies = Tenant::query()
            ->whereIn('id', $tenantIds)
            ->whereNotNull('currency')
            ->distinct()
            ->pluck('currency');

        $currency = $currencies->count() === 1 ? $currencies->first() : null;

        return [
            'user' => $user,
            'is_platform_admin' => $isPlatformAdmin,
            'tenant' => $tenantScope,
            'tenant_ids' => $tenantIds,
            'currency' => $currency,
            'viewer' => [
                'id' => $user->id,
                'scope' => $isPlatformAdmin ? 'platform' : 'tenant',
                'name' => $tenantScope?->name ?? ($isPlatformAdmin ? 'Platform overview' : 'No tenant assigned'),
                'role' => ($user->platform_role ?? PlatformRole::TenantUser)->value,
                'currency' => $currency,
            ],
        ];
    }
}
