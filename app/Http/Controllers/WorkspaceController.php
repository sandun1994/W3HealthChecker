<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanChange;
use App\Models\Website;
use App\Services\Security\UrlValidationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function __construct(
        protected UrlValidationService $urlValidator,
        protected \App\Services\Billing\FeatureGate $featureGate
    ) {}

    /**
     * Workspace summary metrics and dashboard overview.
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $savedWebsites = $user->websites()->with(['latestScan', 'project', 'monitoredWebsite'])->get();
        $recentScans = $user->scans()->with('website')->take(8)->get();

        $scores = $savedWebsites->pluck('latestScan.overall_score')->filter(fn($s) => $s !== null);
        $averageScore = $scores->isNotEmpty() ? round($scores->average()) : null;

        $websiteIds = $savedWebsites->pluck('id')->all();
        $recentChanges = ScanChange::whereIn('website_id', $websiteIds)->latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'overview' => [
                'total_websites' => $savedWebsites->count(),
                'total_projects' => $user->projects()->count(),
                'total_monitored' => $user->monitoredWebsites()->where('is_active', true)->count(),
                'total_scans' => $user->scans()->count(),
                'average_score' => $averageScore,
            ],
            'entitlements' => $this->featureGate->getUsageAndLimits($user),
            'websites' => $savedWebsites,
            'recent_scans' => $recentScans,
            'recent_changes' => $recentChanges,
        ]);
    }

    /**
     * List all user's workspace websites.
     */
    public function websites(Request $request): JsonResponse
    {
        $websites = $request->user()
            ->websites()
            ->with(['latestScan', 'project', 'monitoredWebsite'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'websites' => $websites,
        ]);
    }

    /**
     * Add a website to user's workspace.
     */
    public function storeWebsite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'monitoring_frequency' => ['nullable', 'string', 'in:daily,weekly'],
        ]);

        try {
            $validatedUrl = $this->urlValidator->validateAndNormalize($validated['url']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('url') ?? 'Invalid URL format.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $user = $request->user();

        // Verify project ownership if project_id is passed
        $projectId = $validated['project_id'] ?? null;
        if ($projectId) {
            $ownsProject = $user->projects()->where('id', $projectId)->exists();
            if (!$ownsProject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found in your workspace.',
                ], 403);
            }
        }

        $website = Website::updateOrCreate(
            ['domain' => $validatedUrl['domain']],
            [
                'user_id' => $user->id,
                'project_id' => $projectId,
                'scheme' => $validatedUrl['scheme'],
                'canonical_url' => $validatedUrl['normalized_url'],
            ]
        );

        if (!empty($validated['monitoring_frequency'])) {
            if (!$this->featureGate->canMonitorWebsites($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monitoring quota reached for your plan. Upgrade to Pro to monitor more websites.',
                    'upgrade_required' => true,
                ], 403);
            }

            if ($validated['monitoring_frequency'] === 'daily' && !$this->featureGate->hasFeature($user, 'daily_monitoring')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily monitoring requires a Pro plan. Free accounts support weekly checks.',
                    'upgrade_required' => true,
                ], 403);
            }

            \App\Models\MonitoredWebsite::updateOrCreate(
                ['website_id' => $website->id],
                [
                    'user_id' => $user->id,
                    'schedule' => $validated['monitoring_frequency'],
                    'is_active' => true,
                    'next_scan_at' => $validated['monitoring_frequency'] === 'weekly' ? now()->addWeek() : now()->addDay(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "{$website->domain} added to your workspace.",
            'website' => $website->load(['latestScan', 'project', 'monitoredWebsite']),
        ], 201);
    }

    /**
     * Remove a website from workspace.
     */
    public function destroyWebsite(Request $request, int $id): JsonResponse
    {
        $website = Website::findOrFail($id);

        if ($website->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to website resource.',
            ], 403);
        }

        $website->update(['user_id' => null, 'project_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Website removed from your workspace.',
        ]);
    }

    /**
     * List user's workspace projects.
     */
    public function projects(Request $request): JsonResponse
    {
        $projects = $request->user()
            ->projects()
            ->withCount('websites')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'projects' => $projects,
        ]);
    }

    /**
     * Create a new project.
     */
    public function storeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if (!$this->featureGate->canCreateProjects($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Project quota reached for your current plan. Upgrade to Pro or Agency to create more projects.',
                'upgrade_required' => true,
            ], 403);
        }

        $project = $user->projects()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'project' => $project,
        ], 201);
    }

    /**
     * Delete a project and unassign its websites.
     */
    public function destroyProject(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        if ($project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to project.',
            ], 403);
        }

        // Unlink websites from project
        Website::where('project_id', $project->id)->update(['project_id' => null]);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * Synchronize and associate anonymous local scan history with user account upon user consent.
     */
    public function syncHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'public_ids' => ['required', 'array'],
            'public_ids.*' => ['string', 'max:64'],
        ]);

        $user = $request->user();
        $updatedCount = 0;

        foreach ($validated['public_ids'] as $publicId) {
            $scan = Scan::where('public_id', $publicId)->first();
            if ($scan && ($scan->user_id === null || $scan->user_id === $user->id)) {
                $scan->update(['user_id' => $user->id]);
                if ($scan->website && $scan->website->user_id === null) {
                    $scan->website->update(['user_id' => $user->id]);
                }
                $updatedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synchronized {$updatedCount} historical scans into your account workspace.",
            'synced_count' => $updatedCount,
        ]);
    }
}
