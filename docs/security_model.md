# W3HealthChecker — Security Model & SSRF Defense Architecture

## 1. Threat Model & Purpose

As a service that requests user-provided URLs on behalf of users, W3HealthChecker could potentially be abused as a Server-Side Request Forgery (SSRF) proxy or port scanner if unhardened.

The security model establishes defense-in-depth across DNS resolution, network addresses, protocol schemes, ports, redirect hops, payload limits, and response types.

---

## 2. Multi-Layer SSRF Protections

### A. Network & IP Address Filtering
Every resolved IP address is checked against both IPv4 and IPv6 private, reserved, multicast, and cloud metadata CIDR ranges:

**IPv4 Blocked Ranges:**
- `0.0.0.0/8` (Current network)
- `10.0.0.0/8` (RFC 1918 Private)
- `100.64.0.0/10` (Carrier-grade NAT)
- `127.0.0.0/8` (Loopback)
- `169.254.0.0/16` (Link-local & Cloud Metadata)
- `172.16.0.0/12` (RFC 1918 Private)
- `192.0.0.0/24` (IETF Protocol Assignments)
- `192.0.2.0/24` (TEST-NET-1)
- `192.168.0.0/16` (RFC 1918 Private)
- `198.18.0.0/15` (Benchmark testing)
- `198.51.100.0/24` (TEST-NET-2)
- `203.0.113.0/24` (TEST-NET-3)
- `224.0.0.0/4` (Multicast)
- `240.0.0.0/4` (Reserved)
- `255.255.255.255/32` (Broadcast)

**IPv6 Blocked Ranges:**
- `::1/128` (Loopback)
- `::/128` (Unspecified)
- `fc00::/7` (Unique Local Address)
- `fe80::/10` (Link-local)
- `ff00::/8` (Multicast)
- `::ffff:0:0/96` (IPv4-mapped IPv6)
- `fd00:ec2::254` (AWS IPv6 IMDS)

**Explicit Cloud Metadata Endpoints:**
- `169.254.169.254`
- `metadata.google.internal`
- `169.254.169.254.xip.io` / nip.io patterns

### B. DNS Rebinding Protection
Hostnames are resolved to physical IP addresses via `dns_get_record()` and `gethostbynamel()`. Both A and AAAA records are verified. If any resolved IP belongs to a forbidden CIDR, the scan is rejected immediately before socket connection.

### C. Per-Hop Redirect Validation
- cURL's automatic `CURLOPT_FOLLOWLOCATION` is explicitly disabled.
- Redirects are manually followed hop-by-hop (maximum 5 hops).
- Destination URLs at each hop are independently re-checked through `UrlValidationService` to prevent public-to-private SSRF pivoting.
- Circular redirect loops are detected and aborted.

### D. Port Policy
Only standard HTTP/HTTPS web ports are allowed:
- Port `80` (HTTP)
- Port `443` (HTTPS)
- Port `8080` (HTTP Alternate)
- Port `8443` (HTTPS Alternate)
All other ports (e.g. 21, 22, 25, 3306, 6379, 9200) are blocked to prevent internal port scanning.

### E. Protocol Restriction
Only `http://` and `https://` schemes are accepted.
Schemes such as `file://`, `ftp://`, `gopher://`, `data:`, `javascript:`, `ssh://`, `dict://` are strictly rejected.

### F. Payload & Timeout Limits
- **Max Response Size:** 5 MB (`5,242,880 bytes`). Streaming checks terminate download early if headers or body exceed limit.
- **Connection Timeout:** 5 seconds.
- **Total Request Timeout:** 15 seconds.
- **Content-Type Filter:** Only `text/html` and `application/xhtml+xml` documents are accepted. Binary downloads (PDFs, executables, ZIPs) are rejected early without downloading.

---

## 3. Application Security Headers (W3HealthChecker Itself)

W3HealthChecker implements strict defensive headers on all application responses:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com; font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:; connect-src 'self' http: https: ws: wss:; frame-ancestors 'self';`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (when served over TLS).
