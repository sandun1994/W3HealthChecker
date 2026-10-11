<?php

namespace App\Services\Integrations\Adapters;

use App\Models\Integration;
use App\Services\Integrations\Contracts\IntegrationAdapterInterface;
use App\Services\Security\UrlValidationService;
use Illuminate\Support\Facades\Http;

class SlackAdapter implements IntegrationAdapterInterface
{
    public function __construct(protected UrlValidationService $urlValidator) {}

    public function dispatch(Integration $integration, string $event, array $payload): array
    {
        $credentials = $integration->getCredentials();
        $webhookUrl = $credentials['webhook_url'] ?? null;

        if (!$webhookUrl) {
            return ['success' => false, 'error' => 'Missing Slack webhook URL.'];
        }

        try {
            $validated = $this->urlValidator->validateAndNormalize($webhookUrl);
            $validatedUrl = $validated['normalized_url'];
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => 'Invalid or restricted Slack webhook destination: ' . $e->getMessage()];
        }

        $message = $this->formatMessage($event, $payload);

        try {
            $response = Http::timeout(5)->post($validatedUrl, $message);

            if ($response->successful()) {
                $integration->update([
                    'last_dispatched_at' => now(),
                    'last_error' => null,
                    'status' => 'connected',
                ]);
                return ['success' => true, 'response' => 'Dispatched to Slack successfully.'];
            }

            $integration->update([
                'last_error' => 'Slack returned HTTP ' . $response->status(),
                'status' => 'degraded',
            ]);
            return ['success' => false, 'error' => 'Slack returned HTTP ' . $response->status()];
        } catch (\Exception $e) {
            $integration->update([
                'last_error' => $e->getMessage(),
                'status' => 'degraded',
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(Integration $integration): array
    {
        return $this->dispatch($integration, 'test_ping', [
            'domain' => 'w3healthchecker.com',
            'message' => 'W3HealthChecker test notification connection confirmed.',
            'overall_score' => 100,
        ]);
    }

    protected function formatMessage(string $event, array $payload): array
    {
        $domain = $payload['domain'] ?? 'target';
        $score = $payload['overall_score'] ?? 'N/A';
        $reportUrl = $payload['report_url'] ?? "https://w3healthchecker.com";

        $text = match ($event) {
            'scan.completed' => "✅ Website scan completed for *{$domain}*. Overall Score: *{$score}/100*. <{$reportUrl}|View Full Health Report>",
            'regression.detected' => "⚠️ *Health Regression Detected* for *{$domain}*! Critical issues identified. <{$reportUrl}|Investigate Issues>",
            default => "ℹ️ W3HealthChecker notification for *{$domain}*: " . ($payload['message'] ?? 'Status update.'),
        };

        return [
            'text' => $text,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $text,
                    ],
                ],
            ],
        ];
    }
}
