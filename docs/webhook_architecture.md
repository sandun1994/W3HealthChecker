# Webhook Architecture & Event Delivery

## 1. Overview
The W3HealthChecker webhook engine enables external applications, Slack bots, and internal systems to receive real-time HTTP POST notifications whenever audits complete or critical regressions occur.

## 2. Event Types
- `scan.completed`: Dispatched when an audit finishes successfully.
- `scan.failed`: Dispatched when target resolution fails or times out.
- `score.changed`: Dispatched when monitored target overall health score shifts by ≥ 5 points.
- `critical_issue.detected`: Dispatched when a critical severity issue (e.g., missing HTTPS, broken canonical) appears.

## 3. Cryptographic Signature Verification
Every outgoing webhook POST delivery includes the header:
```
X-W3-Signature: sha256=<hex_digest>
```
The signature is generated using HMAC-SHA256 with the endpoint's unique secret (`whsec_...`) and the raw request body:
```php
$signature = 'sha256=' . hash_hmac('sha256', $rawBody, $endpoint->secret);
```

## 4. Replay Prevention & Idempotency
- Receivers should verify the signature using constant-time comparison (`hash_equals` / `timingSafeEqual`).
- Webhook payloads include an `event_id` and `timestamp` allowing receivers to reject delayed or duplicate requests.
