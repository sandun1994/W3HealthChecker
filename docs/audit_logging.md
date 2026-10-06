# Audit Logging & Compliance Architecture

## 1. Objective
Enterprise compliance requires an immutable, privacy-respecting log of administrative and operational actions across the platform.

## 2. Data Model
Recorded within the `audit_logs` table:
- `user_id`: Foreign key referencing the authenticated actor (null for unauthenticated system events).
- `action`: Standardized event slug (e.g. `login`, `api_key.created`, `website.added`, `plan.changed`).
- `resource_type`: Targeted model entity (e.g. `Website`, `ApiKey`, `Subscription`).
- `resource_id`: Targeted entity identifier.
- `ip_address`: Originating IP address of the request.
- `metadata`: JSON payload containing context.

## 3. Strict Secret Sanitization
All metadata passed to [`App\Services\Security\AuditLogger::log()`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Security/AuditLogger.php) undergoes recursive redaction against known sensitive keywords:
- `password`, `password_confirmation`
- `token`, `plain_token`, `auth_token`
- `secret`, `key_hash`, `api_key`
- `card`, `cvv`

Any key matching these patterns is immediately replaced with `[REDACTED]`.

## 4. Audited Events
1. Authentication: `auth.login`, `auth.logout`, `auth.password_reset`.
2. Credentials: `api_key.created`, `api_key.revoked`, `api_key.rotated`.
3. Workspaces & Projects: `project.created`, `project.deleted`, `website.added`, `website.deleted`.
4. Subscriptions & Billing: `plan.upgraded`, `plan.downgraded`, `subscription.cancelled`.
5. Team Members: `member.invited`, `member.role_changed`, `member.removed`.
