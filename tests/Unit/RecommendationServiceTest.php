<?php

namespace Tests\Unit;

use App\Services\Scoring\RecommendationService;
use PHPUnit\Framework\TestCase;

class RecommendationServiceTest extends TestCase
{
    protected RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecommendationService();
    }

    public function test_prioritizes_issues_by_multi_factor_formula(): void
    {
        $rawIssues = [
            [
                'rule_id' => 'perf_slow_response',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => 'Slow Server TTFB Latency',
                'why_it_matters' => 'Server response latency delays rendering.',
                'recommendation' => 'Deploy Redis cache or CDN.',
            ],
            [
                'rule_id' => 'sec_https_missing',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'Insecure HTTP (No SSL)',
                'why_it_matters' => 'Insecure connections leak data.',
                'recommendation' => 'Enable SSL certificate.',
            ],
            [
                'rule_id' => 'seo_title_missing',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'Missing HTML Title Tag',
                'why_it_matters' => 'Missing titles damage organic search click-through rates.',
                'recommendation' => 'Add <title> tag.',
            ],
            [
                'rule_id' => 'ai_llms_txt_missing',
                'severity' => 'info',
                'confidence' => 'high',
                'title' => 'No llms.txt Found',
                'why_it_matters' => 'Emerging AI convention.',
                'recommendation' => 'Consider publishing llms.txt.',
            ],
        ];

        $ranked = $this->service->prioritizeIssues($rawIssues);

        $this->assertCount(4, $ranked);

        // Every issue must have impact, effort, priority_score, priority_order
        foreach ($ranked as $issue) {
            $this->assertArrayHasKey('impact', $issue);
            $this->assertArrayHasKey('effort', $issue);
            $this->assertArrayHasKey('priority_score', $issue);
            $this->assertArrayHasKey('priority_order', $issue);
        }

        // Priority scores must be in descending order
        for ($i = 0; $i < count($ranked) - 1; $i++) {
            $this->assertGreaterThanOrEqual($ranked[$i + 1]['priority_score'], $ranked[$i]['priority_score']);
        }

        // The top issue should be critical quick-fix (like missing title or insecure https)
        $this->assertEquals('critical', $ranked[0]['severity']);
        // The lowest issue should be the info issue
        $this->assertEquals('info', $ranked[3]['severity']);
    }

    public function test_get_fix_these_first_returns_top_n_items(): void
    {
        $rawIssues = array_map(function ($i) {
            return [
                'rule_id' => "rule_{$i}",
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "Issue Title {$i}",
                'why_it_matters' => "Why {$i}",
                'recommendation' => "Fix {$i}",
            ];
        }, range(1, 10));

        $ranked = $this->service->prioritizeIssues($rawIssues);
        $top5 = $this->service->getFixTheseFirst($ranked, 5);

        $this->assertCount(5, $top5);
        $this->assertEquals(1, $top5[0]['priority_order']);
        $this->assertEquals(5, $top5[4]['priority_order']);
    }
}
