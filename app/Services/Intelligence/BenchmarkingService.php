<?php

namespace App\Services\Intelligence;

use App\Models\Scan;
use App\Models\Website;
use Illuminate\Support\Collection;

class BenchmarkingService
{
    public const MINIMUM_COHORT_SIZE = 10;

    /**
     * Compute privacy-preserving cohort benchmarks for a target website.
     * Returns explicit non-availability notice if cohort size < MINIMUM_COHORT_SIZE.
     */
    public function getBenchmark(Website $website): array
    {
        // Sample latest completed scan from all distinct public websites
        $cohortScans = Scan::query()
            ->where('status', 'completed')
            ->whereNotNull('overall_score')
            ->select('website_id', 'overall_score', 'score_seo', 'score_security', 'score_performance', 'score_accessibility', 'score_mobile', 'score_technical', 'score_ai_readiness')
            ->latest('id')
            ->get()
            ->unique('website_id');

        $cohortSize = $cohortScans->count();

        if ($cohortSize < self::MINIMUM_COHORT_SIZE) {
            return [
                'available' => false,
                'cohort_size' => $cohortSize,
                'minimum_required' => self::MINIMUM_COHORT_SIZE,
                'message' => "Insufficient benchmark data for this cohort ({$cohortSize}/" . self::MINIMUM_COHORT_SIZE . " websites). Industry benchmarks will activate once representative public data is available.",
            ];
        }

        $latestTargetScan = Scan::where('website_id', $website->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $targetOverall = $latestTargetScan?->overall_score ?? 0;

        $overallScores = $cohortScans->pluck('overall_score')->sort()->values();

        $pillars = ['seo', 'performance', 'security', 'accessibility', 'mobile', 'technical', 'ai_readiness'];
        $pillarStats = [];

        foreach ($pillars as $pillar) {
            $col = 'score_' . $pillar;
            $scores = $cohortScans->pluck($col)->filter()->sort()->values();
            $targetPillar = $latestTargetScan?->{$col} ?? 0;

            $pillarStats[$pillar] = [
                'target_score' => $targetPillar,
                'median' => $this->calculatePercentile($scores, 50),
                'p25' => $this->calculatePercentile($scores, 25),
                'p75' => $this->calculatePercentile($scores, 75),
                'relative_tier' => $this->getRelativeTier($targetPillar, $scores),
            ];
        }

        return [
            'available' => true,
            'cohort_size' => $cohortSize,
            'privacy_notice' => 'All benchmarks are computed from anonymized, aggregated scores. No private URLs, tokens, or customer identifiers are shared.',
            'target_overall' => $targetOverall,
            'cohort_overall' => [
                'median' => $this->calculatePercentile($overallScores, 50),
                'p25' => $this->calculatePercentile($overallScores, 25),
                'p75' => $this->calculatePercentile($overallScores, 75),
                'relative_tier' => $this->getRelativeTier($targetOverall, $overallScores),
            ],
            'pillar_benchmarks' => $pillarStats,
        ];
    }

    /**
     * Calculate percentile rank from a sorted numeric collection.
     */
    protected function calculatePercentile(Collection $sortedValues, int $percentile): int
    {
        $count = $sortedValues->count();
        if ($count === 0) {
            return 0;
        }

        $index = ($percentile / 100) * ($count - 1);
        $fraction = $index - floor($index);
        $lower = $sortedValues[floor($index)];
        $upper = $sortedValues[ceil($index)] ?? $lower;

        return (int) round($lower + ($fraction * ($upper - $lower)));
    }

    /**
     * Categorize score into Top 25%, Above Average, Below Average, Bottom 25%.
     */
    protected function getRelativeTier(int $score, Collection $sortedScores): string
    {
        $p25 = $this->calculatePercentile($sortedScores, 25);
        $p50 = $this->calculatePercentile($sortedScores, 50);
        $p75 = $this->calculatePercentile($sortedScores, 75);

        if ($score >= $p75) {
            return 'Top Quartile (75th+ Percentile)';
        }
        if ($score >= $p50) {
            return 'Above Median';
        }
        if ($score >= $p25) {
            return 'Below Median';
        }

        return 'Bottom Quartile (< 25th Percentile)';
    }
}
