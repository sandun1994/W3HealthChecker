<?php

namespace Tests\Unit;

use App\Services\Pillars\MobileAnalyzer;
use App\Services\Scanner\HtmlDocument;
use PHPUnit\Framework\TestCase;

class MobileAnalyzerTest extends TestCase
{
    protected MobileAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new MobileAnalyzer();
    }

    public function test_passes_with_responsive_viewport(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-excellent.html');
        $doc = new HtmlDocument($html);

        $result = $this->analyzer->analyze($doc, ['final_url' => 'https://example.com/']);

        $this->assertIsArray($result);
        $this->assertEquals(100, $result['score']);
        $this->assertEmpty($result['issues']);
        $this->assertTrue($result['summary']['mobile_friendly']);
    }

    public function test_flags_problematic_or_restrictive_viewport(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-mobile-problems.html');
        $doc = new HtmlDocument($html);

        $result = $this->analyzer->analyze($doc, ['final_url' => 'https://example.com/mobile-test']);

        $this->assertIsArray($result);
        $this->assertLessThan(80, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('mobile_viewport_non_responsive', $ruleIds);
    }

    public function test_flags_missing_viewport_completely(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-poor-seo.html');
        $doc = new HtmlDocument($html);

        $result = $this->analyzer->analyze($doc, ['final_url' => 'https://example.com/no-viewport']);

        $this->assertLessThan(60, $result['score']);
        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('mobile_viewport_missing', $ruleIds);
    }
}
