<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Sender;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    public function test_no_commission_by_default_driver_keeps_full_fee(): void
    {
        $c = app(CommissionService::class)->compute(10.0);

        $this->assertEquals(0, $c['driver_commission']);
        $this->assertEquals(0, $c['sender_commission']);
        $this->assertEquals(10.0, $c['driver_payout']);
        $this->assertEquals(0, $c['platform_revenue']);
    }

    public function test_percentage_driver_and_fixed_sender_commission(): void
    {
        $s = $this->settings();
        $s->setMany([
            'commission.charge_driver' => true,
            'commission.driver_type' => 'percent',
            'commission.driver_rate' => 20,
            'commission.charge_sender' => true,
            'commission.sender_type' => 'fixed',
            'commission.sender_rate' => 3,
        ]);

        $c = app(CommissionService::class)->compute(10.0);

        $this->assertEquals(2.0, $c['driver_commission']);
        $this->assertEquals(8.0, $c['driver_payout']);
        $this->assertEquals(3.0, $c['sender_commission']);
        $this->assertEquals(5.0, $c['platform_revenue']);
    }

    public function test_driver_commission_cannot_exceed_the_fee(): void
    {
        $s = $this->settings();
        $s->setMany([
            'commission.charge_driver' => true,
            'commission.driver_type' => 'fixed',
            'commission.driver_rate' => 999,
        ]);

        $c = app(CommissionService::class)->compute(10.0);

        $this->assertEquals(10.0, $c['driver_commission']);
        $this->assertEquals(0.0, $c['driver_payout']);
    }

    public function test_snapshot_persists_onto_order(): void
    {
        $this->settings()->setMany([
            'commission.charge_driver' => true,
            'commission.driver_type' => 'percent',
            'commission.driver_rate' => 25,
        ]);

        $order = $this->makeDeliveredOrder(deliveryFee: 12.0, snapshot: true);

        $this->assertEquals(3.0, $order->driver_commission);
        $this->assertEquals(9.0, $order->driver_payout);
    }

    public function test_admin_can_save_commission_tab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->put(route('admin.settings.update', 'commission'), [
            'charge_driver' => '1',
            'driver_type' => 'percent',
            'driver_rate' => '15',
            'sender_type' => 'percent',
            'sender_rate' => '0',
        ])->assertRedirect();

        $this->assertTrue((bool) settings('commission.charge_driver'));
        $this->assertEquals(15.0, settings('commission.driver_rate'));
    }

    public function test_driver_earnings_report_returns_net_and_commission(): void
    {
        $this->settings()->setMany([
            'commission.charge_driver' => true,
            'commission.driver_type' => 'percent',
            'commission.driver_rate' => 20,
        ]);

        $order = $this->makeDeliveredOrder(deliveryFee: 10.0, snapshot: true);
        $driverUser = $order->driver->user;

        $res = $this->actingAs($driverUser, 'sanctum')->getJson('/api/driver/earnings')->assertOk();

        // JSON serializes 8.0 as 8 / 2.0 as 2.
        $res->assertJsonPath('all_time.earnings', 8)
            ->assertJsonPath('all_time.commission', 2);
    }

    /** Build a delivered order with a driver + sender, optionally snapshotting commission. */
    private function makeDeliveredOrder(float $deliveryFee, bool $snapshot = false): Order
    {
        $senderUser = User::factory()->create(['role' => 'sender']);
        $sender = Sender::create(['user_id' => $senderUser->id, 'type' => 'individual', 'status' => 'active']);

        $driverUser = User::factory()->create(['role' => 'driver']);
        $driver = Driver::create(['user_id' => $driverUser->id, 'vehicle_type' => 'bike', 'status' => 'online']);

        $order = Order::create([
            'order_no' => 'ORD-' . uniqid(),
            'sender_id' => $sender->id,
            'driver_id' => $driver->id,
            'customer_name' => 'Cust',
            'customer_phone' => '+971500000000',
            'order_value' => 0,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $deliveryFee,
            'status' => 'delivered',
            'delivered_at' => now(),
            'location_token' => uniqid('tok'),
        ]);

        if ($snapshot) {
            app(CommissionService::class)->snapshot($order);
        }

        return $order->fresh();
    }
}
