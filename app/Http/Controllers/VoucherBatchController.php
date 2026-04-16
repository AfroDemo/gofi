<?php

namespace App\Http\Controllers;

use App\Enums\VoucherStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Voucher;
use App\Models\VoucherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VoucherBatchController extends Controller
{
    use ResolvesWorkspaceScope;

    public function create(Request $request, VoucherProfile $voucherProfile): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $voucherProfile = $this->scopedProfile($voucherProfile->id, $scope['tenant_ids']);

        return Inertia::render('operations/voucher-batch-form', [
            'viewer' => $scope['viewer'],
            'profile' => $this->serializeProfile($voucherProfile),
        ]);
    }

    public function store(Request $request, VoucherProfile $voucherProfile): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $voucherProfile = $this->scopedProfile($voucherProfile->id, $scope['tenant_ids']);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $created = DB::transaction(function () use ($request, $voucherProfile, $validated) {
            $count = (int) $validated['quantity'];
            $startSequence = Voucher::query()->where('voucher_profile_id', $voucherProfile->id)->count() + 1;
            $expiresAt = $voucherProfile->expires_in_days
                ? Carbon::now()->addDays($voucherProfile->expires_in_days)
                : null;

            $rows = collect(range(0, $count - 1))
                ->map(function (int $offset) use ($request, $voucherProfile, $startSequence, $expiresAt) {
                    $sequence = $startSequence + $offset;

                    return [
                        'tenant_id' => $voucherProfile->tenant_id,
                        'branch_id' => $voucherProfile->branch_id,
                        'voucher_profile_id' => $voucherProfile->id,
                        'access_package_id' => $voucherProfile->access_package_id,
                        'code' => $this->buildVoucherCode($voucherProfile->code_prefix ?: 'GFI', $voucherProfile->id, $sequence),
                        'status' => VoucherStatus::Unused->value,
                        'expires_at' => $expiresAt,
                        'created_by_user_id' => $request->user()?->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->all();

            Voucher::query()->insert($rows);

            return $count;
        });

        return to_route('vouchers.index')->with('success', "Generated {$created} vouchers for {$voucherProfile->name}.");
    }

    /**
     * @param  array<int, int>  $tenantIds
     */
    protected function scopedProfile(int $id, array $tenantIds): VoucherProfile
    {
        return VoucherProfile::query()
            ->whereIn('tenant_id', $tenantIds)
            ->with(['tenant:id,name,currency', 'branch:id,name,location', 'accessPackage:id,name,currency'])
            ->withCount([
                'vouchers',
                'vouchers as unused_count' => fn ($query) => $query->where('status', VoucherStatus::Unused),
            ])
            ->findOrFail($id);
    }

    protected function buildVoucherCode(string $prefix, int $profileId, int $sequence): string
    {
        return sprintf('%s-%03d-%04d', strtoupper($prefix), $profileId, $sequence);
    }

    protected function serializeProfile(VoucherProfile $voucherProfile): array
    {
        return [
            'id' => $voucherProfile->id,
            'name' => $voucherProfile->name,
            'tenant' => $voucherProfile->tenant?->name,
            'branch' => $voucherProfile->branch?->name,
            'location' => $voucherProfile->branch?->location,
            'package' => $voucherProfile->accessPackage?->name,
            'currency' => $voucherProfile->accessPackage?->currency ?? $voucherProfile->tenant?->currency,
            'price' => (float) $voucherProfile->price,
            'code_prefix' => $voucherProfile->code_prefix,
            'duration_minutes' => $voucherProfile->duration_minutes,
            'data_limit_mb' => $voucherProfile->data_limit_mb,
            'speed_limit_kbps' => $voucherProfile->speed_limit_kbps,
            'expires_in_days' => $voucherProfile->expires_in_days,
            'mac_lock_on_first_use' => $voucherProfile->mac_lock_on_first_use,
            'is_active' => $voucherProfile->is_active,
            'vouchers_count' => $voucherProfile->vouchers_count,
            'unused_count' => $voucherProfile->unused_count,
        ];
    }
}
