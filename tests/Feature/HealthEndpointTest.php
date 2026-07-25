<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_is_available_without_internal_details(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertDontSee('DB_PASSWORD');
    }
}
