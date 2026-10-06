<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\User;

interface BillingProviderInterface
{
    /**
     * Create or retrieve customer reference in billing provider.
     */
    public function createCustomer(User $user): string;

    /**
     * Create or activate a subscription for a given plan.
     */
    public function createSubscription(User $user, string $plan): Subscription;

    /**
     * Change plan tier (upgrade / downgrade).
     */
    public function changePlan(User $user, string $newPlan): Subscription;

    /**
     * Cancel active subscription.
     */
    public function cancelSubscription(User $user): Subscription;

    /**
     * Resume a cancelled subscription before expiration.
     */
    public function resumeSubscription(User $user): Subscription;

    /**
     * Handle incoming provider webhooks idempotently.
     */
    public function handleWebhook(array $payload, string $signature): array;
}
