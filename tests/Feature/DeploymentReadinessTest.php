<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_reports_production_readiness(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'duration_ms',
            'subsystems' => [
                'database',
                'cache',
                'queue',
            ],
            'environment',
        ]);
        $this->assertEquals('healthy', $response->json('status'));
        $this->assertEquals('connected', $response->json('subsystems.database.status'));
    }

    public function test_critical_pages_deliver_defensive_security_headers(): void
    {
        $pages = ['/', '/tools', '/compare', '/about', '/monitoring', '/billing', '/developers'];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        }
    }

    public function test_monitoring_command_is_registered_and_executable(): void
    {
        $exitCode = Artisan::call('w3:monitoring:run', ['--force' => true]);
        $this->assertEquals(0, $exitCode);
    }

    public function test_openapi_specification_is_served_properly(): void
    {
        $path = public_path('openapi.json');
        $this->assertFileExists($path);

        $content = json_decode(file_get_contents($path), true);
        $this->assertIsArray($content);
        $this->assertEquals('3.0.3', $content['openapi']);
    }

    public function test_forwarded_cloudflare_proto_and_ip_are_handled(): void
    {
        $response = $this->withHeaders([
            'CF-Connecting-IP' => '203.0.113.195',
            'X-Forwarded-Proto' => 'https',
        ])->get('/');

        $response->assertStatus(200);
    }

    public function test_monetag_service_worker_is_served(): void
    {
        $response = $this->get('/sw.js');
        $response->assertStatus(200);
        $this->assertStringContainsString('application/javascript', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('3nbf4.com', $response->getContent());
        $this->assertStringContainsString('12071690', $response->getContent());
    }

    public function test_monetization_scripts_are_embedded_in_head(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertStringContainsString('https://abscloud.org/1/8d7eba83bb4fe44748e1c032505510d1', $response->getContent());
        $this->assertStringContainsString('3nbf4.com', $response->getContent());
        $this->assertStringContainsString('12071690', $response->getContent());
    }
}
