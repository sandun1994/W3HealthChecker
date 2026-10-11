# W3HealthChecker — Website Health Intelligence Methodology

## 1. Overview
The W3HealthChecker Intelligence layer ([`HistoricalIntelligenceService`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Intelligence/HistoricalIntelligenceService.php)) transforms raw scan snapshots into continuous website health intelligence.
It analyzes historical audit trajectories, tracks the lifecycle of individual technical findings, groups related findings into root-cause clusters, and provides context through privacy-preserving cohort benchmarks.

---

## 2. Issue Lifecycle Classification
Instead of presenting a static list of findings, the system tracks each issue across consecutive and historical audits:

| Lifecycle State | Definition | Significance |
|---|---|---|
| **Newly Detected** | Finding was not present in the immediately preceding scan. | Represents newly introduced technical debt, broken deployments, or altered assets. |
| **Persistent** | Finding was present in the preceding scan and remains unresolved. | Highlights unresolved technical issues requiring prioritized developer attention. |
| **Resolved** | Finding was present in the preceding scan but successfully absent in the current audit. | Validates successful engineering remediation. |
| **Regressed** | Finding was previously resolved in past scans but has returned. | Signals deployment regressions or configuration reversions. |

---

## 3. Inferred Root-Cause Clustering
To prevent finding overload, findings are mapped to hypothesized root-cause areas:
1. **Transport Security & Header Hygiene**: Clusters missing HSTS, CSP, and TLS vulnerabilities to indicate server-level hardening gaps.
2. **Canonicalization & Crawlability Directives**: Combines conflicting canonicals, redirect loops, and robots blocks to address URL authority.
3. **Asset Delivery & Payload Weight**: Unifies uncompressed text, heavy DOM trees, and high TTFB into delivery pipeline optimization.
4. **Semantic Hierarchy & Accessibility Contracts**: Groups missing alt tags, missing H1s, and poor contrast into WCAG accessibility improvements.
5. **Machine Comprehension & Structured Entities**: Connects missing Schema.org JSON-LD and OpenGraph tags to machine readability.
