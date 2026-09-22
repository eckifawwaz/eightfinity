<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Feature/unit tests must never call the real Midtrans API just because
        // a developer's local .env contains sandbox/production credentials.
        // Individual tests can still opt in by overriding these config values.
        config([
            'services.midtrans.server_key' => null,
            'services.midtrans.client_key' => null,
            'services.midtrans.is_production' => false,
        ]);
    }
}
