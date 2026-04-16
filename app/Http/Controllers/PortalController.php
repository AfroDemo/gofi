<?php

namespace App\Http\Controllers;

use App\Actions\Finance\CreateRevenueAllocation;
use App\Enums\BranchStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\LedgerDirection;
use App\Enums\TenantStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\VoucherStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\HotspotSession;
use App\Models\LedgerEntry;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function __construct(
        protected CreateRevenueAllocation $createRevenueAllocation,
    ) {}

    public function show(string $tenantSlug, string $branchCode): Response
    {
        [$tenant, $branch] = $this->resolvePortalWorkspace($tenantSlug, $branchCode);

        $packages = AccessPackage::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)->orWhereNull('branch_id');
            })
            ->orderByRaw('case when branch_id = ? then 0 else 1 end', [$branch->id])
            ->orderBy('price')
            ->get()
            ->map(fn (AccessPackage $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => (float) $package->price,
                'currency' => $package->currency,
                'duration_minutes' => $package->duration_minutes,
                'data_limit_mb' => $package->data_limit_mb,
                'speed_limit_kbps' => $package->speed_limit_kbps,
                'is_branch_specific' => $package->branch_id === $branch->id,
            ])
            ->values();

        return Inertia::render('portal/show', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
            ],
            'branch' => [
                'name' => $branch->name,
                'code' => $branch->code,
                'location' => $branch->location,
                'address' => $branch->address,
            ],
            'packages' => $packages,
        ]);
    }

    public function checkout(Request $request, string $tenantSlug, string $branchCode): RedirectResponse
    {
        [$tenant, $branch] = $this->resolvePortalWorkspace($tenantSlug, $branchCode);

        $validated = $request->validate([
            'package_id' => ['required', 'integer'],
            'phone_number' => ['required', 'string', 'max:32'],
        ]);

        $package = AccessPackage::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)->orWhereNull('branch_id');
            })
            ->find($validated['package_id']);

        if (! $package) {
            throw ValidationException::withMessages([
                'package_id' => 'Selected package is not available at this hotspot.',
            ]);
        }

        $phoneNumber = $this->normalizePhoneNumber($validated['phone_number']);
        $reference = $this->generatePortalReference();
        $rule = $this->resolveRevenueShareRule($tenant->id, $branch->id, $package->id);

        $transaction = Transaction::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'access_package_id' => $package->id,
            'revenue_share_rule_id' => $rule?->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Pending,
            'reference' => $reference,
            'provider_reference' => $reference,
            'phone_number' => $phoneNumber,
            'amount' => $package->price,
            'gateway_fee' => 0,
            'currency' => $package->currency,
            'metadata' => [
                'channel' => 'portal',
                'flow' => 'mobile_money_checkout',
                'branch_code' => $branch->code,
            ],
        ]);

        return to_route('portal.transactions.show', [
            'tenantSlug' => $tenant->slug,
            'branchCode' => $branch->code,
            'reference' => $transaction->reference,
        ])->with('success', 'Payment request created. Ask the customer to approve the mobile money prompt on their phone.');
    }

    public function redeemVoucher(Request $request, string $tenantSlug, string $branchCode): RedirectResponse
    {
        [$tenant, $branch] = $this->resolvePortalWorkspace($tenantSlug, $branchCode);

        $validated = $request->validate([
            'voucher_code' => ['required', 'string', 'max:64'],
        ]);

        $voucherCode = Str::upper(trim($validated['voucher_code']));

        /** @var Transaction $transaction */
        $transaction = DB::transaction(function () use ($branch, $request, $tenant, $voucherCode) {
            $voucher = Voucher::query()
                ->where('tenant_id', $tenant->id)
                ->whereRaw('upper(code) = ?', [$voucherCode])
                ->where(function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)->orWhereNull('branch_id');
                })
                ->with(['voucherProfile', 'accessPackage'])
                ->lockForUpdate()
                ->first();

            if (! $voucher) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher code is invalid for this hotspot.',
                ]);
            }

            if ($voucher->status !== VoucherStatus::Unused) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher is no longer available for redemption.',
                ]);
            }

            if ($voucher->expires_at && $voucher->expires_at->isPast()) {
                $voucher->update(['status' => VoucherStatus::Expired]);

                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher has expired.',
                ]);
            }

            $package = $voucher->accessPackage;
            $profile = $voucher->voucherProfile;

            if (! $profile || ! $profile->is_active) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher profile is not active anymore.',
                ]);
            }

            if ($package && ! $package->is_active) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'Voucher package is not active anymore.',
                ]);
            }

            $reference = $this->generatePortalReference();
            $rule = $this->resolveRevenueShareRule($tenant->id, $branch->id, $package?->id);
            $deviceMacAddress = $this->generatePseudoMacAddress($voucherCode.'|'.$request->ip().'|'.$branch->id);

            $transaction = Transaction::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'access_package_id' => $package?->id,
                'voucher_id' => $voucher->id,
                'revenue_share_rule_id' => $rule?->id,
                'source' => TransactionSource::Voucher,
                'status' => TransactionStatus::Successful,
                'reference' => $reference,
                'provider_reference' => $reference,
                'amount' => $profile->price,
                'gateway_fee' => 0,
                'currency' => $tenant->currency,
                'paid_at' => now(),
                'confirmed_at' => now(),
                'metadata' => [
                    'channel' => 'portal',
                    'flow' => 'voucher_redemption',
                    'branch_code' => $branch->code,
                ],
            ]);

            if ($rule) {
                $allocation = $this->createRevenueAllocation->execute($transaction, $rule);
                $this->appendLedgerEntry($transaction, (float) $allocation->tenant_amount);
            }

            $voucher->update([
                'status' => VoucherStatus::Used,
                'locked_mac_address' => $profile->mac_lock_on_first_use ? $deviceMacAddress : $voucher->locked_mac_address,
                'redeemed_at' => now(),
            ]);

            HotspotSession::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'access_package_id' => $package?->id,
                'voucher_id' => $voucher->id,
                'transaction_id' => $transaction->id,
                'device_mac_address' => $deviceMacAddress,
                'device_ip_address' => $request->ip(),
                'status' => HotspotSessionStatus::Active,
                'duration_minutes' => $profile->duration_minutes ?? $package?->duration_minutes,
                'data_limit_mb' => $profile->data_limit_mb ?? $package?->data_limit_mb,
                'data_used_mb' => 0,
                'started_at' => now(),
                'expires_at' => ($profile->duration_minutes ?? $package?->duration_minutes)
                    ? now()->addMinutes($profile->duration_minutes ?? $package?->duration_minutes)
                    : null,
            ]);

            return $transaction;
        });

        return to_route('portal.transactions.show', [
            'tenantSlug' => $tenant->slug,
            'branchCode' => $branch->code,
            'reference' => $transaction->reference,
        ])->with('success', 'Voucher redeemed successfully. Access session has started.');
    }

    public function showTransaction(string $tenantSlug, string $branchCode, string $reference): Response
    {
        [$tenant, $branch] = $this->resolvePortalWorkspace($tenantSlug, $branchCode);

        $transaction = Transaction::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $branch->id)
            ->where('reference', $reference)
            ->with([
                'accessPackage:id,name,duration_minutes,data_limit_mb',
                'voucher:id,code',
                'hotspotSessions:id,transaction_id,status,device_mac_address,device_ip_address,duration_minutes,data_limit_mb,data_used_mb,started_at,expires_at,ended_at',
            ])
            ->firstOrFail();

        $session = $transaction->hotspotSessions->sortByDesc('started_at')->first();

        return Inertia::render('portal/transaction-status', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'branch' => [
                'name' => $branch->name,
                'code' => $branch->code,
                'location' => $branch->location,
            ],
            'transaction' => [
                'reference' => $transaction->reference,
                'source' => $transaction->source->value,
                'status' => $transaction->status->value,
                'phone_number' => $transaction->phone_number,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'package' => $transaction->accessPackage?->name,
                'voucher_code' => $transaction->voucher?->code,
                'created_at' => $transaction->created_at?->toIso8601String(),
                'confirmed_at' => $transaction->confirmed_at?->toIso8601String(),
                'session' => $session ? [
                    'status' => $session->status->value,
                    'device_mac_address' => $session->device_mac_address,
                    'device_ip_address' => $session->device_ip_address,
                    'duration_minutes' => $session->duration_minutes,
                    'data_limit_mb' => $session->data_limit_mb,
                    'started_at' => $session->started_at?->toIso8601String(),
                    'expires_at' => $session->expires_at?->toIso8601String(),
                    'ended_at' => $session->ended_at?->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * @return array{0: Tenant, 1: Branch}
     */
    protected function resolvePortalWorkspace(string $tenantSlug, string $branchCode): array
    {
        $tenant = Tenant::query()
            ->where('slug', $tenantSlug)
            ->where('status', TenantStatus::Active)
            ->firstOrFail();

        $branch = Branch::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', Str::upper($branchCode))
            ->where('status', BranchStatus::Active)
            ->firstOrFail();

        return [$tenant, $branch];
    }

    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        $normalized = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (strlen($normalized) < 9 || strlen($normalized) > 15) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid mobile money number.',
            ]);
        }

        return $normalized;
    }

    protected function generatePortalReference(): string
    {
        do {
            $reference = 'PRT-'.Str::upper(Str::random(10));
        } while (Transaction::query()->where('reference', $reference)->exists());

        return $reference;
    }

    protected function generatePseudoMacAddress(string $seed): string
    {
        $hex = substr(md5($seed), 0, 12);

        return Str::upper(implode(':', str_split($hex, 2)));
    }

    protected function resolveRevenueShareRule(int $tenantId, int $branchId, ?int $packageId): ?RevenueShareRule
    {
        return RevenueShareRule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->when(
                $packageId,
                fn ($query) => $query->where(function ($inner) use ($packageId) {
                    $inner->where('access_package_id', $packageId)->orWhereNull('access_package_id');
                }),
                fn ($query) => $query->whereNull('access_package_id')
            )
            ->orderByRaw(
                'case
                    when branch_id = ? and access_package_id = ? then 4
                    when access_package_id = ? and branch_id is null then 3
                    when branch_id = ? and access_package_id is null then 2
                    else 1
                end desc',
                [$branchId, $packageId, $packageId, $branchId]
            )
            ->orderByDesc('id')
            ->first();
    }

    protected function appendLedgerEntry(Transaction $transaction, float $tenantAmount): void
    {
        $currentBalance = (float) (LedgerEntry::query()
            ->where('tenant_id', $transaction->tenant_id)
            ->latest('id')
            ->value('balance_after') ?? 0);

        LedgerEntry::query()->create([
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->id,
            'direction' => LedgerDirection::Credit,
            'entry_type' => 'sale',
            'amount' => $tenantAmount,
            'currency' => $transaction->currency,
            'balance_after' => $currentBalance + $tenantAmount,
            'description' => 'Tenant share for '.$transaction->reference,
            'posted_at' => $transaction->confirmed_at ?? $transaction->created_at ?? now(),
        ]);
    }
}
