<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SenderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_sender_can_register_and_receive_dev_otp(): void
    {
        $res = $this->postJson('/api/auth/register-sender', [
            'type' => 'individual',
            'name' => 'Mariam',
            'phone' => '+971500000001',
            'password' => 'secret123',
            'default_pickup_address' => 'Jumeirah, Dubai',
            'default_pickup_lat' => 25.2,
            'default_pickup_lng' => 55.27,
        ])->assertCreated();

        $res->assertJsonPath('user.role', 'sender')
            ->assertJsonPath('user.phone_verified', false);
        $this->assertNotNull($res->json('dev_otp')); // exposed in testing env

        $this->assertDatabaseHas('senders', ['type' => 'individual', 'status' => 'pending']);
    }

    public function test_business_sender_requires_business_name(): void
    {
        $this->postJson('/api/auth/register-sender', [
            'type' => 'business',
            'name' => 'Omar',
            'phone' => '+971500000002',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors('business_name');
    }

    public function test_master_otp_verifies_phone(): void
    {
        $register = $this->postJson('/api/auth/register-sender', [
            'type' => 'individual', 'name' => 'Sara', 'phone' => '+971500000003', 'password' => 'secret123',
        ])->assertCreated();

        $token = $register->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/verify-otp', ['code' => '123456']) // MASTER_OTP
            ->assertOk()
            ->assertJsonPath('user.phone_verified', true);

        $this->assertDatabaseHas('senders', ['status' => 'active']);
    }

    public function test_unverified_sender_cannot_create_order(): void
    {
        $user = User::factory()->create(['role' => 'sender']); // not verified
        Sender::create(['user_id' => $user->id, 'type' => 'individual', 'status' => 'pending']);

        $this->actingAs($user)->postJson('/api/sender/orders', [
            'customer_name' => 'X', 'customer_phone' => '+971500000009',
            'items' => [['name' => 'Box', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(403);
    }

    public function test_verified_sender_creates_order_with_items_and_total(): void
    {
        $user = User::factory()->create(['role' => 'sender', 'phone_verified_at' => now()]);
        Sender::create([
            'user_id' => $user->id, 'type' => 'individual', 'status' => 'active',
            'default_pickup_address' => 'HQ', 'default_pickup_lat' => 25.2, 'default_pickup_lng' => 55.2,
        ]);

        $res = $this->actingAs($user)->postJson('/api/sender/orders', [
            'customer_name' => 'Layla', 'customer_phone' => '+971500000010',
            'items' => [
                ['name' => 'Gift', 'quantity' => 2, 'unit_price' => 50],
                ['name' => 'Card', 'quantity' => 1, 'unit_price' => 15],
            ],
        ])->assertCreated();

        // 2*50 + 1*15 = 115
        $res->assertJsonPath('order.order_value', 115);
        $this->assertCount(2, $res->json('order.items'));
        $this->assertDatabaseCount('order_items', 2);
        // Pickup prefilled from the sender default
        $this->assertSame(25.2, $res->json('order.pickup_lat'));
    }
}
