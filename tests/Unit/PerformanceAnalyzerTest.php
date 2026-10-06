<?php

namespace Tests\Unit;

use App\Services\Pillars\PerformanceAnalyzer;
use App\Services\Scanner\HtmlDocument;
use PHPUnit\Framework\TestCase;

class PerformanceAnalyzerTest extends TestCase
{
    protected PerformanceAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new PerformanceAnalyzer();
    }

    public function test_flags_slow_ttfb_and_uncompressed_html(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-excellent.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/slow',
            'response_time_ms' => 1500,
            'ttfb_ms' => 1500,
            'body_bytes' => 25000,
            'headers' => [
                // No content-encoding (no gzip or brotli)
            ],
        ];

        $result = $this->analyzer->analyze($doc, $fetchData);

        $this->assertIsArray($result);
        $this->assertLessThan(75, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('perf_slow_response', $ruleIds);
        $this->assertContains('perf_uncompressed_html', $ruleIds);

        // Verify Core Web Vitals passive scan disclaimer is present
        $this->assertArrayHasKey('core_web_vitals_disclaimer', $result['summary']);
        $this->assertStringContainsString('passive', strtolower($result['summary']['core_web_vitals_disclaimer']));
    }

    public function test_passes_when_fast_compressed_and_optimized(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-excellent.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/fast',
            'response_time_ms' => 120,
            'ttfb_ms' => 95,
            'body_bytes' => 4500,
            'headers' => [
                'content-encoding' => 'br',
                'cache-control' => 'public, max-age=3600',
            ],
        ];

        $result = $this->analyzer->analyze($doc, $fetchData);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(90, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertNotContains('perf_slow_response', $ruleIds);
        $this->assertNotContains('perf_uncompressed_html', $ruleIds);
    }
}
