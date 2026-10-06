# Advanced Intelligence Engine

## 1. Overview
The W3HealthChecker Intelligence Engine synthesizes deep technical signals from raw HTML and network data into actionable insights across four critical dimensions:

```
Intelligence Engine
 ├── TechnologyDetector (Passive CMS, Framework, CDN, Analytics discovery)
 ├── ResourceAnalyzer (Scripts, Stylesheets, Images, Fonts, 3rd-party domains)
 ├── ContentQualityAnalyzer (Text/HTML ratio, headings, thin content checks)
 └── AI Search Readiness (Robots directives for GPTBot, ClaudeBot, Google-Extended)
```

## 2. Resource & Third-Party Dependency Analysis
- Categorizes all embedded assets by type.
- Isolates third-party domains (e.g. ad networks, tracking pixels, external CDNs) to compute the external dependency footprint.
- Identifies critical performance attributes like `async`, `defer`, and `loading="lazy"`.

## 3. AI Search Readiness Model
We do not make deceptive claims like *"AI score guarantees ChatGPT citations"*. Instead:
- We analyze technical readability signals: structured Schema.org data, clean semantic HTML hierarchy (`<main>`, `<article>`, `<nav>`), and robots.txt permission directives for leading LLM crawlers (`GPTBot`, `ClaudeBot`, `Google-Extended`, `CCBot`, `PerplexityBot`).
- This equips webmasters with actionable visibility into their readiness for generative AI answer engines.
