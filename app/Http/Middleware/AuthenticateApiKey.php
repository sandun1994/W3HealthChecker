<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Billing\FeatureGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(
        protected FeatureGate $featureGate
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-API-Key');

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing API token. Provide credentials via Bearer authorization or X-API-Key header.',
            ], 401);
        }

        $apiKey = ApiKey::findByToken($token);

        if (!$apiKey || !$apiKey->is_active) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or revoked API key.',
            ], 401);
        }

        $user = $apiKey->user;

        // Entitlement check: API access requires Agency plan
        if (!$this->featureGate->canUseApi($user)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'REST API access requires an active Agency plan subscription.',
                'upgrade_required' => true,
            ], 403);
        }

        // Track usage asynchronously or directly
        $apiKey->forceFill([
            'last_used_at' => now(),
            'requests_count' => $apiKey->requests_count + 1,
        ])->saveQuietly();

        $request->setUserResolver(fn() => $user);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
