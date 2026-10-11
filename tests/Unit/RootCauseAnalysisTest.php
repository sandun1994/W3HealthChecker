<?php

namespace Tests\Unit;

use App\Models\ScanIssue;
use App\Services\Intelligence\HistoricalIntelligenceService;
use PHPUnit\Framework\TestCase;

class RootCauseAnalysisTest extends TestCase
{
    public function test_groups_issues_into_root_cause_clusters(): void
    {
        $service = new HistoricalIntelligenceService();

        $issues = collect([
            new ScanIssue([
                'rule_id' => 'sec_hsts_missing',
                'title' => 'Missing HSTS Header',
                'category' => 'security',
                'severity' => 'high',
            ]),
            new ScanIssue([
                'rule_id' => 'sec_csp_missing',
                'title' => 'Missing Content Security Policy',
                'category' => 'security',
                'severity' => 'medium',
            ]),
            new ScanIssue([
                'rule_id' => 'seo_canonical_missing',
                'title' => 'Missing Canonical URL',
                'category' => 'seo',
                'severity' => 'high',
            ]),
            new ScanIssue([
                'rule_id' => 'perf_uncompressed_assets',
                'title' => 'Uncompressed Text Assets',
                'category' => 'performance',
                'severity' => 'medium',
            ]),
            new ScanIssue([
                'rule_id' => 'a11y_missing_image_alt',
                'title' => 'Images Missing Alt Text',
                'category' => 'accessibility',
                'severity' => 'medium',
            ]),
        ]);

        $clusters = $service->groupRootCauses($issues);

        $this->assertNotEmpty($clusters);
        $clusterTitles = array_column($clusters, 'title');

        $this->assertContains('Transport Security & Header Hygiene', $clusterTitles);
        $this->assertContains('Canonicalization & Crawlability Directives', $clusterTitles);
        $this->assertContains('Asset Delivery & Payload Weight', $clusterTitles);
        $this->assertContains('Semantic Hierarchy & Accessibility Contracts', $clusterTitles);

        // Verify hypothesis text is present
        foreach ($clusters as $cluster) {
            $this->assertArrayHasKey('hypothesis', $cluster);
            $this->assertNotEmpty($cluster['hypothesis']);
            $this->assertNotEmpty($cluster['issues']);
        }
    }
}
