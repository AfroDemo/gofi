<?php

namespace App\Http\Controllers;

use App\Enums\PackageType;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PackageManagementController extends Controller
{
    use ResolvesWorkspaceScope;

    public function create(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenantOptions = $this->tenantOptions($scope['tenant_ids']);

        return Inertia::render('operations/package-form', [
            'mode' => 'create',
            'viewer' => $scope['viewer'],
            'packageTypes' => $this->packageTypes(),
            'tenantOptions' => $tenantOptions,
            'branchOptions' => $this->branchOptions($tenantOptions->pluck('id')->all()),
            'package' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $payload = $this->validatePayload($request, $scope['tenant_ids']);

        AccessPackage::query()->create($payload);

        return to_route('packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Request $request, AccessPackage $package): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $package = AccessPackage::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with(['tenant:id,name,currency', 'branch:id,name,tenant_id,location'])
            ->findOrFail($package->id);

        $tenantOptions = $this->tenantOptions($scope['tenant_ids']);

        return Inertia::render('operations/package-form', [
            'mode' => 'edit',
            'viewer' => $scope['viewer'],
            'packageTypes' => $this->packageTypes(),
            'tenantOptions' => $tenantOptions,
            'branchOptions' => $this->branchOptions($tenantOptions->pluck('id')->all()),
            'package' => [
                'id' => $package->id,
                'tenant_id' => $package->tenant_id,
                'branch_id' => $package->branch_id,
                'name' => $package->name,
                'package_type' => $package->package_type->value,
                'description' => $package->description,
                'price' => (string) $package->price,
                'currency' => $package->currency,
                'duration_minutes' => $package->duration_minutes,
                'data_limit_mb' => $package->data_limit_mb,
                'speed_limit_kbps' => $package->speed_limit_kbps,
                'is_active' => $package->is_active,
            ],
        ]);
    }

    public function update(Request $request, AccessPackage $package): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $package = AccessPackage::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($package->id);
        $payload = $this->validatePayload($request, $scope['tenant_ids']);

        $package->update($payload);

        return to_route('packages.index')->with('success', 'Package updated successfully.');
    }

    /**
     * @param  array<int, int>  $tenantIds
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request, array $tenantIds): array
    {
        /** @var Validator $validator */
        $validator = validator($request->all(), [
            'tenant_id' => ['required', 'integer', Rule::in($tenantIds)],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('tenant_id', $request->input('tenant_id'))),
            ],
            'name' => ['required', 'string', 'max:255'],
            'package_type' => ['required', 'string', Rule::in(array_map(fn (PackageType $type) => $type->value, PackageType::cases()))],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'data_limit_mb' => ['nullable', 'integer', 'min:1'],
            'speed_limit_kbps' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (Validator $validator) use ($request) {
            $type = $request->string('package_type')->toString();
            $hasDuration = filled($request->input('duration_minutes'));
            $hasDataLimit = filled($request->input('data_limit_mb'));

            if (in_array($type, [PackageType::Time->value, PackageType::Mixed->value], true) && ! $hasDuration) {
                $validator->errors()->add('duration_minutes', 'Duration is required for time-based and mixed packages.');
            }

            if (in_array($type, [PackageType::Data->value, PackageType::Mixed->value], true) && ! $hasDataLimit) {
                $validator->errors()->add('data_limit_mb', 'Data limit is required for data-based and mixed packages.');
            }
        });

        $validated = $validator->validate();
        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);

        return [
            'tenant_id' => $tenant->id,
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'package_type' => $validated['package_type'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'currency' => $tenant->currency,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'data_limit_mb' => $validated['data_limit_mb'] ?? null,
            'speed_limit_kbps' => $validated['speed_limit_kbps'] ?? null,
            'is_active' => $request->boolean('is_active', true),
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
     * @return array<int, array<string, int|string|null>>
     */
    protected function branchOptions(array $tenantIds): array
    {
        return Branch::query()
            ->whereIn('tenant_id', $tenantIds)
            ->orderBy('name')
            ->get(['id', 'tenant_id', 'name', 'location'])
            ->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'tenant_id' => $branch->tenant_id,
                'name' => $branch->name,
                'location' => $branch->location,
                'label' => $branch->location ? "{$branch->name} - {$branch->location}" : $branch->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, description: string}>
     */
    protected function packageTypes(): array
    {
        return [
            [
                'value' => PackageType::Time->value,
                'label' => 'Time',
                'description' => 'Best for sessions sold by hour, day, or weekend window.',
            ],
            [
                'value' => PackageType::Data->value,
                'label' => 'Data',
                'description' => 'Best for capped bundles sold by megabytes or gigabytes.',
            ],
            [
                'value' => PackageType::Mixed->value,
                'label' => 'Mixed',
                'description' => 'Use when both time window and data cap should apply together.',
            ],
        ];
    }
}
