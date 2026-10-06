# Role-Based Access Control (RBAC) Specification

## 1. Enterprise Roles
W3HealthChecker implements a 4-tier hierarchical Role-Based Access Control model enforced by [`App\Services\Security\RbacManager`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Security/RbacManager.php):

| Role | Description |
|---|---|
| **Owner** | Full administrative and organizational authority, including billing and member management. |
| **Admin** | Manages workspaces, projects, team members, monitoring, API keys, and webhooks. Cannot modify billing or delete the organization. |
| **Member** | Operational role capable of adding websites, triggering scans, viewing reports, and setting up monitoring. |
| **Viewer** | Read-only stakeholder role with access to view dashboards, examine reports, and export executive summaries. |

---

## 2. Capability Matrix

| Permission | Owner | Admin | Member | Viewer |
|---|:---:|:---:|:---:|:---:|
| `manage_organization` | ✅ | ❌ | ❌ | ❌ |
| `manage_billing` | ✅ | ❌ | ❌ | ❌ |
| `manage_members` | ✅ | ✅ | ❌ | ❌ |
| `manage_websites` | ✅ | ✅ | ✅ | ❌ |
| `run_scans` | ✅ | ✅ | ✅ | ❌ |
| `view_reports` | ✅ | ✅ | ✅ | ✅ |
| `export_reports` | ✅ | ✅ | ✅ | ✅ |
| `manage_api_keys` | ✅ | ✅ | ❌ | ❌ |
| `manage_monitoring` | ✅ | ✅ | ✅ | ❌ |
| `manage_webhooks` | ✅ | ✅ | ❌ | ❌ |

---

## 3. Enforcement Implementation
All permissions are checked using the centralized helper:
```php
if (!RbacManager::can($userRole, 'manage_api_keys')) {
    abort(403, 'Unauthorized capability for role');
}
```
This avoids ad-hoc role comparisons across controllers and guarantees consistent access boundaries.
