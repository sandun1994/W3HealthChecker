<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Scan;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndWorkspaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_default_personal_workspace_project()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Founder',
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'jane@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Personal Workspace',
        ]);
    }

    public function test_user_login_validates_credentials_and_handles_bad_password()
    {
        $user = User::create([
            'name' => 'Alex Developer',
            'email' => 'alex@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // Failed attempt
        $badResponse = $this->postJson('/api/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'WrongPassword!',
        ]);
        $badResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Successful attempt
        $goodResponse = $this->postJson('/api/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'CorrectPassword123!',
        ]);
        $goodResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'alex@example.com');

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_create_projects_and_add_monitored_websites()
    {
        $user = User::create([
            'name' => 'Sarah SiteAdmin',
            'email' => 'sarah@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Client Portfolios',
        ]);

        $this->actingAs($user);

        // Add website to project
        $response = $this->postJson('/api/workspace/websites', [
            'url' => 'https://example.com',
            'project_id' => $project->id,
            'monitoring_frequency' => 'weekly',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('websites', [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'domain' => 'example.com',
        ]);

        $this->assertDatabaseHas('monitored_websites', [
            'user_id' => $user->id,
            'schedule' => 'weekly',
        ]);
    }

    public function test_workspace_rejects_ssrf_malicious_urls()
    {
        $user = User::create([
            'name' => 'Security Tester',
            'email' => 'sec@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/workspace/websites', [
            'url' => 'http://127.0.0.1:8080/admin',
            'monitoring_frequency' => 'daily',
        ]);

        $response->assertStatus(422);
    }

    public function test_workspace_overview_aggregates_stats()
    {
        $user = User::create([
            'name' => 'Stats User',
            'email' => 'stats@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Corporate Sites',
        ]);

        $website = Website::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'domain' => 'example.com',
            'scheme' => 'https',
            'canonical_url' => 'https://example.com',
        ]);

        Scan::create([
            'website_id' => $website->id,
            'user_id' => $user->id,
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'target_url' => 'https://example.com',
            'final_url' => 'https://example.com',
            'status' => 'completed',
            'overall_score' => 88,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(58),
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/workspace/overview');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('overview.total_websites', 1)
            ->assertJsonPath('overview.total_projects', 1)
            ->assertJsonPath('overview.total_scans', 1)
            ->assertJsonPath('overview.average_score', 88);
    }

    public function test_consensual_local_history_sync_links_scans_to_user_workspace()
    {
        $user = User::create([
            'name' => 'Sync User',
            'email' => 'sync@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $anonymousWebsite = Website::create([
            'domain' => 'example.com',
            'scheme' => 'https',
            'canonical_url' => 'https://example.com',
            'user_id' => null,
        ]);

        $scan = Scan::create([
            'website_id' => $anonymousWebsite->id,
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'target_url' => 'https://example.com',
            'final_url' => 'https://example.com',
            'status' => 'completed',
            'overall_score' => 92,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinute(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/workspace/sync-history', [
            'public_ids' => [$scan->public_id],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('synced_count', 1);

        $this->assertDatabaseHas('websites', [
            'id' => $anonymousWebsite->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_privacy_account_deletion_anonymizes_or_scrubs_personal_records()
    {
        $user = User::create([
            'name' => 'Delete Me',
            'email' => 'delete@example.com',
            'password' => Hash::make('DeletePass123!'),
        ]);

        Project::create([
            'user_id' => $user->id,
            'name' => 'Temp Project',
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/auth/account', [
            'password' => 'DeletePass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', [
            'email' => 'delete@example.com',
        ]);

        $this->assertDatabaseMissing('projects', [
            'user_id' => $user->id,
        ]);
    }
}
