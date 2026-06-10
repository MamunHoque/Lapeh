<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_writes_an_entry_with_actor_and_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        ActivityLog::record('zone.created', $admin, ['name' => 'Downtown']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'zone.created',
            'user_id' => $admin->id,
            'actor_role' => 'admin',
            'subject_type' => 'User',
        ]);
    }

    public function test_record_never_throws_on_bad_input(): void
    {
        // Even with a non-existent table state, record must swallow errors.
        ActivityLog::record('order.created', null, ['x' => fn () => 1]);
        $this->assertTrue(true); // reached here = no exception
    }

    public function test_login_is_logged(): void
    {
        $user = User::factory()->create(['role' => 'restaurant', 'password' => bcrypt('secret123')]);

        $this->postJson('/api/auth/login', ['phone' => $user->phone, 'password' => 'secret123'])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_admin_activity_page_filters_by_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ActivityLog::record('order.created', null, [], $admin);
        ActivityLog::record('order.delivered', null, [], $admin);

        // Filtering to one action narrows the result set to a single event.
        $this->actingAs($admin)
            ->get('/admin/activity-logs?action=order.created')
            ->assertOk()
            ->assertSee('Created order')
            ->assertSee('1 events');

        // No filter shows both.
        $this->actingAs($admin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('2 events');
    }

    public function test_order_creation_records_activity(): void
    {
        $user = User::factory()->create(['role' => 'restaurant']);
        Restaurant::create([
            'user_id' => $user->id, 'name' => 'Kitchen', 'phone' => '+97140000000',
            'area' => 'Jumeirah', 'address' => '1 Rd', 'lat' => 25.2, 'lng' => 55.27,
        ]);

        $this->actingAs($user)->postJson('/api/restaurant/orders', [
            'customer_name' => 'Sara', 'customer_phone' => '+971500000000', 'order_value' => 80,
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', ['action' => 'order.created', 'actor_role' => 'restaurant']);
        $this->assertSame(1, Order::count());
    }
}
