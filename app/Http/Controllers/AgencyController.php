<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\WebhookEndpoint;
use App\Models\Website;
use App\Services\Billing\FeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(
        protected FeatureGate $featureGate
    ) {}

    /**
     * Agency overview metrics and limits.
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'agency' => [
                'can_use_agency_features' => $this->featureGate->canUseApi($user),
                'total_clients' => $user->clients()->count(),
                'total_team_members' => $user->teamMembers()->count(),
                'total_api_keys' => $user->apiKeys()->where('is_active', true)->count(),
                'total_webhooks' => $user->webhookEndpoints()->where('is_active', true)->count(),
            ],
            'entitlements' => $this->featureGate->getUsageAndLimits($user),
        ]);
    }

    /**
     * List all agency clients.
     */
    public function clients(Request $request): JsonResponse
    {
        $clients = $request->user()
            ->clients()
            ->withCount('websites')
            ->with(['websites.latestScan'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'clients' => $clients,
        ]);
    }

    /**
     * Create a new agency client.
     */
    public function storeClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'company' => ['nullable', 'string', 'max:128'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'white_label_settings' => ['nullable', 'array'],
            'white_label_settings.agency_name' => ['nullable', 'string', 'max:128'],
            'white_label_settings.logo_url' => ['nullable', 'url', 'max:1024'],
            'white_label_settings.brand_color' => ['nullable', 'string', 'max:16'],
            'white_label_settings.footer_text' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $client = $user->clients()->create($validated);

        return response()->json([
            'success' => true,
            'message' => "Client {$client->name} created.",
            'client' => $client,
        ], 201);
    }

    /**
     * Delete an agency client.
     */
    public function destroyClient(Request $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        if ($client->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized client access.',
            ], 403);
        }

        // Unlink websites
        Website::where('client_id', $client->id)->update(['client_id' => null]);
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully.',
        ]);
    }

    /**
     * List user's API keys.
     */
    public function apiKeys(Request $request): JsonResponse
    {
        $keys = $request->user()
            ->apiKeys()
            ->latest()
            ->get(['id', 'name', 'key_prefix', 'requests_count', 'last_used_at', 'is_active', 'created_at']);

        return response()->json([
            'success' => true,
            'api_keys' => $keys,
        ]);
    }

    /**
     * Create a new API key.
     */
    public function storeApiKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
        ]);

        $user = $request->user();

        if (!$this->featureGate->canUseApi($user)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key generation requires an Agency subscription.',
                'upgrade_required' => true,
            ], 403);
        }

        [$apiKey, $plainToken] = ApiKey::generate($user, $validated['name']);

        return response()->json([
            'success' => true,
            'message' => 'API key created. Save this secret token now; it will not be shown again.',
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'token' => $plainToken,
                'created_at' => $apiKey->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Revoke an API key.
     */
    public function destroyApiKey(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);

        if ($apiKey->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $apiKey->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'API key revoked.',
        ]);
    }

    /**
     * List user's registered webhook endpoints.
     */
    public function webhooks(Request $request): JsonResponse
    {
        $webhooks = $request->user()
            ->webhookEndpoints()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'webhooks' => $webhooks,
        ]);
    }

    /**
     * Register a new webhook endpoint.
     */
    public function storeWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:scan.completed,scan.failed,score.changed,critical_issue.detected'],
        ]);

        $user = $request->user();

        if (!$this->featureGate->canUseApi($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook configuration requires an Agency subscription.',
                'upgrade_required' => true,
            ], 403);
        }

        $webhook = WebhookEndpoint::createWithSecret([
            'user_id' => $user->id,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook endpoint registered with cryptographic signature secret.',
            'webhook' => $webhook,
        ], 201);
    }

    /**
     * Delete a webhook endpoint.
     */
    public function destroyWebhook(Request $request, int $id): JsonResponse
    {
        $webhook = WebhookEndpoint::findOrFail($id);

        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook removed.',
        ]);
    }
}
