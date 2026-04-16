<?php

namespace App\Http\Controllers\Api;

use App\Actions\Payments\FulfillSuccessfulTransaction;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\AccessPackage;
use App\Models\Transaction;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $paymentGatewayManager,
        protected FulfillSuccessfulTransaction $fulfillSuccessfulTransaction,
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'access_package_id' => ['required', 'integer', 'exists:access_packages,id'],
            'phone_number' => ['required', 'string', 'max:32'],
            'initiated_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'device_mac_address' => ['nullable', 'string', 'max:64'],
            'device_ip_address' => ['nullable', 'ip'],
        ]);

        $package = AccessPackage::query()
            ->where('id', $validated['access_package_id'])
            ->where('tenant_id', $validated['tenant_id'])
            ->where('is_active', true)
            ->first();

        if (! $package) {
            return response()->json([
                'success' => false,
                'message' => 'Package is not available for the selected tenant.',
            ], 422);
        }

        if (isset($validated['branch_id']) && (int) $package->branch_id !== (int) $validated['branch_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected package does not belong to the requested branch.',
            ], 422);
        }

        $transaction = DB::transaction(function () use ($validated, $package) {
            return Transaction::query()->create([
                'tenant_id' => $validated['tenant_id'],
                'branch_id' => $validated['branch_id'] ?? $package->branch_id,
                'access_package_id' => $package->id,
                'initiated_by_user_id' => $validated['initiated_by_user_id'] ?? null,
                'source' => TransactionSource::MobileMoney,
                'status' => TransactionStatus::Pending,
                'reference' => (string) Str::uuid(),
                'phone_number' => $validated['phone_number'],
                'amount' => $package->price,
                'currency' => $package->currency,
                'metadata' => [
                    'device_mac_address' => $validated['device_mac_address'] ?? null,
                    'device_ip_address' => $validated['device_ip_address'] ?? null,
                ],
            ]);
        });

        $result = $this->paymentGatewayManager->initiateWithFallback($transaction, [
            'phone_number' => $validated['phone_number'],
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

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate payment.',
                'transaction_reference' => $transaction->reference,
                'attempts' => $result['attempts'] ?? [],
                'selection' => $result['selection'] ?? null,
            ], 502);
        }

        $transaction->update([
            'status' => TransactionStatus::Pending,
            'provider_reference' => $result['provider_reference'] ?? null,
            'gateway_fee' => (float) ($result['gateway_fee'] ?? 0),
            'metadata' => $this->mergeMetadata($transaction->metadata, [
                'payment' => [
                    'gateway' => $result['gateway'] ?? null,
                    'selection' => $result['selection'] ?? null,
                    'attempts' => $result['attempts'] ?? [],
                    'message' => $result['message'] ?? 'Payment initiated.',
                ],
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Payment initiated.',
            'transaction_reference' => $transaction->reference,
            'provider_reference' => $transaction->provider_reference,
            'status' => $transaction->status->value,
            'gateway' => $result['gateway'] ?? null,
            'attempts' => $result['attempts'] ?? [],
            'selection' => $result['selection'] ?? null,
        ], 201);
    }

    public function status(string $reference): JsonResponse
    {
        $transaction = Transaction::query()->where('reference', $reference)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        if ($transaction->status !== TransactionStatus::Pending || ! $transaction->provider_reference) {
            return response()->json([
                'success' => true,
                'transaction' => $this->serializeTransaction($transaction),
            ]);
        }

        $gatewayName = data_get($transaction->metadata, 'payment.gateway')
            ?? data_get($transaction->metadata, 'payment.selection.active')
            ?? $this->paymentGatewayManager->activeName();

        $gateway = $this->paymentGatewayManager->gateway((string) $gatewayName, allowDisabled: true);
        $pollResult = $gateway->checkPaymentStatus((string) $transaction->provider_reference);

        $status = strtolower((string) ($pollResult['status'] ?? 'pending'));

        if (in_array($status, ['successful', 'success', 'paid', 'completed'], true)) {
            $transaction->update([
                'status' => TransactionStatus::Successful,
                'provider_reference' => $pollResult['provider_reference'] ?? $transaction->provider_reference,
                'gateway_fee' => (float) ($pollResult['gateway_fee'] ?? $transaction->gateway_fee),
                'paid_at' => $transaction->paid_at ?? now(),
                'confirmed_at' => now(),
            ]);

            $this->fulfillSuccessfulTransaction->execute($transaction);
        } elseif (in_array($status, ['failed', 'cancelled', 'declined'], true)) {
            $transaction->update([
                'status' => $status === 'cancelled' ? TransactionStatus::Cancelled : TransactionStatus::Failed,
            ]);
        }

        return response()->json([
            'success' => true,
            'polled' => [
                'gateway' => $gatewayName,
                'status' => $status,
            ],
            'transaction' => $this->serializeTransaction($transaction->fresh()),
        ]);
    }

    protected function serializeTransaction(Transaction $transaction): array
    {
        return [
            'reference' => $transaction->reference,
            'provider_reference' => $transaction->provider_reference,
            'status' => $transaction->status->value,
            'amount' => (float) $transaction->amount,
            'gateway_fee' => (float) $transaction->gateway_fee,
            'currency' => $transaction->currency,
            'phone_number' => $transaction->phone_number,
            'confirmed_at' => $transaction->confirmed_at?->toIso8601String(),
            'paid_at' => $transaction->paid_at?->toIso8601String(),
        ];
    }

    protected function mergeMetadata(mixed $metadata, array $additions): array
    {
        $base = is_array($metadata) ? $metadata : [];

        return array_replace_recursive($base, $additions);
    }
}
