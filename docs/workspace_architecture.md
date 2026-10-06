# Workspace Architecture

## 1. Domain Model
Workspaces organize scanned websites into logical operational containers called **Projects**:

```
User (Account Holder)
 ├── Projects ("Personal", "Client A", "SaaS Portfolio")
 │    └── Websites (domain, scheme, canonical_url, project_id)
 │         ├── Scans (historical audit runs, scores, pillar breakdowns)
 │         ├── MonitoredWebsite (daily / weekly recurring schedules)
 │         └── ScanChanges (regression and delta logs)
```

## 2. Key Entities
- **User**: The root tenant for individual or agency accounts.
- **Project**: Represents a grouping of related websites (e.g., "Personal Sites", "Client Portfolios").
- **Website**: The root target domain entity, referencing `user_id` and optional `project_id`.
- **Scan**: An execution instance tied to a website, optionally linked to `user_id` for account attribution.

## 3. Endpoints & Capabilities
- `GET /api/workspace/overview`: Returns aggregate counts (websites, projects, monitored targets, scans) and portfolio average scores.
- `GET /api/workspace/websites`: Lists all saved websites with their latest scan scores, assigned project, and active monitoring schedules.
- `POST /api/workspace/websites`: Validates target URL through SSRF defensive pipeline, creates or updates the website, assigns it to a project, and optionally configures automated monitoring.
- `DELETE /api/workspace/websites/{id}`: Unlinks a website from the user's workspace without destroying the underlying historical scan data.
- `GET /api/workspace/projects`: Lists user projects with attached website counts.
- `POST /api/workspace/projects`: Creates a new named project container.
- `DELETE /api/workspace/projects/{id}`: Deletes the project and safely detaches websites from the deleted project (`project_id = null`).

## 4. Multi-Tenant Authorization
All queries strictly enforce `$request->user()->id` ownership:
- Foreign project assignment is blocked with HTTP 403.
- Accessing or modifying another user's website record is blocked with HTTP 403.
- Database records never leak cross-tenant metadata.
