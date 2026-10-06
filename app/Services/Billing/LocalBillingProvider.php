<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

class LocalBillingProvider implements BillingProviderInterface
{
    public function createCustomer(User $user): string
    {
        return 'cus_' . Str::random(16);
    }

    public function createSubscription(User $user, string $plan): Subscription
    {
        $customerId = $this->createCustomer($user);
        $subscriptionId = 'sub_' . Str::random(16);

        return Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan' => $plan,
                'status' => 'active',
                'provider' => 'local',
                'provider_customer_id' => $customerId,
                'provider_subscription_id' => $subscriptionId,
                'renews_at' => now()->addMonth(),
                'cancelled_at' => null,
            ]
        );
    }

    public function changePlan(User $user, string $newPlan): Subscription
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            return $this->createSubscription($user, $newPlan);
        }

        $subscription->update([
            'plan' => $newPlan,
            'status' => 'active',
            'cancelled_at' => null,
            'renews_at' => now()->addMonth(),
        ]);

        return $subscription->fresh();
    }

    public function cancelSubscription(User $user): Subscription
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            $subscription = $this->createSubscription($user, 'free');
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }

    public function resumeSubscription(User $user): Subscription
    {
        $subscription = $user->subscription;

        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'cancelled_at' => null,
                'renews_at' => now()->addMonth(),
            ]);
            return $subscription->fresh();
        }

        return $this->createSubscription($user, 'pro');
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        // Verify signature format defensively
        if (empty($signature) || strlen($signature) < 8) {
            return [
                'handled' => false,
                'error' => 'Invalid webhook signature.',
            ];
        }

        $eventType = $payload['type'] ?? 'unknown';

        return [
            'handled' => true,
            'event' => $eventType,
            'processed_at' => now()->toIso8601String(),
        ];
    }
}
