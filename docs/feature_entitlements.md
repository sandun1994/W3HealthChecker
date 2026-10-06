# Feature Entitlement Engine

## 1. Design Philosophy
Feature access in W3HealthChecker is **strictly centralized**. We enforce a hard architectural rule:
**NEVER scatter `if ($plan === 'pro')` or arbitrary plan string comparisons across controllers or models.**

Instead, all authorization and feature gating flows through `App\Services\Billing\FeatureGate`.

## 2. API Contract
```php
class FeatureGate
{
    public function getPlan(?User $user): string;
    public function getPlanConfig(string $planId): array;
    public function hasFeature(?User $user, string $featureKey): bool;
    public function canMonitorWebsites(User $user, int $additionalCount = 1): bool;
    public function canCreateProjects(User $user, int $additionalCount = 1): bool;
    public function canExportPdf(?User $user): bool;
    public function canUseApi(?User $user): bool;
    public function canInviteMembers(?User $user): bool;
    public function canUseWhiteLabel(?User $user): bool;
    public function getUsageAndLimits(User $user): array;
}
```

## 3. Enforcement Points
1. **Workspace Controller (`POST /api/workspace/websites`)**:
   - Checks `canMonitorWebsites($user)` if monitoring is requested.
   - Enforces `hasFeature($user, 'daily_monitoring')` if a daily frequency is chosen.
2. **Projects Controller (`POST /api/workspace/projects`)**:
   - Enforces `canCreateProjects($user)`.
3. **Monitoring Controller (`POST /api/monitoring`)**:
   - Verifies available monitoring quotas against active subscriptions.
4. **Dashboard & Billing Views**:
   - Ingests real-time quota telemetry via `getUsageAndLimits($user)` to render accurate visual progress meters and helpful upgrade paths.
