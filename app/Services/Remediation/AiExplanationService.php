<?php

namespace App\Services\Remediation;

use App\Models\ScanIssue;

class AiExplanationService
{
    /**
     * Generate plain-language, evidence-grounded explanation tailored to user skill level.
     */
    public function explain(ScanIssue $issue, string $skillLevel = 'intermediate'): array
    {
        $level = in_array(strtolower($skillLevel), ['beginner', 'intermediate', 'advanced'], true)
            ? strtolower($skillLevel)
            : 'intermediate';

        // Deterministic, evidence-grounded explanation tailored by skill level
        $explanation = match ($level) {
            'beginner' => $this->explainForBeginner($issue),
            'advanced' => $this->explainForAdvanced($issue),
            default => $this->explainForIntermediate($issue),
        };

        return [
            'issue_id' => $issue->id,
            'rule_id' => $issue->rule_id,
            'skill_level' => $level,
            'plain_language_summary' => $explanation['summary'],
            'actionable_steps' => $explanation['steps'],
            'risks_if_ignored' => $explanation['risks'],
            'tradeoffs' => $explanation['tradeoffs'],
            'is_ai_generated' => false,
            'source' => 'W3HealthChecker Deterministic Intelligence Engine',
        ];
    }

    protected function explainForBeginner(ScanIssue $issue): array
    {
        return [
            'summary' => "Your website has an issue titled \"{$issue->title}\". In simple terms, this means part of your website is missing a recommended setting that helps visitors or search engines safely read your content.",
            'steps' => [
                "1. Understand the goal: {$issue->recommendation}",
                "2. Share this report with your web developer or agency, or copy the code snippet below into your site builder settings.",
                "3. Once applied, run a new scan on W3HealthChecker to confirm the issue is fixed.",
            ],
            'risks' => "Leaving this unfixed may make your website slower, less secure, or harder for search engines and visitors with disabilities to use.",
            'tradeoffs' => "Applying this fix is generally low risk and follows standard web best practices.",
        ];
    }

    protected function explainForIntermediate(ScanIssue $issue): array
    {
        return [
            'summary' => "During the scan, W3HealthChecker identified \"{$issue->title}\" in the {$issue->category} pillar. {$issue->why_it_matters}",
            'steps' => [
                "1. Review the affected resource: {$issue->affected_resource}",
                "2. Apply fix: {$issue->recommendation}",
                "3. Validate locally via curl or browser developer tools before deploying to production.",
            ],
            'risks' => "Impacts {$issue->category} scoring and can introduce technical debt or accessibility non-compliance.",
            'tradeoffs' => "Ensure any HTTP headers or redirect changes are thoroughly tested in staging to avoid accidental redirect loops or asset blocks.",
        ];
    }

    protected function explainForAdvanced(ScanIssue $issue): array
    {
        return [
            'summary' => "Evaluation of rule `{$issue->rule_id}` resulted in a {$issue->severity}-severity finding. Technical details: " . ($issue->technical_details ?? $issue->why_it_matters),
            'steps' => [
                "1. Inspect response headers or DOM node: `{$issue->affected_resource}`",
                "2. Implement standard directive: `{$issue->recommendation}`",
                "3. Verify via HTTP response analysis: `curl -sI https://... | grep -i ...`",
            ],
            'risks' => "Security or structural violation of standard web specifications.",
            'tradeoffs' => "When implementing strict transport or CSP policies, ensure all third-party subdomains and CDN origins are explicitly whitelisted.",
        ];
    }
}
