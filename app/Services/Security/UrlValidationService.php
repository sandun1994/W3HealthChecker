<?php

namespace App\Services\Security;

use InvalidArgumentException;

class UrlValidationService
{
    /**
     * Permitted HTTP/HTTPS ports.
     */
    protected const ALLOWED_PORTS = [80, 443, 8080, 8443];

    /**
     * Blocked IPv4 CIDR blocks.
     */
    protected const BLOCKED_IPV4_CIDRS = [
        '0.0.0.0/8',          // "This" network
        '10.0.0.0/8',         // RFC 1918 Private
        '100.64.0.0/10',      // Carrier-Grade NAT (RFC 6598)
        '127.0.0.0/8',        // Loopback
        '169.254.0.0/16',     // Link-Local & Cloud Metadata (AWS/GCP/Azure)
        '172.16.0.0/12',      // RFC 1918 Private
        '192.0.0.0/24',       // IETF Protocol Assignments
        '192.0.2.0/24',       // TEST-NET-1 (RFC 5737)
        '192.168.0.0/16',     // RFC 1918 Private
        '198.18.0.0/15',      // Network Interconnect Benchmark (RFC 2544)
        '198.51.100.0/24',    // TEST-NET-2 (RFC 5737)
        '203.0.113.0/24',     // TEST-NET-3 (RFC 5737)
        '224.0.0.0/4',        // Multicast (RFC 5771)
        '240.0.0.0/4',        // Reserved / Future Use (RFC 1112)
        '255.255.255.255/32', // Limited Broadcast
    ];

    /**
     * Blocked IPv6 CIDR blocks.
     */
    protected const BLOCKED_IPV6_CIDRS = [
        '::/128',             // Unspecified
        '::1/128',            // Loopback
        '::ffff:0:0/96',      // IPv4-mapped IPv6
        '100::/64',           // Discard-only prefix (RFC 6666)
        '2001:db8::/32',      // Documentation (RFC 3849)
        'fc00::/7',           // Unique Local Address (ULA)
        'fe80::/10',          // Link-Local Unicast
        'ff00::/8',           // Multicast
    ];

