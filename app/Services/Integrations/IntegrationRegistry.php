<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\Integrations\Adapters\GitHubAdapter;
use App\Services\Integrations\Adapters\SlackAdapter;
use App\Services\Integrations\Adapters\WebhookAdapter;
use App\Services\Integrations\Contracts\IntegrationAdapterInterface;
use App\Services\Security\AuditLogger;
use App\Services\Security\UrlValidationService;
use InvalidArgumentException;

class IntegrationRegistry
{
    protected array $adapters = [];

    public function __construct(UrlValidationService $urlValidator)
    {
        $this->adapters['slack'] = new SlackAdapter($urlValidator);
        $this->adapters['github'] = new GitHubAdapter();
        $this->adapters['webhook'] = new WebhookAdapter($urlValidator);
    }

    /**
     * Resolve the adapter for a given provider.
     */
    public function getAdapter(string $provider): IntegrationAdapterInterface
    {
        $provider = strtolower(trim($provider));

        if (!isset($this->adapters[$provider])) {
            throw new InvalidArgumentException("Unsupported integration provider: {$provider}");
        }

        return $this->adapters[$provider];
    }

    /**
     * Dispatch an event to all active integrations configured for a user.
     */
    public function dispatchToUser(int $userId, string $event, array $payload): array
    {
        $integrations = Integration::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($integrations as $integration) {
            try {
                $adapter = $this->getAdapter($integration->provider);
                $result = $adapter->dispatch($integration, $event, $payload);
                $results[$integration->id] = $result;

                AuditLogger::log(
                    action: 'integration.dispatched',
                    userId: $userId,
                    resourceType: 'Integration',
                    resourceId: (string) $integration->id,
                    metadata: [
                        'provider' => $integration->provider,
                        'event' => $event,
                        'success' => $result['success'] ?? false,
                    ]
                );
            } catch (\Exception $e) {
                $results[$integration->id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Test a specific integration connection.
     */
    public function test(Integration $integration): array
    {
        $adapter = $this->getAdapter($integration->provider);
        return $adapter->testConnection($integration);
    }

    /**
     * Format integration record for public / API response with redacted secrets.
     */
    public function sanitizeIntegration(Integration $integration): array
    {
        $creds = $integration->getCredentials();
        $sanitizedCreds = [];

        foreach ($creds as $key => $val) {
            if (in_array(strtolower($key), ['token', 'secret', 'password', 'api_key'], true)) {
                $sanitizedCreds[$key] = '••••••••';
            } elseif (str_contains(strtolower($key), 'webhook_url') && is_string($val)) {
                // Obfuscate secret token in webhook URL
                $sanitizedCreds[$key] = preg_replace('/services\/[A-Z0-9\/]+$/i', 'services/••••••••', $val);
            } else {
                $sanitizedCreds[$key] = $val;
            }
        }

        return [
            'id' => $integration->id,
            'provider' => $integration->provider,
            'name' => $integration->name,
            'status' => $integration->status,
            'settings' => $integration->settings,
            'credentials_preview' => $sanitizedCreds,
            'last_dispatched_at' => $integration->last_dispatched_at?->toIso8601String(),
            'last_error' => $integration->last_error,
            'is_active' => $integration->is_active,
            'created_at' => $integration->created_at?->toIso8601String(),
        ];
    }
}
