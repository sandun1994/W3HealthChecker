<?php

namespace App\Services\Monitoring;

use App\Models\Scan;
use App\Models\ScanChange;
use Illuminate\Support\Collection;

class ChangeDetectionService
{
    /**
     * Compare current scan against previous scan, detecting regressions and major changes.
     */
    public function detectChanges(Scan $currentScan, ?Scan $previousScan): array
    {
        if (!$previousScan) {
            return [];
        }

        $changes = [];

        // Load metrics and issues if not loaded
        $currentScan->loadMissing(['metric', 'issues']);
        $previousScan->loadMissing(['metric', 'issues']);

        $currentMeta = $currentScan->metric?->meta_summary ?? [];
        $previousMeta = $previousScan->metric?->meta_summary ?? [];

        $currentHeaders = $currentScan->metric?->headers ?? [];
        $previousHeaders = $previousScan->metric?->headers ?? [];

        // 1. Overall Score Regression
        if ($currentScan->overall_score !== null && $previousScan->overall_score !== null) {
            $scoreDiff = $currentScan->overall_score - $previousScan->overall_score;
            if ($scoreDiff <= -10) {
                $changes[] = [
                    'change_type' => 'overall_score_dropped_critical',
                    'category' => 'overall',
                    'severity' => 'critical',
                    'title' => "Critical Health Score Drop (-" . abs($scoreDiff) . " pts)",
                    'description' => "Overall website health score fell from {$previousScan->overall_score}/100 to {$currentScan->overall_score}/100.",
                    'old_value' => (string) $previousScan->overall_score,
                    'new_value' => (string) $currentScan->overall_score,
                ];
            } elseif ($scoreDiff <= -5) {
                $changes[] = [
                    'change_type' => 'overall_score_dropped_warning',
                    'category' => 'overall',
                    'severity' => 'high',
                    'title' => "Health Score Decreased (-" . abs($scoreDiff) . " pts)",
                    'description' => "Overall health score decreased from {$previousScan->overall_score}/100 to {$currentScan->overall_score}/100.",
                    'old_value' => (string) $previousScan->overall_score,
                    'new_value' => (string) $currentScan->overall_score,
                ];
            } elseif ($scoreDiff >= 5) {
                $changes[] = [
                    'change_type' => 'overall_score_improved',
                    'category' => 'overall',
                    'severity' => 'info',
                    'title' => "Health Score Improved (+" . $scoreDiff . " pts)",
                    'description' => "Overall health score improved from {$previousScan->overall_score}/100 to {$currentScan->overall_score}/100.",
                    'old_value' => (string) $previousScan->overall_score,
                    'new_value' => (string) $currentScan->overall_score,
                ];
            }
        }

        // 2. Pillar Specific Regressions
        $pillars = [
            'seo' => 'SEO',
            'security' => 'Security',
            'performance' => 'Performance',
            'accessibility' => 'Accessibility',
            'mobile' => 'Mobile Readiness',
            'technical' => 'Technical Infrastructure',
            'ai_readiness' => 'AI Search Readiness',
        ];

        foreach ($pillars as $key => $label) {
            $currScore = $currentScan->{"score_{$key}"};
            $prevScore = $previousScan->{"score_{$key}"};
            if ($currScore !== null && $prevScore !== null) {
                $pillarDiff = $currScore - $prevScore;
                if ($pillarDiff <= -15) {
                    $changes[] = [
                        'change_type' => "pillar_{$key}_dropped",
                        'category' => $key,
                        'severity' => 'high',
                        'title' => "{$label} Score Regressed (-" . abs($pillarDiff) . " pts)",
                        'description' => "{$label} category score dropped significantly from {$prevScore} to {$currScore}.",
                        'old_value' => (string) $prevScore,
                        'new_value' => (string) $currScore,
                    ];
                }
            }
        }

        // 3. Title Tag Changes
        $prevTitle = $previousMeta['title'] ?? null;
        $currTitle = $currentMeta['title'] ?? null;
        if ($prevTitle !== $currTitle) {
            if (!empty($prevTitle) && empty($currTitle)) {
                $changes[] = [
                    'change_type' => 'title_removed',
                    'category' => 'seo',
                    'severity' => 'high',
                    'title' => 'HTML Title Tag Removed',
                    'description' => 'The webpage title tag was completely removed.',
                    'old_value' => $prevTitle,
                    'new_value' => null,
                ];
            } elseif (!empty($prevTitle) && !empty($currTitle)) {
                $changes[] = [
                    'change_type' => 'title_changed',
                    'category' => 'seo',
                    'severity' => 'medium',
                    'title' => 'Title Tag Updated',
                    'description' => "Title changed from \"{$prevTitle}\" to \"{$currTitle}\".",
                    'old_value' => $prevTitle,
                    'new_value' => $currTitle,
                ];
            }
        }

        // 4. Meta Description Changes
        $prevDesc = $previousMeta['meta_description'] ?? null;
        $currDesc = $currentMeta['meta_description'] ?? null;
        if ($prevDesc !== $currDesc) {
            if (!empty($prevDesc) && empty($currDesc)) {
                $changes[] = [
                    'change_type' => 'meta_description_removed',
                    'category' => 'seo',
                    'severity' => 'high',
                    'title' => 'Meta Description Removed',
                    'description' => 'The meta description snippet tag was removed.',
                    'old_value' => $prevDesc,
                    'new_value' => null,
                ];
            } elseif (!empty($prevDesc) && !empty($currDesc)) {
                $changes[] = [
                    'change_type' => 'meta_description_changed',
                    'category' => 'seo',
                    'severity' => 'medium',
                    'title' => 'Meta Description Updated',
                    'description' => 'The page meta description content was modified.',
                    'old_value' => $prevDesc,
                    'new_value' => $currDesc,
                ];
            }
        }

        // 5. Canonical URL Changes
        $prevCanonical = $previousMeta['canonical'] ?? null;
        $currCanonical = $currentMeta['canonical'] ?? null;
        if ($prevCanonical !== $currCanonical) {
            $changes[] = [
                'change_type' => 'canonical_changed',
                'category' => 'seo',
                'severity' => 'high',
                'title' => 'Canonical Tag Modified',
                'description' => 'Canonical destination URL changed or was updated.',
                'old_value' => $prevCanonical,
                'new_value' => $currCanonical,
            ];
        }

        // 6. Robots / Indexability Changes
        $prevRobots = strtolower($previousMeta['robots'] ?? '');
        $currRobots = strtolower($currentMeta['robots'] ?? '');
        if ($prevRobots !== $currRobots) {
            $becameNoindex = str_contains($currRobots, 'noindex') && !str_contains($prevRobots, 'noindex');
            $changes[] = [
                'change_type' => $becameNoindex ? 'robots_noindex_added' : 'robots_directive_changed',
                'category' => 'seo',
                'severity' => $becameNoindex ? 'critical' : 'high',
                'title' => $becameNoindex ? 'Webpage Marked Noindex' : 'Robots Meta Directive Changed',
                'description' => $becameNoindex
                    ? 'Page now contains a "noindex" meta directive, instructing search crawlers to exclude it.'
                    : "Robots meta tag updated from \"{$prevRobots}\" to \"{$currRobots}\".",
                'old_value' => $prevRobots,
                'new_value' => $currRobots,
            ];
        }

        // 7. Primary Heading (H1) Changes
        $prevH1 = $previousScan->metric?->headings_summary['h1'][0] ?? null;
        $currH1 = $currentScan->metric?->headings_summary['h1'][0] ?? null;
        if ($prevH1 !== $currH1) {
            if (!empty($prevH1) && empty($currH1)) {
                $changes[] = [
                    'change_type' => 'h1_removed',
                    'category' => 'seo',
                    'severity' => 'high',
                    'title' => 'Primary H1 Heading Missing',
                    'description' => 'The primary <h1> heading element was removed.',
                    'old_value' => $prevH1,
                    'new_value' => null,
                ];
            } elseif (!empty($prevH1) && !empty($currH1)) {
                $changes[] = [
                    'change_type' => 'h1_changed',
                    'category' => 'seo',
                    'severity' => 'low',
                    'title' => 'Primary H1 Heading Changed',
                    'description' => "Primary heading changed from \"{$prevH1}\" to \"{$currH1}\".",
                    'old_value' => $prevH1,
                    'new_value' => $currH1,
                ];
            }
        }

        // 8. Defensive Security Header Regressions
        $secHeaders = [
            'strict-transport-security' => ['HSTS Encryption Header', 'critical'],
            'content-security-policy' => ['Content-Security-Policy (CSP)', 'high'],
            'x-frame-options' => ['X-Frame-Options (Clickjacking defense)', 'medium'],
            'x-content-type-options' => ['X-Content-Type-Options (MIME sniffing defense)', 'medium'],
        ];

        foreach ($secHeaders as $headerKey => [$headerName, $severity]) {
            $prevHad = isset($previousHeaders[$headerKey]);
            $currHad = isset($currentHeaders[$headerKey]);

            if ($prevHad && !$currHad) {
                $changes[] = [
                    'change_type' => "security_header_removed_{$headerKey}",
                    'category' => 'security',
                    'severity' => $severity,
                    'title' => "{$headerName} Removed",
                    'description' => "The defensive {$headerName} was present in the prior scan but is no longer detected.",
                    'old_value' => 'Present',
                    'new_value' => 'Missing',
                ];
            } elseif (!$prevHad && $currHad) {
                $changes[] = [
                    'change_type' => "security_header_added_{$headerKey}",
                    'category' => 'security',
                    'severity' => 'info',
                    'title' => "{$headerName} Implemented",
                    'description' => "Defensive security header {$headerName} is now active.",
                    'old_value' => 'Missing',
                    'new_value' => 'Present',
                ];
            }
        }

        // 9. SSL Validity & Protocol
        $prevSsl = $previousScan->metric?->ssl_data['valid'] ?? true;
        $currSsl = $currentScan->metric?->ssl_data['valid'] ?? true;
        if ($prevSsl && !$currSsl) {
            $changes[] = [
                'change_type' => 'ssl_validity_lost',
                'category' => 'security',
                'severity' => 'critical',
                'title' => 'SSL/TLS Certificate Invalid or Failed',
                'description' => 'The SSL certificate is no longer verifying successfully over HTTPS.',
                'old_value' => 'Valid',
                'new_value' => 'Invalid',
            ];
        }

        // 10. Performance / TTFB Latency Surge
        $prevTtfb = $previousScan->response_time_ms ?? $previousMeta['ttfb_ms'] ?? null;
        $currTtfb = $currentScan->response_time_ms ?? $currentMeta['ttfb_ms'] ?? null;
        if ($prevTtfb && $currTtfb && ($currTtfb - $prevTtfb >= 1000) && $currTtfb > 1200) {
            $changes[] = [
                'change_type' => 'ttfb_surged',
                'category' => 'performance',
                'severity' => 'high',
                'title' => 'Server Response Latency Surged',
                'description' => "Initial response time jumped from {$prevTtfb}ms to {$currTtfb}ms (+ " . ($currTtfb - $prevTtfb) . "ms).",
                'old_value' => "{$prevTtfb}ms",
                'new_value' => "{$currTtfb}ms",
            ];
        }

        // 11. Structured Data Markup Changes
        $prevSchemasCount = count($previousScan->metric?->structured_data ?? []);
        $currSchemasCount = count($currentScan->metric?->structured_data ?? []);
        if ($prevSchemasCount > 0 && $currSchemasCount === 0) {
            $changes[] = [
                'change_type' => 'structured_data_removed',
                'category' => 'ai_readiness',
                'severity' => 'high',
                'title' => 'Structured Data (Schema.org) Removed',
                'description' => 'All JSON-LD structured data schemas were removed from the document.',
                'old_value' => "{$prevSchemasCount} schemas found",
                'new_value' => '0 schemas found',
            ];
        }

        // 12. New Critical Issues
        $prevCriticalRules = $previousScan->issues
            ->where('severity', 'critical')
            ->pluck('rule_id')
            ->all();

        $currCriticalIssues = $currentScan->issues
            ->where('severity', 'critical');

        foreach ($currCriticalIssues as $issue) {
            if (!in_array($issue->rule_id, $prevCriticalRules)) {
                $changes[] = [
                    'change_type' => 'new_critical_issue',
                    'category' => $issue->category,
                    'severity' => 'critical',
                    'title' => "New Critical Issue: {$issue->title}",
                    'description' => $issue->why_it_matters,
                    'old_value' => 'Clean',
                    'new_value' => $issue->rule_id,
                ];
            }
        }

        return $changes;
    }

    /**
     * Detect and persist changes to the database.
     */
    public function recordChanges(Scan $currentScan, ?Scan $previousScan, array $changes): array
    {
        $savedChanges = [];
        $websiteId = $currentScan->website_id;

        foreach ($changes as $item) {
            $record = ScanChange::create([
                'website_id' => $websiteId,
                'current_scan_id' => $currentScan->id,
                'previous_scan_id' => $previousScan?->id,
                'change_type' => $item['change_type'],
                'category' => $item['category'],
                'severity' => $item['severity'],
                'title' => $item['title'],
                'description' => $item['description'],
                'old_value' => $item['old_value'] ?? null,
                'new_value' => $item['new_value'] ?? null,
            ]);
            $savedChanges[] = $record;
        }

        return $savedChanges;
    }

    /**
     * Detect and record in one call.
     */
    public function detectAndRecordChanges(Scan $currentScan, ?Scan $previousScan): array
    {
        $changes = $this->detectChanges($currentScan, $previousScan);
        return $this->recordChanges($currentScan, $previousScan, $changes);
    }
}
