<?php

namespace App\Http\Controllers;

use App\Services\Billing\BillingProviderInterface;
use App\Services\Billing\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(
        protected FeatureGate $featureGate,
        protected BillingProviderInterface $billingProvider
    ) {}

    /**
     * Return public plans catalog.
     */
    public function plans(Request $request): JsonResponse
    {
        $plans = config('plans.plans', []);
        $currentPlan = $request->user() ? $this->featureGate->getPlan($request->user()) : 'free';

        return response()->json([
            'success' => true,
            'current_plan' => $currentPlan,
            'plans' => array_values($plans),
        ]);
    }

    /**
     * Return active user usage and plan limits.
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $usage = $this->featureGate->getUsageAndLimits($user);

        return response()->json([
            'success' => true,
            'usage' => $usage,
        ]);
    }

    /**
     * Subscribe or change plan tier.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in(['free', 'pro', 'agency'])],
        ]);

        $user = $request->user();
        $targetPlan = $validated['plan'];

        if ($targetPlan === 'free') {
            $subscription = $this->billingProvider->changePlan($user, 'free');
            $message = 'Switched to Free plan.';
        } else {
            $subscription = $this->billingProvider->changePlan($user, $targetPlan);
            $message = "Successfully upgraded to {$targetPlan} plan!";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'subscription' => $subscription,
            'usage' => $this->featureGate->getUsageAndLimits($user),
        ]);
    }

    /**
     * Cancel recurring subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $this->billingProvider->cancelSubscription($user);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled. Access remains active through the end of the billing period.',
            'subscription' => $subscription,
            'usage' => $this->featureGate->getUsageAndLimits($user),
        ]);
    }

    /**
     * Resume a previously cancelled subscription.
     */
    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $this->billingProvider->resumeSubscription($user);

        return response()->json([
            'success' => true,
            'message' => 'Subscription successfully resumed!',
            'subscription' => $subscription,
            'usage' => $this->featureGate->getUsageAndLimits($user),
        ]);
    }

    /**
     * Process webhook from billing provider.
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Billing-Signature', '');
        $payload = $request->all();

        $result = $this->billingProvider->handleWebhook($payload, $signature);

        if (!$result['handled']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Webhook verification failed.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
