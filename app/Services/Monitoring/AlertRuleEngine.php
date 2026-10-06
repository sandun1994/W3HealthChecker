<?php

namespace App\Services\Monitoring;

use App\Models\Scan;

class AlertRuleEngine
{
    /**
     * Default rule definitions.
     */
    protected array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? [
            [
                'id' => 'rule_score_threshold',
                'type' => 'score_drop',
                'threshold' => 70,
                'severity' => 'HIGH',
                'description' => 'Overall score dropped below acceptable baseline',
            ],
            [
                'id' => 'rule_security_pillar_drop',
                'type' => 'pillar_drop',
                'pillar' => 'security',
                'drop_threshold' => 10,
                'severity' => 'CRITICAL',
                'description' => 'Security score decreased significantly',
            ],
            [
                'id' => 'rule_ssl_expiry_critical',
                'type' => 'ssl_expiry',
                'days_threshold' => 30,
                'severity' => 'CRITICAL',
                'description' => 'SSL certificate approaching expiration',
            ],
            [
                'id' => 'rule_critical_issues_detected',
                'type' => 'critical_issue',
                'severity' => 'CRITICAL',
                'description' => 'Critical security or technical issues identified',
            ],
        ];
    }

    /**
     * Evaluate rules against the current scan and optional previous scan.
     * Returns an array of triggered alerts.
     */
    public function evaluate(Scan $currentScan, ?Scan $previousScan = null): array
    {
        $triggeredAlerts = [];
        $currentScores = $currentScan->scores ?? [];
        $currentOverall = $currentScan->overall_score ?? 0;

        foreach ($this->rules as $rule) {
            switch ($rule['type']) {
                case 'score_drop':
                    if ($currentOverall < ($rule['threshold'] ?? 70)) {
                        $triggeredAlerts[] = [
                            'rule_id' => $rule['id'],
                            'type' => 'SCORE_BELOW_THRESHOLD',
                            'severity' => $rule['severity'],
                            'message' => "Overall health score ({$currentOverall}/100) is below the configured threshold of {$rule['threshold']}.",
                            'value' => $currentOverall,
                        ];
                    }
                    break;

                case 'pillar_drop':
                    if ($previousScan) {
                        $pillar = $rule['pillar'] ?? 'security';
                        $col = 'score_' . $pillar;
                        $prevPillar = $previousScan->{$col} ?? ($previousScan->scores[$pillar] ?? 0);
                        $currPillar = $currentScan->{$col} ?? ($currentScan->scores[$pillar] ?? 0);
                        $drop = $prevPillar - $currPillar;

                        if ($drop >= ($rule['drop_threshold'] ?? 10)) {
                            $triggeredAlerts[] = [
                                'rule_id' => $rule['id'],
                                'type' => 'PILLAR_SCORE_DROPPED',
                                'severity' => $rule['severity'],
                                'message' => ucfirst($pillar) . " score dropped by {$drop} points (from {$prevPillar} to {$currPillar}).",
                                'drop' => $drop,
                            ];
                        }
                    }
                    break;

                case 'ssl_expiry':
                    $sslInfo = $currentScan->metric?->ssl_data ?? ($currentScan->raw_data['security']['ssl'] ?? []);
                    $daysRemaining = $sslInfo['days_remaining'] ?? null;
                    if ($daysRemaining !== null && $daysRemaining <= ($rule['days_threshold'] ?? 30)) {
                        $triggeredAlerts[] = [
                            'rule_id' => $rule['id'],
                            'type' => 'SSL_EXPIRING_SOON',
                            'severity' => $rule['severity'],
                            'message' => "SSL certificate expires in {$daysRemaining} days (threshold: {$rule['days_threshold']} days).",
                            'days_remaining' => $daysRemaining,
                        ];
                    }
                    break;

                case 'critical_issue':
                    $criticalCount = $currentScan->issues()->where('severity', 'critical')->count();
                    if ($criticalCount > 0) {
                        $triggeredAlerts[] = [
                            'rule_id' => $rule['id'],
                            'type' => 'CRITICAL_ISSUES_FOUND',
                            'severity' => $rule['severity'],
                            'message' => "Scan identified {$criticalCount} critical issue(s) requiring immediate resolution.",
                            'critical_count' => $criticalCount,
                        ];
                    }
                    break;
            }
        }

        return $triggeredAlerts;
    }
}
