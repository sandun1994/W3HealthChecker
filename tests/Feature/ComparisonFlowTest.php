<?php

namespace Tests\Feature;

use Tests\TestCase;

class ComparisonFlowTest extends TestCase
{
    public function test_compare_endpoint_requires_both_urls(): void
    {
        $response = $this->postJson('/api/compare', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['url_a', 'url_b']);
    }

    public function test_compare_endpoint_rejects_ssrf_urls(): void
    {
        $response = $this->postJson('/api/compare', [
            'url_a' => 'http://127.0.0.1:8000',
            'url_b' => 'https://example.com',
        ]);
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_compare_view_renders_correctly(): void
    {
        $response = $this->get('/compare');
        $response->assertStatus(200);
        $response->assertSee('Website Comparison Tool');
    }

    public function test_report_page_handles_unknown_public_id_gracefully(): void
    {
        $response = $this->get('/report/example.com/w3_unknown_report_id');
        $response->assertStatus(200);
        $response->assertSee('Website Health Report');
    }
}
