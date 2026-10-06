<?php

namespace App\Http\Controllers;

use App\Models\MonitoredWebsite;
use App\Models\Scan;
use App\Models\ScanChange;
use App\Models\Website;
use App\Services\Monitoring\MonitoringExecutionService;
use App\Services\Security\UrlValidationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MonitoringController extends Controller
{
    public function __construct(
        protected UrlValidationService $urlValidator,
        protected MonitoringExecutionService $monitoringService,
        protected \App\Services\Billing\FeatureGate $featureGate
    ) {}

    /**
     * List all monitored websites.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $monitors = MonitoredWebsite::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->with(['website.latestScan'])
            ->latest()
            ->get()
            ->map(function ($monitored) {
                $latestScan = $monitored->website->latestScan;
                return [
                    'id' => $monitored->id,
                    'domain' => $monitored->website->domain,
                    'canonical_url' => $monitored->website->canonical_url,
                    'schedule' => $monitored->schedule,
                    'alert_email' => $monitored->alert_email,
                    'status' => $monitored->status,
                    'is_active' => $monitored->is_active,
                    'last_scanned_at' => $monitored->last_scanned_at?->toIso8601String(),
                    'next_scan_at' => $monitored->next_scan_at?->toIso8601String(),
                    'latest_score' => $latestScan?->overall_score,
                    'latest_status_label' => $latestScan?->status_label,
                    'public_id' => $latestScan?->public_id,
                ];
            });

        return response()->json([
            'success' => true,
            'monitored_websites' => $monitors,
        ]);
    }

    /**
     * Subscribe a website to automated monitoring.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedInput = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'schedule' => ['nullable', 'string', 'in:daily,weekly'],
            'alert_email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            // Validate & normalize URL through defensive SSRF pipeline
            $validatedUrl = $this->urlValidator->validateAndNormalize($validatedInput['url']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('url') ?? 'Invalid URL provided.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $userId = $request->user()?->id;

        $website = Website::firstOrCreate(
            ['domain' => $validatedUrl['domain']],
            [
                'user_id' => $userId,
                'scheme' => $validatedUrl['scheme'],
                'canonical_url' => $validatedUrl['normalized_url'],
            ]
        );

        $schedule = $validatedInput['schedule'] ?? 'daily';
        $alertEmail = $validatedInput['alert_email'] ?? $request->user()?->email;

        if ($request->user()) {
            $user = $request->user();
            if (!$this->featureGate->canMonitorWebsites($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monitoring quota reached for your current plan. Upgrade to Pro to monitor more websites.',
                    'upgrade_required' => true,
                ], 403);
            }

            if ($schedule === 'daily' && !$this->featureGate->hasFeature($user, 'daily_monitoring')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily monitoring requires a Pro plan. Free accounts support weekly checks.',
                    'upgrade_required' => true,
                ], 403);
            }
        }

        $monitored = MonitoredWebsite::updateOrCreate(
            ['website_id' => $website->id],
            [
                'user_id' => $userId,
                'schedule' => $schedule,
                'alert_email' => $alertEmail,
                'is_active' => true,
                'status' => 'active',
                'next_scan_at' => now(), // Queue for execution immediately
            ]
        );

        // Update website flag
        $website->update(['is_monitored' => true, 'monitoring_frequency' => $schedule]);

        return response()->json([
            'success' => true,
            'message' => "Monitoring configured successfully for {$website->domain}.",
            'monitored_website' => [
                'id' => $monitored->id,
                'domain' => $website->domain,
                'canonical_url' => $website->canonical_url,
                'schedule' => $monitored->schedule,
                'alert_email' => $monitored->alert_email,
                'next_scan_at' => $monitored->next_scan_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get monitoring details for a specific monitored website.
     */
    public function show(int $id): JsonResponse
    {
        $monitored = MonitoredWebsite::with(['website.scans' => function ($q) {
            $q->where('status', 'completed')->take(10);
        }, 'alerts'])->findOrFail($id);

        $website = $monitored->website;
        $changes = ScanChange::where('website_id', $website->id)->latest()->take(20)->get();

        return response()->json([
            'success' => true,
            'monitored_website' => $monitored,
            'recent_scans' => $website->scans,
            'recent_changes' => $changes,
        ]);
    }

    /**
     * Trigger immediate monitoring execution.
     */
    public function run(int $id): JsonResponse
    {
        $monitored = MonitoredWebsite::with('website')->findOrFail($id);
        $result = $this->monitoringService->executeMonitor($monitored);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Delete or deactivate monitoring.
     */
    public function destroy(int $id): JsonResponse
    {
        $monitored = MonitoredWebsite::findOrFail($id);
        $website = $monitored->website;

        $monitored->delete();
        $website?->update(['is_monitored' => false, 'monitoring_frequency' => null]);

        return response()->json([
            'success' => true,
            'message' => "Monitoring deleted successfully.",
        ]);
    }

    /**
     * Historical scans for a given domain.
     */
    public function history(string $domain): JsonResponse
    {
        $website = Website::where('domain', $domain)->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => "Website record not found for {$domain}.",
            ], 404);
        }

        $scans = Scan::where('website_id', $website->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'asc')
            ->get([
                'id',
                'public_id',
                'overall_score',
                'status_label',
                'score_seo',
                'score_performance',
                'score_security',
                'score_accessibility',
                'score_mobile',
                'score_technical',
                'score_ai_readiness',
                'response_time_ms',
                'completed_at',
            ]);

        return response()->json([
            'success' => true,
            'domain' => $domain,
            'scans' => $scans,
        ]);
    }

    /**
     * Health score trend and changes between scans.
     */
    public function trend(string $domain): JsonResponse
    {
        $website = Website::where('domain', $domain)->first();

        if (!$website) {
            return response()->json([
                'success' => false,
                'message' => "Website record not found for {$domain}.",
            ], 404);
        }

        $scans = Scan::where('website_id', $website->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'asc')
            ->get();

        $changes = ScanChange::where('website_id', $website->id)
            ->latest()
            ->take(30)
            ->get();

        // Calculate deltas between the last two scans if available
        $latest = $scans->last();
        $previous = $scans->count() > 1 ? $scans->get($scans->count() - 2) : null;

        $deltas = null;
        if ($latest && $previous) {
            $deltas = [
                'overall' => $latest->overall_score - $previous->overall_score,
                'seo' => $latest->score_seo - $previous->score_seo,
                'performance' => $latest->score_performance - $previous->score_performance,
                'security' => $latest->score_security - $previous->score_security,
                'accessibility' => $latest->score_accessibility - $previous->score_accessibility,
                'mobile' => $latest->score_mobile - $previous->score_mobile,
                'technical' => $latest->score_technical - $previous->score_technical,
                'ai_readiness' => $latest->score_ai_readiness - $previous->score_ai_readiness,
            ];
        }

        return response()->json([
            'success' => true,
            'domain' => $domain,
            'total_scans' => $scans->count(),
            'score_trajectory' => $scans->map(fn($s) => [
                'date' => $s->completed_at?->format('M j, Y'),
                'score' => $s->overall_score,
                'public_id' => $s->public_id,
            ]),
            'deltas' => $deltas,
            'changes' => $changes,
        ]);
    }
}
