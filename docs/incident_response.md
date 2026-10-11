# W3HealthChecker — Incident Response & Operational Governance

## 1. Incident Severity Levels

| Severity | Definition | Target Response Time | Actions |
|---|---|:---:|---|
| **P1 - Critical** | Scanner pipeline offline, database inaccessible, or critical vulnerability detected. | < 30 mins | Page on-call engineers, activate maintenance mode (`php artisan down`), triage issue, patch and deploy. |
| **P2 - High** | Degraded performance, queue processing delays, or integration webhook failures. | < 2 hours | Scale queue workers, inspect supervisor logs, drain dead-letter jobs. |
| **P3 - Medium** | Non-blocking bug in report view or false positive on low-severity audit rule. | < 24 hours | Create bug ticket, replicate locally with test fixtures, deploy release. |
| **P4 - Low** | Minor cosmetic styling issue or non-breaking documentation typo. | Next release | Address in standard sprint release cycle. |

---

## 2. Emergency Escalation & Triage
1. **Health Verification**: Check `https://w3healthchecker.com/health` to isolate failing subsystems (Database, Cache, Queue).
2. **Worker Recovery**: Restart queue workers gracefully via `php artisan queue:restart` or Supervisor restart.
3. **Database Safeguard**: Verify transaction logs and trigger snapshot before recovery actions.
4. **Post-Mortem**: Document root causes, timeline, and mitigation regression tests within 48 hours.
