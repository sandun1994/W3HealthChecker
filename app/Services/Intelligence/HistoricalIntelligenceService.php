<?php

namespace App\Services\Intelligence;

use App\Models\Scan;
use App\Models\Website;
use Illuminate\Support\Collection;

class HistoricalIntelligenceService
{
    public const SCORE_VERSION = '2.0';

    /**
     * Compute comprehensive historical health intelligence for a website.
     */
    public function analyze(Website $website): array
    {
        $scans = Scan::where('website_id', $website->id)
            ->where('status', 'completed')
            ->with(['issues.rule'])
            ->orderBy('completed_at', 'asc')
            ->get();

        if ($scans->isEmpty()) {
            return [
                'has_data' => false,
                'message' => 'No completed scans available for this website.',
            ];
        }

        $latestScan = $scans->last();
        $previousScan = $scans->count() > 1 ? $scans->get($scans->count() - 2) : null;

        $issueLifecycle = $this->computeIssueLifecycle($latestScan, $previousScan, $scans);
        $rootCauses = $this->groupRootCauses($latestScan->issues);
        $pillarTrends = $this->computePillarTrends($scans);

        return [
            'has_data' => true,
            'domain' => $website->domain,
            'total_scans' => $scans->count(),
            'score_version' => self::SCORE_VERSION,
            'methodology_notice' => 'W3HealthChecker score is a deterministic technical health index and does not represent an official Google search ranking or third-party certification.',
            'latest_score' => $latestScan->overall_score,
            'latest_scan_at' => $latestScan->completed_at?->toIso8601String(),
            'pillar_trends' => $pillarTrends,
            'issue_lifecycle' => $issueLifecycle,
            'root_causes' => $rootCauses,
            'trajectory' => $scans->map(fn($s) => [
                'public_id' => $s->public_id,
                'overall_score' => $s->overall_score,
                'score_seo' => $s->score_seo,
                'score_security' => $s->score_security,
                'score_performance' => $s->score_performance,
                'score_accessibility' => $s->score_accessibility,
                'score_mobile' => $s->score_mobile,
                'score_technical' => $s->score_technical,
                'score_ai_readiness' => $s->score_ai_readiness,
                'completed_at' => $s->completed_at?->toIso8601String(),
            ]),
        ];
    }

    /**
     * Track newly detected, resolved, persistent, and regressed issues.
     */
    public function computeIssueLifecycle(Scan $latestScan, ?Scan $previousScan, Collection $allScans): array
    {
        $currentRules = $latestScan->issues->pluck('rule_id')->unique()->toArray();
        $previousRules = $previousScan ? $previousScan->issues->pluck('rule_id')->unique()->toArray() : [];

        // All historical rule IDs prior to latest scan
        $priorRules = $allScans->where('id', '!=', $latestScan->id)
            ->flatMap(fn($s) => $s->issues->pluck('rule_id'))
            ->unique()
            ->toArray();

        $new = [];
        $persistent = [];
        $regressed = [];
        $resolved = [];

        foreach ($latestScan->issues as $issue) {
            $ruleId = $issue->rule_id;
            if (!in_array($ruleId, $previousRules, true)) {
                if (in_array($ruleId, $priorRules, true)) {
                    $regressed[] = $this->formatIssueSummary($issue, 'Regressed: Issue was previously resolved in earlier scans but has returned.');
                } else {
                    $new[] = $this->formatIssueSummary($issue, 'Newly Detected in current scan.');
                }
            } else {
                $persistent[] = $this->formatIssueSummary($issue, 'Persistent: Issue was present in previous scan.');
            }
        }

        if ($previousScan) {
            foreach ($previousScan->issues as $prevIssue) {
                if (!in_array($prevIssue->rule_id, $currentRules, true)) {
                    $resolved[] = $this->formatIssueSummary($prevIssue, 'Resolved: Successfully remediated since previous scan.');
                }
            }
        }

        return [
            'summary' => [
                'new_count' => count($new),
                'resolved_count' => count($resolved),
                'persistent_count' => count($persistent),
                'regressed_count' => count($regressed),
            ],
            'new' => $new,
            'resolved' => $resolved,
            'persistent' => $persistent,
            'regressed' => $regressed,
        ];
    }

