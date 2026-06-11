<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Persistent, cached application configuration store.
 *
 * Settings live in the `app_settings` table and take precedence over .env,
 * which remains the bootstrap/fallback. Reads are served from a single cached
 * map; secrets are stored encrypted and never returned to views in the clear.
 *
 * Keys are dotted "group.key" (e.g. "mail.host", "payment.stripe_secret_key").
 */
class SettingsService
{
    private const CACHE_KEY = 'app_settings.map';

    /**
     * The full catalog of known settings: type, whether the value is a secret
     * (encrypted + masked in the UI), and the .env/config-derived default used
     * to seed the store on first migrate. Drives seeding, casting and the UI.
     *
     * Secrets are NOT seeded — they stay null and fall back to .env until an
     * admin enters a value in the settings hub.
     */
    public static function catalog(): array
    {
        return [
            'general' => [
                'app_name'            => ['type' => 'string', 'secret' => false, 'default' => fn () => config('app.name', 'Lapeh')],
                'tagline'             => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'timezone'            => ['type' => 'string', 'secret' => false, 'default' => fn () => config('app.timezone', 'UTC')],
                'locale'              => ['type' => 'string', 'secret' => false, 'default' => fn () => config('app.locale', 'en')],
                'currency'            => ['type' => 'string', 'secret' => false, 'default' => fn () => 'AED'],
                'maintenance_mode'    => ['type' => 'bool',   'secret' => false, 'default' => fn () => false],
                'maintenance_message' => ['type' => 'text',   'secret' => false, 'default' => fn () => ''],
            ],
            'branding' => [
                'logo_url'       => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'admin_logo_url' => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'favicon_url'    => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'primary_color'  => ['type' => 'string', 'secret' => false, 'default' => fn () => '#FB0E72'],
            ],
            'registration' => [
                'sender_enabled'           => ['type' => 'bool', 'secret' => false, 'default' => fn () => true],
                'driver_enabled'           => ['type' => 'bool', 'secret' => false, 'default' => fn () => true],
                'require_otp'              => ['type' => 'bool', 'secret' => false, 'default' => fn () => true],
                'sender_requires_approval' => ['type' => 'bool', 'secret' => false, 'default' => fn () => false],
                'driver_requires_approval' => ['type' => 'bool', 'secret' => false, 'default' => fn () => false],
            ],
            'mail' => [
                'mailer'       => ['type' => 'string', 'secret' => false, 'default' => fn () => config('mail.default', 'log')],
                'host'         => ['type' => 'string', 'secret' => false, 'default' => fn () => config('mail.mailers.smtp.host', '')],
                'port'         => ['type' => 'int',    'secret' => false, 'default' => fn () => (int) config('mail.mailers.smtp.port', 587)],
                'encryption'   => ['type' => 'string', 'secret' => false, 'default' => fn () => config('mail.mailers.smtp.encryption', 'tls')],
                'username'     => ['type' => 'string', 'secret' => false, 'default' => fn () => config('mail.mailers.smtp.username', '')],
                'password'     => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'from_address' => ['type' => 'string', 'secret' => false, 'default' => fn () => config('mail.from.address', '')],
                'from_name'    => ['type' => 'string', 'secret' => false, 'default' => fn () => config('mail.from.name', 'Lapeh')],
            ],
            'sms' => [
                'provider'           => ['type' => 'string', 'secret' => false, 'default' => fn () => 'log'],
                'unifonic_app_sid'   => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'unifonic_sender_id' => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'unifonic_base_url'  => ['type' => 'string', 'secret' => false, 'default' => fn () => 'https://el.cloud.unifonic.com'],
                'infobip_api_key'    => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'infobip_base_url'   => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'infobip_sender_id'  => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'gateway_api_key'    => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'gateway_username'   => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'gateway_sender_id'  => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'gateway_endpoint'   => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
            ],
            'payment' => [
                'gateway'                => ['type' => 'string', 'secret' => false, 'default' => fn () => config('services.payment.gateway', 'stripe')],
                'currency'               => ['type' => 'string', 'secret' => false, 'default' => fn () => 'AED'],
                'stripe_mode'            => ['type' => 'string', 'secret' => false, 'default' => fn () => 'test'],
                'stripe_publishable_key' => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'stripe_secret_key'      => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'stripe_webhook_secret'  => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'telr_mode'              => ['type' => 'string', 'secret' => false, 'default' => fn () => 'test'],
                'telr_store_id'          => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'telr_auth_key'          => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'telr_api_secret'        => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
            ],
            'maps' => [
                'server_key'      => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
                'client_key'      => ['type' => 'string', 'secret' => false, 'default' => fn () => ''],
                'default_lat'     => ['type' => 'float',  'secret' => false, 'default' => fn () => 25.2048],
                'default_lng'     => ['type' => 'float',  'secret' => false, 'default' => fn () => 55.2708],
                'default_country' => ['type' => 'string', 'secret' => false, 'default' => fn () => 'AE'],
            ],
            'fcm' => [
                'project_id'       => ['type' => 'string', 'secret' => false, 'default' => fn () => config('services.fcm.project_id', '')],
                'credentials_path' => ['type' => 'string', 'secret' => false, 'default' => fn () => config('services.fcm.credentials', '')],
            ],
            'otp' => [
                'length'      => ['type' => 'int',    'secret' => false, 'default' => fn () => (int) config('lapeh.otp.length', 6)],
                'ttl_minutes' => ['type' => 'int',    'secret' => false, 'default' => fn () => (int) config('lapeh.otp.ttl_minutes', 10)],
                'master'      => ['type' => 'string', 'secret' => true,  'default' => fn () => null],
            ],
            'commission' => [
                'charge_sender' => ['type' => 'bool',   'secret' => false, 'default' => fn () => false],
                'sender_type'   => ['type' => 'string', 'secret' => false, 'default' => fn () => 'percent'], // percent|fixed
                'sender_rate'   => ['type' => 'float',  'secret' => false, 'default' => fn () => 0],
                'charge_driver' => ['type' => 'bool',   'secret' => false, 'default' => fn () => false],
                'driver_type'   => ['type' => 'string', 'secret' => false, 'default' => fn () => 'percent'],
                'driver_rate'   => ['type' => 'float',  'secret' => false, 'default' => fn () => 0],
            ],
            'support' => [
                'phone'    => ['type' => 'string', 'secret' => false, 'default' => fn () => config('lapeh.support.phone', '')],
                'email'    => ['type' => 'string', 'secret' => false, 'default' => fn () => config('lapeh.support.email', '')],
                'whatsapp' => ['type' => 'string', 'secret' => false, 'default' => fn () => config('lapeh.support.whatsapp', '')],
                'faq'      => ['type' => 'json',   'secret' => false, 'default' => fn () => config('lapeh.support.faq', [])],
            ],
        ];
    }

