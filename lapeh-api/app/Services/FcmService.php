<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key', '');
    }

    /**
     * Send a data-only FCM push to a single device token.
     * Data-only messages work in both foreground and background without showing a system notification.
     */
    public function sendToToken(string $token, array $data, string $title = '', string $body = ''): bool
    {
        if (empty($this->serverKey) || empty($token)) {
            Log::debug('FCM skipped — no server key or token', ['token' => substr($token, 0, 20)]);
            return false;
        }

        $payload = [
            'to' => $token,
            'data' => $data,
            'priority' => 'high',
        ];

        if ($title || $body) {
            $payload['notification'] = ['title' => $title, 'body' => $body];
        }

        try {
            $res = Http::withHeaders([
                'Authorization' => "key={$this->serverKey}",
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if (!$res->successful()) {
                Log::warning('FCM send failed', ['status' => $res->status(), 'body' => $res->body()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendOfferToDriver(string $fcmToken, array $offerPayload): void
    {
        $this->sendToToken(
            $fcmToken,
            [
                'type' => 'new_offer',
                'offer' => json_encode($offerPayload),
            ],
            'New delivery request',
            "AED {$offerPayload['delivery_fee']} · {$offerPayload['restaurant_name']}"
        );
    }
}
