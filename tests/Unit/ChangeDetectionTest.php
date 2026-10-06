<?php

namespace Tests\Unit;

use App\Models\Scan;
use App\Models\ScanMetric;
use App\Models\Website;
use App\Services\Monitoring\ChangeDetectionService;
use Tests\TestCase;

class ChangeDetectionTest extends TestCase
{
    protected ChangeDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChangeDetectionService();
    }

    public function test_detects_score_regression(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'score-regression.test'],
            ['scheme' => 'https', 'canonical_url' => 'https://score-regression.test']
        );

        $prevScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://score-regression.test',
            'public_id' => 'prev_score_' . uniqid(),
            'overall_score' => 90,
            'score_security' => 95,
            'status' => 'completed',
        ]);

        $currScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://score-regression.test',
            'public_id' => 'curr_score_' . uniqid(),
            'overall_score' => 74, // Dropped 16 points (Critical)
            'score_security' => 70, // Dropped 25 points (High)
            'status' => 'completed',
        ]);

        $changes = $this->service->detectChanges($currScan, $prevScan);

        $this->assertNotEmpty($changes);
        $types = array_column($changes, 'change_type');
        $this->assertContains('overall_score_dropped_critical', $types);
        $this->assertContains('pillar_security_dropped', $types);
    }

    public function test_detects_title_and_description_removal(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'meta-changes.test'],
            ['scheme' => 'https', 'canonical_url' => 'https://meta-changes.test']
        );

        $prevScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://meta-changes.test',
            'public_id' => 'prev_meta_' . uniqid(),
            'overall_score' => 85,
            'status' => 'completed',
        ]);

        ScanMetric::create([
            'scan_id' => $prevScan->id,
            'meta_summary' => [
                'title' => 'Original SEO Title Tag',
                'meta_description' => 'Original engaging meta description for search snippets.',
                'canonical' => 'https://meta-changes.test',
                'robots' => 'index, follow',
            ],
            'headings_summary' => ['h1' => ['Main Heading H1']],
        ]);

        $currScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://meta-changes.test',
            'public_id' => 'curr_meta_' . uniqid(),
            'overall_score' => 75,
            'status' => 'completed',
        ]);

        ScanMetric::create([
            'scan_id' => $currScan->id,
            'meta_summary' => [
                'title' => '', // Removed
                'meta_description' => '', // Removed
                'canonical' => 'https://meta-changes.test/alt', // Changed
                'robots' => 'noindex, follow', // Became noindex!
            ],
            'headings_summary' => ['h1' => []], // H1 removed
        ]);

        $changes = $this->service->detectChanges($currScan, $prevScan);
        $types = array_column($changes, 'change_type');

        $this->assertContains('title_removed', $types);
        $this->assertContains('meta_description_removed', $types);
        $this->assertContains('canonical_changed', $types);
        $this->assertContains('robots_noindex_added', $types);
        $this->assertContains('h1_removed', $types);
    }

    public function test_detects_security_header_removal(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'sec-headers.test'],
            ['scheme' => 'https', 'canonical_url' => 'https://sec-headers.test']
        );

        $prevScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://sec-headers.test',
            'public_id' => 'prev_sec_' . uniqid(),
            'overall_score' => 92,
            'status' => 'completed',
        ]);

        ScanMetric::create([
            'scan_id' => $prevScan->id,
            'headers' => [
                'strict-transport-security' => 'max-age=31536000',
                'content-security-policy' => "default-src 'self'",
            ],
            'ssl_data' => ['valid' => true],
        ]);

        $currScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://sec-headers.test',
            'public_id' => 'curr_sec_' . uniqid(),
            'overall_score' => 70,
            'status' => 'completed',
        ]);

        ScanMetric::create([
            'scan_id' => $currScan->id,
            'headers' => [], // All security headers removed!
            'ssl_data' => ['valid' => false], // SSL lost!
        ]);

        $changes = $this->service->detectChanges($currScan, $prevScan);
        $types = array_column($changes, 'change_type');

        $this->assertContains('security_header_removed_strict-transport-security', $types);
        $this->assertContains('security_header_removed_content-security-policy', $types);
        $this->assertContains('ssl_validity_lost', $types);
    }
}