    /** Metadata (type, secret) for a dotted key, or null if unknown. */
    public static function meta(string $key): ?array
    {
        [$group, $name] = array_pad(explode('.', $key, 2), 2, null);
        return self::catalog()[$group][$name] ?? null;
    }

    /** True if the key holds an encrypted secret. */
    public static function isSecret(string $key): bool
    {
        return (bool) (self::meta($key)['secret'] ?? false);
    }

    /**
     * Read a setting. Returns the stored (decrypted, cast) value when present,
     * otherwise $default — which call sites set to the relevant config()/env
     * value so .env keeps working as a fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->map()[$key] ?? null;
        if ($row === null || $row['value'] === null) {
            return $default;
        }

        $value = $row['value'];
        if ($row['is_encrypted']) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Throwable $e) {
                Log::warning('SettingsService: failed to decrypt', ['key' => $key]);
                return $default;
            }
        }

        return $this->cast($value, $row['type']);
    }

    /** All settings in a group as [key => castedValue] (secrets decrypted). */
    public function group(string $group): array
    {
        $out = [];
        foreach (array_keys(self::catalog()[$group] ?? []) as $key) {
            $out[$key] = $this->get("$group.$key");
        }
        return $out;
    }

    /** True when a value is actually stored (used for "configured" badges). */
    public function has(string $key): bool
    {
        $row = $this->map()[$key] ?? null;
        return $row !== null && $row['value'] !== null && $row['value'] !== '';
    }

