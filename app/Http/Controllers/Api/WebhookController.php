<?php

namespace App\Http\Controllers\Api;

use App\Actions\Payments\FulfillSuccessfulTransaction;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentCallback;
use App\Models\Transaction;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $paymentGatewayManager,
        protected FulfillSuccessfulTransaction $fulfillSuccessfulTransaction,
    ) {}

    public function palmpesa(Request $request): JsonResponse
    {
        return $this->handleGatewayWebhook($request, 'palmpesa');
    }

    public function snippe(Request $request): JsonResponse
    {
        return $this->handleGatewayWebhook($request, 'snippe');
    }

    protected function handleGatewayWebhook(Request $request, string $gatewayName): JsonResponse
    {
        $gateway = $this->paymentGatewayManager->gateway($gatewayName, allowDisabled: true);
        $payload = $request->all();
        $rawPayload = $request->getContent();

        if (! $gateway->verifyWebhookSignature($rawPayload, $request->headers->all())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 403);
        }

        $normalized = $gateway->normalizeWebhookPayload($payload);
        $reference = $normalized['reference'] ?? null;
        $providerReference = $normalized['provider_reference'] ?? null;

        $transaction = Transaction::query()
            ->when(
                is_string($reference) && $reference !== '',
                fn ($query) => $query->where('reference', $reference),
                fn ($query) => $query->where('provider_reference', $providerReference)
            )
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook accepted but no matching transaction was found.',
            ], 202);
        }

        DB::transaction(function () use ($transaction, $gatewayName, $normalized, $payload) {
            $callbackReference = $normalized['provider_reference']
                ?? $normalized['reference']
                ?? sha1(json_encode($payload));

            $callback = PaymentCallback::query()->updateOrCreate(
                [
                    'transaction_id' => $transaction->id,
                    'provider' => $gatewayName,
                    'event_type' => (string) ($normalized['event_type'] ?? 'payment_update'),
                    'callback_reference' => (string) $callbackReference,
                ],
                [
                    'payload' => $payload,
                    'received_at' => now(),
                ]
            );

            $status = strtolower((string) ($normalized['status'] ?? 'pending'));

            if (in_array($status, ['successful', 'success', 'paid', 'completed'], true)) {
                $transaction->update([
                    'status' => TransactionStatus::Successful,
                    'provider_reference' => $normalized['provider_reference'] ?? $transaction->provider_reference,
                    'gateway_fee' => (float) ($normalized['gateway_fee'] ?? $transaction->gateway_fee),
                    'paid_at' => $transaction->paid_at ?? ($normalized['paid_at'] ?? now()),
                    'confirmed_at' => now(),
                ]);

                $this->fulfillSuccessfulTransaction->execute($transaction);
            } elseif (in_array($status, ['failed', 'declined'], true)) {
                $transaction->update([
                    'status' => TransactionStatus::Failed,
                ]);
            } elseif (in_array($status, ['cancelled', 'canceled'], true)) {
                $transaction->update([
                    'status' => TransactionStatus::Cancelled,
                ]);
            }

            $callback->update([
                'processed_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed.',
        ]);
    }
}
