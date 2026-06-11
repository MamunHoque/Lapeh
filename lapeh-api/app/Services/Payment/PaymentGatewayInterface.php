<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Lapeh supports exactly two gateways: Stripe and Telr. Do not add others.
 */
interface PaymentGatewayInterface
{
    /** Machine name stored on the Payment row ("stripe" | "telr"). */
    public function name(): string;

    /** Whether usable credentials are configured for this gateway. */
    public function isConfigured(): bool;

    /**
     * Validate the configured credentials against the gateway's sandbox/live
     * API without charging anything.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array;

    /**
     * Begin a charge for an order. In sandbox mode with no live secret this
     * auto-approves (returns status "paid") to preserve the dev demo flow;
     * with live credentials it returns "pending" plus a redirect_url for the
     * hosted checkout, to be confirmed later by the webhook.
     *
     * @return array{status: string, reference: string, redirect_url?: string, message?: string}
     */
    public function charge(Order $order): array;

    /** Verify an inbound webhook's signature for this gateway. */
    public function verifyWebhook(Request $request): bool;
}
