<?php

namespace Tests\Feature;

use App\Models\MonitoredWebsite;
use App\Models\Scan;
use App\Models\ScanChange;
use App\Models\Website;
use App\Services\Monitoring\AlertNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringFlowTest extends TestCase
{
    use RefreshDatabase;
    public function test_subscribes_website_to_monitoring_with_safe_ssrf_checks(): void
    {
        // 1. Valid public website (example.com resolves publicly)
        $response = $this->postJson('/api/monitoring', [
            'url' => 'https://example.com',
            'schedule' => 'weekly',
            'alert_email' => 'dev@example.com',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'monitored_website' => [
                'domain' => 'example.com',
                'schedule' => 'weekly',
                'alert_email' => 'dev@example.com',
            ],
        ]);

        $this->assertDatabaseHas('monitored_websites', [
            'schedule' => 'weekly',
            'alert_email' => 'dev@example.com',
            'is_active' => true,
        ]);

        // 2. SSRF malicious URL must be rejected
        $badResponse = $this->postJson('/api/monitoring', [
            'url' => 'http://169.254.169.254/latest/meta-data',
            'schedule' => 'daily',
        ]);

        $badResponse->assertStatus(422);
        $badResponse->assertJson(['success' => false]);
    }

    public function test_lists_monitored_websites(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'test-list-domain.com'],
            ['scheme' => 'https', 'canonical_url' => 'https://test-list-domain.com']
        );

        MonitoredWebsite::updateOrCreate(
            ['website_id' => $website->id],
            [
                'schedule' => 'daily',
                'alert_email' => 'admin@test-list-domain.com',
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $response = $this->getJson('/api/monitoring');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $domains = array_column($response->json('monitored_websites'), 'domain');
        $this->assertContains('test-list-domain.com', $domains);
    }

    public function test_retrieves_monitored_website_details_and_changes(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'details-test.org'],
            ['scheme' => 'https', 'canonical_url' => 'https://details-test.org']
        );

        $monitored = MonitoredWebsite::updateOrCreate(
            ['website_id' => $website->id],
            [
                'schedule' => 'daily',
                'alert_email' => 'team@details-test.org',
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $scan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://details-test.org',
            'public_id' => 'detail_scan_' . uniqid(),
            'overall_score' => 84,
            'status' => 'completed',
        ]);

        ScanChange::create([
            'website_id' => $website->id,
            'current_scan_id' => $scan->id,
            'change_type' => 'title_changed',
            'category' => 'seo',
            'severity' => 'medium',
            'title' => 'Title Tag Updated',
            'description' => 'Title tag modified on homepage.',
        ]);

        $response = $this->getJson("/api/monitoring/{$monitored->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals('details-test.org', $response->json('monitored_website.website.domain'));
        $this->assertCount(1, $response->json('recent_changes'));
    }

    public function test_deletes_monitoring(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'to-delete.org'],
            ['scheme' => 'https', 'canonical_url' => 'https://to-delete.org']
        );

        $monitored = MonitoredWebsite::create([
            'website_id' => $website->id,
            'schedule' => 'daily',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/monitoring/{$monitored->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('monitored_websites', ['id' => $monitored->id]);
    }

    public function test_retrieves_historical_scans_and_score_trends(): void
    {
        $domain = 'trend-sample.org';
        $website = Website::firstOrCreate(
            ['domain' => $domain],
            ['scheme' => 'https', 'canonical_url' => "https://{$domain}"]
        );

        $scan1 = Scan::create([
            'website_id' => $website->id,
            'target_url' => "https://{$domain}",
            'public_id' => 'trend_scan_1_' . uniqid(),
            'overall_score' => 75,
            'score_seo' => 80,
            'score_security' => 70,
            'status' => 'completed',
            'completed_at' => now()->subDays(7),
        ]);

        $scan2 = Scan::create([
            'website_id' => $website->id,
            'target_url' => "https://{$domain}",
            'public_id' => 'trend_scan_2_' . uniqid(),
            'overall_score' => 85,
            'score_seo' => 90,
            'score_security' => 85,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // History endpoint
        $historyRes = $this->getJson("/api/websites/{$domain}/history");
        $historyRes->assertStatus(200);
        $this->assertCount(2, $historyRes->json('scans'));

        // Trend endpoint
        $trendRes = $this->getJson("/api/websites/{$domain}/trend");
        $trendRes->assertStatus(200);
        $this->assertEquals(2, $trendRes->json('total_scans'));
        $this->assertEquals(10, $trendRes->json('deltas.overall')); // 85 - 75 = +10
        $this->assertEquals(10, $trendRes->json('deltas.seo')); // 90 - 80 = +10
    }

    public function test_alert_notification_service_formats_and_persists_alerts(): void
    {
        $website = Website::firstOrCreate(
            ['domain' => 'alert-test.com'],
            ['scheme' => 'https', 'canonical_url' => 'https://alert-test.com']
        );

        $monitored = MonitoredWebsite::create([
            'website_id' => $website->id,
            'schedule' => 'daily',
            'alert_email' => 'ops@alert-test.com',
            'notify_on_critical_issues' => true,
            'is_active' => true,
        ]);

        $scan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://alert-test.com',
            'public_id' => 'alert_pubid_' . uniqid(),
            'overall_score' => 65,
            'status' => 'completed',
        ]);

        $changes = [
            [
                'change_type' => 'overall_score_dropped_critical',
                'category' => 'overall',
                'severity' => 'critical',
                'title' => 'Critical Health Score Drop',
                'description' => 'Overall score plummeted by 20 points.',
            ],
            [
                'change_type' => 'security_header_removed_strict-transport-security',
                'category' => 'security',
                'severity' => 'critical',
                'title' => 'HSTS Encryption Header Removed',
                'description' => 'HSTS header missing.',
            ],
        ];

        $service = new AlertNotificationService();
        $this->assertTrue($service->shouldAlert($monitored, $scan, $changes));

        $alert = $service->dispatchAlert($monitored, $scan, $changes);

        $this->assertNotNull($alert);
        $this->assertEquals('ops@alert-test.com', $alert->recipient);
        $this->assertEquals('critical', $alert->severity);
        $this->assertStringContainsString('CRITICAL ALERT', $alert->subject);
        $this->assertDatabaseHas('monitoring_alerts', [
            'id' => $alert->id,
            'recipient' => 'ops@alert-test.com',
            'severity' => 'critical',
        ]);
    }
}
