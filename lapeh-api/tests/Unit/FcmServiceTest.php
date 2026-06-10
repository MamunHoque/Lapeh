<?php

namespace Tests\Unit;

use App\Services\FcmService;
use Tests\TestCase;

class FcmServiceTest extends TestCase
{
    public function test_send_returns_false_when_no_credentials_configured(): void
    {
        config(['services.fcm.credentials' => null]);

        $service = new FcmService();

        // No credentials → graceful no-op, never throws.
        $this->assertFalse($service->sendToToken('some-token', ['type' => 'ping']));
    }

    public function test_send_returns_false_for_missing_credentials_file(): void
    {
        config(['services.fcm.credentials' => '/tmp/does-not-exist-'.uniqid().'.json']);

        $service = new FcmService();

        $this->assertFalse($service->sendToToken('some-token', ['type' => 'ping']));
    }
}
