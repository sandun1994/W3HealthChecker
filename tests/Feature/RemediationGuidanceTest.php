<?php

namespace Tests\Feature;

use App\Models\AuditRule;
use App\Models\Scan;
use App\Models\ScanIssue;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemediationGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_retrieves_structured_remediation_guidance_and_code_snippets(): void
    {
        $website = Website::create([
            'domain' => 'remediation-target.com',
            'canonical_url' => 'https://remediation-target.com',
        ]);

        $scan = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'scan_remed_1',
            'target_url' => 'https://remediation-target.com',
            'status' => 'completed',
        ]);

        $rule = AuditRule::firstOrCreate(['id' => 'sec_hsts_missing'], [
            'category' => 'security',
            'title' => 'Missing Strict-Transport-Security Header',
            'severity' => 'high',
            'impact_description' => 'Susceptible to man-in-the-middle attacks.',
            'why_it_matters' => 'Browsers may attempt unencrypted HTTP connections.',
            'fix_guidance' => 'Send HSTS header on all HTTPS responses.',
        ]);

        $issue = ScanIssue::create([
            'scan_id' => $scan->id,
            'rule_id' => $rule->id,
            'category' => 'security',
            'severity' => 'high',
            'title' => $rule->title,
            'why_it_matters' => $rule->why_it_matters,
            'recommendation' => 'Enable HSTS with max-age=31536000.',
        ]);

        $response = $this->getJson("/api/remediation/{$issue->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'guidance' => [
                'issue_id' => $issue->id,
                'rule_id' => 'sec_hsts_missing',
                'category' => 'security',
                'severity' => 'high',
                'recommended_fix' => 'Enable HSTS with max-age=31536000.',
            ],
        ]);

        $this->assertNotEmpty($response->json('guidance.code_examples'));
        $this->assertNotEmpty($response->json('guidance.verification_procedure'));
    }

    public function test_explains_finding_adapting_to_skill_levels(): void
    {
        $website = Website::create([
            'domain' => 'remediation-target.com',
            'canonical_url' => 'https://remediation-target.com',
        ]);

        $scan = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'scan_remed_2',
            'target_url' => 'https://remediation-target.com',
            'status' => 'completed',
        ]);

        $rule = AuditRule::firstOrCreate(['id' => 'seo_canonical_missing'], [
            'category' => 'seo',
            'title' => 'Missing Canonical URL',
            'severity' => 'medium',
            'impact_description' => 'Duplicate content signals.',
            'why_it_matters' => 'Search engines cannot determine canonical version.',
            'fix_guidance' => 'Add link rel=canonical.',
        ]);

        $issue = ScanIssue::create([
            'scan_id' => $scan->id,
            'rule_id' => $rule->id,
            'category' => 'seo',
            'severity' => 'medium',
            'title' => $rule->title,
            'why_it_matters' => $rule->why_it_matters,
            'recommendation' => 'Add rel=canonical tag.',
        ]);

        // Beginner explanation
        $begRes = $this->postJson("/api/remediation/{$issue->id}/explain", [
            'skill_level' => 'beginner',
        ]);
        $begRes->assertStatus(200);
        $this->assertEquals('beginner', $begRes->json('explanation.skill_level'));
        $this->assertStringContainsString('Your website has an issue', $begRes->json('explanation.plain_language_summary'));

        // Advanced explanation
        $advRes = $this->postJson("/api/remediation/{$issue->id}/explain", [
            'skill_level' => 'advanced',
        ]);
        $advRes->assertStatus(200);
        $this->assertEquals('advanced', $advRes->json('explanation.skill_level'));
        $this->assertStringContainsString('Evaluation of rule', $advRes->json('explanation.plain_language_summary'));
    }

    public function test_updates_workflow_status(): void
    {
        $website = Website::create([
            'domain' => 'remediation-target.com',
            'canonical_url' => 'https://remediation-target.com',
        ]);

        $scan = Scan::create([
            'website_id' => $website->id,
            'public_id' => 'scan_remed_3',
            'target_url' => 'https://remediation-target.com',
            'status' => 'completed',
        ]);

        $rule = AuditRule::firstOrCreate(['id' => 'a11y_missing_alt'], [
            'category' => 'accessibility',
            'title' => 'Missing Alt Attributes',
            'severity' => 'medium',
            'impact_description' => 'WCAG failure.',
            'why_it_matters' => 'Screen readers impaired.',
            'fix_guidance' => 'Add alt attributes.',
        ]);

        $issue = ScanIssue::create([
            'scan_id' => $scan->id,
            'rule_id' => $rule->id,
            'category' => 'accessibility',
            'severity' => 'medium',
            'title' => $rule->title,
            'why_it_matters' => $rule->why_it_matters,
            'recommendation' => 'Add alt tags.',
        ]);

        $response = $this->postJson("/api/remediation/{$issue->id}/status", [
            'status' => 'planned',
            'notes' => 'Sprint 4 backlog item assigned to team.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'workflow' => [
                'status' => 'planned',
                'user_notes' => 'Sprint 4 backlog item assigned to team.',
            ],
        ]);

        $this->assertDatabaseHas('scan_issue_remediations', [
            'scan_issue_id' => $issue->id,
            'status' => 'planned',
        ]);
    }
}
