<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Services\Integrations\IntegrationRegistry;
use App\Services\Security\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct(protected IntegrationRegistry $registry) {}

    /**
     * List user integrations.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $integrations = Integration::where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn($item) => $this->registry->sanitizeIntegration($item));

        return response()->json([
            'success' => true,
            'integrations' => $integrations,
        ]);
    }

    /**
     * Connect a new integration.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:slack,github,webhook',
            'name' => 'required|string|max:64',
            'credentials' => 'required|array',
            'settings' => 'nullable|array',
        ]);

        $userId = $request->user()->id;

        $integration = new Integration([
            'user_id' => $userId,
            'provider' => $validated['provider'],
            'name' => $validated['name'],
            'settings' => $validated['settings'] ?? [],
            'status' => 'connected',
            'is_active' => true,
        ]);

        $integration->setCredentials($validated['credentials']);
        $integration->save();

        AuditLogger::log(
            action: 'integration.created',
            userId: $userId,
            resourceType: 'Integration',
            resourceId: (string) $integration->id,
            metadata: ['provider' => $integration->provider, 'name' => $integration->name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Integration connected successfully.',
            'integration' => $this->registry->sanitizeIntegration($integration),
        ], 201);
    }

    /**
     * Disconnect / revoke an integration.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $integration = Integration::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found.'], 404);
        }

        $integration->delete();

        AuditLogger::log(
            action: 'integration.revoked',
            userId: $userId,
            resourceType: 'Integration',
            resourceId: (string) $id,
            metadata: ['provider' => $integration->provider]
        );

        return response()->json([
            'success' => true,
            'message' => 'Integration disconnected and revoked successfully.',
        ]);
    }

    /**
     * Test integration connectivity.
     */
    public function test(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $integration = Integration::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'Integration not found.'], 404);
        }

        $result = $this->registry->test($integration);

        return response()->json([
            'success' => $result['success'] ?? false,
            'result' => $result,
        ]);
    }
}
