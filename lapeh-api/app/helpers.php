<?php

use App\Services\SettingsService;

if (! function_exists('settings')) {
    /**
     * Resolve the settings store, or read a single key with a fallback.
     *
     *   settings()->group('mail')      // service instance
     *   settings('general.app_name', 'Lapeh')  // single value
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingsService::class);
        return $key === null ? $service : $service->get($key, $default);
    }
}
