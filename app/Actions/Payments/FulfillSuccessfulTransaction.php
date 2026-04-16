<?php

namespace App\Actions\Payments;

use App\Actions\Finance\CreateRevenueAllocation;
use App\Enums\HotspotSessionStatus;
use App\Enums\LedgerDirection;
use App\Enums\TransactionStatus;
use App\Models\HotspotSession;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FulfillSuccessfulTransaction
{
    public function __construct(
        protected CreateRevenueAllocation $createRevenueAllocation,
    ) {}

    public function execute(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction = Transaction::query()
                ->with([
                    'accessPackage:id,name,duration_minutes,data_limit_mb',
                    'voucher.voucherProfile:id,duration_minutes,data_limit_mb,mac_lock_on_first_use',
                    'revenueShareRule:id,tenant_id,name,model,platform_percentage,platform_fixed_fee',
                    'ledgerEntries:id,transaction_id,entry_type',
                    'hotspotSessions:id,transaction_id',
                ])
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($transaction->status !== TransactionStatus::Successful) {
                return $transaction;
            }

            $allocation = null;

            if ($transaction->revenueShareRule) {
                $allocation = $this->createRevenueAllocation->execute($transaction, $transaction->revenueShareRule);
            }

            if (
                $allocation
                && ! $transaction->ledgerEntries->contains(fn (LedgerEntry $entry) => $entry->entry_type === 'sale')
                && (float) $allocation->tenant_amount > 0
            ) {
                $currentBalance = (float) (LedgerEntry::query()
                    ->where('tenant_id', $transaction->tenant_id)
                    ->latest('id')
                    ->value('balance_after') ?? 0);

                LedgerEntry::query()->create([
                    'tenant_id' => $transaction->tenant_id,
                    'transaction_id' => $transaction->id,
                    'direction' => LedgerDirection::Credit,
                    'entry_type' => 'sale',
                    'amount' => $allocation->tenant_amount,
                    'currency' => $transaction->currency,
                    'balance_after' => $currentBalance + (float) $allocation->tenant_amount,
                    'description' => 'Tenant share for '.$transaction->reference,
                    'posted_at' => $transaction->confirmed_at ?? $transaction->created_at ?? now(),
                ]);
            }

            if ($transaction->hotspotSessions->isEmpty()) {
                $durationMinutes = $transaction->voucher?->voucherProfile?->duration_minutes
                    ?? $transaction->accessPackage?->duration_minutes;
                $dataLimitMb = $transaction->voucher?->voucherProfile?->data_limit_mb
                    ?? $transaction->accessPackage?->data_limit_mb;
                $startedAt = $transaction->confirmed_at ?? now();
                $macAddress = data_get($transaction->metadata, 'device_mac_address')
                    ?: $this->generatePseudoMacAddress($transaction);

                HotspotSession::query()->create([
                    'tenant_id' => $transaction->tenant_id,
                    'branch_id' => $transaction->branch_id,
                    'access_package_id' => $transaction->access_package_id,
                    'voucher_id' => $transaction->voucher_id,
                    'transaction_id' => $transaction->id,
                    'device_mac_address' => $macAddress,
                    'device_ip_address' => data_get($transaction->metadata, 'device_ip_address'),
                    'status' => HotspotSessionStatus::Active,
                    'duration_minutes' => $durationMinutes,
                    'data_limit_mb' => $dataLimitMb,
                    'data_used_mb' => 0,
                    'started_at' => $startedAt,
                    'expires_at' => $durationMinutes ? $startedAt->copy()->addMinutes($durationMinutes) : null,
                ]);
            }

            return $transaction->fresh([
                'revenueAllocation',
                'ledgerEntries',
                'hotspotSessions',
            ]);
        });
    }

    protected function generatePseudoMacAddress(Transaction $transaction): string
    {
        $seed = implode('|', [
            $transaction->reference,
            $transaction->phone_number,
            $transaction->tenant_id,
            $transaction->branch_id,
        ]);
        $hex = substr(md5($seed), 0, 12);

        return Str::upper(implode(':', str_split($hex, 2)));
    }
}
