<?php

namespace App\Services\Remediation;

use App\Models\ScanIssue;
use App\Models\ScanIssueRemediation;

class RemediationGuidanceService
{
    /**
     * Build structured, evidence-grounded remediation guidance for a specific scan finding.
     */
    public function getGuidance(ScanIssue $issue, ?int $userId = null): array
    {
        $remediation = ScanIssueRemediation::where('scan_issue_id', $issue->id)->first();
        $codeExamples = $this->getCodeExamples($issue);

        return [
            'issue_id' => $issue->id,
            'rule_id' => $issue->rule_id,
            'title' => $issue->title,
            'category' => $issue->category,
            'severity' => $issue->severity,
            'confidence' => $issue->confidence ?? 'high',
            'what_was_detected' => $issue->description ?? $issue->title,
            'evidence' => $issue->evidence ?? [],
            'why_it_matters' => $issue->why_it_matters,
            'affected_resource' => $issue->affected_resource ?? $issue->scan?->target_url,
            'recommended_fix' => $issue->recommendation,
            'technical_details' => $issue->technical_details,
            'code_examples' => $codeExamples,
            'verification_procedure' => $this->getVerificationProcedure($issue),
            'expected_outcome' => "Remediating this finding improves the {$issue->category} pillar score and resolves {$issue->severity} compliance debt.",
            'limitations' => 'Recommendations are deterministic and based strictly on the publicly accessible HTTP response data gathered during the audit.',
            'workflow' => [
                'status' => $remediation?->status ?? 'open',
                'user_notes' => $remediation?->user_notes,
                'dismissed_reason' => $remediation?->dismissed_reason,
                'updated_at' => $remediation?->updated_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Provide concrete configuration and code snippets by server environment.
     */
    protected function getCodeExamples(ScanIssue $issue): array
    {
        $rule = $issue->rule_id;

        if (str_contains($rule, 'hsts') || str_contains($rule, 'sec_hsts')) {
            return [
                [
                    'platform' => 'Nginx',
                    'file' => '/etc/nginx/sites-available/your-site.conf',
                    'snippet' => 'add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;',
                ],
                [
                    'platform' => 'Apache',
                    'file' => '.htaccess or httpd.conf',
                    'snippet' => '<IfModule mod_headers.c>\n    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"\n</IfModule>',
                ],
                [
                    'platform' => 'Laravel Middleware',
                    'file' => 'app/Http/Middleware/SecurityHeadersMiddleware.php',
                    'snippet' => '$response->headers->set("Strict-Transport-Security", "max-age=31536000; includeSubDomains; preload");',
                ],
            ];
        }

        if (str_contains($rule, 'canonical')) {
            return [
                [
                    'platform' => 'HTML Header',
                    'file' => '<head> section',
                    'snippet' => '<link rel="canonical" href="https://example.com/authoritative-page" />',
                ],
                [
                    'platform' => 'Laravel Blade',
                    'file' => 'resources/views/layouts/app.blade.php',
                    'snippet' => '<link rel="canonical" href="{{ url()->current() }}" />',
                ],
            ];
        }

        if (str_contains($rule, 'alt') || str_contains($rule, 'a11y')) {
            return [
                [
                    'platform' => 'HTML',
                    'file' => '<img> elements',
                    'snippet' => '<img src="/assets/hero.jpg" alt="Descriptive textual summary of the image content" />',
                ],
            ];
        }

        // Generic fallback
        return [
            [
                'platform' => 'General Implementation',
                'file' => 'Server configuration or application template',
                'snippet' => $issue->recommendation,
            ],
        ];
    }

    /**
     * Instructions to verify fix before running next scan.
     */
    protected function getVerificationProcedure(ScanIssue $issue): string
    {
        if (str_contains($issue->rule_id, 'sec_') || str_contains($issue->rule_id, 'header_')) {
            return "Run `curl -I https://your-domain.com` in your terminal and verify the response headers contain the expected directive.";
        }

        if (str_contains($issue->rule_id, 'canonical') || str_contains($issue->rule_id, 'seo_')) {
            return "Inspect page source (`View Source`) in your browser and confirm the `<link rel='canonical'>` or metadata tag appears inside `<head>`.";
        }

        return "Inspect browser developer tools (Elements and Network tabs) to confirm the resource meets the standard.";
    }

    /**
     * Update user remediation workflow status.
     */
    public function updateStatus(ScanIssue $issue, ?int $userId, string $status, ?string $notes = null, ?string $reason = null): ScanIssueRemediation
    {
        return ScanIssueRemediation::updateOrCreate(
            ['scan_issue_id' => $issue->id],
            [
                'user_id' => $userId,
                'status' => $status,
                'user_notes' => $notes,
                'dismissed_reason' => $reason,
            ]
        );
    }
}
