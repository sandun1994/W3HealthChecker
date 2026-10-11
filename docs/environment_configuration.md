# W3HealthChecker — Environment Configuration Specification

## 1. Environment Segregation Matrix

| Configuration Variable | Local Development | Staging | Production | Description / Security Impact |
|---|---|---|---|---|
| `APP_ENV` | `local` | `staging` | `production` | Dictates error verbosity and service bindings. |
| `APP_DEBUG` | `true` | `false` | `false` | **Critical**: Must never be `true` in production to prevent stack trace leakage. |
| `APP_URL` | `http://localhost:8000` | `https://staging.w3healthchecker.com` | `https://w3healthchecker.com` | Base URL used for email links and absolute URLs. |
| `DB_CONNECTION` | `sqlite` or `mysql` | `mysql` | `mysql` | Database driver. |
| `DB_DATABASE` | `database.sqlite` | `w3_staging` | `w3healthchecker` | Target database schema. |
| `SESSION_DRIVER` | `database` / `file` | `database` / `redis` | `database` / `redis` | Session storage adapter. |
| `SESSION_SECURE_COOKIE`| `false` | `true` | `true` | Ensures cookies are only transmitted over TLS/HTTPS. |
| `QUEUE_CONNECTION` | `database` / `sync` | `database` / `redis` | `database` / `redis` | Queue broker. |
| `CACHE_STORE` | `database` / `array` | `database` / `redis` | `database` / `redis` | Cache backend. |
| `MAIL_MAILER` | `log` | `smtp` / `log` | `smtp` / `ses` / `postmark` | Outbound transactional mail driver. |
| `SCAN_MAX_RESPONSE_BYTES`| `5242880` (5MB) | `5242880` (5MB) | `5242880` (5MB) | Network payload limit per scanned page. |
| `SCAN_TIMEOUT_CONNECT` | `5` | `5` | `5` | Socket connect timeout in seconds. |
| `SCAN_TIMEOUT_TOTAL` | `15` | `15` | `15` | Total HTTP response timeout in seconds. |
| `MAX_CONCURRENT_SCANS` | `5` | `10` | `25` | Concurrency ceiling for worker processing. |

---

## 2. Production Security Controls
In production environments:
1. **Debug Mode Disabled**: `APP_DEBUG=false` ensures all unhandled errors return generic, sanitized responses (`500 Server Error`) with unique tracking IDs.
2. **Reverse Proxy Trust**: Laravel must trust Cloudflare IP ranges so that rate limiters and audit logs capture true client IPs rather than Cloudflare edge IPs.
3. **Session Cookies**:
   - `SESSION_SECURE_COOKIE=true`
   - `SESSION_HTTP_ONLY=true`
   - `SESSION_SAME_SITE=lax`
4. **Secrets Redaction**: All API keys, database credentials, and webhook signing secrets must be stored exclusively in `.env` and never tracked in git or committed to repository branches.
