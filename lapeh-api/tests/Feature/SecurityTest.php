<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_endpoint_returns_rating_tags_and_complaint_types(): void
    {
        $this->getJson('/api/meta')
            ->assertOk()
            ->assertJsonStructure([
                'rating_tags' => ['excellent_service' => ['en', 'ar']],
                'complaint_types' => ['late' => ['en', 'ar']],
            ]);
    }

    public function test_payment_webhook_rejects_invalid_signature(): void
    {
        config(['services.payment.webhook_secret' => 'test-secret']);

        $this->postJson('/api/webhooks/payment', ['reference' => 'abc'], [
            'X-Signature' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_payment_webhook_accepts_valid_signature(): void
    {
        config(['services.payment.webhook_secret' => 'test-secret']);

        $body = json_encode(['reference' => 'abc', 'status' => 'paid']);
        $signature = hash_hmac('sha256', $body, 'test-secret');

        $this->call(
            'POST',
            '/api/webhooks/payment',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $signature],
            $body,
        )->assertOk()->assertJson(['received' => true]);
    }
}
