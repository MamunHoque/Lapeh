<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound SMS via UAE-focused providers only.
 *
 * Supported providers (selected in the admin settings hub, stored in the
 * settings store with .env fallback):
 *   - log      : development — writes to sms_logs, no real send (default)
 *   - unifonic : Unifonic (UAE/MENA) REST API
 *   - infobip  : Infobip (UAE) REST API
 *   - gateway  : generic UAE gateway (Etisalat / Broadnet-style REST)
 *
 * Do NOT add Twilio, Vonage, AWS SNS, MessageBird or other generic
 * international providers — sender IDs must be TRA-approved with the UAE carrier.
 */
class SmsService
{
    public function __construct(private SettingsService $settings) {}

    /** Render a template and send it to a recipient. Always records to sms_logs. */
    public function send(string $to, string $templateKey, array $vars = [], string $locale = 'en'): void
    {
        $template = SmsTemplate::where('key', $templateKey)->first();
        $body = $template
            ? $this->render($locale === 'ar' ? $template->content_ar : $template->content_en, $vars)
            : implode(' ', $vars);

        $this->deliver($to, $body, $templateKey);
    }

    /**
     * Send an ad-hoc test message (admin "Send test SMS"). UAE numbers only.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(string $to, string $body = 'Lapeh test SMS'): array
    {
        if (! preg_match('/^\+971\d{8,9}$/', $to)) {
            return ['ok' => false, 'message' => 'Phone must be a UAE number in +971 format.'];
        }

        $log = $this->deliver($to, $body, 'test');
        return [
            'ok' => $log->status === 'sent',
            'message' => $log->status === 'sent'
                ? "Sent via {$this->provider()} — logged in SMS logs."
                : "Send failed via {$this->provider()}. Check credentials.",
        ];
    }

    public function provider(): string
    {
        return (string) $this->settings->get('sms.provider', 'log');
    }

    /** Route the body to the active provider and record the attempt. */
    protected function deliver(string $to, string $body, string $templateKey): SmsLog
    {
        [$status, $ref] = match ($this->provider()) {
            'unifonic' => $this->sendUnifonic($to, $body),
            'infobip'  => $this->sendInfobip($to, $body),
            'gateway'  => $this->sendGateway($to, $body),
            default    => $this->sendLog($to, $body, $templateKey),
        };

        return SmsLog::create([
            'to' => $to,
            'template_key' => $templateKey,
            'body' => $body,
            'status' => $status,
            'provider_ref' => $ref,
        ]);
    }

    private function sendLog(string $to, string $body, string $templateKey): array
    {
        Log::channel('single')->info("SMS [log:{$templateKey}] to {$to}: {$body}");
        return ['sent', 'log-' . now()->timestamp];
    }

    private function sendUnifonic(string $to, string $body): array
    {
        $appSid = $this->settings->get('sms.unifonic_app_sid');
        $sender = $this->settings->get('sms.unifonic_sender_id', '');
        $base = rtrim((string) $this->settings->get('sms.unifonic_base_url', 'https://el.cloud.unifonic.com'), '/');

        if (! $appSid) {
            return $this->fail('unifonic', 'missing AppSid');
        }

        try {
            $res = Http::asForm()->post("{$base}/rest/SMS/messages", array_filter([
                'AppSid' => $appSid,
                'SenderID' => $sender ?: null,
                'Recipient' => ltrim($to, '+'),
                'Body' => $body,
            ]));

            if ($res->successful() && ($res->json('success') ?? 'true') !== 'false') {
                return ['sent', 'unifonic-' . ($res->json('data.MessageID') ?? now()->timestamp)];
            }
            return $this->fail('unifonic', $res->body());
        } catch (\Throwable $e) {
            return $this->fail('unifonic', $e->getMessage());
        }
    }

    private function sendInfobip(string $to, string $body): array
    {
        $apiKey = $this->settings->get('sms.infobip_api_key');
        $base = rtrim((string) $this->settings->get('sms.infobip_base_url', ''), '/');
        $sender = $this->settings->get('sms.infobip_sender_id', '');

        if (! $apiKey || ! $base) {
            return $this->fail('infobip', 'missing API key or base URL');
        }

        try {
            $res = Http::withHeaders([
                'Authorization' => "App {$apiKey}",
                'Accept' => 'application/json',
            ])->post("{$base}/sms/2/text/advanced", [
                'messages' => [[
                    'from' => $sender ?: 'Lapeh',
                    'destinations' => [['to' => ltrim($to, '+')]],
                    'text' => $body,
                ]],
            ]);

            if ($res->successful()) {
                return ['sent', 'infobip-' . ($res->json('messages.0.messageId') ?? now()->timestamp)];
            }
            return $this->fail('infobip', $res->body());
        } catch (\Throwable $e) {
            return $this->fail('infobip', $e->getMessage());
        }
    }

    private function sendGateway(string $to, string $body): array
    {
        $endpoint = (string) $this->settings->get('sms.gateway_endpoint', '');
        $apiKey = $this->settings->get('sms.gateway_api_key');
        $username = $this->settings->get('sms.gateway_username', '');
        $sender = $this->settings->get('sms.gateway_sender_id', '');

        if (! $endpoint || ! $apiKey) {
            return $this->fail('gateway', 'missing endpoint or API key');
        }

        try {
            // Generic REST contract — adjust field names per the carrier's API doc.
            $res = Http::asForm()->post($endpoint, array_filter([
                'api_key' => $apiKey,
                'username' => $username ?: null,
                'sender' => $sender ?: null,
                'to' => ltrim($to, '+'),
                'message' => $body,
            ]));

            if ($res->successful()) {
                return ['sent', 'gateway-' . now()->timestamp];
            }
            return $this->fail('gateway', $res->body());
        } catch (\Throwable $e) {
            return $this->fail('gateway', $e->getMessage());
        }
    }

    private function fail(string $provider, string $detail): array
    {
        Log::warning("SMS send failed [{$provider}]", ['detail' => $detail]);
        return ['failed', "{$provider}-error"];
    }

    protected function render(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }
}
