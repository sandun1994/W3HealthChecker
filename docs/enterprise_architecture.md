# Enterprise Architecture — W3HealthChecker Platform

## 1. Overview
W3HealthChecker is designed with an enterprise-ready tiered architecture that guarantees multi-tenant isolation, granular Role-Based Access Control (RBAC), immutable audit logging, and high-throughput scanning pipelines.

## 2. Multi-Tenant Model
Tenant boundaries are strictly maintained across databases and application layers:
```
Organization (Tenant Boundary)
  ├── Teams / Members (RBAC: Owner, Admin, Member, Viewer)
  ├── Projects (Logical grouping: Staging, Production, Client Workspaces)
  │     └── Websites
  │           ├── Historical Scans
  │           ├── Change Audits
  │           └── Monitoring Runs
  ├── API Keys & Webhooks (Scoped to Tenant)
  └── Subscriptions & Billing
```

### Isolation Guarantees
- **Data Boundary**: Every query for projects, websites, scans, clients, API keys, or webhooks resolves through the authenticated user's organization boundary.
- **Cross-Tenant Prevention**: Foreign tenant identifiers passed in route parameters trigger immediate `404 Not Found` or `403 Forbidden` responses.
- **Secret Sanitization**: API tokens are stored using irreversible SHA-256 hashes (`key_hash`). Plain tokens are only emitted upon creation.

## 3. Scalable Execution Pipeline
- **Decoupled Queuing**: High-volume scan triggers (manual, scheduled, bulk API) are enqueued in Redis / database worker queues.
- **Worker Isolation**: Scans execute under strict concurrency limits and domain throttles to prevent target DDoS or system starvation.
- **Passive Defensive Guarantees**: Strict SSRF firewalling (RFC 1918, link-local, loopback, cloud metadata blocking) applies uniformly across manual audits, monitoring workers, and bulk API triggers.
