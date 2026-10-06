<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScaleAndObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_reports_subsystem_health()
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('subsystems.database.status', 'connected')
            ->assertJsonPath('subsystems.cache.status', 'operational')
            ->assertJsonStructure([
                'status',
                'timestamp',
                'duration_ms',
                'subsystems' => [
                    'database' => ['status', 'latency_ms'],
                    'cache' => ['status', 'latency_ms'],
                    'queue' => ['status'],
                ],
            ]);
    }

    public function test_observability_middleware_attaches_request_id_and_timing_headers()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Request-Id'));
        $this->assertTrue($response->headers->has('X-Response-Time-Ms'));

        $customId = 'test_req_trace_987654321';
        $tracedResponse = $this->withHeaders([
            'X-Request-Id' => $customId,
        ])->get('/');

        $tracedResponse->assertHeader('X-Request-Id', $customId);
    }
}
