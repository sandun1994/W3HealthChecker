# W3HealthChecker — Score Versioning & Comparability

## 1. Score Transparency & Version Tagging
As diagnostic rules, web standards, and security requirements evolve, scoring algorithms must be transparently versioned.

Current Methodology Version: **`2.0`**

### Principles
1. **Preservation of Historical Integrity**:
   - Past scan scores are never silently modified or retroactively rewritten when new analyzer rules are introduced.
   - Each audit payload specifies the active `score_version`.
2. **Explicit Non-Google Disclaimer**:
   - The overall W3HealthChecker score is a deterministic, technical composite of 7 health pillars.
   - It is never represented as an official Google ranking factor, PageRank equivalent, or certified third-party security certification.
3. **Transparent Weights**:
   - SEO: 20%
   - Security: 20%
   - Performance: 15%
   - Accessibility: 15%
   - Mobile: 10%
   - Technical: 10%
   - AI / Search Readiness: 10%
