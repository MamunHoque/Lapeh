<?php

namespace Tests\Feature;

use App\Models\LapehNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithNotifications(int $unread = 2): User
    {
        $user = User::factory()->create(['role' => 'sender']);
        LapehNotification::create(['user_id' => $user->id, 'title' => 'Read one', 'body' => 'x', 'read_at' => now()]);
        for ($i = 0; $i < $unread; $i++) {
            LapehNotification::create(['user_id' => $user->id, 'title' => "Unread $i", 'body' => 'x']);
        }
        return $user;
    }

    public function test_index_lists_paginated_notifications(): void
    {
        $user = $this->userWithNotifications();

        $res = $this->actingAs($user)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(3, 'notifications.data');

        $unread = collect($res->json('notifications.data'))->where('read', false)->count();
        $this->assertSame(2, $unread);
    }

    public function test_unread_count(): void
    {
        $user = $this->userWithNotifications(2);

        $this->actingAs($user)->getJson('/api/notifications/unread-count')
            ->assertOk()->assertJsonPath('count', 2);
    }

    public function test_mark_one_read(): void
    {
        $user = $this->userWithNotifications(1);
        $unread = LapehNotification::where('user_id', $user->id)->whereNull('read_at')->first();

        $this->actingAs($user)->patchJson("/api/notifications/{$unread->id}/read")
            ->assertOk()->assertJsonPath('notification.read', true);

        $this->assertNotNull($unread->fresh()->read_at);
    }

    public function test_mark_all_read(): void
    {
        $user = $this->userWithNotifications(3);

        $this->actingAs($user)->postJson('/api/notifications/read-all')->assertOk();

        $this->assertSame(0, LapehNotification::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_cannot_read_another_users_notification(): void
    {
        $owner = $this->userWithNotifications(1);
        $other = User::factory()->create(['role' => 'sender']);
        $n = LapehNotification::where('user_id', $owner->id)->first();

        $this->actingAs($other)->patchJson("/api/notifications/{$n->id}/read")->assertStatus(403);
    }
}
