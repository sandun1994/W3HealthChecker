# W3HealthChecker — Scanner Engine & Analyzer Documentation

## 1. Scanner Workflow

When a scan is initiated, the engine executes an atomic pipeline:

```
[Target URL]
     │
     ▼
1. Validate & Normalize URL (UrlValidationService)
     │
     ▼
2. Check Rate Limit & Cache Deduplication (ScanController)
     │
     ▼
3. Safe HTTP Fetch with Timeouts & Size Guard (HttpFetchService)
     │
     ▼
4. Auxiliary Discovery (/robots.txt, /sitemap.xml, /llms.txt, sensitive paths)
     │
     ▼
5. HTML Document Parsing (HtmlDocument - DOMDocument & XPath)
     │
     ▼
6. Multi-Pillar Analysis:
   - TechnicalAnalyzer
   - SeoAnalyzer
   - SecurityAnalyzer
   - PerformanceAnalyzer
   - AccessibilityAnalyzer
   - MobileAnalyzer
   - AiReadinessAnalyzer
   - StructuredDataAnalyzer & SocialAnalyzer
     │
     ▼
7. Scoring & Recommendation Consolidation:
   - ScoreService (Category & Overall Score calculation)
   - RecommendationService (Priority Ranking: (Severity * Impact * Conf) / Effort)
     │
     ▼
8. Atomic Database Persistence & Log Dispatch
```

---

## 2. Pillar Analyzers Overview

| Pillar | Weight | Key Technical Checks |
|---|---|---|
| **Technical & On-Page SEO** | 20% | Title length (30-60 chars target), meta description presence & length, canonical link consistency, H1 hierarchy, image alt coverage %, sitemap accessibility. |
| **Defensive Security & SSL** | 20% | HTTPS enforcement, certificate validity & expiration, HSTS, CSP, X-Frame-Options, X-Content-Type-Options: nosniff, Referrer-Policy, Permissions-Policy, server software disclosure, passive exposure of `.env` / `.git`. |
| **Performance & Document Hygiene** | 15% | Server TTFB latency (<600ms target), Gzip/Brotli text compression, HTML document weight (<100KB target), external script count, resource hints (preconnect, preload). |
| **Accessibility Indicators** | 15% | Root `<html lang>` attribute, button accessible names, link anchor text, form input `<label>` pairing, iframe title attributes, duplicate element IDs. Includes WCAG disclaimer. |
| **Mobile Readiness** | 10% | `<meta name="viewport">` presence, `width=device-width`, `initial-scale=1`, pinch-to-zoom restriction checks. |
| **Technical Infrastructure** | 10% | HTTP status code handling (4xx/5xx), redirect chain hop count (<= 1 hop target), DNS resolution latency. |
| **AI & Search Readiness** | 10% | Schema.org JSON-LD structured data depth, semantic heading chunking outline, `/llms.txt` discovery (objective convention, not ranking guarantee), AI bot directives in `robots.txt`. |

---

## 3. Disclaimers & Integrity Standards

- **Core Web Vitals:** Passive server-side HTML scraping cannot measure browser rendering metrics (LCP, INP, CLS). The platform explicitly labels server latency as TTFB and displays:
  > *"Browser Core Web Vitals are not directly measured by this passive server-side scan. Field metrics require Chrome User Experience Report (CrUX) or real-user measurement (RUM)."*
- **AI Conventions:** `llms.txt` is treated as an emerging convenience specification, explicitly noting it is not a Google ranking factor.
- **Accessibility:** Labeled clearly as a fast automated heuristic check that does not substitute for a manual WCAG 2.2 audit.
