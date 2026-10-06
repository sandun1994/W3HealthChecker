<?php

namespace Tests\Unit;

use App\Services\Pillars\AiReadinessAnalyzer;
use App\Services\Scanner\HtmlDocument;
use PHPUnit\Framework\TestCase;

class AiReadinessAnalyzerTest extends TestCase
{
    protected AiReadinessAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new AiReadinessAnalyzer();
    }

    public function test_analyzes_structured_data_and_semantics(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-structured-data.html');
        $doc = new HtmlDocument($html);

        $auxiliary = [
            'llms_txt' => ['found' => true],
            'robots' => ['content' => "User-agent: GPTBot\nAllow: /\n"],
        ];

        $result = $this->analyzer->analyze($doc, ['final_url' => 'https://example.com/ai-test'], $auxiliary);

        $this->assertIsArray($result);
        $this->assertTrue($result['summary']['structured_data_present']);
        $this->assertTrue($result['summary']['llms_txt_detected']);
        $this->assertTrue($result['summary']['ai_crawler_directives_detected']);
        $this->assertStringContainsString('does not guarantee ranking', $result['summary']['disclaimer']);
    }

    public function test_flags_missing_structured_data_and_weak_semantics(): void
    {
        $html = file_get_contents(dirname(__DIR__) . '/Fixtures/fixture-poor-seo.html');
        $doc = new HtmlDocument($html);

        $result = $this->analyzer->analyze($doc, ['final_url' => 'https://example.com/no-ai']);

        $this->assertIsArray($result);
        $this->assertFalse($result['summary']['structured_data_present']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('ai_structured_data_missing', $ruleIds);
        $this->assertContains('ai_semantic_structure_weak', $ruleIds);
        $this->assertContains('ai_llms_txt_missing', $ruleIds);
    }
}
