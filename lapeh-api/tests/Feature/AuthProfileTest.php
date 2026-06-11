<?php

namespace Tests\Feature;

use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthProfileTest extends TestCase
{
    use RefreshDatabase;

    private function sender(): User
    {
        $user = User::factory()->create([
            'role' => 'sender',
            'password' => Hash::make('secret123'),
            'phone_verified_at' => now(),
        ]);
        Sender::create(['user_id' => $user->id, 'type' => 'individual', 'status' => 'active']);
        return $user;
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = $this->sender();

        $this->actingAs($user)->patchJson('/api/auth/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.email', 'updated@example.com');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_avatar_upload_stores_file_and_returns_url(): void
    {
        Storage::fake('public');
        $user = $this->sender();

        $res = $this->actingAs($user)->patchJson('/api/auth/profile', [
            'avatar' => UploadedFile::fake()->create('me.jpg', 80, 'image/jpeg'),
        ])->assertOk();

        $this->assertNotNull($res->json('user.avatar'));
        $this->assertStringContainsString('storage/avatars/', $res->json('user.avatar'));
        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_avatar_upload_via_post_method_spoofing(): void
    {
        // Mirrors the Flutter client: multipart POST + _method=PATCH, since PHP
        // doesn't parse multipart bodies on real PATCH requests.
        Storage::fake('public');
        $user = $this->sender();

        $res = $this->actingAs($user)->post('/api/auth/profile', [
            '_method' => 'PATCH',
            'name' => 'Spoofed Save',
            'avatar' => UploadedFile::fake()->create('me.png', 60, 'image/png'),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame('Spoofed Save', $res->json('user.name'));
        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_change_password_rejects_wrong_current(): void
    {
        $user = $this->sender();

        $this->actingAs($user)->postJson('/api/auth/change-password', [
            'current_password' => 'wrongpass',
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
    }

    public function test_change_password_succeeds_and_new_password_works(): void
    {
        $user = $this->sender();

        $this->actingAs($user)->postJson('/api/auth/change-password', [
            'current_password' => 'secret123',
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
        ])->assertOk();

        $this->assertTrue(Hash::check('newsecret', $user->fresh()->password));

        // Old token-less login proves the new credential is active.
        $this->postJson('/api/auth/login', [
            'phone' => $user->phone,
            'password' => 'newsecret',
        ])->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_change_password_requires_confirmation_and_min_length(): void
    {
        $user = $this->sender();

        $this->actingAs($user)->postJson('/api/auth/change-password', [
            'current_password' => 'secret123',
            'password' => '123',
            'password_confirmation' => '999',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_sender_profile_update_returns_full_user_payload(): void
    {
        $user = $this->sender();

        $this->actingAs($user)->patchJson('/api/sender/profile', [
            'name' => 'Mariam Updated',
            'default_pickup_address' => 'New Pickup, Dubai',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Mariam Updated')
            ->assertJsonPath('user.sender.default_pickup_address', 'New Pickup, Dubai')
            ->assertJsonPath('user.sender.type', 'individual');
    }
}
