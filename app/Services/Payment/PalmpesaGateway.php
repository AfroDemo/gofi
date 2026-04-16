<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Services\Payment\Contracts\HotspotPaymentGateway;
use Illuminate\Support\Facades\Http;

class PalmpesaGateway implements HotspotPaymentGateway
{
    public function name(): string
    {
        return 'palmpesa';
    }

    public function initiatePayment(Transaction $transaction, array $payload = []): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken((string) config('payment.palmpesa.api_token', ''))
            ->post(
                $this->url((string) config('payment.palmpesa.initiate_path', '/api/pay-via-mobile')),
                [
                    'amount' => (float) $transaction->amount,
                    'phone_number' => $payload['phone_number'] ?? $transaction->phone_number,
                    'currency' => $transaction->currency,
                    'reference' => $transaction->reference,
                    'vendor' => config('payment.palmpesa.vendor'),
                    'user_id' => config('payment.palmpesa.user_id'),
                    'callback_url' => $this->callbackUrl(),
                ]
            );

        $data = $response->json() ?? [];
        $status = strtolower((string) ($data['status'] ?? $data['payment_status'] ?? 'pending'));
        $success = $response->successful() && in_array($status, ['pending', 'processing', 'successful', 'success', 'queued'], true);

        return [
            'success' => $success,
            'status' => $success ? 'pending' : 'failed',
            'provider_reference' => $data['transaction_id'] ?? $data['provider_reference'] ?? $data['reference'] ?? null,
            'gateway_fee' => (float) ($data['fee'] ?? 0),
            'message' => $data['message'] ?? ($success ? 'PalmPesa accepted payment request.' : 'PalmPesa rejected payment request.'),
            'raw' => $data,
        ];
    }

    public function checkPaymentStatus(string $providerReference): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken((string) config('payment.palmpesa.api_token', ''))
            ->post(
                $this->url((string) config('payment.palmpesa.status_path', '/api/order-status')),
                [
                    'reference' => $providerReference,
                ]
            );

        $data = $response->json() ?? [];
        $status = strtolower((string) ($data['status'] ?? $data['payment_status'] ?? 'pending'));

        return [
            'success' => $response->successful(),
            'status' => $status,
            'provider_reference' => $data['transaction_id'] ?? $providerReference,
            'gateway_fee' => (float) ($data['fee'] ?? 0),
            'paid_at' => $data['paid_at'] ?? $data['completed_at'] ?? null,
            'raw' => $data,
        ];
    }

    public function verifyWebhookSignature(string $payload, array $headers = []): bool
    {
        $secret = (string) config('payment.palmpesa.webhook_secret', '');

        if ($secret === '') {
            return app()->environment(['local', 'testing']);
        }

        $signature = $this->headerValue($headers, 'x-webhook-signature')
            ?? $this->headerValue($headers, 'x-palmpesa-signature');

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, trim($signature));
    }

    public function normalizeWebhookPayload(array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['payment_status'] ?? 'pending'));

        return [
            'reference' => $payload['reference'] ?? $payload['merchant_reference'] ?? null,
            'provider_reference' => $payload['transaction_id'] ?? $payload['provider_reference'] ?? null,
            'event_type' => $payload['event'] ?? 'payment_update',
            'status' => $status,
            'gateway_fee' => (float) ($payload['fee'] ?? 0),
            'paid_at' => $payload['paid_at'] ?? $payload['completed_at'] ?? null,
        ];
    }

    protected function url(string $path): string
    {
        $baseUrl = rtrim((string) config('payment.palmpesa.base_url', ''), '/');
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . $path;

        return $baseUrl . $normalizedPath;
    }

    protected function callbackUrl(): string
    {
        $callbackPath = (string) config('payment.palmpesa.callback_url', '/api/v1/webhooks/palmpesa');

        if (str_starts_with($callbackPath, 'http://') || str_starts_with($callbackPath, 'https://')) {
            return $callbackPath;
        }

        return url($callbackPath);
    }

    protected function headerValue(array $headers, string $key): ?string
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) !== strtolower($key)) {
                continue;
            }

            if (is_array($value)) {
                return isset($value[0]) ? (string) $value[0] : null;
            }

            return (string) $value;
        }

        return null;
    }
}
