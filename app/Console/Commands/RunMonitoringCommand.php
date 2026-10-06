<?php

namespace App\Console\Commands;

use App\Models\MonitoredWebsite;
use App\Services\Monitoring\MonitoringExecutionService;
use Illuminate\Console\Command;

class RunMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'w3:monitoring:run {--website-id= : Optional specific monitored website ID to execute} {--force : Run even if not strictly due yet}';

    /**
     * The console command description.
     */
    protected $description = 'Execute due website health monitoring scans, detect changes, and dispatch regression alerts';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringExecutionService $monitoringService): int
    {
        $this->info('Starting W3HealthChecker scheduled monitoring execution...');

        $websiteId = $this->option('website-id');
        $force = $this->option('force');

        if ($websiteId) {
            $monitored = MonitoredWebsite::find($websiteId);
            if (!$monitored) {
                $this->error("Monitored website ID {$websiteId} not found.");
                return Command::FAILURE;
            }

            $this->info("Executing monitoring scan for: {$monitored->website->domain}");
            $result = $monitoringService->executeMonitor($monitored);

            if ($result['success']) {
                $this->info("Completed scan for {$result['domain']}: Score {$result['overall_score']}/100, Changes detected: {$result['changes_count']}");
                return Command::SUCCESS;
            } else {
                $this->error("Failed to run monitor for {$result['domain']}: {$result['error']}");
                return Command::FAILURE;
            }
        }

        // Run all due monitors
        $results = $monitoringService->runDueMonitors();
        $total = count($results);
        $successful = count(array_filter($results, fn($r) => $r['success']));
        $failed = $total - $successful;

        $this->info("Monitoring execution complete: {$successful} successful, {$failed} failed out of {$total} due websites.");

        return Command::SUCCESS;
    }
}
