<?php

namespace App\Http\Controllers;

use App\Enums\VoucherStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Voucher;
use App\Models\VoucherProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenantIds = $scope['tenant_ids'];

        $vouchers = Voucher::query()
            ->whereIn('tenant_id', $tenantIds)
            ->with([
                'tenant:id,name',
                'branch:id,name',
                'accessPackage:id,name',
                'voucherProfile:id,name',
                'creator:id,name',
            ])
            ->latest()
            ->get();

        $profiles = VoucherProfile::query()
            ->whereIn('tenant_id', $tenantIds)
            ->with(['tenant:id,name', 'branch:id,name', 'accessPackage:id,name'])
            ->withCount('vouchers')
            ->withCount([
                'vouchers as unused_count' => fn (Builder $query) => $query->where('status', VoucherStatus::Unused),
                'vouchers as used_count' => fn (Builder $query) => $query->where('status', VoucherStatus::Used),
                'vouchers as expired_count' => fn (Builder $query) => $query->where('status', VoucherStatus::Expired),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('operations/vouchers', [
            'viewer' => $scope['viewer'],
            'summary' => [
                'total' => $vouchers->count(),
                'unused' => $vouchers->where('status', VoucherStatus::Unused)->count(),
                'used' => $vouchers->where('status', VoucherStatus::Used)->count(),
                'expired' => $vouchers->where('status', VoucherStatus::Expired)->count(),
                'profiles' => $profiles->count(),
            ],
            'profiles' => $profiles->map(fn (VoucherProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'tenant' => $profile->tenant?->name,
                'branch' => $profile->branch?->name,
                'package' => $profile->accessPackage?->name,
                'price' => (float) $profile->price,
                'duration_minutes' => $profile->duration_minutes,
                'data_limit_mb' => $profile->data_limit_mb,
                'expires_in_days' => $profile->expires_in_days,
                'total_count' => $profile->vouchers_count,
                'unused_count' => $profile->unused_count,
                'used_count' => $profile->used_count,
                'expired_count' => $profile->expired_count,
                'is_active' => $profile->is_active,
            ])->values(),
            'vouchers' => $vouchers->map(fn (Voucher $voucher) => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'status' => $voucher->status->value,
                'tenant' => $voucher->tenant?->name,
                'branch' => $voucher->branch?->name,
                'package' => $voucher->accessPackage?->name,
                'profile' => $voucher->voucherProfile?->name,
                'created_by' => $voucher->creator?->name,
                'locked_mac_address' => $voucher->locked_mac_address,
                'redeemed_at' => $voucher->redeemed_at?->toIso8601String(),
                'expires_at' => $voucher->expires_at?->toIso8601String(),
                'created_at' => $voucher->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
