<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestObservability
{
    /**
     * Attach distributed request ID and measure execution response time.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $requestId = $request->header('X-Request-Id') ?: 'req_' . Str::random(24);
        $request->headers->set('X-Request-Id', $requestId);

        $response = $next($request);

        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Response-Time-Ms', (string) $durationMs);

        return $response;
    }
}
