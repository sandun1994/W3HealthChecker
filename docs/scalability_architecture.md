# Scalability Architecture

## 1. Capacity Goals
The target architecture scales smoothly from 100 to 100,000+ monthly visitors and tens of thousands of audits:
- **Stateless App Tier**: Web requests are horizontally scalable behind Nginx / Cloudflare load balancers.
- **Dedicated Queue Workers**: Scans, report calculations, and automated monitoring run asynchronously on queue workers.
- **Database Partitioning by Public Token**: Scans and reports are addressable via opaque UUID `public_id`, keeping sequential database IDs unexposed.

## 2. Queue Isolation
- **Default Queue**: Authentication, workspace CRUD, and billing webhooks.
- **Scans Queue (`scans`)**: CPU and network-intensive HTTP audits, DOM parsing, and recommendation generation.
- **Monitoring Queue (`monitoring`)**: Periodic batch evaluation scheduled hourly.
- **Concurrency Guard**: Concurrency is capped (configurable via `MAX_CONCURRENT_SCANS`) to prevent network interface saturation or worker deadlocks.
