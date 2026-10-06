# Platform Architecture — W3HealthChecker

## 1. High-Level Architecture

```
                          W3HEALTHCHECKER PLATFORM
                                     │
      ┌──────────────────────────────┼──────────────────────────────┐
      │                              │                              │
 WEB APPLICATION                  REST API                  SCHEDULED RUNNERS
 (Inertia/Blade + React)         (v1 OpenAPI)             (Monitoring & Queues)
      │                              │                              │
      └──────────────────────────────┼──────────────────────────────┘
                                     │
                       CENTRAL APPLICATION CORE
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         │                           │                           │
  SECURITY FIREWALL             FEATURE GATING               AUDIT LOGGING
  (SSRF, Rate Limiting)      (Free, Pro, Agency)          (Sanitized Records)
         │                           │                           │
         └───────────────────────────┼───────────────────────────┘
                                     │
                         SCANNING & AUDIT PIPELINE
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         │                           │                           │
  HTTP FETCH ENGINE          PILLAR ANALYZERS           CHANGE DETECTION
  (Passive, Safe DNS)      (SEO, Sec, Perf, etc.)      (Unified Diffing Engine)
         │                           │                           │
         └───────────────────────────┼───────────────────────────┘
                                     │
                       STORAGE & OBSERVABILITY LAYER
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         │                           │                           │
  RELATIONAL DATABASE             CACHE / REDIS             OBSERVABILITY
  (Multi-tenant Isolated)     (Scan Cache & Queues)      (Metrics & Health API)
```

## 2. Core Subsystems
1. **Security Engine**: Active SSRF protection, strict private IP blocklists, cloud metadata blocking, safe redirect resolution, response byte limits (2MB).
2. **Scanner & Analyzers**: 7 pillars of website health scoring + 19 specialized tools + Controlled multi-page crawler.
3. **Monitoring & Change Detection**: Automated scheduled audits (Daily, Weekly), change classification, regression alerting.
4. **Multi-Tenant Workspaces**: User accounts, projects, agency clients, team management, and RBAC authorization.
5. **Billing & Entitlements**: Configurable plans (`Free`, `Pro`, `Agency`), centralized `FeatureGate` engine, webhook handling.
6. **Developer Ecosystem**: REST API v1, SHA-256 hashed API keys, signed webhook notifications, interactive documentation, OpenAPI 3.0 specification.
7. **Reliability & Observability**: Structured `/health` endpoint, `X-Request-Id` tracing, response timing headers, automated regression suite.
