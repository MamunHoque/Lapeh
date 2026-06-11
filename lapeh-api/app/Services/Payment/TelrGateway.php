<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telr Payment Gateway (UAE, AED). Uses the hosted-payment-page "order" API.
 * @see https://telr.com — Telr integration/API reference.
 */
class TelrGateway implements PaymentGatewayInterface
{
    private const ORDER_API = 'https://secure.telr.com/gateway/order.json';

    public function __construct(private SettingsService $settings) {}

    public function name(): string
    {
        return 'telr';
    }

    private function storeId(): ?string
    {
        return $this->settings->get('payment.telr_store_id');
    }

    private function authKey(): ?string
    {
        return $this->settings->get('payment.telr_auth_key');
    }

    private function isTestMode(): bool
    {
        return $this->settings->get('payment.telr_mode', 'test') !== 'live';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->storeId()) && ! empty($this->authKey());
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Telr Store ID and Auth Key are required.'];
        }

        try {
            $res = $this->createOrder('TEST-' . now()->timestamp, 1.00, 'Connection test');
            if (isset($res['order']['ref']) || isset($res['order']['url'])) {
                return ['ok' => true, 'message' => 'Telr credentials valid (' . ($this->isTestMode() ? 'test' : 'live') . ' mode).'];
            }
            $msg = $res['error']['note'] ?? $res['error']['message'] ?? 'Unknown Telr response';
            return ['ok' => false, 'message' => 'Telr rejected the request: ' . $msg];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Telr connection error: ' . $e->getMessage()];
        }
    }

    public function charge(Order $order): array
    {
        if (! $this->isConfigured()) {
            return [
                'status' => 'paid',
                'reference' => 'TELR-SANDBOX-' . strtoupper(uniqid()),
                'message' => 'Sandbox payment accepted (no live keys).',
            ];
        }

        try {
            $res = $this->createOrder(
                $order->order_no,
                (float) $order->total_amount,
                'Lapeh delivery ' . $order->order_no,
            );

            if (isset($res['order']['ref'])) {
                return [
                    'status' => 'pending',
                    'reference' => $res['order']['ref'],
                    'redirect_url' => $res['order']['url'] ?? null,
                    'message' => 'Telr order created.',
                ];
            }
            Log::warning('Telr charge failed', ['res' => $res]);
            return ['status' => 'failed', 'reference' => '', 'message' => $res['error']['note'] ?? 'Telr error'];
        } catch (\Throwable $e) {
            Log::error('Telr charge exception', ['error' => $e->getMessage()]);
            return ['status' => 'failed', 'reference' => '', 'message' => $e->getMessage()];
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        // Telr callbacks are verified by re-querying the order. When an API
        // secret is configured we additionally accept a signed HMAC payload.
        $secret = $this->settings->get('payment.telr_api_secret');
        if ($secret) {
            $signature = $request->header('X-Telr-Signature', '');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if ($signature !== '' && hash_equals($expected, $signature)) {
                return true;
            }
        }

        $ref = $request->input('tran_ref') ?? $request->input('order.ref');
        if (! $ref || ! $this->isConfigured()) {
            return false;
        }

        try {
            $res = Http::asJson()->post(self::ORDER_API, [
                'method' => 'check',
                'store' => $this->storeId(),
                'authkey' => $this->authKey(),
                'order' => ['ref' => $ref],
            ])->json();

            return ($res['order']['status']['code'] ?? null) === 3; // 3 = Paid
        } catch (\Throwable $e) {
            Log::error('Telr webhook verify exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function createOrder(string $cartId, float $amount, string $description): array
    {
        return Http::asJson()->post(self::ORDER_API, [
            'method' => 'create',
            'store' => $this->storeId(),
            'authkey' => $this->authKey(),
            'framed' => 0,
            'order' => [
                'cartid' => $cartId,
                'test' => $this->isTestMode() ? '1' : '0',
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => (string) $this->settings->get('payment.currency', 'AED'),
                'description' => $description,
            ],
            'return' => [
                'authorised' => url('/c'),
                'declined' => url('/c'),
                'cancelled' => url('/c'),
            ],
        ])->json() ?? [];
    }
}
