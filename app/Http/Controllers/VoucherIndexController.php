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
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => in_array($request->string('status')->toString(), ['all', 'unused', 'active', 'used', 'expired', 'cancelled'], true)
                ? $request->string('status')->toString()
                : 'all',
        ];

        $vouchers = Voucher::query()
            ->whereIn('tenant_id', $tenantIds)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('code', 'like', $search)
                        ->orWhere('locked_mac_address', 'like', $search)
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('name', 'like', $search))
                        ->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $search))
                        ->orWhereHas('accessPackage', fn (Builder $package) => $package->where('name', 'like', $search))
                        ->orWhereHas('voucherProfile', fn (Builder $profile) => $profile->where('name', 'like', $search))
                        ->orWhereHas('creator', fn (Builder $creator) => $creator->where('name', 'like', $search));
                });
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
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
            ->whereIn('id', $vouchers->pluck('voucher_profile_id')->filter()->unique())
            ->with(['tenant:id,name,currency', 'branch:id,name', 'accessPackage:id,name,currency'])
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
            'filters' => $filters,
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
                'currency' => $profile->accessPackage?->currency ?? $profile->tenant?->currency,
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
