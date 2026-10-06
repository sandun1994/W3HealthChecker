<?php

namespace App\Services\Scanner;

use App\Services\Security\UrlValidationService;
use Exception;
use RuntimeException;

class HttpFetchService
{
    /**
     * Maximum response size in bytes (5 MB).
     */
    public const MAX_RESPONSE_BYTES = 5242880;

    /**
     * Permitted HTML Content-Types.
     */
    public const PERMITTED_CONTENT_TYPES = [
        'text/html',
        'application/xhtml+xml',
    ];

    public function __construct(protected UrlValidationService $urlValidator) {}

    /**
     * Fetch the target webpage with redirect tracking, timing, and security controls.
     *
     * @throws Exception
     */
    public function fetchPage(string $url): array
    {
        $maxRedirects = 5;
        $currentUrl = $url;
        $redirectHistory = [];
        $startTime = microtime(true);

        $responseBody = '';
        $responseHeaders = [];
        $httpCode = 0;
        $effectiveUrl = $url;
        $contentType = '';

        for ($i = 0; $i <= $maxRedirects; $i++) {
            // Validate destination URL on each hop against SSRF & Port restrictions
            $normalized = $this->urlValidator->validateAndNormalize($currentUrl);
            $validatedUrl = $normalized['normalized_url'];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $validatedUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_FOLLOWLOCATION => false, // Manual redirect following for per-hop SSRF validation
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; W3HealthChecker/1.0; +https://w3healthchecker.com/bot)',
                CURLOPT_ENCODING => '', // Accept gzip, deflate, br
                CURLOPT_MAXFILESIZE => self::MAX_RESPONSE_BYTES,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ]);

            $rawResponse = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            $info = curl_getinfo($ch);

            if ($rawResponse === false) {
                curl_close($ch);
                throw new RuntimeException($this->mapCurlError($curlErrno, $curlError, $currentUrl));
            }

            $headerSize = $info['header_size'];
            $rawHeaderStr = substr($rawResponse, 0, $headerSize);
            $responseBody = substr($rawResponse, $headerSize);
            $httpCode = $info['http_code'];
            $effectiveUrl = $info['url'] ?: $validatedUrl;
            $contentType = strtolower($info['content_type'] ?? '');

            // Parse headers
            $responseHeaders = $this->parseHeaders($rawHeaderStr);
            curl_close($ch);

            // Handle Redirects (301, 302, 303, 307, 308)
            if ($httpCode >= 300 && $httpCode < 400 && !empty($responseHeaders['location'])) {
                if ($i === $maxRedirects) {
                    throw new RuntimeException("Too many redirects encountered (maximum limit of {$maxRedirects} reached).");
                }

                $location = is_array($responseHeaders['location']) ? end($responseHeaders['location']) : $responseHeaders['location'];
                $nextUrl = $this->resolveRelativeUrl($currentUrl, $location);

                // Detect circular redirects
                foreach ($redirectHistory as $hop) {
                    if ($hop['to'] === $nextUrl) {
                        throw new RuntimeException("Circular redirect loop detected: {$nextUrl}");
                    }
                }

                $redirectHistory[] = [
                    'from' => $currentUrl,
                    'to' => $nextUrl,
                    'status' => $httpCode,
                ];

                $currentUrl = $nextUrl;
                continue;
            }

