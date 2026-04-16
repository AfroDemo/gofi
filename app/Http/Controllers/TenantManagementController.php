<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Enums\TenantUserRole;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantManagementController extends Controller
{
    use ResolvesWorkspaceScope;

    public function create(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        abort_unless($scope['is_platform_admin'], 403);

        return Inertia::render('operations/tenant-form', [
            'mode' => 'create',
            'viewer' => $scope['viewer'],
            'statusOptions' => $this->statusOptions(),
            'ownerOptions' => $this->ownerOptions(),
            'tenant' => null,
            'capabilities' => [
                'can_edit_owner' => true,
                'can_edit_status' => true,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        abort_unless($scope['is_platform_admin'], 403);

        $payload = $this->validatePayload($request, null, true);
        $tenant = Tenant::query()->create([
            ...$payload,
            'created_by_user_id' => $scope['user']->id,
        ]);

        $this->ensureOwnerMembership($tenant, $payload['owner_user_id'] ?? null);

        return to_route('tenants.index')->with('success', 'Tenant created successfully.');
    }

    public function edit(Request $request, Tenant $tenant): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenant = Tenant::query()
            ->whereIn('id', $scope['tenant_ids'])
            ->with(['owner:id,name,email'])
            ->findOrFail($tenant->id);

        return Inertia::render('operations/tenant-form', [
            'mode' => 'edit',
            'viewer' => $scope['viewer'],
            'statusOptions' => $this->statusOptions(),
            'ownerOptions' => $scope['is_platform_admin'] ? $this->ownerOptions() : [],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status->value,
                'currency' => $tenant->currency,
                'country_code' => $tenant->country_code,
                'timezone' => $tenant->timezone,
                'owner_user_id' => $tenant->owner_user_id,
                'owner_name' => $tenant->owner?->name,
            ],
            'capabilities' => [
                'can_edit_owner' => $scope['is_platform_admin'],
                'can_edit_status' => $scope['is_platform_admin'],
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenant = Tenant::query()->whereIn('id', $scope['tenant_ids'])->findOrFail($tenant->id);
        $payload = $this->validatePayload($request, $tenant, $scope['is_platform_admin']);

        $tenant->update($payload);

        if ($scope['is_platform_admin']) {
            $this->ensureOwnerMembership($tenant, $payload['owner_user_id'] ?? null);
        }

        return to_route('tenants.index')->with('success', 'Tenant updated successfully.');
    }

    protected function validatePayload(Request $request, ?Tenant $tenant, bool $canEditPlatformFields): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                Rule::requiredIf($canEditPlatformFields),
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'slug')->ignore($tenant?->id),
            ],
            'status' => [
                Rule::requiredIf($canEditPlatformFields),
                'nullable',
                Rule::in(array_map(fn (TenantStatus $status) => $status->value, TenantStatus::cases())),
            ],
            'currency' => ['required', 'string', 'size:3'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'owner_user_id' => [
                Rule::requiredIf($canEditPlatformFields),
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $canEditPlatformFields
                ? ($validated['slug'] ?: Str::slug($validated['name']))
                : ($tenant?->slug ?? Str::slug($validated['name'])),
            'status' => $canEditPlatformFields
                ? ($validated['status'] ?? TenantStatus::Active->value)
                : ($tenant?->status->value ?? TenantStatus::Active->value),
            'currency' => strtoupper($validated['currency']),
            'country_code' => isset($validated['country_code']) ? strtoupper((string) $validated['country_code']) : null,
            'timezone' => $validated['timezone'],
            'owner_user_id' => $canEditPlatformFields ? ($validated['owner_user_id'] ?? null) : $tenant?->owner_user_id,
        ];
    }

    protected function ensureOwnerMembership(Tenant $tenant, ?int $ownerUserId): void
    {
        if (! $ownerUserId) {
            return;
        }

        $tenant->users()->syncWithoutDetaching([
            $ownerUserId => [
                'role' => TenantUserRole::Owner->value,
                'is_primary' => true,
            ],
        ]);
    }

    protected function ownerOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'label' => "{$user->name} ({$user->email})",
            ])
            ->all();
    }

    protected function statusOptions(): array
    {
        return collect(TenantStatus::cases())
            ->map(fn (TenantStatus $status) => [
                'value' => $status->value,
                'label' => Str::headline($status->value),
            ])
            ->all();
    }
}
