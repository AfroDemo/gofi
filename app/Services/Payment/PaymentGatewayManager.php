<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Services\Payment\Contracts\HotspotPaymentGateway;
use InvalidArgumentException;
use Throwable;

class PaymentGatewayManager
{
    protected array $gateways;

    public function __construct(
        protected PalmpesaGateway $palmpesaGateway,
        protected SnippeGateway $snippeGateway,
    ) {
        $this->gateways = [
            'palmpesa' => $this->palmpesaGateway,
            'snippe' => $this->snippeGateway,
        ];
    }

    public function active(): HotspotPaymentGateway
    {
        return $this->gateway($this->activeName());
    }

    public function gateway(?string $name = null, bool $allowDisabled = false): HotspotPaymentGateway
    {
        $resolvedName = $name ?? $this->activeName();

        if (! array_key_exists($resolvedName, $this->gateways)) {
            throw new InvalidArgumentException("Unsupported payment gateway [{$resolvedName}].");
        }

        if (! $allowDisabled && ! $this->isEnabled($resolvedName)) {
            throw new InvalidArgumentException("Payment gateway [{$resolvedName}] is disabled.");
        }

        return $this->gateways[$resolvedName];
    }

    public function configuredName(): string
    {
        return (string) config('payment.gateway', 'palmpesa');
    }

    public function fallbackName(): ?string
    {
        $fallback = config('payment.fallback_gateway');

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    public function isEnabled(string $name): bool
    {
        return (bool) config("payment.{$name}.enabled", false);
    }

    public function availableNames(bool $includeDisabled = false): array
    {
        return array_values(array_filter(
            array_keys($this->gateways),
            fn(string $name) => $includeDisabled || $this->isEnabled($name)
        ));
    }

    public function activeName(): string
    {
        $configured = $this->configuredName();

        if ($this->isEnabled($configured)) {
            return $configured;
        }

        $fallback = $this->fallbackName();

        if ($fallback !== null && $this->isEnabled($fallback)) {
            return $fallback;
        }

        $available = $this->availableNames();

        if ($available !== []) {
            return $available[0];
        }

        throw new InvalidArgumentException('No enabled payment gateways are configured.');
    }

    public function selection(): array
    {
        $configured = $this->configuredName();
        $active = $this->activeName();
        $fallback = $this->fallbackName();

        return [
            'configured' => $configured,
            'active' => $active,
            'fallback' => $fallback,
            'using_fallback' => $active !== $configured,
            'available' => $this->availableNames(),
            'supported' => $this->availableNames(includeDisabled: true),
            'enabled' => collect($this->availableNames(includeDisabled: true))
                ->mapWithKeys(fn(string $name) => [$name => $this->isEnabled($name)])
                ->all(),
        ];
    }

    public function initiateWithFallback(Transaction $transaction, array $payload = []): array
    {
        $configured = $this->configuredName();
        $fallback = $this->fallbackName();

        $attemptOrder = [];

        if ($this->isEnabled($configured) && array_key_exists($configured, $this->gateways)) {
            $attemptOrder[] = $configured;
        }

        if (
            is_string($fallback)
            && $fallback !== ''
            && $fallback !== $configured
            && $this->isEnabled($fallback)
            && array_key_exists($fallback, $this->gateways)
        ) {
            $attemptOrder[] = $fallback;
        }

        if ($attemptOrder === []) {
            $attemptOrder[] = $this->activeName();
        }

        $attempts = [];

        foreach ($attemptOrder as $gatewayName) {
            try {
                $gateway = $this->gateway($gatewayName);
                $result = $gateway->initiatePayment($transaction, $payload);
                $success = (bool) ($result['success'] ?? false);

                $attempts[] = [
                    'gateway' => $gatewayName,
                    'success' => $success,
                    'message' => $result['message'] ?? null,
                ];

                if ($success) {
                    return [
                        'success' => true,
                        'gateway' => $gatewayName,
                        'provider_reference' => $result['provider_reference'] ?? null,
                        'gateway_fee' => $result['gateway_fee'] ?? 0,
                        'status' => $result['status'] ?? 'pending',
                        'message' => $result['message'] ?? 'Payment initiated.',
                        'raw' => $result['raw'] ?? null,
                        'attempts' => $attempts,
                        'selection' => $this->selection(),
                    ];
                }
            } catch (Throwable $e) {
                $attempts[] = [
                    'gateway' => $gatewayName,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'All configured gateways failed to initiate payment.',
            'attempts' => $attempts,
            'selection' => $this->selection(),
        ];
    }
}
