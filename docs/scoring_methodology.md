# W3HealthChecker Scoring Methodology & Normalization Specification

**Version:** 1.0 Production  
**Scope:** Transparent, evidence-backed website health scoring across 7 technical pillars.

---

## 1. Executive Scoring Philosophy

W3HealthChecker is built on four core principles:
1. **No Artificial Zeroes or Penalties:** A failed sub-check never destroys unrelated categories.
2. **Transparent Weights:** Every category has a fixed, documented contribution to the overall score.
3. **No Speculative Ranking Guarantees:** Scores reflect technical hygiene and best practices, not speculative search engine ranking claims.
4. **Distinction of Lab vs. Field Data:** Server-side audits reflect single synthetic requests; they are never disguised as browser-rendered Core Web Vitals.

---

## 2. Category Weight Distribution (100% Total)

The composite Website Health Score (0–100) is calculated as a weighted average across the 7 distinct pillars:

| Pillar | Weight | Focus Areas |
|---|---|---|
| **Technical & On-Page SEO** | **20%** | Titles, meta descriptions, single H1, canonical tags, image alt coverage, sitemaps, robots.txt |
| **Defensive Security & SSL** | **20%** | HTTPS, TLS certificates, HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, passive exposure checks |
| **Performance & Document Hygiene** | **15%** | Server TTFB, Gzip/Brotli compression, HTML payload byte weight, external scripts count, lazy loading |
| **Accessibility Indicators** | **15%** | HTML lang, button names, link names, form input labels, iframe titles, duplicate ID avoidance |
| **Mobile Readiness** | **10%** | Viewport meta tag, responsive width scaling, pinch-to-zoom accessibility |
| **Technical Infrastructure** | **10%** | HTTP status codes, multi-hop redirect chains, server software version leaks |
| **AI & Search Readiness** | **10%** | Schema.org JSON-LD depth & syntax, semantic document hierarchy, `/llms.txt`, AI bot crawlability |

### Mathematical Formula:
$$\text{Overall Score} = \sum_{i=1}^{7} (\text{Pillar Score}_i \times \text{Weight}_i)$$

Where:
$$\sum_{i=1}^{7} \text{Weight}_i = 1.00$$

---

## 3. Score Thresholds & Status Labels

Every numeric score maps directly to a standardized, accessible label and color indicator:

| Range | Status Label | Meaning | Color Indicator |
|---|---|---|---|
| **90 – 100** | **Excellent** | Exceptional technical hygiene with minimal or no observable issues | Emerald Green (`#10b981`) |
| **80 – 89** | **Good** | Strong foundation with minor non-critical optimization opportunities | Royal Blue (`#3b82f6`) |
| **70 – 79** | **Needs Improvement** | Several medium-to-high priority issues affecting search, speed, or security | Amber (`#f59e0b`) |
| **50 – 69** | **Poor** | Substantial technical flaws requiring immediate engineering remediation | Orange (`#f97316`) |
| **0 – 49** | **Critical** | Major blocking issues (e.g. site unencrypted, blocked by noindex, or unreachable) | Rose Red (`#f43f5e`) |

---

## 4. Multi-Factor Prioritization Formula ("Fix These First")

Rather than arbitrarily dumping hundreds of issues, W3HealthChecker calculates an algorithmic **Priority Score** for each detected finding:

$$\text{Priority Score} = \frac{\text{Severity Weight} \times \text{Impact Factor} \times \text{Confidence Multiplier}}{\text{Effort Factor}}$$

### Factor Definitions:
1. **Severity Weight:**
   - `Critical`: 100
   - `High`: 70
   - `Medium`: 40
   - `Low`: 20
   - `Info`: 10
2. **Impact Factor:**
   - Range: `1.0` (standard) to `1.5` (critical site-wide blocker).
3. **Confidence Multiplier:**
   - `High` (Directly verified in HTML / headers): `1.0`
   - `Medium` (Observed from partial response or redirect): `0.8`
   - `Low` (Inferred): `0.6`
4. **Effort Factor:**
   - `Quick Fix (< 5 mins)` (e.g. meta tag addition, HTTP response header): `1.0`
   - `Moderate (10–20 mins)` (e.g. image alt tags, JSON-LD schema): `1.2`
   - `Substantial` (e.g. database query optimization, CDN edge caching): `1.6`

The top 5 findings with the highest Priority Scores are elevated to the **Fix These First** section, providing immediate high-ROI action items.

---

## 5. Non-Fabrication Guarantee

- **Core Web Vitals:** If Google Chrome UX Report (CrUX) data is unavailable, W3HealthChecker explicitly states *"Browser Core Web Vitals are not directly measured by this passive server-side scan"*, rather than inventing synthetic numbers.
- **AI Search Readiness:** Presence of `/llms.txt` or Schema.org JSON-LD is framed accurately as machine discoverability and entity disambiguation—**never** as a guaranteed search ranking factor.
