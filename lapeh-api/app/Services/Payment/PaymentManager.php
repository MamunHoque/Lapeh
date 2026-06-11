<?php

namespace App\Services\Payment;

use App\Services\SettingsService;
use InvalidArgumentException;

/**
 * Resolves the active payment gateway. Exactly two drivers exist: Stripe and
 * Telr. The active one (for customer checkout) is set in the settings hub.
 */
class PaymentManager
{
    public function __construct(private SettingsService $settings) {}

    /** The gateway currently selected for customer checkout. */
    public function active(): PaymentGatewayInterface
    {
        return $this->driver((string) $this->settings->get('payment.gateway', 'stripe'));
    }

    public function activeName(): string
    {
        return (string) $this->settings->get('payment.gateway', 'stripe');
    }

    public function driver(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'stripe' => app(StripeGateway::class),
            'telr' => app(TelrGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment gateway: {$name}"),
        };
    }
}
