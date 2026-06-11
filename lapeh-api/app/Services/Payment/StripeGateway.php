<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(private SettingsService $settings) {}

    public function name(): string
    {
        return 'stripe';
    }

    private function secret(): ?string
    {
        return $this->settings->get('payment.stripe_secret_key', config('services.payment.secret'));
    }

    private function mode(): string
    {
        return (string) $this->settings->get('payment.stripe_mode', 'test');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secret());
    }

    public function testConnection(): array
    {
        $secret = $this->secret();
        if (! $secret) {
            return ['ok' => false, 'message' => 'No Stripe secret key configured.'];
        }

        try {
            // /v1/balance is a cheap authenticated read — validates the key.
            $res = Http::withToken($secret)->asForm()->get('https://api.stripe.com/v1/balance');
            if ($res->successful()) {
                return ['ok' => true, 'message' => 'Stripe keys valid (' . $this->mode() . ' mode).'];
            }
            return ['ok' => false, 'message' => 'Stripe rejected the key: ' . ($res->json('error.message') ?? $res->status())];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Stripe connection error: ' . $e->getMessage()];
        }
    }

    public function charge(Order $order): array
    {
        // With no live secret, keep the sandbox auto-approve demo flow.
        if (! $this->isConfigured()) {
            return [
                'status' => 'paid',
                'reference' => 'STRIPE-SANDBOX-' . strtoupper(uniqid()),
                'message' => 'Sandbox payment accepted (no live keys).',
            ];
        }

        try {
            // Create a PaymentIntent; the hosted/Elements confirmation and the
            // webhook complete the charge.
            $res = Http::withToken($this->secret())->asForm()->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($order->total_amount * 100),
                'currency' => strtolower((string) $this->settings->get('payment.currency', 'AED')),
                'metadata' => ['order_no' => $order->order_no],
                'automatic_payment_methods' => ['enabled' => 'true'],
            ]);

            if ($res->successful()) {
                return [
                    'status' => 'pending',
                    'reference' => $res->json('id'),
                    'message' => 'PaymentIntent created.',
                ];
            }
            Log::warning('Stripe charge failed', ['body' => $res->body()]);
            return ['status' => 'failed', 'reference' => '', 'message' => $res->json('error.message') ?? 'Stripe error'];
        } catch (\Throwable $e) {
            Log::error('Stripe charge exception', ['error' => $e->getMessage()]);
            return ['status' => 'failed', 'reference' => '', 'message' => $e->getMessage()];
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = $this->settings->get('payment.stripe_webhook_secret', config('services.payment.webhook_secret'));
        if (! $secret) {
            return false;
        }

        $header = $request->header('Stripe-Signature', '');
        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $parts[$k][] = $v;
        }
        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if (! $timestamp || empty($signatures)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }
        return false;
    }
}
