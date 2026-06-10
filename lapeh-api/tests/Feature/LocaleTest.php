<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $user = User::factory()->create(['role' => 'sender']);
        $sender = Sender::create([
            'user_id' => $user->id,
            'type' => 'individual',
            'default_pickup_address' => '1 Beach Road',
            'default_pickup_lat' => 25.2048,
            'default_pickup_lng' => 55.2708,
            'status' => 'active',
        ]);

        return Order::create([
            'order_no' => 'LP-1001',
            'sender_id' => $sender->id,
            'pickup_lat' => 25.2048,
            'pickup_lng' => 55.2708,
            'customer_name' => 'Sara',
            'customer_phone' => '+971500000000',
            'order_value' => 100,
            'status' => 'waiting_for_location',
            'location_token' => 'tok-test-123',
        ]);
    }

    public function test_customer_page_renders_arabic_rtl_with_lang_param(): void
    {
        $order = $this->makeOrder();

        $this->get('/c/'.$order->location_token.'?lang=ar')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('أكّد موقعك');
    }

    public function test_customer_locale_persists_in_session(): void
    {
        $order = $this->makeOrder();

        $this->get('/c/'.$order->location_token.'?lang=ar')->assertOk();
        $this->get('/c/'.$order->location_token)
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_customer_page_defaults_to_english_ltr(): void
    {
        $order = $this->makeOrder();

        $this->get('/c/'.$order->location_token)
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Confirm Your Location');
    }

    public function test_admin_login_page_switches_to_arabic(): void
    {
        $this->get('/admin/login?lang=ar')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('تسجيل الدخول');
    }

    public function test_admin_dashboard_uses_user_locale(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'locale' => 'ar']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('لوحة التحكم');
    }

    public function test_admin_lang_switch_overrides_and_persists(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'locale' => 'ar']);

        $this->actingAs($admin)->get('/admin?lang=en')->assertOk()->assertSee('dir="ltr"', false);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('dir="ltr"', false);
    }
}
