<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging via the HTTP v1 API.
 *
 * Authenticates with a service-account JSON (Firebase Console → Project
 * Settings → Service accounts → Generate new private key). The legacy
 * server-key API (fcm/send) was shut down by Google in June 2024.
 *
 * The OAuth2 JWT is signed in-process with openssl (RS256) — no extra
 * composer dependency required.
 */
class FcmService
{
    private ?array $credentials = null;
    private string $projectId = '';

    public function __construct()
    {
        $path = config('services.fcm.credentials');
        if (! $path) {
            return;
        }

        $resolved = str_starts_with($path, '/') ? $path : base_path($path);
        if (! is_file($resolved)) {
            Log::warning('FCM credentials file not found', ['path' => $resolved]);
            return;
        }

        $json = json_decode((string) file_get_contents($resolved), true);
        if (is_array($json) && isset($json['client_email'], $json['private_key'])) {
            $this->credentials = $json;
            $this->projectId = config('services.fcm.project_id') ?: ($json['project_id'] ?? '');
        } else {
            Log::warning('FCM credentials file is malformed', ['path' => $resolved]);
        }
    }

    /**
     * Send a high-priority data message to a single device token.
     * Returns false (and logs) on any misconfiguration or transport error.
     */
    public function sendToToken(string $token, array $data, string $title = '', string $body = ''): bool
    {
        if (! $this->credentials || empty($token) || empty($this->projectId)) {
            Log::debug('FCM skipped — no credentials/project/token');
            return false;
        }

        $accessToken = $this->accessToken();
        if (! $accessToken) {
            return false;
        }

        // v1 requires every data value to be a string.
        $message = [
            'token' => $token,
            'data' => array_map(fn ($v) => is_string($v) ? $v : (string) $v, $data),
            'android' => ['priority' => 'high'],
            'apns' => ['headers' => ['apns-priority' => '10']],
        ];
        if ($title !== '' || $body !== '') {
            $message['notification'] = ['title' => $title, 'body' => $body];
        }

        try {
            $res = Http::withToken($accessToken)->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                ['message' => $message],
            );

            if (! $res->successful()) {
                Log::warning('FCM v1 send failed', ['status' => $res->status(), 'body' => $res->body()]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM v1 exception', ['error' => $e->getMessage()]);
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
            "AED {$offerPayload['delivery_fee']} · {$offerPayload['pickup_name']}",
        );
    }

    /**
     * OAuth2 access token for the FCM scope, cached until ~5 min before expiry.
     */
    private function accessToken(): ?string
    {
        $email = $this->credentials['client_email'];

        return Cache::remember(
            'fcm_access_token_' . md5($email),
            3300, // tokens live 1h; refresh a little early
            fn () => $this->fetchAccessToken(),
        );
    }

    private function fetchAccessToken(): ?string
    {
        $now = time();
        $tokenUri = $this->credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        $jwt = $this->signJwt([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        if (! $jwt) {
            return null;
        }

        try {
            $res = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($res->successful()) {
                return $res->json('access_token');
            }

            Log::warning('FCM token exchange failed', ['status' => $res->status(), 'body' => $res->body()]);
        } catch (\Throwable $e) {
            Log::error('FCM token exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * RS256-sign a service-account JWT with the private key.
     */
    private function signJwt(array $claims): ?string
    {
        $segments = [
            $this->base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url((string) json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            Log::error('FCM JWT signing failed — check the service-account private_key');
            return null;
        }

        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
