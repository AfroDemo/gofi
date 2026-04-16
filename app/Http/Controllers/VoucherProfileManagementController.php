<?php

namespace App\Http\Controllers;

use App\Enums\PackageType;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\VoucherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class VoucherProfileManagementController extends Controller
{
    use ResolvesWorkspaceScope;

    public function create(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenantOptions = $this->tenantOptions($scope['tenant_ids']);

        return Inertia::render('operations/voucher-profile-form', [
            'mode' => 'create',
            'viewer' => $scope['viewer'],
            'tenantOptions' => $tenantOptions,
            'branchOptions' => $this->branchOptions($tenantOptions->pluck('id')->all()),
            'packageOptions' => $this->packageOptions($tenantOptions->pluck('id')->all()),
            'profile' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $payload = $this->validatePayload($request, $scope['tenant_ids']);

        VoucherProfile::query()->create($payload);

        return to_route('vouchers.index')->with('success', 'Voucher profile created successfully.');
    }

    public function edit(Request $request, VoucherProfile $voucherProfile): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $voucherProfile = VoucherProfile::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with(['tenant:id,name,currency', 'branch:id,name,tenant_id,location', 'accessPackage:id,name,tenant_id,branch_id,currency'])
            ->findOrFail($voucherProfile->id);

        $tenantOptions = $this->tenantOptions($scope['tenant_ids']);

        return Inertia::render('operations/voucher-profile-form', [
            'mode' => 'edit',
            'viewer' => $scope['viewer'],
            'tenantOptions' => $tenantOptions,
            'branchOptions' => $this->branchOptions($tenantOptions->pluck('id')->all()),
            'packageOptions' => $this->packageOptions($tenantOptions->pluck('id')->all()),
            'profile' => [
                'id' => $voucherProfile->id,
                'tenant_id' => $voucherProfile->tenant_id,
                'branch_id' => $voucherProfile->branch_id,
                'access_package_id' => $voucherProfile->access_package_id,
                'name' => $voucherProfile->name,
                'code_prefix' => $voucherProfile->code_prefix,
                'price' => (string) $voucherProfile->price,
                'duration_minutes' => $voucherProfile->duration_minutes,
                'data_limit_mb' => $voucherProfile->data_limit_mb,
                'speed_limit_kbps' => $voucherProfile->speed_limit_kbps,
                'expires_in_days' => $voucherProfile->expires_in_days,
                'mac_lock_on_first_use' => $voucherProfile->mac_lock_on_first_use,
                'is_active' => $voucherProfile->is_active,
            ],
        ]);
    }

    public function update(Request $request, VoucherProfile $voucherProfile): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $voucherProfile = VoucherProfile::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($voucherProfile->id);
        $payload = $this->validatePayload($request, $scope['tenant_ids']);

        $voucherProfile->update($payload);

        return to_route('vouchers.index')->with('success', 'Voucher profile updated successfully.');
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
            'access_package_id' => [
                'required',
                'integer',
                Rule::exists('access_packages', 'id')->where(fn ($query) => $query->where('tenant_id', $request->input('tenant_id'))),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code_prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'data_limit_mb' => ['nullable', 'integer', 'min:1'],
            'speed_limit_kbps' => ['nullable', 'integer', 'min:1'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'mac_lock_on_first_use' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator->after(function (Validator $validator) use ($request) {
            $packageId = $request->integer('access_package_id');

            if (! $packageId) {
                return;
            }

            $package = AccessPackage::query()->find($packageId);

            if (! $package) {
                return;
            }

            $duration = $request->input('duration_minutes') ?: $package->duration_minutes;
            $dataLimit = $request->input('data_limit_mb') ?: $package->data_limit_mb;

            if (in_array($package->package_type, [PackageType::Time, PackageType::Mixed], true) && ! $duration) {
                $validator->errors()->add('duration_minutes', 'Duration is required for voucher profiles tied to time-based or mixed packages.');
            }

            if (in_array($package->package_type, [PackageType::Data, PackageType::Mixed], true) && ! $dataLimit) {
                $validator->errors()->add('data_limit_mb', 'Data limit is required for voucher profiles tied to data-based or mixed packages.');
            }
        });

        $validated = $validator->validate();
        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $package = AccessPackage::query()->where('tenant_id', $tenant->id)->findOrFail($validated['access_package_id']);

        return [
            'tenant_id' => $tenant->id,
            'branch_id' => $validated['branch_id'] ?? $package->branch_id,
            'access_package_id' => $package->id,
            'name' => $validated['name'],
            'code_prefix' => strtoupper($validated['code_prefix']),
            'price' => $validated['price'] ?? $package->price,
            'duration_minutes' => $validated['duration_minutes'] ?? $package->duration_minutes,
            'data_limit_mb' => $validated['data_limit_mb'] ?? $package->data_limit_mb,
            'speed_limit_kbps' => $validated['speed_limit_kbps'] ?? $package->speed_limit_kbps,
            'expires_in_days' => $validated['expires_in_days'] ?? null,
            'mac_lock_on_first_use' => $request->boolean('mac_lock_on_first_use', true),
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
     * @param  array<int, int>  $tenantIds
     * @return array<int, array<string, int|string|null>>
     */
    protected function packageOptions(array $tenantIds): array
    {
        return AccessPackage::query()
            ->whereIn('tenant_id', $tenantIds)
            ->orderBy('name')
            ->get([
                'id',
                'tenant_id',
                'branch_id',
                'name',
                'package_type',
                'price',
                'currency',
                'duration_minutes',
                'data_limit_mb',
                'speed_limit_kbps',
            ])
            ->map(fn (AccessPackage $package) => [
                'id' => $package->id,
                'tenant_id' => $package->tenant_id,
                'branch_id' => $package->branch_id,
                'name' => $package->name,
                'package_type' => $package->package_type->value,
                'price' => (float) $package->price,
                'currency' => $package->currency,
                'duration_minutes' => $package->duration_minutes,
                'data_limit_mb' => $package->data_limit_mb,
                'speed_limit_kbps' => $package->speed_limit_kbps,
                'label' => $package->name,
            ])
            ->all();
    }
}
