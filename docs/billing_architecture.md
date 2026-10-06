# Billing Abstraction & Security

## 1. Billing Provider Abstraction
To avoid vendor lock-in to any single payment gateway (Stripe, LemonSqueezy, Paddle), W3HealthChecker relies on the `BillingProviderInterface`:

```php
interface BillingProviderInterface
{
    public function createCustomer(User $user): string;
    public function createSubscription(User $user, string $plan): Subscription;
    public function changePlan(User $user, string $newPlan): Subscription;
    public function cancelSubscription(User $user): Subscription;
    public function resumeSubscription(User $user): Subscription;
    public function handleWebhook(array $payload, string $signature): array;
}
```

The application provides `LocalBillingProvider` bound in `AppServiceProvider`, which handles all testable subscription state transitions locally without external network bottlenecks.

## 2. Payment Security Rules
1. **Zero Raw Card Storage**: Credit card numbers, expiration dates, and CVVs are NEVER received or stored in our database.
2. **Provider Tokenization**: Subscriptions store only opaque customer and subscription identifiers (`provider_customer_id`, `provider_subscription_id`).
3. **Webhook Signatures**: All incoming webhook notifications require cryptographic signature verification (`X-Billing-Signature`).
4. **Idempotent Webhooks**: Events are processed idempotently to prevent duplicate charges or inconsistent state changes during network retries.
