# Observability & Distributed Tracing

## 1. Tracing & Headers
Every HTTP request passing through W3HealthChecker is automatically instrumented with:
- `X-Request-Id`: Unique 24-character random identifier (`req_...`) or pass-through of upstream load balancer request ID.
- `X-Response-Time-Ms`: Precise server processing time in milliseconds.

## 2. Health Monitoring (`GET /health`)
- Uptime diagnostic endpoint verifying:
  - Database connectivity and micro-benchmark query latency.
  - Cache storage round-trip operational verification.
  - Queue driver status and pending jobs backlog.
- Returns HTTP 200 on healthy; HTTP 503 on degraded or failed subsystems.
- Never discloses database credentials, secrets, or internal stack traces.
