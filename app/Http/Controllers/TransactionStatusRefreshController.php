<?php

namespace App\Http\Controllers;

use App\Actions\Payments\FulfillSuccessfulTransaction;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Transaction;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class TransactionStatusRefreshController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __construct(
        protected PaymentGatewayManager $paymentGatewayManager,
        protected FulfillSuccessfulTransaction $fulfillSuccessfulTransaction,
    ) {}

    public function __invoke(Request $request, Transaction $transaction): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $transaction = Transaction::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($transaction->id);

        if ($transaction->status !== TransactionStatus::Pending || ! $transaction->provider_reference) {
            return to_route('transactions.show', $transaction)->with('success', 'Transaction status is already up to date.');
        }

        $gatewayName = data_get($transaction->metadata, 'payment.gateway')
            ?? data_get($transaction->metadata, 'payment.selection.active')
            ?? $this->paymentGatewayManager->activeName();

        try {
            $gateway = $this->paymentGatewayManager->gateway((string) $gatewayName, allowDisabled: true);
            $pollResult = $gateway->checkPaymentStatus((string) $transaction->provider_reference);
        } catch (Throwable $exception) {
            return to_route('transactions.show', $transaction)->with('error', 'Status check failed: '.$exception->getMessage());
        }

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

            return to_route('transactions.show', $transaction)->with('success', 'Payment confirmed and fulfilment completed.');
        }

        if ($transaction->status === TransactionStatus::Pending) {
            return to_route('transactions.show', $transaction)->with('success', 'Payment is still pending. Follow up with the customer or provider if it stays stuck.');
        }

        return to_route('transactions.show', $transaction)->with('error', 'Payment did not complete successfully.');
    }

    protected function mergeMetadata(mixed $metadata, array $additions): array
    {
        $base = is_array($metadata) ? $metadata : [];

        return array_replace_recursive($base, $additions);
    }
}
