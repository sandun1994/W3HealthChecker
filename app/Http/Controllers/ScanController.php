<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebsiteScan;
use App\Models\Scan;
use App\Models\Website;
use App\Services\Scanner\WebsiteScanService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function __construct(protected WebsiteScanService $scanner) {}

    /**
     * Submit a URL for analysis.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $rawUrl = $request->input('url');
        $ip = $request->ip() ?? '127.0.0.1';

        // 1. Rate Limiting Check (Configurable via env)
        $rateLimit = (int) env('SCAN_RATE_LIMIT', 10);
        $rateWindow = (int) env('SCAN_RATE_WINDOW', 60); // minutes
        $limiterKey = 'scan-ip:' . $ip;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limiterKey, $rateLimit)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($limiterKey);
            \Illuminate\Support\Facades\Log::warning('Scan rate limit exceeded', [
                'ip' => $ip,
                'retry_after_seconds' => $seconds,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Scan rate limit reached ({$rateLimit} scans per {$rateWindow}m). Please retry in {$seconds} seconds.",
                'status' => 'RATE_LIMITED',
                'retry_after' => $seconds,
            ], 429);
        }

        try {
            // 2. Validate & normalize URL early for deduplication checks
            $urlValidator = app(\App\Services\Security\UrlValidationService::class);
            $validated = $urlValidator->validateAndNormalize($rawUrl);
            $normalizedUrl = $validated['normalized_url'];

            $force = $request->boolean('force') || $request->boolean('rescan');
            $cacheMinutes = (int) env('SCAN_CACHE_MINUTES', 30);

            // 3. Cache Deduplication: Reuse recent completed scan if within cache period
            if (!$force) {
                $recentScan = Scan::with(['website', 'issues', 'metric'])
                    ->where('target_url', $normalizedUrl)
                    ->where('status', 'completed')
                    ->where('completed_at', '>=', now()->subMinutes($cacheMinutes))
                    ->latest('completed_at')
                    ->first();

                if ($recentScan) {
                    \Illuminate\Support\Facades\Log::info('Serving cached scan report', [
                        'public_id' => $recentScan->public_id,
                        'url' => $normalizedUrl,
                        'completed_at' => $recentScan->completed_at,
                    ]);

                    return response()->json([
                        'success' => true,
                        'cached' => true,
                        'public_id' => $recentScan->public_id,
                        'domain' => $recentScan->website->domain,
                        'status' => $recentScan->status,
                        'status_stage' => 'Completed (cached)',
                        'progress_percentage' => 100,
                        'report_url' => url("/report/{$recentScan->website->domain}/{$recentScan->public_id}"),
                    ]);
                }

                // 4. In-flight Deduplication: If scan is already queued/processing for this URL
                $activeScan = Scan::with('website')
                    ->where('target_url', $normalizedUrl)
                    ->whereIn('status', ['queued', 'processing'])
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->latest('id')
                    ->first();

                if ($activeScan) {
                    return response()->json([
                        'success' => true,
                        'cached' => false,
                        'in_progress' => true,
                        'public_id' => $activeScan->public_id,
                        'domain' => $activeScan->website->domain,
                        'status' => $activeScan->status,
                        'status_stage' => $activeScan->status_stage,
                        'progress_percentage' => $activeScan->progress_percentage,
                        'report_url' => url("/report/{$activeScan->website->domain}/{$activeScan->public_id}"),
                    ]);
                }
            }

            // 5. Concurrency Check
            $maxConcurrent = (int) env('MAX_CONCURRENT_SCANS', 5);
            $activeScansCount = Scan::whereIn('status', ['queued', 'processing'])->count();
            if ($activeScansCount >= $maxConcurrent) {
                return response()->json([
                    'success' => false,
                    'message' => 'The scanner is currently processing the maximum allowed concurrent scans. Please try again shortly.',
                    'status' => 'BUSY',
                ], 429);
            }

            // Hit rate limiter on successful dispatch
            \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, $rateWindow * 60);

            // 6. Initiate Scan
            $scan = $this->scanner->initiateScan($rawUrl, auth()->id());

            // If queue connection is sync or in development, process immediately, or dispatch to queue
            if (config('queue.default') === 'sync' || $request->boolean('sync')) {
                $this->scanner->processScan($scan);
            } else {
                ProcessWebsiteScan::dispatch($scan);
            }

            return response()->json([
                'success' => true,
                'cached' => false,
                'public_id' => $scan->public_id,
                'domain' => $scan->website->domain,
                'status' => $scan->status,
                'status_stage' => $scan->status_stage,
                'progress_percentage' => $scan->progress_percentage,
                'report_url' => url("/report/{$scan->website->domain}/{$scan->public_id}"),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Poll scan status & real-time progress.
     */
    public function status(string $publicId): JsonResponse
    {
        $scan = Scan::with(['website', 'issues', 'metric'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'public_id' => $scan->public_id,
            'domain' => $scan->website->domain,
            'status' => $scan->status,
            'status_stage' => $scan->status_stage,
            'progress_percentage' => $scan->progress_percentage,
            'overall_score' => $scan->overall_score,
            'status_label' => $scan->status_label,
            'error_message' => $scan->error_message,
            'scores' => [
                'seo' => $scan->score_seo,
                'performance' => $scan->score_performance,
                'security' => $scan->score_security,
                'accessibility' => $scan->score_accessibility,
                'mobile' => $scan->score_mobile,
                'technical' => $scan->score_technical,
                'ai_readiness' => $scan->score_ai_readiness,
            ],
            'issues_count' => $scan->issues->count(),
            'completed' => $scan->status === 'completed',
        ]);
    }

    /**
     * Fetch full report data.
     */
    public function show(string $domain, string $publicId): JsonResponse
    {
        $scan = Scan::with(['website', 'issues.rule', 'metric'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        $rankedIssues = $scan->issues->sortBy('priority_order')->values();
        $fixTheseFirst = $rankedIssues->take(5);

        return response()->json([
            'success' => true,
            'scan' => [
                'public_id' => $scan->public_id,
                'domain' => $scan->website->domain,
                'target_url' => $scan->target_url,
                'final_url' => $scan->final_url,
                'status' => $scan->status,
                'overall_score' => $scan->overall_score,
                'status_label' => $scan->status_label,
                'http_status_code' => $scan->http_status_code,
                'response_time_ms' => $scan->response_time_ms,
                'completed_at' => $scan->completed_at ? $scan->completed_at->toIso8601String() : null,
                'scores' => [
                    'seo' => $scan->score_seo,
                    'performance' => $scan->score_performance,
                    'security' => $scan->score_security,
                    'accessibility' => $scan->score_accessibility,
                    'mobile' => $scan->score_mobile,
                    'technical' => $scan->score_technical,
                    'ai_readiness' => $scan->score_ai_readiness,
                ],
            ],
            'fix_these_first' => $fixTheseFirst,
            'all_issues' => $rankedIssues,
            'metrics' => $scan->metric,
            'summary' => [
                'critical_count' => $rankedIssues->where('severity', 'critical')->count(),
                'high_count' => $rankedIssues->where('severity', 'high')->count(),
                'medium_count' => $rankedIssues->where('severity', 'medium')->count(),
                'low_count' => $rankedIssues->where('severity', 'low')->count(),
                'info_count' => $rankedIssues->where('severity', 'info')->count(),
            ]
        ]);
    }

    /**
     * Compare two websites side-by-side.
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'url_a' => ['required', 'string'],
            'url_b' => ['required', 'string'],
        ]);

        try {
            $scanA = $this->scanner->initiateScan($request->input('url_a'));
            $scanB = $this->scanner->initiateScan($request->input('url_b'));

            $this->scanner->processScan($scanA);
            $this->scanner->processScan($scanB);

            return response()->json([
                'success' => true,
                'site_a' => $scanA->fresh(['website', 'issues']),
                'site_b' => $scanB->fresh(['website', 'issues']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Controlled same-domain site crawl.
     */
    public function crawl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'max_pages' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        $user = $request->user();
        $crawler = app(\App\Services\Intelligence\ControlledSiteCrawler::class);

        $maxPages = $validated['max_pages'] ?? 5;
        if (!$user) {
            $maxPages = min(3, $maxPages);
            $maxDepth = 1;
        } else {
            $maxPages = min(15, $maxPages);
            $maxDepth = 2;
        }

        $result = $crawler->crawl($validated['url'], $maxPages, $maxDepth);

        return response()->json($result);
    }
}
