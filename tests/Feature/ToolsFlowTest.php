<?php

namespace Tests\Feature;

use App\Http\Controllers\ToolController;
use Tests\TestCase;

class ToolsFlowTest extends TestCase
{
    public function test_tools_directory_returns_complete_tools_list(): void
    {
        $response = $this->get('/tools');

        $response->assertStatus(200);
        $response->assertSee('Free Website Intelligence Tools');
    }

    public function test_individual_tool_pages_load_with_correct_seo_meta(): void
    {
        $tools = ToolController::getToolsList();
        $this->assertGreaterThanOrEqual(10, count($tools));

        $testSlugs = [
            'seo-checker',
            'security-headers-checker',
            'ssl-checker',
            'accessibility-checker',
            'mobile-readiness-checker',
            'ai-search-readiness-checker',
        ];

        foreach ($testSlugs as $slug) {
            $response = $this->get("/tools/{$slug}");
            $response->assertStatus(200);

            // Must include unique meta tags and tool titles
            $this->assertNotEmpty($tools[$slug]['name']);
            $this->assertNotEmpty($tools[$slug]['meta_description']);
            $response->assertSee($tools[$slug]['short_name']);
        }
    }

    public function test_unknown_tool_slug_returns_404(): void
    {
        $response = $this->get('/tools/non-existent-tool-xyz');
        $response->assertStatus(404);
    }
}
