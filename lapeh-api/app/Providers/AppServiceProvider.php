<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applySettingsOverrides();
    }

    /**
     * Let the persistent settings store override .env-derived config at
     * runtime (mail transport, Maps server key). Guarded so it never breaks
     * console bootstrapping before the table/cache exists.
     */
    private function applySettingsOverrides(): void
    {
        try {
            if ($this->app->runningInConsole() && ! \Illuminate\Support\Facades\Schema::hasTable('app_settings')) {
                return;
            }

            /** @var SettingsService $settings */
            $settings = $this->app->make(SettingsService::class);

            if ($settings->has('mail.host') || $settings->get('mail.mailer')) {
                config([
                    'mail.default' => $settings->get('mail.mailer', config('mail.default')),
                    'mail.mailers.smtp.host' => $settings->get('mail.host', config('mail.mailers.smtp.host')),
                    'mail.mailers.smtp.port' => $settings->get('mail.port', config('mail.mailers.smtp.port')),
                    'mail.mailers.smtp.encryption' => $settings->get('mail.encryption', config('mail.mailers.smtp.encryption')),
                    'mail.mailers.smtp.username' => $settings->get('mail.username', config('mail.mailers.smtp.username')),
                    'mail.mailers.smtp.password' => $settings->get('mail.password', config('mail.mailers.smtp.password')),
                    'mail.from.address' => $settings->get('mail.from_address', config('mail.from.address')),
                    'mail.from.name' => $settings->get('mail.from_name', config('mail.from.name')),
                ]);
            }

            if ($settings->has('maps.server_key')) {
                config(['services.google_maps.key' => $settings->get('maps.server_key')]);
            }
        } catch (\Throwable $e) {
            // Settings store not ready (fresh install / migration) — keep .env.
        }
    }
}