            // Final destination reached
            break;
        }

        // Content-Type Safety Check: Must be HTML (allow empty for certain 204/3xx or fallback)
        if ($httpCode >= 200 && $httpCode < 300 && !empty($contentType)) {
            $isHtml = false;
            foreach (self::PERMITTED_CONTENT_TYPES as $allowed) {
                if (str_contains($contentType, $allowed)) {
                    $isHtml = true;
                    break;
                }
            }

            if (!$isHtml) {
                // Reject non-HTML documents gracefully
                $cleanType = explode(';', $contentType)[0];
                throw new RuntimeException("This URL returned an unsupported content type ({$cleanType}). W3HealthChecker only analyzes HTML web pages.");
            }
        }

        $totalTimeMs = (int) round((microtime(true) - $startTime) * 1000);
        $serverTimeMs = isset($info['starttransfer_time']) ? (int) round($info['starttransfer_time'] * 1000) : $totalTimeMs;

        // SSL Certificate Inspection
        $sslCertInfo = $this->extractSslInfo($effectiveUrl);

        return [
            'initial_url' => $url,
            'final_url' => $effectiveUrl,
            'http_status' => $httpCode,
            'response_time_ms' => $totalTimeMs,
            'ttfb_ms' => $serverTimeMs,
            'body_bytes' => strlen($responseBody),
            'html' => $responseBody,
            'headers' => $responseHeaders,
            'redirects' => $redirectHistory,
            'ssl' => $sslCertInfo,
            'content_type' => $contentType,
        ];
    }

    /**
     * Check auxiliary resources safely (robots.txt, sitemap.xml, llms.txt)
     */
    public function fetchAuxiliary(string $rootUrl, string $path): array
    {
        $targetUrl = rtrim($rootUrl, '/') . '/' . ltrim($path, '/');
        try {
            $this->urlValidator->validateAndNormalize($targetUrl);
        } catch (Exception $e) {
            return ['found' => false, 'status' => 0, 'content' => ''];
        }

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; W3HealthChecker/1.0; +https://w3healthchecker.com/bot)',
            CURLOPT_MAXFILESIZE => 1024 * 1024, // 1MB max for auxiliary files
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $found = ($code >= 200 && $code < 300 && !empty($content));

        return [
            'url' => $targetUrl,
            'found' => $found,
            'status' => $code,
            'content' => $found ? substr($content, 0, 50000) : '',
        ];
    }

    /**
     * Defensive check: Check if sensitive paths (.env, .git/config, .git/HEAD) return HTTP 200.
     * Note: We NEVER store, parse, or display contents of sensitive files.
     */
    public function checkSensitiveExposure(string $rootUrl): array
    {
        $sensitivePaths = ['.env', '.git/config', '.git/HEAD', 'wp-config.php.bak'];
        $exposures = [];

        foreach ($sensitivePaths as $path) {
            $targetUrl = rtrim($rootUrl, '/') . '/' . $path;
            try {
                $this->urlValidator->validateAndNormalize($targetUrl);
            } catch (Exception $e) {
                continue;
            }

            $ch = curl_init($targetUrl);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true, // HEAD request only
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; W3HealthChecker/1.0; +https://w3healthchecker.com/bot)',
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ]);

            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                $exposures[] = [
                    'path' => $path,
                    'status' => 200,
                    'warning' => "Potential configuration exposure detected at /{$path}",
                ];
            }
        }

        return $exposures;
    }

    /**
     * Map low-level cURL error codes to user-friendly explanations.
     */
    public function mapCurlError(int $errno, string $rawError, string $url): string
    {
        switch ($errno) {
            case CURLE_OPERATION_TIMEDOUT:
                return 'The website took too long to respond (timeout after 15 seconds).';
            case CURLE_COULDNT_CONNECT:
                return 'Unable to establish a connection to the target server. The website may be offline or unreachable.';
            case CURLE_COULDNT_RESOLVE_HOST:
                return 'Could not resolve the website domain name. Please check the spelling of the URL.';
            case CURLE_SSL_CONNECT_ERROR:
            case 60: // CURLE_SSL_CACERT / CURLE_PEER_FAILED_VERIFICATION
                return 'TLS/SSL certificate handshake failed or the certificate is invalid.';
            case CURLE_TOO_MANY_REDIRECTS:
                return 'Too many redirects encountered (maximum limit of 5 exceeded).';
            case CURLE_FILESIZE_EXCEEDED:
                return 'The website response payload exceeded the 5 MB download limit.';
            case CURLE_UNSUPPORTED_PROTOCOL:
                return 'The requested protocol is not supported. Only HTTP and HTTPS are permitted.';
            default:
                return 'Could not complete the website scan: ' . ($rawError ?: 'Host unreachable.');
        }
    }

    /**
     * Parse raw HTTP header string into a normalized array.
     */
    public function parseHeaders(string $headerStr): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerStr);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $key = strtolower(trim($parts[0]));
                $val = trim($parts[1]);
                if (isset($headers[$key])) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = [$headers[$key]];
                    }
                    $headers[$key][] = $val;
                } else {
                    $headers[$key] = $val;
                }
            }
        }
        return $headers;
    }

    /**
     * Extract SSL/TLS certificate information.
     */
    protected function extractSslInfo(string $url): ?array
    {
        $parsed = parse_url($url);
        if (($parsed['scheme'] ?? '') !== 'https') {
            return null;
        }

        $host = $parsed['host'] ?? '';
        $port = $parsed['port'] ?? 443;

        $g = @stream_context_create([
            "ssl" => [
                "capture_peer_cert" => true,
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ]);

        $client = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $g);
        if (!$client) {
            return [
                'valid' => false,
                'error' => "SSL connection failed: {$errstr}",
            ];
        }

        $params = stream_context_get_params($client);
        $cert = $params["options"]["ssl"]["peer_certificate"] ?? null;
        fclose($client);

        if (!$cert) {
            return ['valid' => false, 'error' => 'No peer certificate found.'];
        }

        $parsedCert = openssl_x509_parse($cert);
        if (!$parsedCert) {
            return ['valid' => false, 'error' => 'Unable to parse peer certificate.'];
        }

        $validFrom = $parsedCert['validFrom_time_t'] ?? 0;
        $validTo = $parsedCert['validTo_time_t'] ?? 0;
        $now = time();

        $daysRemaining = max(0, (int) round(($validTo - $now) / 86400));
        $isValid = ($now >= $validFrom && $now <= $validTo);

        return [
            'valid' => $isValid,
            'issuer' => $parsedCert['issuer']['O'] ?? ($parsedCert['issuer']['CN'] ?? 'Unknown'),
            'subject' => $parsedCert['subject']['CN'] ?? $host,
            'valid_from' => date('Y-m-d H:i:s', $validFrom),
            'valid_to' => date('Y-m-d H:i:s', $validTo),
            'days_remaining' => $daysRemaining,
        ];
    }

    /**
     * Resolve relative URLs against a base URL.
     */
    protected function resolveRelativeUrl(string $base, string $relative): string
    {
        if (parse_url($relative, PHP_URL_SCHEME) != '') {
            return $relative;
        }

        $baseParts = parse_url($base);
        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        if (str_starts_with($relative, '//')) {
            return $scheme . ':' . $relative;
        }

        if (str_starts_with($relative, '/')) {
            return "{$scheme}://{$host}{$port}{$relative}";
        }

        $path = $baseParts['path'] ?? '/';
        $dir = dirname($path);
        if ($dir === '\\' || $dir === '.') $dir = '';
        return "{$scheme}://{$host}{$port}{$dir}/{$relative}";
    }
}
