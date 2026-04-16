<?php

namespace App\Services\Payment\Contracts;

use App\Models\Transaction;

interface HotspotPaymentGateway
{
    public function name(): string;

    public function initiatePayment(Transaction $transaction, array $payload = []): array;

    public function checkPaymentStatus(string $providerReference): array;

    public function verifyWebhookSignature(string $payload, array $headers = []): bool;

    public function normalizeWebhookPayload(array $payload): array;
}
