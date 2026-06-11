<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverEarningsTest extends TestCase
{
    use RefreshDatabase;

    private Sender $sender;

    private function makeDriver(): User
    {
        $senderUser = User::factory()->create(['role' => 'sender']);
        $this->sender = Sender::create(['user_id' => $senderUser->id, 'type' => 'individual', 'status' => 'active']);

        $user = User::factory()->create(['role' => 'driver']);
        Driver::create([
            'user_id' => $user->id,
            'vehicle_type' => 'bike',
            'vehicle_plate' => 'A 12345',
            'status' => 'online',
            'is_verified' => true,
        ]);
        return $user;
    }

    private function delivered(User $user, float $fee, $deliveredAt, float $distance = 4.0): void
    {
        Order::create([
            'order_no' => 'ORD-' . uniqid(),
            'driver_id' => $user->driver->id,
            'sender_id' => $this->sender->id,
            'customer_name' => 'Customer',
            'customer_phone' => '+971500000000',
            'customer_address' => 'Business Bay, Dubai',
            'order_value' => 50,
            'delivery_fee' => $fee,
            'distance_km' => $distance,
            'status' => 'delivered',
            'location_token' => uniqid('tok_'),
            'otp_code' => '1234',
            'delivered_at' => $deliveredAt,
        ]);
    }

    public function test_earnings_returns_full_shape_with_zero_trips(): void
    {
        $user = $this->makeDriver();

        $this->actingAs($user)->getJson('/api/driver/earnings')
            ->assertOk()
            ->assertJsonPath('today.earnings', 0)
            ->assertJsonPath('today.trips', 0)
            ->assertJsonPath('all_time.trips', 0)
            ->assertJsonPath('yesterday_earnings', 0)
            ->assertJsonCount(7, 'daily_breakdown')
            ->assertJsonCount(0, 'history.data');
    }

    public function test_earnings_aggregates_today_and_history(): void
    {
        $user = $this->makeDriver();
        $this->delivered($user, 12.50, now());
        $this->delivered($user, 7.50, now());
        $this->delivered($user, 20.00, now()->subDay()); // yesterday

        $res = $this->actingAs($user)->getJson('/api/driver/earnings')->assertOk();

        $res->assertJsonPath('today.earnings', 20)
            ->assertJsonPath('today.trips', 2)
            ->assertJsonPath('yesterday_earnings', 20)
            ->assertJsonPath('all_time.trips', 3)
            ->assertJsonPath('history.data.0.earning', 12.5)
            ->assertJsonPath('history.data.0.area', 'Business Bay, Dubai');

        // Aggregates are real numbers, not PDO strings.
        $this->assertIsInt($res->json('today.trips'));
        $this->assertIsNumeric($res->json('today.avg_earning'));
    }
}
