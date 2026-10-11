# W3HealthChecker — Integration Framework & Workflow Automation

## 1. Overview
The W3HealthChecker Integration Framework ([`IntegrationRegistry`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Integrations/IntegrationRegistry.php)) allows authorized users to connect external developer, operations, and communication platforms to their website intelligence events.

---

## 2. Core Architecture
```
                         W3HealthChecker Core Events
               (scan.completed, regression.detected, test_ping)
                                    │
                                    ▼
                         IntegrationRegistry
                                    │
         ┌──────────────────────────┼──────────────────────────┐
         ▼                          ▼                          ▼
    SlackAdapter              GitHubAdapter              WebhookAdapter
 (Incoming Webhook)         (Issues REST API)          (Signed Webhooks)
         │                          │                          │
         ▼                          ▼                          ▼
   Slack Channel             GitHub Repository          Custom Receiver
```

### Supported Providers
1. **Slack**: Posts rich markdown blocks summarizing website health scores, regressions, and direct links to full audit reports.
2. **GitHub**: Drafts structured issues from validated audit recommendations containing finding title, severity, why it matters, and remediation instructions.
3. **Generic Webhooks**: Dispatches signed HTTP POST payloads to arbitrary subscriber endpoints with replay protection and event tracking headers.

---

## 3. Security & Isolation Guarantees
- **Credential Encryption**: All tokens, secrets, and URLs are encrypted at rest using AES-256-CBC via `encrypted_credentials`.
- **SSRF Immunity**: All outgoing integration URLs pass through [`UrlValidationService`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Security/UrlValidationService.php) to reject loopback, private networks, and cloud metadata targets (`169.254.169.254`).
- **Tenant Isolation**: Integrations are scoped strictly to the authenticated `user_id`; cross-tenant inspection or dispatch yields `404 Not Found`.
- **Secret Redaction**: API endpoints return `credentials_preview` where sensitive tokens and webhook secrets are masked (`••••••••`).
