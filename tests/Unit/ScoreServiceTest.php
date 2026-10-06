<?php

namespace Tests\Unit;

use App\Services\Scoring\ScoreService;
use PHPUnit\Framework\TestCase;

class ScoreServiceTest extends TestCase
{
    protected ScoreService $scoreService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoreService = new ScoreService();
    }

    public function test_category_weights_sum_to_one_hundred_percent(): void
    {
        $weights = $this->scoreService->getCategoryWeights();
        $this->assertNotEmpty($weights);
        $totalWeight = array_sum($weights);
        $this->assertEqualsWithDelta(1.0, $totalWeight, 0.0001, 'Category weights must sum to exactly 1.0 (100%)');
    }

    public function test_calculates_weighted_composite_score(): void
    {
        // Exact manual calculation:
        // seo (20%): 100 * 0.20 = 20
        // security (20%): 100 * 0.20 = 20
        // performance (15%): 80 * 0.15 = 12
        // accessibility (15%): 80 * 0.15 = 12
        // mobile (10%): 90 * 0.10 = 9
        // technical (10%): 90 * 0.10 = 9
        // ai_readiness (10%): 70 * 0.10 = 7
        // Total expected = 20 + 20 + 12 + 12 + 9 + 9 + 7 = 89
        $scores = [
            'seo' => 100,
            'security' => 100,
            'performance' => 80,
            'accessibility' => 80,
            'mobile' => 90,
            'technical' => 90,
            'ai_readiness' => 70,
        ];

        $overall = $this->scoreService->calculateOverallScore($scores);
        $this->assertEquals(89, $overall);
    }

    public function test_clamps_boundary_scores_between_zero_and_one_hundred(): void
    {
        $tooHigh = [
            'seo' => 150,
            'security' => 120,
            'performance' => 110,
            'accessibility' => 105,
            'mobile' => 100,
            'technical' => 100,
            'ai_readiness' => 100,
        ];
        $this->assertLessThanOrEqual(100, $this->scoreService->calculateOverallScore($tooHigh));

        $negative = [
            'seo' => -50,
            'security' => -20,
            'performance' => -10,
            'accessibility' => 0,
            'mobile' => 0,
            'technical' => 0,
            'ai_readiness' => 0,
        ];
        $this->assertGreaterThanOrEqual(0, $this->scoreService->calculateOverallScore($negative));
    }

    public function test_assigns_correct_status_labels(): void
    {
        $this->assertEquals('Excellent', $this->scoreService->getStatusLabel(95));
        $this->assertEquals('Good', $this->scoreService->getStatusLabel(85));
        $this->assertEquals('Needs Improvement', $this->scoreService->getStatusLabel(75));
        $this->assertEquals('Poor', $this->scoreService->getStatusLabel(55));
        $this->assertEquals('Critical', $this->scoreService->getStatusLabel(35));
    }
}
