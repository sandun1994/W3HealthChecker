# W3HealthChecker — Change Detection Engine
**Diagnostic Regression & Delta Methodology**

## 1. Objective & Philosophy
Websites constantly undergo deployment, redesign, and configuration updates. Traditional monitors only check whether an HTTP status code is 200. W3HealthChecker's Change Detection Engine analyzes deep technical shifts across all 7 website health pillars, detecting:
- Critical search indexing barriers before traffic drops.
- Dropped security headers before exposure.
- Server latency surges and page weight bloat.

To prevent alert fatigue and spam, changes are strictly classified by severity.

---

## 2. Monitored Change Signals & Severities

| Category | Signal Detected | Trigger Condition | Severity |
| :--- | :--- | :--- | :--- |
| **Overall** | Severe Score Regression | Composite score dropped >= 10 pts | `CRITICAL` |
| **Overall** | Score Regression Warning | Composite score dropped 5 to 9 pts | `HIGH` |
| **Overall** | Health Score Improvement | Composite score improved >= 5 pts | `INFO` |
| **Pillars** | Category Score Drop | Any pillar score regressed >= 15 pts | `HIGH` |
| **SEO** | Noindex Added | Robots meta tag added `noindex` | `CRITICAL` |
| **SEO** | Title Removed | `<title>` tag removed from document | `HIGH` |
| **SEO** | Title Modified | Title text updated | `MEDIUM` |
| **SEO** | Meta Description Removed | Meta description tag removed | `HIGH` |
| **SEO** | Meta Description Updated | Meta description content modified | `MEDIUM` |
| **SEO** | Canonical URL Changed | Canonical tag destination modified | `HIGH` |
| **SEO** | Primary H1 Removed | Primary `<h1>` heading removed | `HIGH` |
| **Security** | HSTS Header Removed | `Strict-Transport-Security` header missing | `CRITICAL` |
| **Security** | CSP Header Removed | `Content-Security-Policy` header missing | `HIGH` |
| **Security** | Clickjacking Header Removed | `X-Frame-Options` header missing | `MEDIUM` |
| **Security** | MIME Sniffing Header Removed | `X-Content-Type-Options` header missing | `MEDIUM` |
| **Security** | SSL Validity Lost | HTTPS certificate failed validation | `CRITICAL` |
| **Performance** | Response Time Surge | TTFB surged by >= 1000ms (and > 1200ms) | `HIGH` |
| **AI Readiness**| Structured Data Removed | Schema.org JSON-LD count dropped to 0 | `HIGH` |
| **Issues** | New Critical Issue | Any new issue categorized as `critical` | `CRITICAL` |

---

## 3. Comparison Pipeline & Delta Calculation
1. **Metadata Persistence**: `ScanMetric` persists a snapshot (`meta_summary`) alongside raw headers, headings, schemas, and SSL data.
2. **Deterministic Evaluation**: `ChangeDetectionService` takes `$currentScan` and `$previousScan` (retrieved by `completed_at` ordering) and performs atomic diffing.
3. **Delta Extraction**:
   - `overall`: Current overall score - Previous overall score
   - `seo`: Current SEO pillar - Previous SEO pillar
   - `security`: Current Security pillar - Previous Security pillar
   - `performance`: Current Performance pillar - Previous Performance pillar
   - `accessibility`: Current Accessibility pillar - Previous Accessibility pillar
   - `mobile`: Current Mobile pillar - Previous Mobile pillar
   - `technical`: Current Technical pillar - Previous Technical pillar
   - `ai_readiness`: Current AI Readiness pillar - Previous AI Readiness pillar
4. **Historical Trajectory**: Exposes historical progression (`72 -> 76 -> 81 -> 78`) with linked report identifiers.
