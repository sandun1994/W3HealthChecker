# Privacy & User Data Governance

## 1. Principles
W3HealthChecker is built on a **Privacy First** and **Zero Dark Pattern** philosophy:
1. **No Forced Registration**: Anonymous scans remain completely functional without requiring signup.
2. **Local History Privacy**: Anonymous scans stored in browser localStorage are NEVER silently uploaded without explicit, consensual user action.
3. **Consensual History Sync**: Authenticated users can choose to import their local scans via `/api/workspace/sync-history`. Only public UUID tokens are transmitted to claim existing scans.
4. **Data Minimization**: We only collect the minimal email, name, and hashed credentials required for account security.

## 2. Right to Eradication & Account Deletion
Users retain total control over their data:
- **Endpoint**: `DELETE /api/auth/account`
- **Security Requirement**: Re-entry of the current account password to prevent accidental or malicious CSRF deletion.
- **Action**:
  - Deletes all user-owned projects.
  - Deactivates and deletes all monitored website schedules.
  - Disassociates user ownership from websites and scan records (`user_id = null`), preserving defensive aggregate health statistics while thoroughly scrubbing all personal data ties.
  - Deletes the `User` model permanently from the database.
  - Invalidates the active web session.

## 3. Passive Scanning Ethics
- Scans are strictly passive HTTP GET / HEAD audits.
- No intrusive penetration testing, fuzzing, or exploit execution.
- Strict SSRF protections ensure internal hostnames, loopbacks, and cloud metadata services can never be probed.
