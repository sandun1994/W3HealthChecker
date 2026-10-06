<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Safe internal application and subsystem health check.
     * Never exposes sensitive database passwords or internal traces.
     */
    public function check(Request $request): JsonResponse
    {
        $start = microtime(true);
        $status = 'healthy';
        $subsystems = [];

        // 1. Database Check
        try {
            $dbStart = microtime(true);
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);
            $subsystems['database'] = [
                'status' => 'connected',
                'latency_ms' => $dbLatencyMs,
            ];
        } catch (Exception $e) {
            $status = 'unhealthy';
            $subsystems['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed.',
            ];
        }

        // 2. Cache Check
        try {
            $cacheStart = microtime(true);
            $testKey = 'health_ping_' . uniqid();
            Cache::put($testKey, 'ok', 5);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            $cacheLatencyMs = round((microtime(true) - $cacheStart) * 1000, 2);

            $subsystems['cache'] = [
                'status' => $retrieved === 'ok' ? 'operational' : 'degraded',
                'latency_ms' => $cacheLatencyMs,
            ];
        } catch (Exception $e) {
            $subsystems['cache'] = [
                'status' => 'error',
                'message' => 'Cache storage unavailable.',
            ];
        }

        // 3. Queue Subsystem Check
        try {
            $pendingJobs = DB::table('jobs')->count();
            $subsystems['queue'] = [
                'status' => 'operational',
                'pending_jobs' => $pendingJobs,
            ];
        } catch (Exception) {
            $subsystems['queue'] = [
                'status' => 'operational',
                'driver' => config('queue.default'),
            ];
        }

        $totalDurationMs = round((microtime(true) - $start) * 1000, 2);

        $httpCode = $status === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => $totalDurationMs,
            'subsystems' => $subsystems,
            'environment' => app()->environment(),
        ], $httpCode);
    }
}
