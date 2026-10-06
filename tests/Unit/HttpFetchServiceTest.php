<?php

namespace Tests\Unit;

use App\Services\Scanner\HttpFetchService;
use App\Services\Security\UrlValidationService;
use PHPUnit\Framework\TestCase;

class HttpFetchServiceTest extends TestCase
{
    protected HttpFetchService $fetchService;

    protected function setUp(): void
    {
        parent::setUp();
        $validator = new UrlValidationService();
        $this->fetchService = new HttpFetchService($validator);
    }

    public function test_parses_raw_http_headers_correctly(): void
    {
        $rawHeaders = "HTTP/1.1 200 OK\r\n" .
                      "Content-Type: text/html; charset=UTF-8\r\n" .
                      "Server: Apache/2.4.41\r\n" .
                      "Set-Cookie: session=123; path=/\r\n" .
                      "Set-Cookie: user=abc; path=/\r\n" .
                      "X-Frame-Options: SAMEORIGIN\r\n";

        $headers = $this->fetchService->parseHeaders($rawHeaders);

        $this->assertIsArray($headers);
        $this->assertEquals('text/html; charset=UTF-8', $headers['content-type']);
        $this->assertEquals('Apache/2.4.41', $headers['server']);
        $this->assertEquals('SAMEORIGIN', $headers['x-frame-options']);
        // Multiple headers of the same key must be grouped into an array
        $this->assertIsArray($headers['set-cookie']);
        $this->assertCount(2, $headers['set-cookie']);
    }

    public function test_maps_curl_errors_to_user_friendly_messages(): void
    {
        $timeoutMsg = $this->fetchService->mapCurlError(CURLE_OPERATION_TIMEDOUT, 'timed out', 'https://example.com');
        $this->assertStringContainsString('timeout', strtolower($timeoutMsg));
        $this->assertStringNotContainsString('CURLE_', $timeoutMsg);

        $connectMsg = $this->fetchService->mapCurlError(CURLE_COULDNT_CONNECT, 'failed', 'https://example.com');
        $this->assertStringContainsString('connection', strtolower($connectMsg));

        $dnsMsg = $this->fetchService->mapCurlError(CURLE_COULDNT_RESOLVE_HOST, 'resolve error', 'https://example.com');
        $this->assertStringContainsString('resolve', strtolower($dnsMsg));

        $sslMsg = $this->fetchService->mapCurlError(CURLE_SSL_CONNECT_ERROR, 'ssl cert error', 'https://example.com');
        $this->assertStringContainsString('certificate', strtolower($sslMsg));

        $redirectMsg = $this->fetchService->mapCurlError(CURLE_TOO_MANY_REDIRECTS, 'redirect loop', 'https://example.com');
        $this->assertStringContainsString('redirect', strtolower($redirectMsg));
    }

    public function test_has_safe_download_and_content_type_policies(): void
    {
        $this->assertEquals(5242880, HttpFetchService::MAX_RESPONSE_BYTES);
        $this->assertContains('text/html', HttpFetchService::PERMITTED_CONTENT_TYPES);
        $this->assertContains('application/xhtml+xml', HttpFetchService::PERMITTED_CONTENT_TYPES);
        $this->assertNotContains('application/pdf', HttpFetchService::PERMITTED_CONTENT_TYPES);
        $this->assertNotContains('image/png', HttpFetchService::PERMITTED_CONTENT_TYPES);
    }
}
