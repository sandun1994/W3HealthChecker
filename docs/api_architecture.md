# REST API v1 Architecture

## 1. Design & Security Principles
- **Base URL**: `/api/v1`
- **Authentication**: Bearer Token via `Authorization: Bearer w3_live_...` or `X-API-Key` header.
- **Plan Enforcement**: REST API access is gated through `FeatureGate::canUseApi($user)`, ensuring only Agency subscribers can generate keys and query API endpoints.
- **Rate Limiting**: 60 requests per minute per key, with rate limit headers returned.
- **SSRF Defenses**: Every API scan passes the identical defensive URL validation, DNS resolution, private IP rejection, and port filtering pipeline.
- **Zero Raw Secret Storage**: Plain API tokens are shown strictly once upon generation; database stores only SHA-256 hashes (`key_hash`).

## 2. API v1 Endpoints
| Verb | Route | Description |
|---|---|---|
| `POST` | `/api/v1/scans` | Initiates an immediate defensive website scan. Returns scan ID, scores, and status. |
| `GET` | `/api/v1/scans/{id}` | Retrieves execution status and pillar scores for a scan. |
| `GET` | `/api/v1/reports/{id}` | Retrieves full 7-pillar telemetry, issues, and prioritized recommendations. |
| `POST` | `/api/v1/scans/bulk` | Submits a batch of up to 10 URLs for sequential scanning. |
| `POST` | `/api/v1/monitoring` | Registers a target for recurring daily/weekly monitoring. |
| `GET` | `/api/v1/monitoring` | Lists all active monitored targets. |
| `DELETE` | `/api/v1/monitoring/{id}` | Removes a target from automated monitoring. |

## 3. Error Contract
Standard structured JSON errors:
```json
{
  "error": "Validation Error | Unauthorized | Forbidden | Not Found",
  "message": "Human-readable explanation of error."
}
```
Internal server stack traces are never exposed.
