<?php

namespace App\Services\Pillars;

class TechnicalAnalyzer
{
    public function analyze(array $fetchData): array
    {
        $issues = [];
        $penalties = 0;
        $headers = $fetchData['headers'] ?? [];
        $statusCode = $fetchData['http_status'] ?? 0;
        $redirects = $fetchData['redirects'] ?? [];
        $finalUrl = $fetchData['final_url'] ?? '';

        // 1. HTTP Status Code
        if ($statusCode >= 400 && $statusCode < 500) {
            $penalties += 50;
            $issues[] = [
                'rule_id' => 'tech_redirect_chain',
                'category' => 'technical',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => "Client HTTP Error Status ({$statusCode})",
                'affected_resource' => $finalUrl,
                'evidence' => ['http_status' => $statusCode],
                'why_it_matters' => 'Search engine crawlers and users cannot access the page, resulting in drop of traffic and immediate de-indexing.',
                'recommendation' => 'Investigate web server routing or URL rewriting rules to resolve the 4xx status.',
                'technical_details' => "Received HTTP status code: {$statusCode}",
            ];
        } elseif ($statusCode >= 500) {
            $penalties += 60;
            $issues[] = [
                'rule_id' => 'tech_redirect_chain',
                'category' => 'technical',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => "Internal Server Error ({$statusCode})",
                'affected_resource' => $finalUrl,
                'evidence' => ['http_status' => $statusCode],
                'why_it_matters' => 'A backend application crash or server misconfiguration prevented the page from rendering.',
                'recommendation' => 'Inspect server error logs to identify the backend crash or database timeout.',
                'technical_details' => "HTTP {$statusCode} Server Error returned.",
            ];
        }

        // 2. Redirect Chain Length
        $redirectHops = count($redirects);
        if ($redirectHops >= 2) {
            $penalties += 12;
            $issues[] = [
                'rule_id' => 'tech_redirect_chain',
                'category' => 'technical',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "Multi-Hop Redirect Chain Detected ({$redirectHops} hops)",
                'affected_resource' => $fetchData['initial_url'],
                'evidence' => ['hops_count' => $redirectHops, 'chain' => $redirects],
                'why_it_matters' => 'Each redirect hop adds network round-trips, delays rendering, and wastes search engine crawl budget.',
                'recommendation' => 'Update server configuration or internal links to redirect directly to the final destination in a single 301 hop.',
                'technical_details' => 'Consolidate HTTP->HTTPS and non-WWW->WWW rules into a single redirect step.',
            ];
        }

        // 3. Server Software / Version Fingerprint
        $serverHeader = $headers['server'] ?? '';
        $xPoweredBy = $headers['x-powered-by'] ?? '';
        if ((!empty($serverHeader) && preg_match('/\d+\.\d+/', $serverHeader)) || !empty($xPoweredBy)) {
            $penalties += 6;
            $issues[] = [
                'rule_id' => 'tech_server_version_exposed',
                'category' => 'technical',
                'severity' => 'low',
                'confidence' => 'high',
                'title' => 'Server Version Information Exposed in Headers',
                'affected_resource' => $finalUrl,
                'evidence' => [
                    'server' => $serverHeader ?: null,
                    'x_powered_by' => $xPoweredBy ?: null,
                ],
                'why_it_matters' => 'Exposing explicit software version numbers assists attackers in targeting known CVE exploits.',
                'recommendation' => 'Disable version signature broadcasting in your server configuration (e.g. server_tokens off in Nginx, ServerSignature Off in Apache).',
                'technical_details' => 'Header: Server: ' . ($serverHeader ?: $xPoweredBy),
            ];
        }

        $score = max(10, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'http_status' => $statusCode,
                'redirect_hops' => $redirectHops,
                'redirect_history' => $redirects,
                'server_header' => $serverHeader ?: 'Hidden / Generic',
                'x_powered_by' => $xPoweredBy ?: 'Hidden',
                'content_type' => $fetchData['content_type'] ?? 'Unknown',
            ],
        ];
    }
}
