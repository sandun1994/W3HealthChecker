<?php

namespace App\Services\Scoring;

class ScoreService
{
    /**
     * Default weights for the 7 pillars (Total: 100%).
     */
    protected array $weights = [
        'seo' => 0.20,
        'security' => 0.20,
        'performance' => 0.15,
        'accessibility' => 0.15,
        'mobile' => 0.10,
        'technical' => 0.10,
        'ai_readiness' => 0.10,
    ];

    public function getCategoryWeights(): array
    {
        return $this->weights;
    }

    /**
     * Calculate weighted overall score from pillar scores.
     */
    public function calculateOverallScore(array $categoryScores): int
    {
        $weightedTotal = 0.0;
        $totalWeight = 0.0;

        foreach ($this->weights as $category => $weight) {
            $score = $categoryScores[$category] ?? 70;
            $weightedTotal += ($score * $weight);
            $totalWeight += $weight;
        }

        $overall = $totalWeight > 0 ? (int) round($weightedTotal / $totalWeight) : 70;
        return max(0, min(100, $overall));
    }

    /**
     * Get textual status label for any score.
     */
    public function getStatusLabel(int $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Good';
        if ($score >= 70) return 'Needs Improvement';
        if ($score >= 50) return 'Poor';
        return 'Critical';
    }

    /**
     * Get color theme descriptor for a score.
     */
    public function getScoreTheme(int $score): array
    {
        if ($score >= 90) {
            return [
                'label' => 'Excellent',
                'color' => 'emerald',
                'hex' => '#10b981',
                'bg_badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            ];
        }
        if ($score >= 80) {
            return [
                'label' => 'Good',
                'color' => 'blue',
                'hex' => '#3b82f6',
                'bg_badge' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            ];
        }
        if ($score >= 70) {
            return [
                'label' => 'Needs Improvement',
                'color' => 'amber',
                'hex' => '#f59e0b',
                'bg_badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            ];
        }
        if ($score >= 50) {
            return [
                'label' => 'Poor',
                'color' => 'orange',
                'hex' => '#f97316',
                'bg_badge' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
            ];
        }
        return [
            'label' => 'Critical',
            'color' => 'rose',
            'hex' => '#f43f5e',
            'bg_badge' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        ];
    }
}