    /**
     * Blocked hostnames and domain suffixes.
     */
    protected const BLOCKED_HOSTNAMES = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
        'metadata.google.internal',
        'instance-data',
        'metadata.tombstone',
    ];

    protected const BLOCKED_SUFFIXES = [
        '.localhost',
        '.local',
        '.internal',
        '.lan',
        '.test',
        '.example',
        '.invalid',
        '.onion',
        '.corp',
        '.home',
    ];

    /**
     * Validate and normalize a URL.
     * Prevents SSRF by verifying scheme, restricting ports, resolving IP,
     * and ensuring destination is public and outside all private/reserved CIDRs.
     *
     * @throws InvalidArgumentException
     */
    public function validateAndNormalize(string $rawUrl): array
    {
        $trimmed = trim($rawUrl);

        // Auto-prepend https:// only if no scheme is specified
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $trimmed)) {
            $trimmed = 'https://' . $trimmed;
        }

        $parsed = parse_url($trimmed);
        if (!$parsed || empty($parsed['host'])) {
            throw new InvalidArgumentException('Invalid URL format. Please provide a valid hostname (e.g. example.com).');
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP and HTTPS protocols are permitted.');
        }

        // Port restrictions
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, self::ALLOWED_PORTS, true)) {
            throw new InvalidArgumentException("Port {$port} is not permitted. Only standard web ports (80, 443, 8080, 8443) are allowed.");
        }

        $host = strtolower($parsed['host']);

        // Check blocked hostnames
        if ($this->isBlockedHostname($host)) {
            throw new InvalidArgumentException('Access to local, internal, or loopback hostnames is prohibited.');
        }

        // If host is an IP literal, validate directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateOrReservedIp($host)) {
                throw new InvalidArgumentException("Target IP address ({$host}) belongs to a restricted private or reserved range.");
            }
            $resolvedIps = [$host];
        } else {
            // DNS resolution check
            $ips = @dns_get_record($host, DNS_A + DNS_AAAA);
            $resolvedIps = [];

            if (!empty($ips)) {
                foreach ($ips as $record) {
                    if (isset($record['ip'])) {
                        $resolvedIps[] = $record['ip'];
                    } elseif (isset($record['ipv6'])) {
                        $resolvedIps[] = $record['ipv6'];
                    }
                }
            } else {
                // Fallback to gethostbyname
                $fallbackIp = @gethostbyname($host);
                if ($fallbackIp && $fallbackIp !== $host) {
                    $resolvedIps[] = $fallbackIp;
                }
            }

            if (empty($resolvedIps)) {
                throw new InvalidArgumentException("Unable to resolve DNS for host: {$host}");
            }

            // Validate each resolved IP against SSRF rules
            foreach ($resolvedIps as $ip) {
                if ($this->isPrivateOrReservedIp($ip)) {
                    throw new InvalidArgumentException("Host resolves to a restricted internal or private IP address ({$ip}).");
                }
            }
        }

        // Reconstruct canonical clean URL
        $portStr = ($port === 80 && $scheme === 'http') || ($port === 443 && $scheme === 'https') ? '' : ':' . $port;
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        $canonicalUrl = "{$scheme}://{$host}{$portStr}{$path}{$query}";

        return [
            'original_url' => $rawUrl,
            'normalized_url' => $canonicalUrl,
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'domain' => $this->extractRootDomain($host),
            'resolved_ips' => $resolvedIps,
            'primary_ip' => $resolvedIps[0] ?? null,
        ];
    }

    /**
     * Check if a hostname is on the blocked list or uses blocked internal suffixes.
     */
    public function isBlockedHostname(string $host): bool
    {
        if (in_array($host, self::BLOCKED_HOSTNAMES, true)) {
            return true;
        }

        foreach (self::BLOCKED_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address belongs to private, loopback, carrier-grade NAT, or cloud-metadata ranges.
     */
    public function isPrivateOrReservedIp(string $ip): bool
    {
        // Cloud metadata explicit check
        if ($ip === '169.254.169.254' || $ip === 'fd00:ec2::254') {
            return true;
        }

        // If IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Check PHP's native filter flags first
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            if (!filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
                return true;
            }

            // Check against comprehensive IPv4 CIDR blocks
            foreach (self::BLOCKED_IPV4_CIDRS as $cidr) {
                if ($this->ipv4InCidr($ip, $cidr)) {
                    return true;
                }
            }

            return false;
        }

        // If IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Check PHP native flags
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            if (!filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
                return true;
            }

            // Check against comprehensive IPv6 CIDR blocks
            foreach (self::BLOCKED_IPV6_CIDRS as $cidr) {
                if ($this->ipv6InCidr($ip, $cidr)) {
                    return true;
                }
            }

            // Check IPv4-mapped IPv6 addresses (e.g. ::ffff:192.168.1.1)
            if (str_starts_with(strtolower($ip), '::ffff:')) {
                $mappedIpv4 = substr($ip, 7);
                if (filter_var($mappedIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $this->isPrivateOrReservedIp($mappedIpv4);
                }
            }

            return false;
        }

        return true; // Not a valid IP, block defensively
    }

    /**
     * Check if IPv4 matches a CIDR range.
     */
    protected function ipv4InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        if ($mask === 0) {
            return true;
        }

        $maskBits = -1 << (32 - $mask);
        return ($ipLong & $maskBits) === ($subnetLong & $maskBits);
    }

    /**
     * Check if IPv6 matches a CIDR range.
     */
    protected function ipv6InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $bytes = (int) floor($mask / 8);
        $bits = $mask % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($bits > 0) {
            $ipByte = ord($ipBin[$bytes]);
            $subnetByte = ord($subnetBin[$bytes]);
            $bitMask = (0xFF << (8 - $bits)) & 0xFF;
            if (($ipByte & $bitMask) !== ($subnetByte & $bitMask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract root domain (e.g. blog.example.co.uk or sub.example.com -> example.com)
     */
    public function extractRootDomain(string $host): string
    {
        $parts = explode('.', $host);
        $count = count($parts);
        if ($count <= 2) {
            return $host;
        }

        $secondLevelTlds = ['co.uk', 'gov.uk', 'ac.uk', 'com.au', 'net.au', 'co.nz', 'co.jp', 'com.br', 'com.sg', 'co.in'];
        $lastTwo = $parts[$count - 2] . '.' . $parts[$count - 1];
        if (in_array($lastTwo, $secondLevelTlds, true) && $count >= 3) {
            return $parts[$count - 3] . '.' . $lastTwo;
        }

        return $parts[$count - 2] . '.' . $parts[$count - 1];
    }
}
