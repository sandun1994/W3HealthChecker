# W3HealthChecker — Website Monitoring Architecture
**Phase 4: Retention, Monitoring & Change Detection**

## 1. Architectural Overview
The Website Monitoring architecture transforms W3HealthChecker from a one-off scan tool into an ongoing website intelligence system that monitors health, regressions, and changes over time.

```
                          ┌────────────────────────┐
                          │   Monitored Websites   │
                          │   (Schedule: Daily/Wk) │
                          └───────────┬────────────┘
                                      │
                         [w3:monitoring:run Artisan]
                                      │
                          ┌───────────▼────────────┐
                          │   SSRF-Safe Scanner    │
                          │   (WebsiteScanService) │
                          └───────────┬────────────┘
                                      │
                       ┌──────────────┴──────────────┐
                       │                             │
               [Completed Scan]               [Previous Scan]
                       │                             │
                       └──────────────┬──────────────┘
                                      │
                         ┌────────────▼────────────┐
                         │ ChangeDetectionService  │
                         │ (12 Diagnostic Signals) │
                         └────────────┬────────────┘
                                      │
                       ┌──────────────┴──────────────┐
                       │                             │
                 [ScanChanges]              [AlertNotification]
                 (Persistence)              (Threshold Check)
                                                     │
                                            ┌────────▼────────┐
                                            │ MonitoringAlert │
                                            │ (Email/In-App)  │
                                            └─────────────────┘
```

---

## 2. Core Entities & Schema

### `monitored_websites`
- `id`: Primary key.
- `website_id`: Foreign key to `websites`.
- `user_id`: Nullable foreign key for future account binding (Phase 5).
- `schedule`: Enum (`daily`, `weekly`).
- `alert_email`: Destination email for notifications.
- `alert_channel`: Channel type (`email`, `in_app`).
- `alert_threshold`: Minimum score drop in points to trigger warnings (default: 5).
- `notify_on_critical_issues`: Boolean flag to notify on critical regressions.
- `notify_on_ssl_expiry`: Boolean flag to notify when certificates approach expiration.
- `last_scanned_at`: Timestamp of previous automated scan.
- `next_scan_at`: Timestamp when the next scan is due.
- `is_active`: Boolean status flag.
- `consecutive_failures`: Counter tracking network/DNS failure resilience.
- `status`: String (`active`, `failing`, `paused`).

### `scan_changes`
- `id`: Primary key.
- `website_id`: Foreign key to `websites`.
- `current_scan_id`: Foreign key to `scans`.
- `previous_scan_id`: Foreign key to previous `scans`.
- `change_type`: Unique event identifier (e.g., `robots_noindex_added`, `security_header_removed_strict-transport-security`, `overall_score_dropped_critical`).
- `category`: Pillar domain (`overall`, `seo`, `security`, `performance`, `accessibility`, `technical`, `ai_readiness`).
- `severity`: Enum (`critical`, `high`, `medium`, `low`, `info`).
- `title`: Human-readable event title.
- `description`: Detailed diagnostic explanation.
- `old_value`: Previous recorded value.
- `new_value`: Current recorded value.

### `monitoring_alerts`
- `id`: Primary key.
- `monitored_website_id`: Foreign key to `monitored_websites`.
- `scan_id`: Foreign key to `scans`.
- `channel`: Notification channel (`email`).
- `recipient`: Destination email address.
- `subject`: Email subject line.
- `severity`: Highest severity among changes in the batch.
- `message`: Formatted human-readable alert message.
- `changes_summary`: JSON summary of changes.
- `status`: Enum (`pending`, `sent`, `failed`).
- `sent_at`: Timestamp of dispatch.

---

## 3. Safe Execution & SSRF Protection
Automated monitoring strictly uses `WebsiteScanService`, guaranteeing that:
1. Every URL is normalized, checked for private CIDR ranges (RFC 1918, RFC 3927 cloud metadata `169.254.169.254`, loopbacks `127.0.0.1`), and port-restricted.
2. Scheduled executions never bypass rate limiting, timeouts, or safe response size caps.
3. Network failures are throttled: if a monitored website fails 3 consecutive times, its status transitions to `failing` and retries back off to 6-hour intervals.

---

## 4. Scheduling & Console Integration
The Artisan command:
```bash
php artisan w3:monitoring:run {--website-id=} {--force}
```
is scheduled hourly in `routes/console.php`:
```php
Schedule::command('w3:monitoring:run')->hourly();
```
It selects all active monitored websites where `next_scan_at <= now()`, runs the scan, records detected changes, and dispatches alerts if required.
