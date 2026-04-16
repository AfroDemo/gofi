<?php

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchManagementController extends Controller
{
    use ResolvesWorkspaceScope;

    public function create(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenantOptions = $this->tenantOptions($scope['tenant_ids']);

        return Inertia::render('operations/branch-form', [
            'mode' => 'create',
            'viewer' => $scope['viewer'],
            'tenantOptions' => $tenantOptions,
            'managerOptions' => $this->managerOptions($tenantOptions->pluck('id')->all()),
            'statusOptions' => $this->statusOptions(),
            'branch' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $payload = $this->validatePayload($request, $scope['tenant_ids'], null);

        Branch::query()->create($payload);

        return to_route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Request $request, Branch $branch): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with(['tenant:id,name,currency', 'manager:id,name,email'])
            ->findOrFail($branch->id);

        $tenantOptions = $this->tenantOptions($scope['tenant_ids']);

        return Inertia::render('operations/branch-form', [
            'mode' => 'edit',
            'viewer' => $scope['viewer'],
            'tenantOptions' => $tenantOptions,
            'managerOptions' => $this->managerOptions($tenantOptions->pluck('id')->all()),
            'statusOptions' => $this->statusOptions(),
            'branch' => [
                'id' => $branch->id,
                'tenant_id' => $branch->tenant_id,
                'name' => $branch->name,
                'code' => $branch->code,
                'status' => $branch->status->value,
                'location' => $branch->location,
                'address' => $branch->address,
                'manager_user_id' => $branch->manager_user_id,
            ],
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $branch = Branch::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($branch->id);
        $payload = $this->validatePayload($request, $scope['tenant_ids'], $branch);

        $branch->update($payload);

        return to_route('branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * @param  array<int, int>  $tenantIds
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request, array $tenantIds, ?Branch $branch): array
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::in($tenantIds)],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $request->input('tenant_id')))
                    ->ignore($branch?->id),
            ],
            'status' => ['required', Rule::in(array_map(fn (BranchStatus $status) => $status->value, BranchStatus::cases()))],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'manager_user_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant_user', 'user_id')->where(fn ($query) => $query->where('tenant_id', $request->input('tenant_id'))),
            ],
        ]);

        return [
            'tenant_id' => $validated['tenant_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'status' => $validated['status'],
            'location' => $validated['location'] ?? null,
            'address' => $validated['address'] ?? null,
            'manager_user_id' => $validated['manager_user_id'] ?? null,
        ];
    }

    /**
     * @param  array<int, int>  $tenantIds
     */
    protected function tenantOptions(array $tenantIds)
    {
        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->orderBy('name')
            ->get(['id', 'name', 'currency']);
    }

    /**
     * @param  array<int, int>  $tenantIds
     * @return array<int, array<string, int|string>>
     */
    protected function managerOptions(array $tenantIds): array
    {
        return User::query()
            ->whereHas('tenantMemberships', fn ($query) => $query->whereIn('tenant_id', $tenantIds))
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
        return collect(BranchStatus::cases())
            ->map(fn (BranchStatus $status) => [
                'value' => $status->value,
                'label' => Str::headline($status->value),
            ])
            ->all();
    }
}
