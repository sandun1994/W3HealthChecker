<?php

namespace App\Services\Integrations\Adapters;

use App\Models\Integration;
use App\Services\Integrations\Contracts\IntegrationAdapterInterface;
use Illuminate\Support\Facades\Http;

class GitHubAdapter implements IntegrationAdapterInterface
{
    public function dispatch(Integration $integration, string $event, array $payload): array
    {
        $credentials = $integration->getCredentials();
        $token = $credentials['token'] ?? null;
        $repo = $integration->settings['repository'] ?? null;

        if (!$token || !$repo) {
            return ['success' => false, 'error' => 'Missing GitHub authentication token or target repository.'];
        }

        $issueData = $this->buildIssuePayload($event, $payload);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->timeout(5)
                ->post("https://api.github.com/repos/{$repo}/issues", $issueData);

            if ($response->successful()) {
                $integration->update([
                    'last_dispatched_at' => now(),
                    'last_error' => null,
                    'status' => 'connected',
                ]);

                return [
                    'success' => true,
                    'issue_url' => $response->json('html_url'),
                    'issue_number' => $response->json('number'),
                ];
            }

            $errMsg = 'GitHub API error: ' . ($response->json('message') ?? 'HTTP ' . $response->status());
            $integration->update(['last_error' => $errMsg, 'status' => 'degraded']);
            return ['success' => false, 'error' => $errMsg];
        } catch (\Exception $e) {
            $integration->update(['last_error' => $e->getMessage(), 'status' => 'degraded']);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(Integration $integration): array
    {
        $credentials = $integration->getCredentials();
        $token = $credentials['token'] ?? null;
        $repo = $integration->settings['repository'] ?? null;

        if (!$token || !$repo) {
            return ['success' => false, 'error' => 'Incomplete GitHub credentials.'];
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->timeout(5)
                ->get("https://api.github.com/repos/{$repo}");

            if ($response->successful()) {
                $integration->update(['status' => 'connected', 'last_error' => null]);
                return ['success' => true, 'repository' => $response->json('full_name')];
            }

            return ['success' => false, 'error' => 'Repository access denied: ' . $response->json('message')];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function buildIssuePayload(string $event, array $payload): array
    {
        $domain = $payload['domain'] ?? 'Website';
        $title = $payload['issue_title'] ?? "Website Health Alert for {$domain}";
        $severity = strtoupper($payload['severity'] ?? 'WARNING');
        $whyItMatters = $payload['why_it_matters'] ?? 'Impairs website quality, performance, or security.';
        $recommendation = $payload['recommendation'] ?? 'Review report recommendations.';
        $reportUrl = $payload['report_url'] ?? 'https://w3healthchecker.com';

        $body = "### W3HealthChecker Diagnostic Finding\n\n" .
                "**Target Website**: `{$domain}`\n" .
                "**Severity**: **{$severity}**\n\n" .
                "#### Why It Matters\n{$whyItMatters}\n\n" .
                "#### Recommended Remediation\n{$recommendation}\n\n" .
                "---\n" .
                "🔗 [View Full W3HealthChecker Audit Report]({$reportUrl})\n" .
                "*Automated issue created by W3HealthChecker Integration Engine.*";

        return [
            'title' => "[{$severity}] {$title} — {$domain}",
            'body' => $body,
            'labels' => ['w3healthchecker', strtolower($severity), 'audit'],
        ];
    }
}
