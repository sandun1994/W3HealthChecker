<?php

namespace Tests\Feature;

use App\Models\AuditRule;
use App\Models\Scan;
use App\Models\ScanIssue;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_intelligence_endpoint_tracks_issue_lifecycle_and_regressions(): void
    {
        $website = Website::create([
            'domain' => 'lifecycle-test.com',
            'canonical_url' => 'https://lifecycle-test.com',
        ]);

        $ruleHsts = AuditRule::firstOrCreate(['id' => 'sec_hsts_missing'], [
            'category' => 'security',
            'title' => 'Missing HSTS Header',
            'severity' => 'high',
            'impact_description' => 'Insecure transport.',
            'why_it_matters' => 'Leaves site prone to downgrade attacks.',
            'fix_guidance' => 'Enable Strict-Transport-Security.',
        ]);

        $ruleAlt = AuditRule::firstOrCreate(['id' => 'a11y_missing_image_alt'], [
            'category' => 'accessibility',
            'title' => 'Images Missing Alt',
            'severity' => 'medium',
            'impact_description' => 'Screen readers impaired.',
            'why_it_matters' => 'WCAG failure.',
            'fix_guidance' => 'Add alt attribute.',
        ]);

        $ruleCanonical = AuditRule::firstOrCreate(['id' => 'seo_canonical_missing'], [
            'category' => 'seo',
            'title' => 'Missing Canonical',
            'severity' => 'high',
            'impact_description' => 'Indexation ambiguity.',
            'why_it_matters' => 'Search engines cannot identify authoritative source.',
            'fix_guidance' => 'Add rel=canonical.',
        ]);

        // Scan 1: Had HSTS missing and Alt missing
        $scan1 = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'scan_life_1',
            'target_url' => 'https://lifecycle-test.com',
            'status' => 'completed',
            'overall_score' => 70,
            'completed_at' => now()->subDays(14),
        ]);
        ScanIssue::create([
            'scan_id' => $scan1->id,
            'rule_id' => $ruleHsts->id,
            'category' => 'security',
            'severity' => 'high',
            'title' => $ruleHsts->title,
            'why_it_matters' => 'Insecure transport.',
            'recommendation' => 'Add HSTS.',
        ]);
        ScanIssue::create([
            'scan_id' => $scan1->id,
            'rule_id' => $ruleAlt->id,
            'category' => 'accessibility',
            'severity' => 'medium',
            'title' => $ruleAlt->title,
            'why_it_matters' => 'WCAG failure.',
            'recommendation' => 'Add alt.',
        ]);

        // Scan 2: Fixed Alt missing, HSTS still present
        $scan2 = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'scan_life_2',
            'target_url' => 'https://lifecycle-test.com',
            'status' => 'completed',
            'overall_score' => 80,
            'completed_at' => now()->subDays(7),
        ]);
        ScanIssue::create([
            'scan_id' => $scan2->id,
            'rule_id' => $ruleHsts->id,
            'category' => 'security',
            'severity' => 'high',
            'title' => $ruleHsts->title,
            'why_it_matters' => 'Insecure transport.',
            'recommendation' => 'Add HSTS.',
        ]);

        // Scan 3: HSTS still present (persistent), Canonical missing (newly detected), Alt missing returned (regressed)
        $scan3 = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'scan_life_3',
            'target_url' => 'https://lifecycle-test.com',
            'status' => 'completed',
            'overall_score' => 65,
            'completed_at' => now(),
        ]);
        ScanIssue::create([
            'scan_id' => $scan3->id,
            'rule_id' => $ruleHsts->id,
            'category' => 'security',
            'severity' => 'high',
            'title' => $ruleHsts->title,
            'why_it_matters' => 'Insecure transport.',
            'recommendation' => 'Add HSTS.',
        ]);
        ScanIssue::create([
            'scan_id' => $scan3->id,
            'rule_id' => $ruleCanonical->id,
            'category' => 'seo',
            'severity' => 'high',
            'title' => $ruleCanonical->title,
            'why_it_matters' => 'Indexation ambiguity.',
            'recommendation' => 'Add canonical.',
        ]);
        ScanIssue::create([
            'scan_id' => $scan3->id,
            'rule_id' => $ruleAlt->id,
            'category' => 'accessibility',
            'severity' => 'medium',
            'title' => $ruleAlt->title,
            'why_it_matters' => 'WCAG failure.',
            'recommendation' => 'Add alt.',
        ]);

        $response = $this->getJson('/api/websites/lifecycle-test.com/intelligence');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'domain' => 'lifecycle-test.com',
            'intelligence' => [
                'has_data' => true,
                'total_scans' => 3,
                'score_version' => '2.0',
                'issue_lifecycle' => [
                    'summary' => [
                        'new_count' => 1,
                        'persistent_count' => 1,
                        'regressed_count' => 1,
                    ],
                ],
            ],
        ]);

        $this->assertNotEmpty($response->json('intelligence.root_causes'));
        $this->assertFalse($response->json('benchmarks.available')); // Under 10 websites
    }

    public function test_benchmarks_activate_and_aggregate_when_minimum_cohort_size_met(): void
    {
        // Create 12 distinct websites with completed scans
        for ($i = 1; $i <= 12; $i++) {
            $site = Website::create([
                'domain' => "cohort-site-{$i}.com",
                'canonical_url' => "https://cohort-site-{$i}.com",
            ]);

            Scan::create([
                'website_id' => $site->id,
                'public_id' => "scan_cohort_{$i}",
                'target_url' => "https://cohort-site-{$i}.com",
                'status' => 'completed',
                'overall_score' => 60 + ($i * 2), // scores 62 to 84
                'score_seo' => 70,
                'score_security' => 80,
                'completed_at' => now(),
            ]);
        }

        $targetSite = Website::first();

        $response = $this->getJson("/api/websites/{$targetSite->domain}/intelligence");

        $response->assertStatus(200);
        $this->assertTrue($response->json('benchmarks.available'));
        $this->assertGreaterThanOrEqual(10, $response->json('benchmarks.cohort_size'));
        $this->assertArrayHasKey('median', $response->json('benchmarks.cohort_overall'));
        $this->assertArrayHasKey('p25', $response->json('benchmarks.cohort_overall'));
        $this->assertArrayHasKey('p75', $response->json('benchmarks.cohort_overall'));
    }
}
