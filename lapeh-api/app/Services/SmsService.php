<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $templateKey, array $vars = [], string $locale = 'en'): void
    {
        $template = SmsTemplate::where('key', $templateKey)->first();
        $body = $template
            ? $this->render($locale === 'ar' ? $template->content_ar : $template->content_en, $vars)
            : implode(' ', $vars);

        Log::channel('single')->info("SMS [{$templateKey}] to {$to}: {$body}");

        SmsLog::create([
            'to' => $to,
            'template_key' => $templateKey,
            'body' => $body,
            'status' => 'sent',
            'provider_ref' => 'log-' . now()->timestamp,
        ]);
    }

    protected function render(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }
}
