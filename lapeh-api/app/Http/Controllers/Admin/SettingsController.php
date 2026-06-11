<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PricingSetting;
use App\Services\BackupService;
use App\Services\FcmService;
use App\Services\Payment\PaymentManager;
use App\Services\SettingsService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * System Settings Hub. Runtime configuration lives in the settings store
 * (see {@see SettingsService}); infrastructure secrets stay in .env and are
 * shown read-only here.
 */
class SettingsController extends Controller
{
    /** Ordered tab list with icons for the sub-navigation. */
    public const TABS = [
        'general', 'pricing', 'commission', 'branding', 'registration', 'mail', 'sms', 'payment',
        'maps', 'fcm', 'otp', 'support', 'database', 'system',
    ];

    public function __construct(private SettingsService $settings) {}

    public function index(string $tab = 'general')
    {
        abort_unless(in_array($tab, self::TABS), 404);

        return view('admin.settings.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            's' => $this->settings,
            'data' => $this->dataForTab($tab),
        ]);
    }

    /** Per-tab view data (read-only/system info, lists, etc.). */
    private function dataForTab(string $tab): array
    {
        return match ($tab) {
            'pricing' => ['pricing' => PricingSetting::current()],
            'payment' => ['activeGateway' => app(PaymentManager::class)->activeName()],
            'support' => ['faq' => $this->settings->get('support.faq', []) ?: []],
            'database' => ['backups' => app(BackupService::class)->list()],
            'system' => $this->systemInfo(),
            default => [],
        };
    }

    /**
     * Persist a settings group. Generic loop drives most fields; branding
     * (file uploads) and support (FAQ array) have dedicated handling.
     */
    public function update(Request $request, string $group)
    {
        $catalog = SettingsService::catalog();
        abort_unless(isset($catalog[$group]), 404);

        if ($group === 'branding') {
            $this->saveBranding($request);
        } elseif ($group === 'support') {
            $this->saveSupport($request);
        } else {
            $this->saveGeneric($request, $group, $catalog[$group]);
        }

        ActivityLog::record('settings.updated', null, [
            'group' => $group,
            'keys' => array_keys($catalog[$group]),
        ]);

        return redirect()
            ->route('admin.settings.tab', $group)
            ->with('success', __('admin.settings_saved'));
    }

    private function saveGeneric(Request $request, string $group, array $keys): void
    {
        $payload = [];
        foreach ($keys as $key => $meta) {
            if ($meta['type'] === 'bool') {
                $payload["$group.$key"] = $request->boolean($key);
            } elseif ($meta['type'] === 'json') {
                continue;
            } elseif ($request->has($key)) {
                $payload["$group.$key"] = $request->input($key);
            }
        }
        $this->settings->setMany($payload);
    }

    private function saveBranding(Request $request): void
    {
        foreach (['logo_url', 'admin_logo_url', 'favicon_url'] as $field) {
            if ($request->hasFile($field)) {
                $request->validate([$field => 'image|mimes:png,jpg,jpeg,svg,webp,ico|max:2048']);
                $path = $request->file($field)->store('branding', 'public');
                $this->settings->set("branding.$field", $path);
            } elseif ($request->filled($field)) {
                // Allow pasting an absolute URL instead of uploading.
                $this->settings->set("branding.$field", $request->input($field));
            }
        }
        $this->settings->set('branding.primary_color', $request->input('primary_color', '#FB0E72'));
    }

    private function saveSupport(Request $request): void
    {
        $this->settings->setMany([
            'support.phone' => $request->input('phone'),
            'support.email' => $request->input('email'),
            'support.whatsapp' => $request->input('whatsapp'),
        ]);

        $faq = [];
        foreach ((array) $request->input('faq', []) as $row) {
            $row = array_map('trim', array_map('strval', $row + ['q_en' => '', 'q_ar' => '', 'a_en' => '', 'a_ar' => '']));
            if ($row['q_en'] === '' && $row['q_ar'] === '') {
                continue; // drop empty rows
            }
            $faq[] = [
                'q_en' => $row['q_en'], 'q_ar' => $row['q_ar'],
                'a_en' => $row['a_en'], 'a_ar' => $row['a_ar'],
            ];
        }
        $this->settings->set('support.faq', $faq);
    }

    // ─── Test actions ────────────────────────────────────────────────────────

    public function testEmail(Request $request)
    {
        $to = Auth::user()->email ?: $request->input('to');
        if (! $to) {
            return back()->with('error', __('admin.test_no_email'));
        }

        try {
            Mail::raw('Lapeh SMTP test — your mail configuration works.', function ($m) use ($to) {
                $m->to($to)->subject('Lapeh SMTP test');
            });
            return back()->with('success', __('admin.test_email_sent', ['email' => $to]));
        } catch (\Throwable $e) {
            return back()->with('error', __('admin.test_failed') . ': ' . $e->getMessage());
        }
    }

    public function testSms(Request $request, SmsService $sms)
    {
        $request->validate(['to' => 'required|string']);
        $result = $sms->test($request->input('to'));

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function testPayment(Request $request, PaymentManager $manager)
    {
        $request->validate(['gateway' => 'required|in:stripe,telr']);
        $result = $manager->driver($request->input('gateway'))->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function testPush(Request $request, FcmService $fcm)
    {
        $token = Auth::user()->fcm_token;
        if (! $fcm->isConfigured()) {
            return back()->with('error', __('admin.fcm_not_configured'));
        }
        if (! $token) {
            return back()->with('error', __('admin.fcm_no_token'));
        }

        $ok = $fcm->sendToToken($token, ['type' => 'test'], 'Lapeh', 'Test push notification');
        return back()->with($ok ? 'success' : 'error', $ok ? __('admin.test_push_sent') : __('admin.test_failed'));
    }

    // ─── Database & maintenance ──────────────────────────────────────────────

    public function backupCreate(BackupService $backup)
    {
        $result = $backup->create();
        ActivityLog::record('settings.backup_created', null, ['file' => $result['file'] ?? null, 'ok' => $result['ok']]);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function backupDownload(string $name, BackupService $backup)
    {
        $path = $backup->path($name);
        abort_unless($path, 404);

        return response()->download($path);
    }

    public function backupDelete(string $name, BackupService $backup)
    {
        $backup->delete($name);
        return back()->with('success', __('admin.backup_deleted'));
    }

    public function clearCache(Request $request)
    {
        $what = $request->input('what', 'cache');
        if ($what === 'config') {
            Artisan::call('config:clear');
            $msg = __('admin.config_cache_cleared');
        } else {
            Artisan::call('cache:clear');
            $this->settings->flush();
            $msg = __('admin.app_cache_cleared');
        }
        return back()->with('success', $msg);
    }

    // ─── System status ───────────────────────────────────────────────────────

    private function systemInfo(): array
    {
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $diskTotal = @disk_total_space(base_path()) ?: 1;

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'broadcast_driver' => config('broadcasting.default'),
            'redis_ok' => $this->redisOk(),
            'disk_free_pct' => round($diskFree / $diskTotal * 100, 1),
            'disk_free_human' => round($diskFree / 1073741824, 1) . ' GB',
            'last_backup' => app(BackupService::class)->lastBackupAt(),
        ];
    }

    private function redisOk(): bool
    {
        try {
            return Redis::connection()->ping() !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
