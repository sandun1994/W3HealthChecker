# W3HealthChecker — REST API v1 Reference

## 1. Authentication
All requests to `/api/v1/*` require an active API key transmitted via the `X-API-Key` header:
```http
X-API-Key: w3_live_abcdef1234567890...
```
API keys are hashed via SHA-256 (`key_hash`) upon generation. Plaintext keys are never stored in the database.

---

## 2. Endpoints Reference

### Trigger Audit Scan
```http
POST /api/v1/scans
Content-Type: application/json

{
  "url": "https://example.com"
}
```
**Response (201 Created)**:
```json
{
  "success": true,
  "scan_id": "scan_66fa1b...",
  "status": "queued",
  "status_url": "/api/v1/scans/scan_66fa1b...",
  "report_url": "/api/v1/reports/scan_66fa1b..."
}
```

### Retrieve Full Audit Report
```http
GET /api/v1/reports/{id}
```
**Response (200 OK)**:
```json
{
  "success": true,
  "id": "scan_66fa1b...",
  "url": "https://example.com",
  "overall_score": 86,
  "scores": {
    "seo": 92,
    "security": 90,
    "performance": 85,
    "accessibility": 88,
    "mobile": 95,
    "technical": 80,
    "ai_readiness": 75
  },
  "issues_summary": {
    "total": 3,
    "critical": 0,
    "warning": 2,
    "info": 1
  },
  "prioritized_recommendations": [...]
}
```

### Submit Bulk Scan Batch
```http
POST /api/v1/scans/bulk
Content-Type: application/json

{
  "urls": [
    "https://example.com",
    "https://client-shop.com"
  ]
}
```

### Manage Scheduled Monitoring
- `GET /api/v1/monitoring`: List target sites enrolled in automated health checks.
- `POST /api/v1/monitoring`: Enroll URL with `schedule: "daily" | "weekly"`.
- `DELETE /api/v1/monitoring/{id}`: Delete monitoring target.
