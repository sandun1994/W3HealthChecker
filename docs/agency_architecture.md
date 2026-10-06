# Agency Workspace Architecture

## 1. Overview
The Agency Workspace enables digital agencies, SEO teams, and consultancies to manage multiple clients, team members, white-label branding, and automated client health reporting within a centralized portal.

## 2. Multi-Client Organization Model
```
Agency Owner (User)
 ├── Clients ("Apex Retail", "Global Logistics")
 │    ├── White-Label Settings (Agency name, brand color, logo URL, footer text)
 │    └── Assigned Websites
 ├── Team Members (Roles: Admin, Member, Viewer)
 ├── API Keys (Automated CI/CD audit tokens)
 └── Webhooks (Real-time regression alerts)
```

## 3. White-Label Reporting Principles
Agencies can configure customized brand headers, client logos, and contact footers for executive client deliverables:
- **Ethical Boundary**: White-label branding allows agencies to present audits to their paying clients under their own consulting brand.
- **Accuracy Guarantee**: The underlying scoring formulas, 7-pillar telemetry, and safety validations remain authentic and tamper-proof. No arbitrary score inflating or falsification is permitted.

## 4. Centralized Agency Endpoints
- `GET /api/agency/overview`: Aggregate telemetry on clients, keys, webhooks, and active quotas.
- `GET /api/agency/clients`: Lists all clients with attached websites and health scores.
- `POST /api/agency/clients`: Creates client profile with custom branding settings.
- `DELETE /api/agency/clients/{id}`: Safely deletes client and detaches associated websites without destroying historical scans.
