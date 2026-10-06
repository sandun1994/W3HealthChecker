<?php

namespace Tests\Unit;

use App\Services\Security\UrlValidationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UrlValidationServiceTest extends TestCase
{
    protected UrlValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UrlValidationService();
    }

    public function test_blocks_localhost_and_loopback_hostnames(): void
    {
        $this->assertTrue($this->service->isBlockedHostname('localhost'));
        $this->assertTrue($this->service->isBlockedHostname('127.0.0.1'));
        $this->assertTrue($this->service->isBlockedHostname('::1'));
        $this->assertTrue($this->service->isBlockedHostname('app.localhost'));
        $this->assertTrue($this->service->isBlockedHostname('service.internal'));
        $this->assertTrue($this->service->isBlockedHostname('test.local'));
        $this->assertTrue($this->service->isBlockedHostname('metadata.google.internal'));
    }

    public function test_blocks_ipv4_private_and_cloud_metadata(): void
    {
        // 169.254.0.0/16 Link-local / Cloud metadata
        $this->assertTrue($this->service->isPrivateOrReservedIp('169.254.169.254'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('169.254.1.1'));

        // 127.0.0.0/8 Loopback
        $this->assertTrue($this->service->isPrivateOrReservedIp('127.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('127.128.0.1'));

        // 10.0.0.0/8 Private
        $this->assertTrue($this->service->isPrivateOrReservedIp('10.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('10.254.254.254'));

        // 172.16.0.0/12 Private
        $this->assertTrue($this->service->isPrivateOrReservedIp('172.16.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('172.31.255.254'));

        // 192.168.0.0/16 Private
        $this->assertTrue($this->service->isPrivateOrReservedIp('192.168.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('192.168.1.100'));

        // 0.0.0.0/8 Current network
        $this->assertTrue($this->service->isPrivateOrReservedIp('0.0.0.0'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('0.1.2.3'));

        // 100.64.0.0/10 Carrier-grade NAT
        $this->assertTrue($this->service->isPrivateOrReservedIp('100.64.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('100.127.255.254'));

        // Multicast & Reserved
        $this->assertTrue($this->service->isPrivateOrReservedIp('224.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('240.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('255.255.255.255'));
    }

    public function test_blocks_ipv6_private_and_reserved_ranges(): void
    {
        // Loopback
        $this->assertTrue($this->service->isPrivateOrReservedIp('::1'));

        // Unspecified
        $this->assertTrue($this->service->isPrivateOrReservedIp('::'));

        // Unique local fc00::/7
        $this->assertTrue($this->service->isPrivateOrReservedIp('fc00::1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('fd00::1'));

        // Link local fe80::/10
        $this->assertTrue($this->service->isPrivateOrReservedIp('fe80::1'));

        // AWS IPv6 metadata endpoint
        $this->assertTrue($this->service->isPrivateOrReservedIp('fd00:ec2::254'));

        // IPv4-mapped IPv6
        $this->assertTrue($this->service->isPrivateOrReservedIp('::ffff:127.0.0.1'));
        $this->assertTrue($this->service->isPrivateOrReservedIp('::ffff:192.168.1.1'));
    }

    public function test_allows_valid_public_ips(): void
    {
        $this->assertFalse($this->service->isPrivateOrReservedIp('8.8.8.8'));
        $this->assertFalse($this->service->isPrivateOrReservedIp('1.1.1.1'));
        $this->assertFalse($this->service->isPrivateOrReservedIp('93.184.216.34'));
        $this->assertFalse($this->service->isPrivateOrReservedIp('2606:4700:4700::1111'));
    }

    public function test_extracts_root_domain_accurately(): void
    {
        $this->assertEquals('example.com', $this->service->extractRootDomain('example.com'));
        $this->assertEquals('example.com', $this->service->extractRootDomain('sub.example.com'));
        $this->assertEquals('example.com', $this->service->extractRootDomain('deep.sub.example.com'));
        $this->assertEquals('example.co.uk', $this->service->extractRootDomain('blog.example.co.uk'));
    }

    public function test_rejects_unsupported_protocols(): void
    {
        $unsupported = [
            'ftp://example.com',
            'file:///etc/passwd',
            'gopher://example.com',
            'javascript:alert(1)',
            'data:text/html,<h1>Hello</h1>',
            'ssh://example.com',
        ];

        foreach ($unsupported as $url) {
            try {
                $this->service->validateAndNormalize($url);
                $this->fail("Expected InvalidArgumentException for protocol in {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function test_rejects_blocked_ports(): void
    {
        $blockedPorts = [
            'http://example.com:21',
            'http://example.com:22',
            'http://example.com:25',
            'http://example.com:3306',
            'http://example.com:6379',
            'http://example.com:9200',
        ];

        foreach ($blockedPorts as $url) {
            try {
                $this->service->validateAndNormalize($url);
                $this->fail("Expected InvalidArgumentException for blocked port in {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Port', $e->getMessage());
            }
        }
    }

    public function test_normalizes_urls_properly(): void
    {
        // Trims whitespace, lowercases scheme & host, strips default port 80/443
        $result = $this->service->validateAndNormalize('  HTTP://EXAMPLE.COM:80/path/test  ');
        $this->assertEquals('http://example.com/path/test', $result['normalized_url']);
        $this->assertEquals('example.com', $result['domain']);
        $this->assertEquals('http', $result['scheme']);

        $resHttps = $this->service->validateAndNormalize('https://sub.domain.org:443/');
        $this->assertEquals('https://sub.domain.org/', $resHttps['normalized_url']);
    }
}
