<?php

namespace App\Services\Integrations\Adapters;

use App\Models\Integration;
use App\Services\Integrations\Contracts\IntegrationAdapterInterface;
use App\Services\Security\UrlValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookAdapter implements IntegrationAdapterInterface
{
    public function __construct(protected UrlValidationService $urlValidator) {}

    public function dispatch(Integration $integration, string $event, array $payload): array
    {
        $credentials = $integration->getCredentials();
        $targetUrl = $credentials['webhook_url'] ?? null;
        $signingSecret = $credentials['secret'] ?? 'default_secret';

        if (!$targetUrl) {
            return ['success' => false, 'error' => 'Missing webhook destination URL.'];
        }

        try {
            $validated = $this->urlValidator->validateAndNormalize($targetUrl);
            $validatedUrl = $validated['normalized_url'];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Restricted or invalid webhook destination: ' . $e->getMessage()];
        }

        $timestamp = (string) time();
        $eventId = (string) Str::uuid();

        $body = [
            'event' => $event,
            'event_id' => $eventId,
            'timestamp' => $timestamp,
            'data' => $payload,
        ];

        $jsonPayload = json_encode($body);
        $signature = hash_hmac('sha256', "{$timestamp}.{$jsonPayload}", $signingSecret);

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'W3HealthChecker-Webhook/1.0',
                    'X-W3-Signature' => $signature,
                    'X-W3-Timestamp' => $timestamp,
                    'X-W3-Event' => $event,
                    'X-W3-Event-Id' => $eventId,
                ])
                ->post($validatedUrl, $body);

            if ($response->successful()) {
                $integration->update([
                    'last_dispatched_at' => now(),
                    'last_error' => null,
                    'status' => 'connected',
                ]);
                return ['success' => true, 'status' => $response->status()];
            }

            $errMsg = "Webhook endpoint responded with HTTP {$response->status()}";
            $integration->update(['last_error' => $errMsg, 'status' => 'degraded']);
            return ['success' => false, 'error' => $errMsg];
        } catch (\Exception $e) {
            $integration->update(['last_error' => $e->getMessage(), 'status' => 'degraded']);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(Integration $integration): array
    {
        return $this->dispatch($integration, 'ping', [
            'message' => 'W3HealthChecker webhook test ping.',
            'test' => true,
        ]);
    }
}
