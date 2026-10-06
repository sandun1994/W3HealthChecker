<?php

namespace App\Services\Scoring;

class RecommendationService
{
    /**
     * Prioritize and rank issues for the "Fix These First" section using:
     * Priority Score = (SeverityWeight × Impact × Confidence) / Effort
     */
    public function prioritizeIssues(array $allIssues): array
    {
        $severityWeights = [
            'critical' => 100,
            'high' => 70,
            'medium' => 40,
            'low' => 20,
            'info' => 10,
        ];

        $confidenceMultipliers = [
            'high' => 1.0,
            'medium' => 0.8,
            'low' => 0.6,
        ];

        // Enrich issues with effort, impact, and calculated priority score
        $enriched = array_map(function ($issue) use ($severityWeights, $confidenceMultipliers) {
            $severity = strtolower($issue['severity'] ?? 'medium');
            $sevWeight = $severityWeights[$severity] ?? 40;

            $confidence = strtolower($issue['confidence'] ?? 'high');
            $confMult = $confidenceMultipliers[$confidence] ?? 0.8;

            $ruleId = $issue['rule_id'] ?? '';

            // Calibrate Impact & Effort by rule type
            [$impactFactor, $impactLabel, $effortFactor, $effortLabel] = $this->determineImpactAndEffort($ruleId, $severity);

            $priorityScore = round(($sevWeight * $impactFactor * $confMult) / $effortFactor, 1);

            $issue['impact'] = $impactLabel;
            $issue['effort'] = $effortLabel;
            $issue['priority_score'] = $priorityScore;

            return $issue;
        }, $allIssues);

        // Sort descending by priority_score
        usort($enriched, function ($a, $b) {
            if ($a['priority_score'] === $b['priority_score']) {
                return strcmp($a['title'], $b['title']);
            }
            return ($a['priority_score'] < $b['priority_score']) ? 1 : -1;
        });

        // Assign clean 1-indexed priority ranking
        $ranked = [];
        $order = 1;
        foreach ($enriched as $issue) {
            $issue['priority_order'] = $order++;
            $ranked[] = $issue;
        }

        return $ranked;
    }

    /**
     * Determine realistic impact factor (1.0 to 1.5) and effort factor (1.0 to 2.0).
     */
    protected function determineImpactAndEffort(string $ruleId, string $severity): array
    {
        // Low effort fixes (header additions, single meta tags, alt attribute addition)
        if (str_contains($ruleId, 'title') || 
            str_contains($ruleId, 'meta_desc') || 
            str_contains($ruleId, 'canonical') ||
            str_contains($ruleId, 'lang') ||
            str_contains($ruleId, 'header') ||
            str_contains($ruleId, 'hsts') ||
            str_contains($ruleId, 'x_') ||
            str_contains($ruleId, 'referrer')) {
            return [1.3, 'High', 1.0, 'Quick Fix (< 5 mins)'];
        }

        // Medium effort (images alt coverage, heading hierarchy, structured data JSON-LD)
        if (str_contains($ruleId, 'alt') || 
            str_contains($ruleId, 'h1') || 
            str_contains($ruleId, 'structured_data') ||
            str_contains($ruleId, 'sitemap') ||
            str_contains($ruleId, 'viewport')) {
            return [1.2, 'High', 1.2, 'Moderate (10–20 mins)'];
        }

        // Substantial effort (server response time / TTFB caching, backend compression, code refactoring)
        if (str_contains($ruleId, 'slow_response') || 
            str_contains($ruleId, 'redirect_chain') || 
            str_contains($ruleId, 'excessive_scripts') || 
            str_contains($ruleId, 'heavy_dom')) {
            return [1.4, 'High', 1.6, 'Substantial (Architecture/CDN)'];
        }

        // Default based on severity
        if ($severity === 'critical') {
            return [1.5, 'Critical', 1.1, 'Immediate Attention'];
        }

        return [1.0, 'Moderate', 1.2, 'Standard (15 mins)'];
    }

    /**
     * Extract top prioritized action items ("If I only have 10 minutes, what should I fix?").
     */
    public function getFixTheseFirst(array $rankedIssues, int $limit = 5): array
    {
        return array_slice($rankedIssues, 0, $limit);
    }
}
