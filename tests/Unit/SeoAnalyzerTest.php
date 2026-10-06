<?php

namespace Tests\Unit;

use App\Services\Pillars\SeoAnalyzer;
use App\Services\Scanner\HtmlDocument;
use PHPUnit\Framework\TestCase;

class SeoAnalyzerTest extends TestCase
{
    protected SeoAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new SeoAnalyzer();
    }

    public function test_analyzes_excellent_seo_page(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-excellent.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/acme',
            'http_status' => 200,
            'response_time_ms' => 150,
            'headers' => [],
        ];

        $auxiliary = [
            'robots' => ['status' => 200, 'body' => "User-agent: *\nAllow: /\nSitemap: https://example.com/sitemap.xml"],
            'sitemap' => ['status' => 200, 'body' => '<?xml version="1.0"?><urlset></urlset>'],
        ];

        $result = $this->analyzer->analyze($doc, $fetchData, $auxiliary);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(90, $result['score']);
        
        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertNotContains('seo_missing_title', $ruleIds);
        $this->assertNotContains('seo_missing_meta_description', $ruleIds);
        $this->assertNotContains('seo_missing_canonical', $ruleIds);
        $this->assertNotContains('seo_missing_h1', $ruleIds);
    }

    public function test_analyzes_poor_seo_page(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-poor-seo.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/poor-page',
            'http_status' => 200,
            'response_time_ms' => 300,
            'headers' => [],
        ];

        $auxiliary = [
            'robots' => ['status' => 404, 'body' => ''],
            'sitemap' => ['status' => 404, 'body' => ''],
        ];

        $result = $this->analyzer->analyze($doc, $fetchData, $auxiliary);

        $this->assertIsArray($result);
        $this->assertLessThan(75, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('seo_title_length', $ruleIds);
        $this->assertContains('seo_meta_desc_missing', $ruleIds);
        $this->assertContains('seo_canonical_missing', $ruleIds);
        $this->assertContains('seo_h1_multiple', $ruleIds);
        $this->assertContains('seo_images_missing_alt', $ruleIds);
    }

    public function test_handles_sitemap_403_gracefully(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-excellent.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/',
            'http_status' => 200,
            'headers' => [],
        ];

        // 403 on sitemap should indicate 403 rather than just generic missing
        $auxiliary = [
            'robots' => ['found' => true, 'status' => 200, 'body' => ''],
            'sitemap' => ['found' => false, 'status' => 403, 'body' => 'Forbidden'],
        ];

        $result = $this->analyzer->analyze($doc, $fetchData, $auxiliary);
        $sitemapIssue = collect($result['issues'])->firstWhere('rule_id', 'seo_sitemap_missing');

        $this->assertNotNull($sitemapIssue);
        $this->assertStringContainsString('403 Forbidden', $sitemapIssue['title']);
    }
}
