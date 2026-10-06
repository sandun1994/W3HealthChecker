<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('W3HealthChecker');
        $response->assertSee('id="root"', false);
    }

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<urlset', false);
        $response->assertSee('seo-checker', false);
    }

    public function test_robots_txt_returns_plain_text(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap:');
    }

    public function test_scan_api_requires_url(): void
    {
        $response = $this->postJson('/api/scan', []);
        $response->assertStatus(422);
    }

    public function test_tools_index_page_loads(): void
    {
        $response = $this->get('/tools');
        $response->assertStatus(200);
        $response->assertSee('W3HealthChecker');
    }

    public function test_compare_page_loads(): void
    {
        $response = $this->get('/compare');
        $response->assertStatus(200);
        $response->assertSee('W3HealthChecker');
    }
}
