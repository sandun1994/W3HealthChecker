# Alert Rule Engine & Notification Architecture

## 1. Overview
The W3HealthChecker Alert Rule Engine ([`App\Services\Monitoring\AlertRuleEngine`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Monitoring/AlertRuleEngine.php)) provides proactive detection of technical degradation, score drops, and security anomalies without generating notification fatigue.

## 2. Default Alert Rule Definitions

| Rule ID | Trigger Condition | Severity | Description |
|---|---|:---:|---|
| `rule_score_threshold` | Overall Health Score < 70 | HIGH | Overall health score has breached the acceptable quality baseline. |
| `rule_security_pillar_drop` | Security score drops by >= 10 points between scans | CRITICAL | Defensive headers removed, SSL issues, or sensitive files exposed. |
| `rule_ssl_expiry_critical` | SSL certificate expires in <= 30 days | CRITICAL | Certificate approaching expiration; renew before browser warnings trigger. |
| `rule_critical_issues_detected` | Any issue with severity `critical` detected | CRITICAL | Vulnerabilities or configuration flaws requiring immediate intervention. |

## 3. Rate Limiting & Deduplication
To prevent alert fatigue:
- Alerts are deduplicated against previously acknowledged or unchanged issues.
- Notifications are rate-limited per domain (maximum 1 digest email per 24 hours unless a `CRITICAL` regression is triggered).
- In-app notification feeds retain the historical log of triggered alerts.
