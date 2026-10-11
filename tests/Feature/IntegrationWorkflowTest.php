<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Integration;
use App\Models\User;
use App\Services\Integrations\IntegrationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_integrations_with_redacted_secrets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/integrations', [
            'provider' => 'slack',
            'name' => 'Engineering Alerts',
            'credentials' => [
                'webhook_url' => 'https://hooks.slack.com/services/T00/B00/X123456789',
            ],
            'settings' => [
                'channel' => '#dev-health',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'integration' => [
                'provider' => 'slack',
                'name' => 'Engineering Alerts',
                'status' => 'connected',
            ],
        ]);

        // Verify credentials in DB are encrypted
        $integration = Integration::first();
        $this->assertNotNull($integration);
        $this->assertNotEquals('https://hooks.slack.com/services/T00/B00/X123456789', $integration->encrypted_credentials);
        $this->assertEquals(['webhook_url' => 'https://hooks.slack.com/services/T00/B00/X123456789'], $integration->getCredentials());

        // Verify index endpoint redacts the secret token
        $listResponse = $this->actingAs($user)->getJson('/api/integrations');
        $listResponse->assertStatus(200);
        $previewUrl = $listResponse->json('integrations.0.credentials_preview.webhook_url');
        $this->assertStringContainsString('••••••••', $previewUrl);
        $this->assertStringNotContainsString('X123456789', $previewUrl);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'integration.created',
        ]);
    }

    public function test_cross_tenant_isolation_prevents_unauthorized_access(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $integrationA = Integration::create([
            'user_id' => $userA->id,
            'provider' => 'webhook',
            'name' => 'Tenant A Webhook',
            'encrypted_credentials' => 'dummy',
            'status' => 'connected',
        ]);

        // User B tries to delete User A's integration
        $response = $this->actingAs($userB)->deleteJson("/api/integrations/{$integrationA->id}");
        $response->assertStatus(404);

        // User B tries to test User A's integration
        $testResponse = $this->actingAs($userB)->postJson("/api/integrations/{$integrationA->id}/test");
        $testResponse->assertStatus(404);

        $this->assertDatabaseHas('integrations', ['id' => $integrationA->id]);
    }

    public function test_ssrf_malicious_webhook_destination_is_rejected(): void
    {
        $user = User::factory()->create();

        $integration = new Integration([
            'user_id' => $user->id,
            'provider' => 'webhook',
            'name' => 'SSRF Attempt',
            'status' => 'connected',
        ]);
        $integration->setCredentials([
            'webhook_url' => 'http://169.254.169.254/latest/meta-data',
            'secret' => 'super_secret_signing_key',
        ]);
        $integration->save();

        $registry = app(IntegrationRegistry::class);
        $result = $registry->test($integration);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Restricted or invalid webhook destination', $result['error']);
    }

    public function test_generic_webhook_dispatch_attaches_hmac_signature_and_headers(): void
    {
        Http::fake([
            'https://example.com/webhooks' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();

        $integration = new Integration([
            'user_id' => $user->id,
            'provider' => 'webhook',
            'name' => 'External Partner API',
            'status' => 'connected',
        ]);
        $integration->setCredentials([
            'webhook_url' => 'https://example.com/webhooks',
            'secret' => 'signing_secret_key_123',
        ]);
        $integration->save();

        $registry = app(IntegrationRegistry::class);
        $results = $registry->dispatchToUser($user->id, 'scan.completed', [
            'domain' => 'client-site.com',
            'overall_score' => 92,
        ]);

        $this->assertTrue($results[$integration->id]['success']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-W3-Signature') &&
                   $request->hasHeader('X-W3-Timestamp') &&
                   $request->hasHeader('X-W3-Event') &&
                   $request->hasHeader('X-W3-Event-Id') &&
                   $request['event'] === 'scan.completed';
        });
    }
}
