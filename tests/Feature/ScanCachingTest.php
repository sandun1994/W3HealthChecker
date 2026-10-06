<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanCachingTest extends TestCase
{
    public function test_blocks_ssrf_and_private_ip_requests_via_api(): void
    {
        $blockedUrls = [
            'http://127.0.0.1',
            'http://localhost:8000',
            'http://169.254.169.254/latest/meta-data',
            'http://192.168.1.1',
            'http://10.0.0.1',
            'ftp://example.com',
            'file:///etc/passwd',
        ];

        foreach ($blockedUrls as $url) {
            $response = $this->postJson('/api/scan', ['url' => $url]);
            $response->assertStatus(422);
            $response->assertJson(['success' => false]);
            $this->assertNotEmpty($response->json('message'));
        }
    }

    public function test_reuses_cached_completed_scan_within_cache_period(): void
    {
        $domain = 'example.com';
        $targetUrl = "https://{$domain}/";

        $website = Website::firstOrCreate(
            ['domain' => $domain],
            ['scheme' => 'https', 'canonical_url' => $targetUrl]
        );

        $scan = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'w3_cache_' . uniqid(),
            'target_url' => $targetUrl,
            'status' => 'completed',
            'status_stage' => 'Completed',
            'progress_percentage' => 100,
            'overall_score' => 88,
            'status_label' => 'Good',
            'score_seo' => 90,
            'score_performance' => 85,
            'score_security' => 85,
            'score_accessibility' => 85,
            'score_mobile' => 90,
            'score_technical' => 90,
            'score_ai_readiness' => 85,
            'completed_at' => now()->subMinutes(5),
        ]);

        // Submit same URL without force flag
        $response = $this->postJson('/api/scan', [
            'url' => "https://{$domain}", // will normalize to https://{$domain}/
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'cached' => true,
            'public_id' => $scan->public_id,
        ]);
    }

    public function test_force_or_rescan_bypasses_cache(): void
    {
        $domain = 'example.com';
        $targetUrl = "https://{$domain}/";

        $website = Website::firstOrCreate(
            ['domain' => $domain],
            ['scheme' => 'https', 'canonical_url' => $targetUrl]
        );

        $scan = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'w3_rescan_' . uniqid(),
            'target_url' => $targetUrl,
            'status' => 'completed',
            'status_stage' => 'Completed',
            'progress_percentage' => 100,
            'overall_score' => 88,
            'completed_at' => now()->subMinutes(5),
        ]);

        // Submit with force flag: should NOT return cached result
        config(['queue.default' => 'database']);

        $response = $this->postJson('/api/scan', [
            'url' => $targetUrl,
            'force' => true,
            'sync' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'cached' => false,
        ]);
        $this->assertNotEquals($scan->public_id, $response->json('public_id'));
    }
}
