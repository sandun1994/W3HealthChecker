<?php

namespace Tests\Feature;

use App\Http\Controllers\GuideController;
use App\Http\Controllers\ToolController;
use App\Models\Scan;
use App\Models\Website;
use Tests\TestCase;

class GuidesFlowTest extends TestCase
{
    public function test_guides_index_returns_all_educational_guides(): void
    {
        $response = $this->get('/guides');

        $response->assertStatus(200);
        $response->assertSee('Website Health');
        $response->assertSee('Optimization Guides');
        $response->assertSee('guides_index');
    }

    public function test_individual_guide_pages_load_with_rich_structured_data(): void
    {
        $guides = GuideController::getGuidesList();
        $this->assertCount(6, $guides);

        $testSlugs = ['seo', 'performance', 'security', 'accessibility', 'structured-data', 'ai-search'];

        foreach ($testSlugs as $slug) {
            $response = $this->get("/guides/{$slug}");
            $response->assertStatus(200);

            $guide = $guides[$slug];
            $response->assertSee($guide['short_title'] ?? $guide['title']);
            $response->assertSee('TechArticle');
            $response->assertSee('BreadcrumbList');
            $response->assertSee('W3HealthChecker Research Team');
        }
    }

    public function test_unknown_guide_slug_returns_404(): void
    {
        $response = $this->get('/guides/unknown-guide-abc-xyz');
        $response->assertStatus(404);
    }

    public function test_about_methodology_page_loads(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('About W3HealthChecker');
        $response->assertSee('Diagnostic Integrity');
        $response->assertSee('AI readiness');
    }

    public function test_robots_txt_disallows_internal_api_and_links_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $this->assertStringContainsString('User-agent: *', $response->getContent());
        $this->assertStringContainsString('Disallow: /api/', $response->getContent());
        $this->assertStringContainsString('/sitemap.xml', $response->getContent());
    }

    public function test_sitemap_xml_contains_all_19_tools_and_6_guides(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $xmlContent = $response->getContent();

        $tools = ToolController::getToolsList();
        $this->assertCount(19, $tools);

        foreach (array_keys($tools) as $toolSlug) {
            $this->assertStringContainsString("/tools/{$toolSlug}", $xmlContent);
        }

        $guides = GuideController::getGuidesList();
        $this->assertCount(6, $guides);

        foreach (array_keys($guides) as $guideSlug) {
            $this->assertStringContainsString("/guides/{$guideSlug}", $xmlContent);
        }

        $this->assertStringContainsString('/compare', $xmlContent);
        $this->assertStringContainsString('/about', $xmlContent);
    }

    public function test_ephemeral_reports_have_noindex_follow_robots_directive(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'test-seo-report.org'],
            ['scheme' => 'https', 'canonical_url' => 'https://test-seo-report.org/']
        );

        $scan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://test-seo-report.org',
            'public_id' => 'test_noindex_' . uniqid(),
            'overall_score' => 88,
            'status' => 'completed',
        ]);

        $response = $this->get("/report/{$website->domain}/{$scan->public_id}");

        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }
}
