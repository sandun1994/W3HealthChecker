<?php

namespace App\Services\Monitoring;

use App\Models\MonitoredWebsite;
use App\Models\Scan;
use App\Services\Scanner\WebsiteScanService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MonitoringExecutionService
{
    public function __construct(
        protected WebsiteScanService $scanService,
        protected ChangeDetectionService $changeDetector,
        protected AlertNotificationService $alertService
    ) {}

    /**
     * Run all due monitors.
     */
    public function runDueMonitors(): array
    {
        $due = MonitoredWebsite::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_scan_at')
                  ->orWhere('next_scan_at', '<=', now());
            })
            ->with(['website', 'user'])
            ->get();

        $results = [];
        foreach ($due as $monitored) {
            $results[] = $this->executeMonitor($monitored);
        }

        return $results;
    }

    /**
     * Execute a single monitored website run safely.
     */
    public function executeMonitor(MonitoredWebsite $monitoredWebsite): array
    {
        $website = $monitoredWebsite->website;
        $domain = $website->domain;
        $url = $website->canonical_url;

        Log::info("Executing scheduled monitor for {$domain}", [
            'monitored_website_id' => $monitoredWebsite->id,
            'url' => $url,
            'schedule' => $monitoredWebsite->schedule,
        ]);

        try {
            // Retrieve previous completed scan before creating the new scan
            $previousScan = Scan::where('website_id', $website->id)
                ->where('status', 'completed')
                ->latest()
                ->first();

            // 1. Initiate safe scan (with SSRF protection & normalization)
            $newScan = $this->scanService->initiateScan($url, $monitoredWebsite->user_id);

            // 2. Execute scan synchronously
            $completedScan = $this->scanService->executeScan($newScan);

            if ($completedScan->status !== 'completed') {
                throw new Exception("Scan ended with status {$completedScan->status}: " . ($completedScan->error_message ?? 'Unknown error'));
            }

            // 3. Detect & Record Changes
            $recordedChanges = [];
            if ($previousScan && $previousScan->id !== $completedScan->id) {
                $recordedChanges = $this->changeDetector->detectAndRecordChanges($completedScan, $previousScan);
            }

            // 4. Dispatch Alerts if needed
            $alert = null;
            if (!empty($recordedChanges)) {
                $alert = $this->alertService->dispatchAlert($monitoredWebsite, $completedScan, $recordedChanges);
            }

            // 5. Update Monitored Website timestamps
            $monitoredWebsite->update([
                'last_scanned_at' => now(),
                'next_scan_at' => $monitoredWebsite->calculateNextScanAt(),
                'consecutive_failures' => 0,
                'status' => 'active',
            ]);

            return [
                'success' => true,
                'monitored_website_id' => $monitoredWebsite->id,
                'domain' => $domain,
                'scan_id' => $completedScan->id,
                'overall_score' => $completedScan->overall_score,
                'changes_count' => count($recordedChanges),
                'alert_dispatched' => $alert !== null,
            ];
        } catch (Exception $e) {
            Log::error("Scheduled monitor run failed for {$domain}: {$e->getMessage()}", [
                'monitored_website_id' => $monitoredWebsite->id,
            ]);

            $failures = $monitoredWebsite->consecutive_failures + 1;
            $status = $failures >= 3 ? 'failing' : 'active';

            $monitoredWebsite->update([
                'consecutive_failures' => $failures,
                'status' => $status,
                'next_scan_at' => now()->addHours(6), // Retry in 6 hours upon failure
            ]);

            return [
                'success' => false,
                'monitored_website_id' => $monitoredWebsite->id,
                'domain' => $domain,
                'error' => $e->getMessage(),
            ];
        }
    }
}