    /**
     * Group individual findings into inferred root-cause clusters.
     */
    public function groupRootCauses(Collection $issues): array
    {
        $clusters = [
            'transport_and_security' => [
                'title' => 'Transport Security & Header Hygiene',
                'hypothesis' => 'Missing defensive HTTP headers or insecure TLS configurations indicate server-level hardening gaps.',
                'matches' => ['sec_', 'ssl_', 'hsts_', 'header_'],
                'issues' => [],
            ],
            'canonical_and_indexing' => [
                'title' => 'Canonicalization & Crawlability Directives',
                'hypothesis' => 'Conflicting canonical tags, redirect loops, or restrictive robots directives prevent search engines from understanding the authoritative URL.',
                'matches' => ['seo_canonical', 'seo_robots', 'redirect_', 'sitemap_'],
                'issues' => [],
            ],
            'asset_and_loading' => [
                'title' => 'Asset Delivery & Payload Weight',
                'hypothesis' => 'Uncompressed assets, high DOM nodes, or slow TTFB degrade Core Web Vitals and user perceived speed.',
                'matches' => ['perf_', 'asset_', 'compression_', 'cache_'],
                'issues' => [],
            ],
            'semantic_and_a11y' => [
                'title' => 'Semantic Hierarchy & Accessibility Contracts',
                'hypothesis' => 'Missing image alt attributes, improper heading structures, or low contrast break WCAG accessibility standards and machine parsability.',
                'matches' => ['a11y_', 'alt_', 'heading_', 'mobile_'],
                'issues' => [],
            ],
            'structured_data_and_ai' => [
                'title' => 'Machine Comprehension & Structured Entities',
                'hypothesis' => 'Absence of Schema.org JSON-LD or OpenGraph tags limits entity discovery and AI-assisted search summaries.',
                'matches' => ['ai_', 'schema_', 'og_', 'twitter_'],
                'issues' => [],
            ],
        ];

        foreach ($issues as $issue) {
            $placed = false;
            foreach ($clusters as $key => &$cluster) {
                foreach ($cluster['matches'] as $prefix) {
                    if (str_starts_with($issue->rule_id, $prefix) || str_contains($issue->rule_id, $prefix)) {
                        $cluster['issues'][] = [
                            'rule_id' => $issue->rule_id,
                            'title' => $issue->title,
                            'severity' => $issue->severity,
                            'category' => $issue->category,
                        ];
                        $placed = true;
                        break 2;
                    }
                }
            }
            if (!$placed) {
                $clusters['other']['title'] = 'Miscellaneous Isolated Findings';
                $clusters['other']['hypothesis'] = 'Individual technical findings that do not share a common root-cause pattern.';
                $clusters['other']['issues'][] = [
                    'rule_id' => $issue->rule_id,
                    'title' => $issue->title,
                    'severity' => $issue->severity,
                    'category' => $issue->category,
                ];
            }
        }

        // Filter out empty clusters
        return array_values(array_filter($clusters, fn($c) => !empty($c['issues'])));
    }

    /**
     * Compute pillar-by-pillar trajectories across all scans.
     */
    protected function computePillarTrends(Collection $scans): array
    {
        $pillars = ['seo', 'performance', 'security', 'accessibility', 'mobile', 'technical', 'ai_readiness'];
        $trends = [];

        foreach ($pillars as $pillar) {
            $col = 'score_' . $pillar;
            $first = $scans->first()->{$col} ?? 0;
            $latest = $scans->last()->{$col} ?? 0;
            $delta = $latest - $first;

            $trends[$pillar] = [
                'initial' => $first,
                'current' => $latest,
                'net_change' => $delta,
                'trajectory' => $scans->map(fn($s) => $s->{$col} ?? 0)->toArray(),
            ];
        }

        return $trends;
    }

    protected function formatIssueSummary($issue, string $statusText): array
    {
        return [
            'rule_id' => $issue->rule_id,
            'title' => $issue->title,
            'severity' => $issue->severity,
            'category' => $issue->category,
            'lifecycle_status' => $statusText,
        ];
    }
}
