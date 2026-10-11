<?php

namespace App\Http\Controllers;

use App\Models\ScanIssue;
use App\Services\Remediation\AiExplanationService;
use App\Services\Remediation\RemediationGuidanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemediationController extends Controller
{
    public function __construct(
        protected RemediationGuidanceService $guidanceService,
        protected AiExplanationService $explanationService
    ) {}

    /**
     * Get structured remediation guidance for a specific scan finding.
     */
    public function show(int $issueId): JsonResponse
    {
        $issue = ScanIssue::with('scan')->find($issueId);

        if (!$issue) {
            return response()->json(['success' => false, 'message' => 'Scan issue not found.'], 404);
        }

        $guidance = $this->guidanceService->getGuidance($issue, auth()->id());

        return response()->json([
            'success' => true,
            'guidance' => $guidance,
        ]);
    }

    /**
     * Get plain-language / skill-adapted explanation for a finding.
     */
    public function explain(Request $request, int $issueId): JsonResponse
    {
        $issue = ScanIssue::with('scan')->find($issueId);

        if (!$issue) {
            return response()->json(['success' => false, 'message' => 'Scan issue not found.'], 404);
        }

        $skillLevel = $request->input('skill_level', 'intermediate');
        $explanation = $this->explanationService->explain($issue, $skillLevel);

        return response()->json([
            'success' => true,
            'explanation' => $explanation,
        ]);
    }

    /**
     * Update user workflow status for a finding (planned, in_progress, dismissed).
     */
    public function updateStatus(Request $request, int $issueId): JsonResponse
    {
        $issue = ScanIssue::find($issueId);

        if (!$issue) {
            return response()->json(['success' => false, 'message' => 'Scan issue not found.'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:open,planned,in_progress,implemented,dismissed',
            'notes' => 'nullable|string|max:1000',
            'dismissed_reason' => 'nullable|string|max:255',
        ]);

        $remediation = $this->guidanceService->updateStatus(
            issue: $issue,
            userId: auth()->id(),
            status: $validated['status'],
            notes: $validated['notes'] ?? null,
            reason: $validated['dismissed_reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Remediation workflow status updated.',
            'workflow' => [
                'status' => $remediation->status,
                'user_notes' => $remediation->user_notes,
                'dismissed_reason' => $remediation->dismissed_reason,
            ],
        ]);
    }
}
