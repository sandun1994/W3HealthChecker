<?php

namespace Tests\Unit;

use App\Services\Intelligence\ContentQualityAnalyzer;
use App\Services\Intelligence\ControlledSiteCrawler;
use App\Services\Intelligence\ResourceAnalyzer;
use App\Services\Intelligence\TechnologyDetector;
use App\Services\Scanner\HttpFetchService;
use App\Services\Security\UrlValidationService;
use Mockery;
use Tests\TestCase;

class AdvancedIntelligenceTest extends TestCase
{
    public function test_technology_detector_identifies_stack_signals()
    {
        $detector = new TechnologyDetector();

        $headers = [
            'server' => 'cloudflare',
            'cf-ray' => '84938294719283-ORD',
            'x-powered-by' => 'PHP/8.3.0',
        ];

        $html = '<!DOCTYPE html><html><head>
            <meta name="generator" content="WordPress 6.4">
            <script src="/_next/static/chunks/main.js"></script>
            <script src="https://www.googletagmanager.com/gtag/js?id=G-12345"></script>
        </head><body class="flex flex-col bg-slate-900 text-white">
            <div id="__next">Hello Next.js App</div>
        </body></html>';

        $result = $detector->detect($headers, $html, 'https://example.com');

        $this->assertGreaterThanOrEqual(4, $result['total_detected']);
        $this->assertStringContainsString('Detected signals represent passive heuristic matches', $result['disclaimer']);

        $names = array_column($result['technologies'], 'name');
        $this->assertContains('Cloudflare', $names);
        $this->assertContains('WordPress', $names);
        $this->assertContains('Next.js', $names);
        $this->assertContains('React', $names);
        $this->assertContains('Google Analytics / Tag Manager', $names);
    }

    public function test_resource_analyzer_inventories_page_assets_and_third_parties()
    {
        $analyzer = new ResourceAnalyzer();

        $html = '<!DOCTYPE html><html><head>
            <link rel="stylesheet" href="/assets/app.css">
            <link rel="stylesheet" href="https://cdn.fonts.net/webfont.css">
            <script src="/js/bundle.js" defer></script>
            <script src="https://analytics.thirdparty.com/tag.js" async></script>
        </head><body>
            <img src="/logo.png" alt="Company Logo" loading="lazy">
            <img src="https://images.unsplash.com/photo-1" alt="Header">
        </body></html>';

        $result = $analyzer->analyze($html, 'https://example.com');

        $summary = $result['summary'];
        $this->assertEquals(2, $summary['total_scripts']);
        $this->assertEquals(2, $summary['total_stylesheets']);
        $this->assertEquals(2, $summary['total_images']);
        $this->assertGreaterThanOrEqual(2, $summary['third_party_domains_count']);

        $this->assertContains('analytics.thirdparty.com', $result['third_party_domains']);
        $this->assertContains('images.unsplash.com', $result['third_party_domains']);
    }

    public function test_content_quality_analyzer_evaluates_metrics_and_ai_bots()
    {
        $analyzer = new ContentQualityAnalyzer();

        $html = '<!DOCTYPE html><html><head><title>Deep Technical Guide</title></head><body>
            <header><nav><a href="/">Home</a></nav></header>
            <main>
                <h1>Comprehensive Architecture Guide</h1>
                <p>' . str_repeat('This is an extensive technical explanation covering architecture and web metrics. ', 20) . '</p>
                <h2>Performance Considerations</h2>
                <p>More detailed paragraphs explaining performance benchmarks and optimization strategies.</p>
            </main>
            <footer><p>&copy; 2026 Test Site</p></footer>
        </body></html>';

        $robotsTxt = "User-agent: *\nAllow: /\n\nUser-agent: GPTBot\nDisallow: /\n";

        $result = $analyzer->analyze($html, $robotsTxt);

        // Content metrics
        $this->assertGreaterThan(100, $result['content_metrics']['word_count']);
        $this->assertFalse($result['content_metrics']['thin_content_flag']);
        $this->assertGreaterThan(5, $result['content_metrics']['text_to_html_ratio_percentage']);

        // Hierarchy
        $this->assertEquals(1, $result['document_hierarchy']['h1_count']);
        $this->assertEquals(1, $result['document_hierarchy']['h2_count']);
        $this->assertTrue($result['document_hierarchy']['single_h1_present']);

        // Semantic landmarks
        $this->assertTrue($result['semantic_landmarks']['main_tag']);
        $this->assertTrue($result['semantic_landmarks']['nav_tag']);

        // AI Search Readiness
        $crawlers = $result['ai_search_readiness']['crawlers'];
        $gptBot = collect($crawlers)->firstWhere('bot', 'GPTBot');
        $this->assertNotNull($gptBot);
        $this->assertTrue($gptBot['is_blocked']);

        $claudeBot = collect($crawlers)->firstWhere('bot', 'ClaudeBot');
        $this->assertNotNull($claudeBot);
        $this->assertFalse($claudeBot['is_blocked']);
    }

    public function test_controlled_site_crawler_bounds_crawl_and_extracts_links()
    {
        $urlValidator = Mockery::mock(UrlValidationService::class);
        $fetchService = Mockery::mock(HttpFetchService::class);

        // Mock URL normalization
        $urlValidator->shouldReceive('validateAndNormalize')
            ->with('https://example.com')
            ->andReturn([
                'scheme' => 'https',
                'domain' => 'example.com',
                'normalized_url' => 'https://example.com',
            ]);

        $urlValidator->shouldReceive('validateAndNormalize')
            ->with('https://example.com/about')
            ->andReturn([
                'scheme' => 'https',
                'domain' => 'example.com',
                'normalized_url' => 'https://example.com/about',
            ]);

        // Mock HTTP Fetch
        $fetchService->shouldReceive('fetchPage')
            ->with('https://example.com')
            ->andReturn([
                'http_status' => 200,
                'response_time_ms' => 45,
                'html' => '<html><body><a href="/about">About Us</a><a href="https://external.org">External</a></body></html>',
            ]);

        $fetchService->shouldReceive('fetchPage')
            ->with('https://example.com/about')
            ->andReturn([
                'http_status' => 200,
                'response_time_ms' => 50,
                'html' => '<html><body><a href="/">Home</a></body></html>',
            ]);

        $crawler = new ControlledSiteCrawler($urlValidator, $fetchService);
        $result = $crawler->crawl('https://example.com', 5, 2);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_crawled']);
        $this->assertCount(1, $result['crawl_map']);
        $this->assertEquals('https://example.com', $result['crawl_map'][0]['from']);
        $this->assertEquals('https://example.com/about', $result['crawl_map'][0]['to']);
    }
}
