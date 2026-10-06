<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\User;

class FeatureGate
{
    /**
     * Resolve the active plan identifier for a user.
     */
    public function getPlan(?User $user): string
    {
        if (!$user) {
            return 'free';
        }

        $subscription = $user->subscription()->first();

        if ($subscription && $subscription->isActive()) {
            return $subscription->plan;
        }

        return 'free';
    }

    /**
     * Get plan configuration array by plan ID.
     */
    public function getPlanConfig(string $planId): array
    {
        $plans = config('plans.plans', []);
        return $plans[$planId] ?? $plans['free'];
    }

    /**
     * Check if a specific named feature is enabled for the user's active plan.
     */
    public function hasFeature(?User $user, string $featureKey): bool
    {
        $plan = $this->getPlan($user);
        $config = $this->getPlanConfig($plan);

        return !empty($config['features'][$featureKey]);
    }

    /**
     * Determine if the user can monitor additional websites.
     */
    public function canMonitorWebsites(User $user, int $additionalCount = 1): bool
    {
        $plan = $this->getPlan($user);
        $config = $this->getPlanConfig($plan);
        $limit = $config['max_monitored_websites'] ?? 1;

        $currentCount = $user->monitoredWebsites()->where('is_active', true)->count();

        return ($currentCount + $additionalCount) <= $limit;
    }

    /**
     * Determine if the user can create additional projects.
     */
    public function canCreateProjects(User $user, int $additionalCount = 1): bool
    {
        $plan = $this->getPlan($user);
        $config = $this->getPlanConfig($plan);
        $limit = $config['max_projects'] ?? 1;

        $currentCount = $user->projects()->count();

        return ($currentCount + $additionalCount) <= $limit;
    }

    /**
     * Determine if user has PDF report export entitlement.
     */
    public function canExportPdf(?User $user): bool
    {
        return $this->hasFeature($user, 'pdf_export');
    }

    /**
     * Determine if user has API access entitlement.
     */
    public function canUseApi(?User $user): bool
    {
        return $this->hasFeature($user, 'api_access');
    }

    /**
     * Determine if user can invite team members.
     */
    public function canInviteMembers(?User $user): bool
    {
        return $this->hasFeature($user, 'team_members');
    }

    /**
     * Determine if user has white-label report privileges.
     */
    public function canUseWhiteLabel(?User $user): bool
    {
        return $this->hasFeature($user, 'white_label');
    }

    /**
     * Retrieve full metrics, quotas, and limits breakdown for dashboard & billing.
     */
    public function getUsageAndLimits(User $user): array
    {
        $plan = $this->getPlan($user);
        $config = $this->getPlanConfig($plan);
        $subscription = $user->subscription()->first();

        $monitoredCount = $user->monitoredWebsites()->where('is_active', true)->count();
        $projectsCount = $user->projects()->count();
        $scansThisMonth = $user->scans()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'plan' => $plan,
            'plan_name' => $config['name'],
            'price_monthly' => $config['price_monthly'],
            'status' => $subscription ? $subscription->status : 'active',
            'is_cancelled' => $subscription ? $subscription->isCancelled() : false,
            'renews_at' => $subscription && $subscription->renews_at ? $subscription->renews_at->toIso8601String() : null,
            'trial_ends_at' => $subscription && $subscription->trial_ends_at ? $subscription->trial_ends_at->toIso8601String() : null,
            'quotas' => [
                'monitored_websites' => [
                    'current' => $monitoredCount,
                    'limit' => $config['max_monitored_websites'],
                    'remaining' => max(0, $config['max_monitored_websites'] - $monitoredCount),
                    'can_add_more' => $monitoredCount < $config['max_monitored_websites'],
                ],
                'projects' => [
                    'current' => $projectsCount,
                    'limit' => $config['max_projects'],
                    'remaining' => max(0, $config['max_projects'] - $projectsCount),
                    'can_add_more' => $projectsCount < $config['max_projects'],
                ],
                'monthly_scans' => [
                    'current' => $scansThisMonth,
                    'limit' => $config['max_scans_per_month'],
                    'remaining' => max(0, $config['max_scans_per_month'] - $scansThisMonth),
                ],
            ],
            'features' => $config['features'],
        ];
    }
}
