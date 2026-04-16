<?php

namespace App\Http\Controllers;

use App\Actions\Payments\FulfillSuccessfulTransaction;
use App\Enums\BranchStatus;
use App\Enums\TenantStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\VoucherStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\Payment\PaymentGatewayManager;
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
        protected FulfillSuccessfulTransaction $fulfillSuccessfulTransaction,
        protected PaymentGatewayManager $paymentGatewayManager,
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
            'phone_number' => $phoneNumber,
            'amount' => $package->price,
            'gateway_fee' => 0,
            'currency' => $package->currency,
            'metadata' => [
                'channel' => 'portal',
                'flow' => 'mobile_money_checkout',
                'branch_code' => $branch->code,
                'device_ip_address' => $request->ip(),
            ],
        ]);

        $result = $this->paymentGatewayManager->initiateWithFallback($transaction, [
            'phone_number' => $phoneNumber,
            'name' => 'GoFi Customer',
            'email' => 'portal+'.$reference.'@gofi.local',
            'address' => $branch->location ?: $branch->name,
            'postcode' => '00000',
            'buyer_uuid' => $transaction->id,
        ]);

        if (! ($result['success'] ?? false)) {
            $transaction->update([
                'status' => TransactionStatus::Failed,
                'metadata' => $this->mergeMetadata($transaction->metadata, [
                    'payment' => [
                        'selection' => $result['selection'] ?? null,
                        'attempts' => $result['attempts'] ?? [],
                        'message' => $result['message'] ?? 'All gateways failed.',
                    ],
                ]),
            ]);

            return to_route('portal.transactions.show', [
                'tenantSlug' => $tenant->slug,
                'branchCode' => $branch->code,
                'reference' => $transaction->reference,
            ])->with('error', $result['message'] ?? 'Payment initiation failed.');
        }

        $status = strtolower((string) ($result['status'] ?? 'pending'));

        $transaction->update([
            'status' => in_array($status, ['successful', 'success', 'paid', 'completed'], true)
                ? TransactionStatus::Successful
                : TransactionStatus::Pending,
            'provider_reference' => $result['provider_reference'] ?? null,
            'gateway_fee' => (float) ($result['gateway_fee'] ?? 0),
            'paid_at' => in_array($status, ['successful', 'success', 'paid', 'completed'], true) ? now() : null,
            'confirmed_at' => in_array($status, ['successful', 'success', 'paid', 'completed'], true) ? now() : null,
            'metadata' => $this->mergeMetadata($transaction->metadata, [
                'payment' => [
                    'gateway' => $result['gateway'] ?? null,
                    'selection' => $result['selection'] ?? null,
                    'attempts' => $result['attempts'] ?? [],
                    'message' => $result['message'] ?? 'Payment initiated.',
                    'raw' => $result['raw'] ?? null,
                ],
            ]),
        ]);

        if ($transaction->status === TransactionStatus::Successful) {
            $this->fulfillSuccessfulTransaction->execute($transaction);
        }

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
                    'device_mac_address' => $deviceMacAddress,
                    'device_ip_address' => $request->ip(),
                ],
            ]);

            $voucher->update([
                'status' => VoucherStatus::Used,
                'locked_mac_address' => $profile->mac_lock_on_first_use ? $deviceMacAddress : $voucher->locked_mac_address,
                'redeemed_at' => now(),
            ]);

            $this->fulfillSuccessfulTransaction->execute($transaction);

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
        $paymentMetadata = is_array($transaction->metadata) ? ($transaction->metadata['payment'] ?? []) : [];
        $pendingAgeMinutes = $transaction->status === TransactionStatus::Pending
            ? (int) ceil($transaction->created_at?->diffInSeconds(now()) / 60)
            : null;
        $isStalePending = $transaction->status === TransactionStatus::Pending && ($pendingAgeMinutes ?? 0) >= 5;
        $stateHint = match (true) {
            $transaction->status === TransactionStatus::Successful && $session !== null => 'access_active',
            $transaction->status === TransactionStatus::Successful => 'payment_confirmed',
            $transaction->status === TransactionStatus::Pending && $isStalePending => 'stale_pending',
            $transaction->status === TransactionStatus::Pending => 'awaiting_confirmation',
            $transaction->status === TransactionStatus::Cancelled => 'cancelled',
            default => 'retry_required',
        };

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
                'state_hint' => $stateHint,
                'pending_age_minutes' => $pendingAgeMinutes,
                'payment' => [
                    'gateway' => data_get($paymentMetadata, 'gateway'),
                    'provider_reference' => $transaction->provider_reference,
                    'message' => data_get($paymentMetadata, 'message'),
                    'using_fallback' => (bool) data_get($paymentMetadata, 'selection.using_fallback', false),
                    'last_poll' => [
                        'gateway' => data_get($paymentMetadata, 'last_poll.gateway'),
                        'status' => data_get($paymentMetadata, 'last_poll.status'),
                        'checked_at' => data_get($paymentMetadata, 'last_poll.checked_at'),
                    ],
                    'attempts' => collect(data_get($paymentMetadata, 'attempts', []))
                        ->map(fn ($attempt) => [
                            'gateway' => data_get($attempt, 'gateway'),
                            'success' => (bool) data_get($attempt, 'success', false),
                            'message' => data_get($attempt, 'message'),
                        ])
                        ->values()
                        ->all(),
                    'can_check_status' => $transaction->status === TransactionStatus::Pending && filled($transaction->provider_reference),
                    'can_restart' => $transaction->status !== TransactionStatus::Successful || $isStalePending,
                ],
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

    public function refreshTransactionStatus(string $tenantSlug, string $branchCode, string $reference): RedirectResponse
    {
        [$tenant, $branch] = $this->resolvePortalWorkspace($tenantSlug, $branchCode);

        $transaction = Transaction::query()
            ->where('tenant_id', $tenant->id)
            ->where('branch_id', $branch->id)
            ->where('reference', $reference)
            ->firstOrFail();

        if ($transaction->status !== TransactionStatus::Pending || ! $transaction->provider_reference) {
            return back()->with('success', 'Transaction status is already up to date.');
        }

        $gatewayName = data_get($transaction->metadata, 'payment.gateway')
            ?? data_get($transaction->metadata, 'payment.selection.active')
            ?? $this->paymentGatewayManager->activeName();

        $gateway = $this->paymentGatewayManager->gateway((string) $gatewayName, allowDisabled: true);
        $pollResult = $gateway->checkPaymentStatus((string) $transaction->provider_reference);
        $status = strtolower((string) ($pollResult['status'] ?? 'pending'));

        $transaction->update([
            'status' => match (true) {
                in_array($status, ['successful', 'success', 'paid', 'completed'], true) => TransactionStatus::Successful,
                in_array($status, ['cancelled', 'canceled'], true) => TransactionStatus::Cancelled,
                in_array($status, ['failed', 'declined'], true) => TransactionStatus::Failed,
                default => TransactionStatus::Pending,
            },
            'provider_reference' => $pollResult['provider_reference'] ?? $transaction->provider_reference,
            'gateway_fee' => (float) ($pollResult['gateway_fee'] ?? $transaction->gateway_fee),
            'paid_at' => in_array($status, ['successful', 'success', 'paid', 'completed'], true)
                ? ($transaction->paid_at ?? now())
                : $transaction->paid_at,
            'confirmed_at' => in_array($status, ['successful', 'success', 'paid', 'completed'], true)
                ? ($transaction->confirmed_at ?? now())
                : $transaction->confirmed_at,
            'metadata' => $this->mergeMetadata($transaction->metadata, [
                'payment' => [
                    'last_poll' => [
                        'gateway' => $gatewayName,
                        'status' => $status,
                        'checked_at' => now()->toIso8601String(),
                        'raw' => $pollResult['raw'] ?? null,
                    ],
                ],
            ]),
        ]);

        if ($transaction->status === TransactionStatus::Successful) {
            $this->fulfillSuccessfulTransaction->execute($transaction);

            return back()->with('success', 'Payment confirmed and access session activated.');
        }

        if ($transaction->status === TransactionStatus::Pending) {
            return back()->with('success', 'Payment is still pending. Ask the customer to complete the mobile-money approval prompt.');
        }

        return back()->with('error', 'Payment did not complete successfully.');
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

    protected function mergeMetadata(mixed $metadata, array $additions): array
    {
        $base = is_array($metadata) ? $metadata : [];

        return array_replace_recursive($base, $additions);
    }
}
