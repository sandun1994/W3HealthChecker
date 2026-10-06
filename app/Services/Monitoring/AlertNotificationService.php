<?php

namespace App\Services\Monitoring;

use App\Models\MonitoredWebsite;
use App\Models\MonitoringAlert;
use App\Models\Scan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertNotificationService
{
    /**
     * Determine if changes warrant an alert according to monitored website preferences.
     */
    public function shouldAlert(MonitoredWebsite $monitoredWebsite, Scan $scan, array $changes): bool
    {
        if (empty($changes)) {
            return false;
        }

        foreach ($changes as $change) {
            $severity = is_array($change) ? ($change['severity'] ?? 'low') : $change->severity;
            $type = is_array($change) ? ($change['change_type'] ?? '') : $change->change_type;

            // 1. Critical severity is always alertable if notify_on_critical_issues is enabled
            if ($severity === 'critical' && $monitoredWebsite->notify_on_critical_issues) {
                return true;
            }

            // 2. SSL expiry alerts
            if (str_contains($type, 'ssl') && $monitoredWebsite->notify_on_ssl_expiry) {
                return true;
            }

            // 3. Significant score drops
            if (str_contains($type, 'score_dropped')) {
                return true;
            }

            // 4. High severity security or indexing regressions
            if ($severity === 'high') {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch an alert for the detected changes.
     */
    public function dispatchAlert(MonitoredWebsite $monitoredWebsite, Scan $scan, array $changes): ?MonitoringAlert
    {
        if (!$this->shouldAlert($monitoredWebsite, $scan, $changes)) {
            return null;
        }

        $recipient = $monitoredWebsite->alert_email ?? $monitoredWebsite->user?->email;
        if (empty($recipient)) {
            $recipient = 'admin@' . $monitoredWebsite->website->domain;
        }

        $domain = $monitoredWebsite->website->domain;
        $score = $scan->overall_score;

        // Determine max severity
        $severities = array_map(function ($c) {
            return is_array($c) ? ($c['severity'] ?? 'info') : $c->severity;
        }, $changes);

        $highestSeverity = in_array('critical', $severities) ? 'critical' : (in_array('high', $severities) ? 'high' : 'medium');

        $subject = match ($highestSeverity) {
            'critical' => "[CRITICAL ALERT] Issues detected on {$domain} (Score: {$score}/100)",
            'high' => "[ALERT] Notable changes detected on {$domain} (Score: {$score}/100)",
            default => "[Notice] Website health update for {$domain}",
        };

        // Build human-readable summary
        $lines = [
            "W3HealthChecker automated monitoring detected changes on {$domain}.",
            "Current Health Score: {$score}/100 ({$scan->status_label})",
            "",
            "Detected Changes & Regressions:",
        ];

        $summaryData = [];

        foreach ($changes as $c) {
            $title = is_array($c) ? $c['title'] : $c->title;
            $desc = is_array($c) ? $c['description'] : $c->description;
            $sev = strtoupper(is_array($c) ? $c['severity'] : $c->severity);

            $lines[] = "• [{$sev}] {$title}: {$desc}";
            $summaryData[] = [
                'title' => $title,
                'severity' => $sev,
                'description' => $desc,
            ];
        }

        $lines[] = "";
        $lines[] = "View complete report: " . url("/report/{$domain}/{$scan->public_id}");

        $messageBody = implode("\n", $lines);

        $alert = MonitoringAlert::create([
            'monitored_website_id' => $monitoredWebsite->id,
            'scan_id' => $scan->id,
            'channel' => $monitoredWebsite->alert_channel ?? 'email',
            'recipient' => $recipient,
            'subject' => $subject,
            'severity' => $highestSeverity,
            'message' => $messageBody,
            'changes_summary' => $summaryData,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Log::info("Monitoring alert dispatched for {$domain}", [
            'alert_id' => $alert->id,
            'recipient' => $recipient,
            'severity' => $highestSeverity,
            'changes_count' => count($changes),
        ]);

        return $alert;
    }
}
