<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MonitoredWebsite;
use App\Models\Scan;
use App\Models\Website;
use App\Services\Billing\FeatureGate;
use App\Services\Monitoring\MonitoringExecutionService;
use App\Services\Scanner\WebsiteScanService;
use App\Services\Security\UrlValidationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public function __construct(
        protected UrlValidationService $urlValidator,
        protected WebsiteScanService $scanner,
        protected MonitoringExecutionService $monitoringService,
        protected FeatureGate $featureGate
    ) {}

    /**
     * POST /api/v1/scans
     * Trigger a defensive website intelligence scan.
     */
    public function triggerScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $validatedUrl = $this->urlValidator->validateAndNormalize($validated['url']);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => $e->validator->errors()->first('url') ?? 'Invalid target URL format.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Invalid Target',
                'message' => $e->getMessage(),
            ], 422);
        }

        $user = $request->user();

        $scan = $this->scanner->initiateScan($validatedUrl['normalized_url'], $user?->id);
        $scan = $this->scanner->processScan($scan);

        return response()->json([
            'success' => true,
            'scan' => [
                'id' => $scan->public_id,
                'url' => $scan->target_url,
                'status' => $scan->status,
                'overall_score' => $scan->overall_score,
                'status_label' => $scan->status_label,
                'created_at' => $scan->created_at->toIso8601String(),
                'report_url' => url("/report/{$scan->website->domain}/{$scan->public_id}"),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/scans/{id}
     * Get scan status and summary.
     */
    public function getScan(Request $request, string $id): JsonResponse
    {
        $scan = Scan::where('public_id', $id)->with('website')->first();

        if (!$scan) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Scan with requested identifier was not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'scan' => [
                'id' => $scan->public_id,
                'domain' => $scan->website->domain,
                'url' => $scan->target_url,
                'status' => $scan->status,
                'overall_score' => $scan->overall_score,
                'status_label' => $scan->status_label,
                'score_seo' => $scan->score_seo,
                'score_performance' => $scan->score_performance,
                'score_security' => $scan->score_security,
                'score_accessibility' => $scan->score_accessibility,
                'score_mobile' => $scan->score_mobile,
                'score_technical' => $scan->score_technical,
                'score_ai_readiness' => $scan->score_ai_readiness,
                'completed_at' => $scan->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/reports/{id}
     * Retrieve full 7-pillar report data with issues and recommendations.
     */
    public function getReport(Request $request, string $id): JsonResponse
    {
        $scan = Scan::where('public_id', $id)
            ->with(['website', 'issues', 'metric'])
            ->first();

        if (!$scan) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Report with requested identifier was not found.',
            ], 404);
        }

        $rankedIssues = $scan->issues->sortBy('priority_order')->values();

        return response()->json([
            'success' => true,
            'report' => [
                'id' => $scan->public_id,
                'domain' => $scan->website->domain,
                'url' => $scan->target_url,
                'overall_score' => $scan->overall_score,
                'status_label' => $scan->status_label,
                'scores' => [
                    'seo' => $scan->score_seo,
                    'performance' => $scan->score_performance,
                    'security' => $scan->score_security,
                    'accessibility' => $scan->score_accessibility,
                    'mobile' => $scan->score_mobile,
                    'technical' => $scan->score_technical,
                    'ai_readiness' => $scan->score_ai_readiness,
                ],
                'issues_count' => $rankedIssues->count(),
                'issues' => $rankedIssues->map(fn($issue) => [
                    'pillar' => $issue->pillar,
                    'severity' => $issue->severity,
                    'title' => $issue->title,
                    'description' => $issue->description,
                ]),
                'recommendations' => $rankedIssues->take(5)->map(fn($rec) => [
                    'title' => $rec->title,
                    'pillar' => $rec->pillar,
                    'effort' => $rec->effort,
                    'impact' => $rec->impact,
                    'action_steps' => $rec->action_steps ?? $rec->how_to_fix,
                ]),
                'completed_at' => $scan->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/scans/bulk
     * Controlled bulk scanning (max 10 URLs per batch, safe defensive execution).
     */
    public function bulkScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'urls' => ['required', 'array', 'min:1', 'max:10'],
            'urls.*' => ['required', 'string', 'max:2048'],
        ]);

        $user = $request->user();
        $results = [];

        foreach ($validated['urls'] as $rawUrl) {
            try {
                $validatedUrl = $this->urlValidator->validateAndNormalize($rawUrl);
                $scan = $this->scanner->initiateScan($validatedUrl['normalized_url'], $user?->id);
                $scan = $this->scanner->processScan($scan);

                $results[] = [
                    'url' => $rawUrl,
                    'status' => 'completed',
                    'scan_id' => $scan->public_id,
                    'overall_score' => $scan->overall_score,
                ];
            } catch (Exception $e) {
                $results[] = [
                    'url' => $rawUrl,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'total_submitted' => count($validated['urls']),
            'processed' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * POST /api/v1/monitoring
     * Register website for automated monitoring.
     */
    public function storeMonitoring(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'schedule' => ['nullable', 'string', 'in:daily,weekly'],
            'alert_email' => ['nullable', 'email', 'max:255'],
        ]);

        $user = $request->user();

        if (!$this->featureGate->canMonitorWebsites($user)) {
            return response()->json([
                'error' => 'Quota Exceeded',
                'message' => 'Monitored websites quota reached for your plan.',
            ], 403);
        }

        try {
            $validatedUrl = $this->urlValidator->validateAndNormalize($validated['url']);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Invalid Target',
                'message' => $e->getMessage(),
            ], 422);
        }

        $website = Website::firstOrCreate(
            ['domain' => $validatedUrl['domain']],
            [
                'user_id' => $user->id,
                'scheme' => $validatedUrl['scheme'],
                'canonical_url' => $validatedUrl['normalized_url'],
            ]
        );

        $schedule = $validated['schedule'] ?? 'daily';
        $alertEmail = $validated['alert_email'] ?? $user->email;

        $monitored = MonitoredWebsite::updateOrCreate(
            ['website_id' => $website->id],
            [
                'user_id' => $user->id,
                'schedule' => $schedule,
                'alert_email' => $alertEmail,
                'is_active' => true,
                'status' => 'active',
                'next_scan_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'monitored_website' => [
                'id' => $monitored->id,
                'domain' => $website->domain,
                'schedule' => $monitored->schedule,
                'alert_email' => $monitored->alert_email,
                'is_active' => $monitored->is_active,
            ],
        ], 201);
    }

    /**
     * GET /api/v1/monitoring
     * List user's monitored websites.
     */
    public function listMonitoring(Request $request): JsonResponse
    {
        $monitors = $request->user()
            ->monitoredWebsites()
            ->with(['website.latestScan'])
            ->latest()
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'domain' => $m->website->domain,
                'schedule' => $m->schedule,
                'alert_email' => $m->alert_email,
                'is_active' => $m->is_active,
                'last_scanned_at' => $m->last_scanned_at?->toIso8601String(),
                'latest_score' => $m->website->latestScan?->overall_score,
            ]);

        return response()->json([
            'success' => true,
            'monitored_websites' => $monitors,
        ]);
    }

    /**
     * DELETE /api/v1/monitoring/{id}
     * Remove website from monitoring.
     */
    public function destroyMonitoring(Request $request, int $id): JsonResponse
    {
        $monitored = MonitoredWebsite::findOrFail($id);

        if ($monitored->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Unauthorized access to monitoring record.',
            ], 403);
        }

        $monitored->delete();

        return response()->json([
            'success' => true,
            'message' => 'Website successfully removed from automated monitoring.',
        ]);
    }
}
