<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class MobileAnalyzer
{
    public function analyze(HtmlDocument $doc, array $fetchData): array
    {
        $issues = [];
        $penalties = 0;
        $finalUrl = $fetchData['final_url'] ?? '';

        $viewport = $doc->getMetaTag('viewport');

        // 1. Viewport Meta Tag
        if (empty($viewport)) {
            $penalties += 45;
            $issues[] = [
                'rule_id' => 'mobile_viewport_missing',
                'category' => 'mobile',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'Missing Mobile Viewport Meta Tag',
                'affected_resource' => $finalUrl,
                'evidence' => ['viewport_tag' => null],
                'why_it_matters' => 'Mobile browsers will render the page using a desktop viewport width (typically 980px) and scale it down, rendering text unreadable without pinch-zooming.',
                'recommendation' => 'Add the standard responsive viewport meta tag to the <head> of the document.',
                'technical_details' => '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
            ];
        } else {
            $hasWidthDevice = str_contains($viewport, 'width=device-width');
            $hasInitialScale = str_contains($viewport, 'initial-scale=1');
            $blocksZoom = str_contains($viewport, 'user-scalable=no') || str_contains($viewport, 'maximum-scale=1');

            if (!$hasWidthDevice || !$hasInitialScale) {
                $penalties += 15;
                $issues[] = [
                    'rule_id' => 'mobile_viewport_non_responsive',
                    'category' => 'mobile',
                    'severity' => 'high',
                    'confidence' => 'high',
                    'title' => 'Non-Standard Viewport Configuration',
                    'affected_resource' => $finalUrl,
                    'evidence' => ['viewport_value' => $viewport],
                    'why_it_matters' => 'A non-standard viewport value may cause incorrect layout scaling or horizontal overflow on modern smartphones.',
                    'recommendation' => 'Use the standard "width=device-width, initial-scale=1.0" configuration.',
                    'technical_details' => 'Found: ' . htmlspecialchars($viewport),
                ];
            }

            if ($blocksZoom) {
                $penalties += 10;
                $issues[] = [
                    'rule_id' => 'mobile_viewport_non_responsive',
                    'category' => 'mobile',
                    'severity' => 'medium',
                    'confidence' => 'high',
                    'title' => 'Pinch-to-Zoom Disabled on Mobile Devices',
                    'affected_resource' => $finalUrl,
                    'evidence' => ['viewport_value' => $viewport],
                    'why_it_matters' => 'Disabling user scaling prevents low-vision mobile users from enlarging text or interfaces.',
                    'recommendation' => 'Remove "user-scalable=no" and "maximum-scale=1.0" from the viewport content attribute.',
                    'technical_details' => 'WCAG Success Criterion 1.4.4 requires content to be resizable up to 200%.',
                ];
            }
        }

        $score = max(10, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'viewport_present' => !empty($viewport),
                'viewport_content' => $viewport ?? 'None',
                'mobile_friendly' => !empty($viewport) && str_contains($viewport, 'width=device-width'),
            ],
        ];
    }
}
