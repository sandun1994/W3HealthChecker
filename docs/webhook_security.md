# W3HealthChecker — Webhook Security & Signature Verification

## 1. Webhook Signature Architecture
All outbound webhooks dispatched by W3HealthChecker include cryptographic proof of authenticity using HMAC-SHA256:

### Headers Sent with Every Webhook
- `X-W3-Signature`: Hex-encoded HMAC-SHA256 signature of the payload.
- `X-W3-Timestamp`: UNIX timestamp of dispatch.
- `X-W3-Event`: Standardized event name (e.g. `scan.completed`, `regression.detected`).
- `X-W3-Event-Id`: Unique UUIDv4 identifier for deduplication.

---

## 2. Verification Example (PHP)
```php
<?php

$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_W3_SIGNATURE'] ?? '';
$timestampHeader = $_SERVER['HTTP_X_W3_TIMESTAMP'] ?? '';
$signingSecret = 'YOUR_CONFIGURED_WEBHOOK_SECRET';

// 1. Replay attack protection (reject payloads older than 5 minutes)
if (abs(time() - (int)$timestampHeader) > 300) {
    http_response_code(400);
    exit('Request timestamp is too old.');
}

// 2. Compute expected HMAC
$expectedSignature = hash_hmac('sha256', "{$timestampHeader}.{$payload}", $signingSecret);

// 3. Constant-time comparison
if (!hash_equals($expectedSignature, $signatureHeader)) {
    http_response_code(401);
    exit('Invalid signature.');
}

// 4. Authenticated payload processing
$eventData = json_decode($payload, true);
http_response_code(200);
echo json_encode(['status' => 'received']);
```

---

## 3. Replay Protection & Deduplication
Receivers should store processed `X-W3-Event-Id` values for 24 hours. Any retransmitted request sharing the same event ID should return `200 OK` without duplicating actions.
