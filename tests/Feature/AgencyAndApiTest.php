<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\Scan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgencyAndApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_client_lifecycle()
    {
        $user = User::create([
            'name' => 'Agency Owner',
            'email' => 'owner@agency.com',
            'password' => Hash::make('Password123!'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'agency',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // 1. Create client
        $response = $this->postJson('/api/agency/clients', [
            'name' => 'Apex Retailers',
            'company' => 'Apex Group',
            'email' => 'contact@apex.com',
            'white_label_settings' => [
                'agency_name' => 'Stellar Media',
                'brand_color' => '#6366f1',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('client.name', 'Apex Retailers');

        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'name' => 'Apex Retailers',
        ]);

        $clientId = $response->json('client.id');

        // 2. List clients
        $listResponse = $this->getJson('/api/agency/clients');
        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'clients');

        // 3. Delete client
        $delResponse = $this->deleteJson("/api/agency/clients/{$clientId}");
        $delResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clients', [
            'id' => $clientId,
        ]);
    }

    public function test_api_key_generation_and_plan_authorization()
    {
        $freeUser = User::create([
            'name' => 'Free Dev',
            'email' => 'free@dev.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($freeUser);

        // Free user cannot create API key
        $failResponse = $this->postJson('/api/agency/api-keys', [
            'name' => 'Test Key',
        ]);
        $failResponse->assertStatus(403)
            ->assertJsonPath('upgrade_required', true);

        // Upgrade user to Agency
        Subscription::create([
            'user_id' => $freeUser->id,
            'plan' => 'agency',
            'status' => 'active',
        ]);

        // Key creation now succeeds
        $keyResponse = $this->postJson('/api/agency/api-keys', [
            'name' => 'CI Pipeline Token',
        ]);

        $keyResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['api_key' => ['id', 'name', 'token', 'key_prefix']]);

        $rawToken = $keyResponse->json('api_key.token');
        $this->assertStringStartsWith('w3_live_', $rawToken);

        // Raw token is NOT stored as plain text
        $this->assertDatabaseMissing('api_keys', [
            'key_hash' => $rawToken,
        ]);
    }

    public function test_rest_api_v1_authentication_and_trigger_scan()
    {
        $user = User::create([
            'name' => 'API Client',
            'email' => 'api@client.com',
            'password' => Hash::make('Password123!'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'agency',
            'status' => 'active',
        ]);

        [$apiKey, $rawToken] = ApiKey::generate($user, 'Test Token');

        // Missing token fails with 401
        $noAuthRes = $this->postJson('/api/v1/scans', ['url' => 'https://example.com']);
        $noAuthRes->assertStatus(401);

        // Valid Bearer token triggers scan
        $scanRes = $this->postJson('/api/v1/scans', [
            'url' => 'https://example.com',
        ], [
            'Authorization' => 'Bearer ' . $rawToken,
        ]);

        $scanRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['scan' => ['id', 'url', 'status', 'overall_score']]);

        $scanId = $scanRes->json('scan.id');

        // Fetch scan summary
        $getRes = $this->getJson("/api/v1/scans/{$scanId}", [
            'Authorization' => 'Bearer ' . $rawToken,
        ]);
        $getRes->assertStatus(200)
            ->assertJsonPath('scan.id', $scanId)
            ->assertJsonPath('scan.domain', 'example.com');

        // Fetch full 7-pillar report
        $reportRes = $this->getJson("/api/v1/reports/{$scanId}", [
            'Authorization' => 'Bearer ' . $rawToken,
        ]);
        $reportRes->assertStatus(200)
            ->assertJsonPath('report.id', $scanId)
            ->assertJsonStructure(['report' => ['scores', 'issues', 'recommendations']]);

        // Key usage was incremented
        $apiKey->refresh();
        $this->assertGreaterThanOrEqual(1, $apiKey->requests_count);
        $this->assertNotNull($apiKey->last_used_at);
    }

    public function test_bulk_scan_enforces_url_limits()
    {
        $user = User::create([
            'name' => 'Bulk Scanner',
            'email' => 'bulk@scanner.com',
            'password' => Hash::make('Password123!'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'agency',
            'status' => 'active',
        ]);

        [$apiKey, $rawToken] = ApiKey::generate($user, 'Bulk Key');

        // Batch of valid URLs
        $response = $this->postJson('/api/v1/scans/bulk', [
            'urls' => [
                'https://example.com',
                'https://iana.org',
            ],
        ], [
            'Authorization' => 'Bearer ' . $rawToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_submitted', 2)
            ->assertJsonPath('processed', 2);

        // Batch exceeding limit (> 10 URLs) is rejected with 422
        $tooManyUrls = array_fill(0, 12, 'https://example.com');
        $overLimitRes = $this->postJson('/api/v1/scans/bulk', [
            'urls' => $tooManyUrls,
        ], [
            'Authorization' => 'Bearer ' . $rawToken,
        ]);

        $overLimitRes->assertStatus(422);
    }

    public function test_webhook_endpoint_registration_and_hmac_signing()
    {
        $user = User::create([
            'name' => 'Webhook User',
            'email' => 'webhook@test.com',
            'password' => Hash::make('Password123!'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'agency',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // Register webhook
        $response = $this->postJson('/api/agency/webhooks', [
            'url' => 'https://client-agency.com/api/w3-hook',
            'events' => ['scan.completed', 'score.changed'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('webhook.url', 'https://client-agency.com/api/w3-hook');

        $webhookId = $response->json('webhook.id');
        $webhook = WebhookEndpoint::findOrFail($webhookId);

        // Test HMAC signature generation
        $payload = json_encode(['event' => 'scan.completed', 'score' => 88]);
        $signature = $webhook->signPayload($payload);

        $this->assertStringStartsWith('sha256=', $signature);

        $expectedSig = 'sha256=' . hash_hmac('sha256', $payload, $webhook->secret);
        $this->assertEquals($expectedSig, $signature);
    }
}
