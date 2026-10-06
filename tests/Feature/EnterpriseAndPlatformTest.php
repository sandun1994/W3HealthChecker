<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Scan;
use App\Models\ScanIssue;
use App\Models\User;
use App\Services\Monitoring\AlertRuleEngine;
use App\Services\Security\AuditLogger;
use App\Services\Security\RbacManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseAndPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logger_records_event_and_redacts_sensitive_keys(): void
    {
        $user = User::factory()->create();

        $metadata = [
            'action_type' => 'credential_rotation',
            'password' => 'secret_password_123',
            'api_key' => 'w3_live_abcdef123456',
            'auth_token' => 'jwt.token.here',
            'safe_param' => 'production_cluster',
            'nested' => [
                'card' => '4111222233334444',
                'description' => 'rotated team key',
            ],
        ];

        $log = AuditLogger::log(
            action: 'api_key.rotated',
            userId: $user->id,
            resourceType: 'ApiKey',
            resourceId: '42',
            metadata: $metadata,
            ipAddress: '192.168.1.100'
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('api_key.rotated', $log->action);
        $this->assertEquals('192.168.1.100', $log->ip_address);

        // Verify redaction of secrets
        $this->assertEquals('[REDACTED]', $log->metadata['password']);
        $this->assertEquals('[REDACTED]', $log->metadata['api_key']);
        $this->assertEquals('[REDACTED]', $log->metadata['auth_token']);
        $this->assertEquals('production_cluster', $log->metadata['safe_param']);
        $this->assertEquals('[REDACTED]', $log->metadata['nested']['card']);
        $this->assertEquals('rotated team key', $log->metadata['nested']['description']);
    }

    public function test_rbac_manager_enforces_hierarchical_permissions(): void
    {
        // Owner
        $this->assertTrue(RbacManager::can('owner', 'manage_organization'));
        $this->assertTrue(RbacManager::can('owner', 'manage_billing'));
        $this->assertTrue(RbacManager::can('owner', 'run_scans'));
        $this->assertTrue(RbacManager::can('owner', 'manage_api_keys'));

        // Admin
        $this->assertFalse(RbacManager::can('admin', 'manage_organization'));
        $this->assertFalse(RbacManager::can('admin', 'manage_billing'));
        $this->assertTrue(RbacManager::can('admin', 'manage_members'));
        $this->assertTrue(RbacManager::can('admin', 'manage_websites'));

        // Member
        $this->assertFalse(RbacManager::can('member', 'manage_members'));
        $this->assertFalse(RbacManager::can('member', 'manage_api_keys'));
        $this->assertTrue(RbacManager::can('member', 'run_scans'));
        $this->assertTrue(RbacManager::can('member', 'view_reports'));

        // Viewer
        $this->assertFalse(RbacManager::can('viewer', 'run_scans'));
        $this->assertFalse(RbacManager::can('viewer', 'manage_websites'));
        $this->assertTrue(RbacManager::can('viewer', 'view_reports'));
        $this->assertTrue(RbacManager::can('viewer', 'export_reports'));

        // Invalid role / permission
        $this->assertFalse(RbacManager::can('unknown_role', 'view_reports'));
        $this->assertFalse(RbacManager::can('viewer', 'non_existent_permission'));
    }

    public function test_alert_rule_engine_triggers_expected_alerts(): void
    {
        $engine = new AlertRuleEngine();

        $website = \App\Models\Website::create([
            'domain' => 'alert-test.com',
            'canonical_url' => 'https://alert-test.com',
        ]);

        $previousScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://alert-test.com',
            'public_id' => 'prev_scan_123',
            'overall_score' => 88,
            'score_security' => 95,
            'score_seo' => 85,
            'status' => 'completed',
        ]);

        $currentScan = Scan::create([
            'website_id' => $website->id,
            'target_url' => 'https://alert-test.com',
            'public_id' => 'curr_scan_456',
            'overall_score' => 64, // < 70 threshold
            'score_security' => 75, // 95 - 75 = 20 drop >= 10
            'score_seo' => 80,
            'status' => 'completed',
        ]);

        \App\Models\ScanMetric::create([
            'scan_id' => $currentScan->id,
            'ssl_data' => [
                'days_remaining' => 14, // <= 30 threshold
            ],
        ]);

        $rule = \App\Models\AuditRule::firstOrCreate(
            ['id' => 'sec_ssl_critical'],
            [
                'category' => 'security',
                'title' => 'Critical SSL Vulnerability',
                'severity' => 'critical',
                'default_weight' => 10.0,
                'impact_description' => 'Compromises encrypted communication.',
                'why_it_matters' => 'Users can have traffic intercepted.',
                'fix_guidance' => 'Renew or configure modern TLS certificates.',
                'is_active' => true,
            ]
        );

        ScanIssue::create([
            'scan_id' => $currentScan->id,
            'rule_id' => $rule->id,
            'category' => 'security',
            'severity' => 'critical',
            'confidence' => 'high',
            'title' => 'Critical SSL Vulnerability',
            'why_it_matters' => 'Leaves website exposed to eavesdropping.',
            'recommendation' => 'Renew valid SSL certificate immediately.',
        ]);

        $alerts = $engine->evaluate($currentScan, $previousScan);

        $this->assertCount(4, $alerts);

        $alertTypes = collect($alerts)->pluck('type')->toArray();
        $this->assertContains('SCORE_BELOW_THRESHOLD', $alertTypes);
        $this->assertContains('PILLAR_SCORE_DROPPED', $alertTypes);
        $this->assertContains('SSL_EXPIRING_SOON', $alertTypes);
        $this->assertContains('CRITICAL_ISSUES_FOUND', $alertTypes);
    }

    public function test_openapi_specification_file_exists_and_is_valid_json(): void
    {
        $path = public_path('openapi.json');
        $this->assertFileExists($path);

        $json = json_decode(file_get_contents($path), true);
        $this->assertNotNull($json);
        $this->assertEquals('3.0.3', $json['openapi']);
        $this->assertArrayHasKey('info', $json);
        $this->assertArrayHasKey('paths', $json);
        $this->assertArrayHasKey('/scans', $json['paths']);
        $this->assertArrayHasKey('/reports/{id}', $json['paths']);
        $this->assertArrayHasKey('/monitoring', $json['paths']);
    }
}