    /**
     * Upsert a setting. Secrets are encrypted at rest; json values encoded.
     * A null/empty secret value is treated as "leave unchanged" so admins can
     * re-save a form without re-typing every password.
     */
    public function set(string $key, mixed $value): void
    {
        [$group, $name] = explode('.', $key, 2);
        $meta = self::meta($key) ?? ['type' => 'string', 'secret' => false];
        $type = $meta['type'];
        $secret = (bool) $meta['secret'];

        if ($secret && ($value === null || $value === '')) {
            return; // don't overwrite an existing secret with a blank submit
        }

        if ($value === null) {
            $stored = null;
        } elseif ($type === 'json') {
            $stored = is_string($value) ? $value : json_encode($value);
        } elseif ($type === 'bool') {
            $stored = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        } else {
            $stored = (string) $value;
        }

        if ($secret && $stored !== null) {
            $stored = Crypt::encryptString($stored);
        }

        AppSetting::updateOrCreate(
            ['group' => $group, 'key' => $name],
            ['value' => $stored, 'type' => $type, 'is_encrypted' => $secret],
        );

        $this->flush();
    }

    /** Persist a batch of dotted-key => value pairs. */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Safe, client-facing configuration for the public meta endpoint. Never
     * includes secrets — client_key is a domain/bundle-restricted Maps key.
     */
    public function publicConfig(): array
    {
        return [
            'app_name'      => $this->get('general.app_name', config('app.name', 'Lapeh')),
            'tagline'       => $this->get('general.tagline', ''),
            'logo_url'      => $this->absoluteUrl($this->get('branding.logo_url', '')),
            'primary_color' => $this->get('branding.primary_color', '#FB0E72'),
            'currency'      => $this->get('general.currency', 'AED'),
            'locale'        => $this->get('general.locale', 'en'),
            'maps_key'      => $this->get('maps.client_key', ''),
            'map_center'    => [
                'lat' => $this->get('maps.default_lat', 25.2048),
                'lng' => $this->get('maps.default_lng', 55.2708),
            ],
            'registration' => [
                'sender' => (bool) $this->get('registration.sender_enabled', true),
                'driver' => (bool) $this->get('registration.driver_enabled', true),
            ],
            'maintenance' => [
                'enabled' => (bool) $this->get('general.maintenance_mode', false),
                'message' => $this->get('general.maintenance_message', ''),
            ],
            'support' => [
                'phone'    => $this->get('support.phone', config('lapeh.support.phone')),
                'email'    => $this->get('support.email', config('lapeh.support.email')),
                'whatsapp' => $this->get('support.whatsapp', config('lapeh.support.whatsapp')),
                'faq'      => $this->get('support.faq', config('lapeh.support.faq', [])),
            ],
        ];
    }

    /** Resolve a stored logo path/URL to an absolute, client-loadable URL. */
    private function absoluteUrl(string $value): string
    {
        if ($value === '' || str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return URL::to(Storage::url($value));
    }

    /**
     * Seed non-secret defaults from the current .env/config. Idempotent:
     * existing rows are never overwritten. Called from the migration.
     */
    public static function seedDefaults(): void
    {
        foreach (self::catalog() as $group => $keys) {
            foreach ($keys as $key => $def) {
                if ($def['secret']) {
                    continue; // secrets stay null → .env fallback
                }
                $exists = AppSetting::where('group', $group)->where('key', $key)->exists();
                if ($exists) {
                    continue;
                }

                $value = ($def['default'])();
                $stored = match ($def['type']) {
                    'json' => json_encode($value),
                    'bool' => $value ? '1' : '0',
                    default => $value === null ? null : (string) $value,
                };

                AppSetting::create([
                    'group' => $group,
                    'key' => $key,
                    'value' => $stored,
                    'type' => $def['type'],
                    'is_encrypted' => false,
                ]);
            }
        }
    }

    private function map(): array
    {
        // Resilient: before the table/migration exists (fresh install, unit
        // tests without a DB) fall back to an empty map so callers get their
        // .env/config defaults instead of an exception.
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return AppSetting::all()
                    ->keyBy(fn ($r) => "{$r->group}.{$r->key}")
                    ->map(fn ($r) => [
                        'value' => $r->value,
                        'type' => $r->type,
                        'is_encrypted' => (bool) $r->is_encrypted,
                    ])
                    ->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
