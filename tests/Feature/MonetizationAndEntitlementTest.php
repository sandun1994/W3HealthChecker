<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Services\Billing\FeatureGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MonetizationAndEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_plans_catalog_returns_all_plans()
    {
        $response = $this->getJson('/api/billing/plans');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'plans')
            ->assertJsonPath('plans.0.id', 'free')
            ->assertJsonPath('plans.1.id', 'pro')
            ->assertJsonPath('plans.2.id', 'agency');
    }

    public function test_free_plan_enforces_monitoring_and_project_limits()
    {
        $user = User::create([
            'name' => 'Free Tier User',
            'email' => 'free@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $defaultProject = Project::create([
            'user_id' => $user->id,
            'name' => 'Default Project',
        ]);

        $this->actingAs($user);

        // 1. Attempt to create a 2nd project on Free plan (Limit is 1)
        $projectResponse = $this->postJson('/api/workspace/projects', [
            'name' => 'Second Project',
        ]);

        $projectResponse->assertStatus(403)
            ->assertJsonPath('upgrade_required', true);

        // 2. Add first website with weekly monitoring (should succeed)
        $siteResponse1 = $this->postJson('/api/workspace/websites', [
            'url' => 'https://example.com',
            'project_id' => $defaultProject->id,
            'monitoring_frequency' => 'weekly',
        ]);
        $siteResponse1->assertStatus(201);

        // 3. Attempt to add a 2nd monitored website on Free plan (Limit is 1)
        $siteResponse2 = $this->postJson('/api/workspace/websites', [
            'url' => 'https://iana.org',
            'project_id' => $defaultProject->id,
            'monitoring_frequency' => 'weekly',
        ]);

        $siteResponse2->assertStatus(403)
            ->assertJsonPath('upgrade_required', true);

        // 4. Attempt daily monitoring on Free plan (requires Pro)
        $dailyResponse = $this->postJson('/api/monitoring', [
            'url' => 'https://example.com',
            'schedule' => 'daily',
        ]);
        $dailyResponse->assertStatus(403)
            ->assertJsonPath('upgrade_required', true);
    }

    public function test_user_can_upgrade_to_pro_and_unlocks_expanded_limits()
    {
        $user = User::create([
            'name' => 'Upgrading User',
            'email' => 'upgrade@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        Project::create([
            'user_id' => $user->id,
            'name' => 'Project 1',
        ]);

        $this->actingAs($user);

        // Upgrade to Pro
        $subResponse = $this->postJson('/api/billing/subscribe', [
            'plan' => 'pro',
        ]);

        $subResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('usage.plan', 'pro');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan' => 'pro',
            'status' => 'active',
        ]);

        // Creating second project now succeeds under Pro (limit is 5)
        $projectResponse = $this->postJson('/api/workspace/projects', [
            'name' => 'Project 2',
        ]);
        $projectResponse->assertStatus(201);

        // Feature gate checks
        $gate = app(FeatureGate::class);
        $this->assertTrue($gate->canExportPdf($user));
        $this->assertFalse($gate->canUseApi($user)); // API requires Agency
    }

    public function test_user_can_upgrade_to_agency_and_unlocks_api_and_white_label()
    {
        $user = User::create([
            'name' => 'Agency Founder',
            'email' => 'agency@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($user);

        $subResponse = $this->postJson('/api/billing/subscribe', [
            'plan' => 'agency',
        ]);

        $subResponse->assertStatus(200)
            ->assertJsonPath('usage.plan', 'agency');

        $gate = app(FeatureGate::class);
        $this->assertTrue($gate->canUseApi($user));
        $this->assertTrue($gate->canUseWhiteLabel($user));
        $this->assertTrue($gate->canInviteMembers($user));
    }

    public function test_subscription_cancellation_and_resumption()
    {
        $user = User::create([
            'name' => 'Subscriber User',
            'email' => 'sub@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'pro',
            'status' => 'active',
            'renews_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        // Cancel
        $cancelResponse = $this->postJson('/api/billing/cancel');
        $cancelResponse->assertStatus(200)
            ->assertJsonPath('subscription.status', 'cancelled');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        // Resume
        $resumeResponse = $this->postJson('/api/billing/resume');
        $resumeResponse->assertStatus(200)
            ->assertJsonPath('subscription.status', 'active');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_billing_webhook_validates_signature()
    {
        // Bad signature
        $badResponse = $this->postJson('/api/billing/webhook', [
            'type' => 'invoice.paid',
        ], [
            'X-Billing-Signature' => 'short',
        ]);
        $badResponse->assertStatus(400);

        // Valid signature
        $goodResponse = $this->postJson('/api/billing/webhook', [
            'type' => 'customer.subscription.updated',
        ], [
            'X-Billing-Signature' => 'sig_valid_test_token_12345',
        ]);
        $goodResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
