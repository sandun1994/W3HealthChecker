<?php

namespace Tests\Unit;

use App\Services\Pillars\AccessibilityAnalyzer;
use App\Services\Scanner\HtmlDocument;
use PHPUnit\Framework\TestCase;

class AccessibilityAnalyzerTest extends TestCase
{
    protected AccessibilityAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new AccessibilityAnalyzer();
    }

    public function test_flags_accessibility_violations(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-accessibility-problems.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/accessibility-test',
        ];

        $result = $this->analyzer->analyze($doc, $fetchData);

        $this->assertIsArray($result);
        $this->assertLessThan(60, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('a11y_html_lang_missing', $ruleIds);
        $this->assertContains('a11y_form_labels_missing', $ruleIds);
        $this->assertContains('a11y_duplicate_ids', $ruleIds);
        $this->assertContains('a11y_iframe_title_missing', $ruleIds);
        $this->assertContains('a11y_empty_buttons', $ruleIds);
        $this->assertContains('a11y_empty_links', $ruleIds);

        // Check WCAG disclaimer
        $this->assertArrayHasKey('audit_disclaimer', $result['summary']);
        $this->assertStringContainsString('WCAG', $result['summary']['audit_disclaimer']);
    }

    public function test_passes_when_page_has_proper_accessibility_attributes(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-excellent.html');
        $doc = new HtmlDocument($html);

        $fetchData = [
            'final_url' => 'https://example.com/excellent',
        ];

        $result = $this->analyzer->analyze($doc, $fetchData);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(95, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertNotContains('a11y_html_lang_missing', $ruleIds);
        $this->assertNotContains('a11y_form_labels_missing', $ruleIds);
        $this->assertNotContains('a11y_duplicate_ids', $ruleIds);
        $this->assertNotContains('a11y_iframe_title_missing', $ruleIds);
    }
}
