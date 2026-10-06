<?php

namespace App\Services\Pillars;

class SecurityAnalyzer
{
    public function analyze(array $fetchData, array $auxiliaryData = []): array
    {
        $issues = [];
        $penalties = 0;
        $headers = $fetchData['headers'] ?? [];
        $finalUrl = $fetchData['final_url'] ?? '';
        $parsed = parse_url($finalUrl);
        $isHttps = ($parsed['scheme'] ?? '') === 'https';
        $ssl = $fetchData['ssl'] ?? null;

        // 1. HTTPS Check
        if (!$isHttps) {
            $penalties += 40;
            $issues[] = [
                'rule_id' => 'sec_https_missing',
                'category' => 'security',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'Insecure HTTP Connection (No HTTPS)',
                'affected_resource' => $finalUrl,
                'evidence' => ['protocol' => $parsed['scheme'] ?? 'http'],
                'why_it_matters' => 'Browsers display "Not Secure" warnings, data is transmitted in plain text susceptible to eavesdropping, and search engines heavily penalize unencrypted pages.',
                'recommendation' => 'Obtain an SSL/TLS certificate (e.g. Let\'s Encrypt) and force a 301 permanent redirect from HTTP to HTTPS.',
                'technical_details' => 'Ensure all assets (CSS, JS, images) are served over HTTPS to prevent mixed content warnings.',
            ];
        } elseif ($ssl && isset($ssl['valid']) && !$ssl['valid']) {
            $penalties += 35;
            $issues[] = [
                'rule_id' => 'sec_https_missing',
                'category' => 'security',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'SSL/TLS Certificate Error or Expired',
                'affected_resource' => $finalUrl,
                'evidence' => ['error' => $ssl['error'] ?? 'Invalid certificate'],
                'why_it_matters' => 'Visitors encounter full-page security interstitials preventing them from entering the site.',
                'recommendation' => 'Renew or reissue your SSL certificate immediately through your hosting provider or certificate authority.',
                'technical_details' => $ssl['error'] ?? 'TLS handshake failed.',
            ];
        }

        // 2. Strict-Transport-Security (HSTS)
        if ($isHttps) {
            if (empty($headers['strict-transport-security'])) {
                $penalties += 15;
                $issues[] = [
                    'rule_id' => 'sec_hsts_missing',
                    'category' => 'security',
                    'severity' => 'high',
                    'confidence' => 'high',
                    'title' => 'Missing Strict-Transport-Security (HSTS) Header',
                    'affected_resource' => $finalUrl,
                    'evidence' => ['header' => 'Strict-Transport-Security', 'present' => false],
                    'why_it_matters' => 'Without HSTS, attackers on the same network can downgrade the initial connection from HTTPS to HTTP (SSL-stripping).',
                    'recommendation' => 'Configure the Strict-Transport-Security header with a minimum duration of one year (31536000 seconds).',
                    'technical_details' => 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload',
                ];
            }
        }

        // 3. Content-Security-Policy (CSP)
        if (empty($headers['content-security-policy'])) {
            $penalties += 12;
            $issues[] = [
                'rule_id' => 'sec_csp_missing',
                'category' => 'security',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => 'Missing Content-Security-Policy (CSP) Header',
                'affected_resource' => $finalUrl,
                'evidence' => ['header' => 'Content-Security-Policy', 'present' => false],
                'why_it_matters' => 'Content-Security-Policy is the strongest browser-native mitigation against Cross-Site Scripting (XSS) and rogue script injections.',
                'recommendation' => 'Implement a Content-Security-Policy header restricting script execution to approved origins and hashes/nonces.',
                'technical_details' => "Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-...'; object-src 'none';",
            ];
        }

        // 4. X-Content-Type-Options
        $xContentType = strtolower($this->getHeaderValue($headers, 'x-content-type-options'));
        if ($xContentType !== 'nosniff') {
            $penalties += 10;
            $issues[] = [
                'rule_id' => 'sec_x_content_type_missing',
                'category' => 'security',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => 'Missing X-Content-Type-Options: nosniff Header',
                'affected_resource' => $finalUrl,
                'evidence' => ['value' => $this->getHeaderValue($headers, 'x-content-type-options') ?: null],
                'why_it_matters' => 'Allows legacy or compliant browsers to MIME-sniff responses away from the declared content-type, creating executable script vulnerabilities.',
                'recommendation' => 'Add the "X-Content-Type-Options: nosniff" header to all server responses.',
                'technical_details' => 'X-Content-Type-Options: nosniff',
            ];
        }

        // 5. X-Frame-Options or CSP frame-ancestors
        $xFrame = strtolower($this->getHeaderValue($headers, 'x-frame-options'));
        $cspVal = strtolower($this->getHeaderValue($headers, 'content-security-policy'));
        $hasCspFrame = !empty($cspVal) && str_contains($cspVal, 'frame-ancestors');
        if (empty($xFrame) && !$hasCspFrame) {
            $penalties += 10;
            $issues[] = [
                'rule_id' => 'sec_x_frame_missing',
                'category' => 'security',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => 'Missing Clickjacking Defense (X-Frame-Options)',
                'affected_resource' => $finalUrl,
                'evidence' => ['x_frame_options' => null, 'csp_frame_ancestors' => false],
                'why_it_matters' => 'Attackers can render your pages inside an invisible iframe on an external site, hijacking user clicks (Clickjacking).',
                'recommendation' => 'Set X-Frame-Options: SAMEORIGIN or declare frame-ancestors in your CSP header.',
                'technical_details' => 'X-Frame-Options: SAMEORIGIN',
            ];
        }

        // 6. Referrer-Policy
        $referrerPolicy = $this->getHeaderValue($headers, 'referrer-policy');
        if (empty($referrerPolicy)) {
            $penalties += 5;
            $issues[] = [
                'rule_id' => 'sec_referrer_policy_missing',
                'category' => 'security',
                'severity' => 'low',
                'confidence' => 'high',
                'title' => 'Missing Referrer-Policy Header',
                'affected_resource' => $finalUrl,
                'evidence' => ['header' => 'Referrer-Policy', 'present' => false],
                'why_it_matters' => 'Outbound clicks from your site may leak URL paths or confidential query parameters in the Referer header to external destinations.',
                'recommendation' => 'Set Referrer-Policy: strict-origin-when-cross-origin.',
                'technical_details' => 'Referrer-Policy: strict-origin-when-cross-origin',
            ];
        }

        // 7. Permissions-Policy
        $permissionsPolicy = $this->getHeaderValue($headers, 'permissions-policy');
        if (empty($permissionsPolicy)) {
            $penalties += 4;
            $issues[] = [
                'rule_id' => 'sec_permissions_policy_missing',
                'category' => 'security',
                'severity' => 'low',
                'confidence' => 'high',
                'title' => 'Missing Permissions-Policy Header',
                'affected_resource' => $finalUrl,
                'evidence' => ['header' => 'Permissions-Policy', 'present' => false],
                'why_it_matters' => 'Permissions-Policy allows developers to explicitly restrict access to sensitive browser features (camera, microphone, geolocation) within iframes.',
                'recommendation' => 'Configure a Permissions-Policy header disabling unused browser APIs.',
                'technical_details' => 'Permissions-Policy: camera=(), microphone=(), geolocation=()',
            ];
        }

        // 8. Server Software & Technology Fingerprinting Exposure
        $serverHeader = $this->getHeaderValue($headers, 'server');
        $poweredByHeader = $this->getHeaderValue($headers, 'x-powered-by');
        if (!empty($serverHeader) || !empty($poweredByHeader)) {
            $penalties += 3;
            $issues[] = [
                'rule_id' => 'sec_server_exposed',
                'category' => 'security',
                'severity' => 'low',
                'confidence' => 'high',
                'title' => 'Server Technology Fingerprint Disclosed',
                'affected_resource' => $finalUrl,
                'evidence' => array_filter(['server' => $serverHeader, 'x_powered_by' => $poweredByHeader]),
                'why_it_matters' => 'Exposing web server software and runtime versions helps automated reconnaissance scanners identify known CVE vulnerabilities for your exact software stack.',
                'recommendation' => 'Disable or mask the "Server" and "X-Powered-By" HTTP headers in your web server configuration.',
                'technical_details' => 'Nginx: server_tokens off;\nPHP: expose_php = Off\nApache: ServerTokens Prod; ServerSignature Off',
            ];
        }

        // 7. Sensitive Exposures (Passive check results)
        $exposures = $auxiliaryData['sensitive_exposures'] ?? [];
        if (!empty($exposures)) {
            $penalties += 35;
            foreach ($exposures as $exp) {
                $issues[] = [
                    'rule_id' => 'sec_sensitive_file_exposure',
                    'category' => 'security',
                    'severity' => 'critical',
                    'confidence' => 'high',
                    'title' => "Potential Exposure Detected at /{$exp['path']}",
                    'affected_resource' => rtrim($finalUrl, '/') . '/' . $exp['path'],
                    'evidence' => ['status_code' => 200, 'path' => $exp['path']],
                    'why_it_matters' => 'Publicly accessible configuration or version control files may leak sensitive database credentials and source code.',
                    'recommendation' => 'Configure your web server to immediately block access to hidden files and backup archives.',
                    'technical_details' => 'Nginx: location ~ /\\.(?!well-known) { deny all; }\nApache: RedirectMatch 404 /\\.(git|env)',
                ];
            }
        }

        $score = max(10, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'https_enabled' => $isHttps,
                'ssl_valid' => $ssl['valid'] ?? false,
                'ssl_issuer' => $ssl['issuer'] ?? 'N/A',
                'ssl_days_remaining' => $ssl['days_remaining'] ?? 0,
                'hsts_enabled' => !empty($headers['strict-transport-security']),
                'csp_enabled' => !empty($headers['content-security-policy']),
                'x_content_type_options' => $headers['x-content-type-options'] ?? null,
                'x_frame_options' => $headers['x-frame-options'] ?? null,
                'referrer_policy' => $headers['referrer-policy'] ?? null,
                'permissions_policy' => $headers['permissions-policy'] ?? null,
                'sensitive_exposures_count' => count($exposures),
            ],
        ];
    }

    protected function getHeaderValue(array $headers, string $key): string
    {
        $val = $headers[$key] ?? '';
        if (is_array($val)) {
            return (string) reset($val);
        }
        return (string) $val;
    }
}
