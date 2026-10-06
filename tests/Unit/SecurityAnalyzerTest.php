<?php

namespace Tests\Unit;

use App\Services\Pillars\SecurityAnalyzer;
use PHPUnit\Framework\TestCase;

class SecurityAnalyzerTest extends TestCase
{
    protected SecurityAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new SecurityAnalyzer();
    }

    public function test_flags_missing_https_and_server_exposure(): void
    {
        $fetchData = [
            'final_url' => 'http://example.com/insecure',
            'ssl' => ['is_https' => false],
            'headers' => [
                'server' => ['Apache/2.4.41 (Ubuntu)'],
            ],
        ];

        $result = $this->analyzer->analyze($fetchData);

        $this->assertIsArray($result);
        $this->assertLessThan(70, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertContains('sec_https_missing', $ruleIds);
        $this->assertContains('sec_csp_missing', $ruleIds);
        $this->assertContains('sec_x_frame_missing', $ruleIds);
        $this->assertContains('sec_x_content_type_missing', $ruleIds);
        $this->assertContains('sec_referrer_policy_missing', $ruleIds);
        $this->assertContains('sec_permissions_policy_missing', $ruleIds);
        $this->assertContains('sec_server_exposed', $ruleIds);
    }

    public function test_flags_missing_hsts_on_https(): void
    {
        $fetchData = [
            'final_url' => 'https://example.com/missing-hsts',
            'ssl' => ['is_https' => true, 'valid' => true, 'days_until_expiration' => 60],
            'headers' => [
                // missing strict-transport-security
                'x-frame-options' => 'SAMEORIGIN',
                'x-content-type-options' => 'nosniff',
            ],
        ];

        $result = $this->analyzer->analyze($fetchData);
        $ruleIds = array_column($result['issues'], 'rule_id');

        $this->assertContains('sec_hsts_missing', $ruleIds);
    }

    public function test_passes_when_defensive_headers_are_present(): void
    {
        $fetchData = [
            'final_url' => 'https://example.com/secure',
            'ssl' => [
                'is_https' => true,
                'valid' => true,
                'days_until_expiration' => 90,
            ],
            'headers' => [
                'strict-transport-security' => ['max-age=31536000; includeSubDomains'],
                'content-security-policy' => ["default-src 'self'"],
                'x-frame-options' => ['DENY'],
                'x-content-type-options' => ['nosniff'],
                'referrer-policy' => ['strict-origin-when-cross-origin'],
                'permissions-policy' => ['camera=(), microphone=()'],
            ],
        ];

        $result = $this->analyzer->analyze($fetchData);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(95, $result['score']);

        $ruleIds = array_column($result['issues'], 'rule_id');
        $this->assertNotContains('sec_missing_hsts', $ruleIds);
        $this->assertNotContains('sec_missing_csp', $ruleIds);
        $this->assertNotContains('sec_missing_x_frame', $ruleIds);
    }

    public function test_flags_passive_sensitive_file_exposure(): void
    {
        $fetchData = [
            'final_url' => 'https://example.com/',
            'ssl' => ['is_https' => true, 'valid' => true, 'days_until_expiration' => 60],
            'headers' => [
                'strict-transport-security' => ['max-age=31536000'],
                'x-frame-options' => ['SAMEORIGIN'],
                'x-content-type-options' => ['nosniff'],
            ],
        ];

        $auxiliary = [
            'sensitive_exposures' => [
                ['path' => '/.env', 'status' => 200],
            ],
        ];

        $result = $this->analyzer->analyze($fetchData, $auxiliary);
        $ruleIds = array_column($result['issues'], 'rule_id');

        $this->assertContains('sec_sensitive_file_exposure', $ruleIds);
    }
}
