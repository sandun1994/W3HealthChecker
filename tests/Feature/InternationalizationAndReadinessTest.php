<?php

namespace Tests\Feature;

use App\Services\Security\AuditLogger;
use App\Services\Security\RbacManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternationalizationAndReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_i18n_endpoint_delivers_localized_dictionaries(): void
    {
        // English
        $enResponse = $this->getJson('/api/i18n/en');
        $enResponse->assertStatus(200);
        $enResponse->assertJson([
            'locale' => 'en',
            'translations' => [
                'brand_name' => 'W3HealthChecker',
                'navigation' => [
                    'tools' => 'Free Tools',
                ],
            ],
        ]);

        // Spanish
        $esResponse = $this->getJson('/api/i18n/es');
        $esResponse->assertStatus(200);
        $esResponse->assertJson([
            'locale' => 'es',
            'translations' => [
                'brand_name' => 'W3HealthChecker',
                'navigation' => [
                    'tools' => 'Herramientas Gratuitas',
                ],
            ],
        ]);

        // Fallback for unknown locale
        $fallbackResponse = $this->getJson('/api/i18n/xx');
        $fallbackResponse->assertStatus(200);
        $this->assertEquals('en', $fallbackResponse->json('locale'));
    }

    public function test_html_document_renders_lang_attribute(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertStringContainsString('<html lang="en"', $response->getContent());
    }

    public function test_final_security_audit_verifications(): void
    {
        // 1. RBAC integrity check
        $this->assertTrue(RbacManager::can('owner', 'manage_organization'));
        $this->assertFalse(RbacManager::can('admin', 'manage_organization'));
        $this->assertFalse(RbacManager::can('member', 'manage_api_keys'));
        $this->assertFalse(RbacManager::can('viewer', 'run_scans'));

        // 2. Audit secret redaction integrity check
        $log = AuditLogger::log(
            action: 'test.credential.check',
            userId: null,
            metadata: [
                'password' => 'super_secret',
                'api_key' => 'token_123',
                'public_data' => 'safe_value',
            ]
        );

        $this->assertEquals('[REDACTED]', $log->metadata['password']);
        $this->assertEquals('[REDACTED]', $log->metadata['api_key']);
        $this->assertEquals('safe_value', $log->metadata['public_data']);
    }
}
