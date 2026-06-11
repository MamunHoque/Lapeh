<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SettingsController;
use App\Models\AppSetting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\BackupService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@lapeh.ae']);
    }

    public function test_defaults_are_seeded_on_migrate(): void
    {
        $this->assertGreaterThan(20, AppSetting::count());
        $this->assertEquals('Lapeh', settings('general.app_name'));
        $this->assertTrue((bool) settings('registration.sender_enabled'));
    }

    public function test_every_settings_tab_renders_for_admin(): void
    {
        $this->actingAs($this->admin());

        foreach (SettingsController::TABS as $tab) {
            $this->get(route('admin.settings.tab', $tab))
                ->assertOk()
                ->assertSee('settings-hub', false);
        }
    }

    public function test_non_admin_cannot_open_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sender']));
        // admin.role middleware redirects non-admins away from the portal.
        $this->get(route('admin.settings'))->assertRedirect();
    }

    public function test_admin_can_change_app_name(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.settings.update', 'general'), [
            'app_name' => 'Speedy Dispatch',
            'tagline' => 'Fast',
            'locale' => 'en',
            'currency' => 'AED',
            'timezone' => 'Asia/Dubai',
            'maintenance_message' => '',
        ])->assertRedirect();

        $this->assertEquals('Speedy Dispatch', settings('general.app_name'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'settings.updated']);
    }

    public function test_secrets_are_encrypted_and_never_rendered(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.settings.update', 'payment'), [
            'gateway' => 'stripe',
            'currency' => 'AED',
            'stripe_publishable_key' => 'pk_test_123',
            'stripe_secret_key' => 'sk_test_SUPERSECRET',
        ])->assertRedirect();

        // Stored encrypted (not plaintext) and readable through the service.
        $row = AppSetting::where('key', 'stripe_secret_key')->first();
        $this->assertTrue($row->is_encrypted);
        $this->assertStringNotContainsString('sk_test_SUPERSECRET', $row->value);
        $this->assertEquals('sk_test_SUPERSECRET', settings('payment.stripe_secret_key'));

        // Never echoed back into the HTML form.
        $this->get(route('admin.settings.tab', 'payment'))
            ->assertOk()
            ->assertDontSee('sk_test_SUPERSECRET');
    }

    public function test_blank_secret_submit_keeps_existing_value(): void
    {
        app(SettingsService::class)->set('payment.stripe_secret_key', 'sk_test_KEEP');
        $this->actingAs($this->admin());

        $this->put(route('admin.settings.update', 'payment'), [
            'gateway' => 'stripe',
            'currency' => 'AED',
            'stripe_secret_key' => '', // blank → keep
        ])->assertRedirect();

        $this->assertEquals('sk_test_KEEP', settings('payment.stripe_secret_key'));
    }

    public function test_disabling_sender_registration_blocks_the_api(): void
    {
        $this->actingAs($this->admin());

        // Submit registration form with sender_enabled unchecked (absent).
        $this->put(route('admin.settings.update', 'registration'), [
            'driver_enabled' => '1',
            'require_otp' => '1',
        ])->assertRedirect();

        $this->assertFalse((bool) settings('registration.sender_enabled'));

        $this->postJson('/api/auth/register-sender', [
            'type' => 'individual',
            'name' => 'Blocked User',
            'phone' => '+971500000001',
            'password' => 'secret123',
        ])->assertStatus(403);
    }

    public function test_payment_gateway_can_be_switched(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('admin.settings.update', 'payment'), [
            'gateway' => 'telr',
            'currency' => 'AED',
        ])->assertRedirect();

        $this->assertEquals('telr', settings('payment.gateway'));
    }

    public function test_sms_test_logs_an_entry_for_uae_number(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.settings.test.sms'), ['to' => '+971501234567'])
            ->assertRedirect();

        $this->assertDatabaseHas('sms_logs', ['to' => '+971501234567', 'template_key' => 'test']);
    }

    public function test_sms_test_rejects_non_uae_number(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.settings.test.sms'), ['to' => '+14155550000'])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('sms_logs', ['to' => '+14155550000']);
    }

    public function test_test_email_sends(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());

        $this->post(route('admin.settings.test.email'))->assertSessionHas('success');
    }

    public function test_meta_endpoint_exposes_public_app_config(): void
    {
        app(SettingsService::class)->set('branding.primary_color', '#123456');

        $this->getJson('/api/meta')
            ->assertOk()
            ->assertJsonPath('app_config.primary_color', '#123456')
            ->assertJsonPath('app_config.registration.sender', true)
            ->assertJsonStructure(['app_config' => ['app_name', 'maps_key', 'support' => ['faq']]]);

        $this->getJson('/api/meta/app-config')
            ->assertOk()
            ->assertJsonPath('app_name', settings('general.app_name'));
    }

    public function test_backup_service_creates_a_file(): void
    {
        // Point the sqlite connection at a real file so it can be copied.
        $dbFile = storage_path('app/test-backup-source.sqlite');
        touch($dbFile);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $dbFile]);

        $backup = app(BackupService::class);
        $result = $backup->create();

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertNotEmpty($backup->list());
        $this->assertNotNull($backup->path($result['file']));

        // Cleanup
        @unlink($dbFile);
        $backup->delete($result['file']);
    }
}
