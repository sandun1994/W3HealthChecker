# W3HealthChecker — Privacy & Data Retention Architecture

## 1. Privacy Principles

W3HealthChecker is designed as a defensive website intelligence tool. We operate with strict data minimization principles:

1. **Passive Public Scanning Only:** We only request publicly accessible HTTP/HTTPS web documents. We do not authenticate, bypass paywalls, or access private endpoints.
2. **Zero Ingestion of Personal Data:** The scanner analyzes publicly broadcast HTML markup and HTTP response headers. We do not extract personal user records, form submission data, or user profiles.
3. **No Credential Logging:** Scans never accept, log, or forward authentication cookies, bearer tokens, or user credentials.

---

## 2. Data Collected & Stored

### What We Store:
- **Target URL & Domain:** The normalized domain and target URL submitted for analysis.
- **Diagnostic Metrics:** Public HTTP status code, latency (TTFB), header values (HSTS, CSP, server tokens), SSL certificate expiration date and issuer.
- **Extracted DOM Metadata:** Headings summary, count of scripts/stylesheets, count of images, Open Graph tags, Schema.org types.
- **Audit Findings:** The list of detected technical issues, severity ratings, and fix recommendations.
- **Timestamps:** When the scan started, was completed, or failed.

### What We DO NOT Store:
- Full response bodies or downloaded asset files.
- Authorization headers or cookies from target websites.
- End-user PII from target websites.
- Intrusive port or vulnerability exploit logs.

---

## 3. Data Retention & Cache Expiration

- **Scan Reports Cache:** Reports are cached for 30 minutes (`SCAN_CACHE_MINUTES=30`) to protect target web servers from redundant queries.
- **Public Reports:** Users can access reports via non-sequential public identifiers (`/report/{domain}/{public_id}`).
- **Logs Retention:** Application diagnostic logs (scan started/completed/failed) store only timestamps, domain, public ID, and duration. IP addresses are used solely for real-time rate limiting (`RateLimiter`) and are discarded according to the rate window.
