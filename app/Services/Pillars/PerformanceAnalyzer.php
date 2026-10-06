<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class PerformanceAnalyzer
{
    public function analyze(HtmlDocument $doc, array $fetchData): array
    {
        $issues = [];
        $penalties = 0;
        $headers = $fetchData['headers'] ?? [];
        $responseTimeMs = $fetchData['response_time_ms'] ?? 0;
        $ttfbMs = $fetchData['ttfb_ms'] ?? $responseTimeMs;
        $bodyBytes = $fetchData['body_bytes'] ?? 0;
        $encoding = strtolower($headers['content-encoding'] ?? '');
        $cacheControl = strtolower($headers['cache-control'] ?? '');

        $scripts = $doc->getScripts();
        $styles = $doc->getStylesheets();
        $images = $doc->getImages();
        $hints = $doc->getResourceHints();

        // 1. TTFB / Server Latency
        if ($ttfbMs > 1200) {
            $penalties += 20;
            $issues[] = [
                'rule_id' => 'perf_slow_response',
                'category' => 'performance',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => "High Time-To-First-Byte (TTFB: {$ttfbMs}ms)",
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['ttfb_ms' => $ttfbMs, 'recommended' => '< 600ms'],
                'why_it_matters' => 'Server delays bottleneck all subsequent asset fetching and directly increase Largest Contentful Paint (LCP). Note: This is a server response measurement, not a browser render timing.',
                'recommendation' => 'Implement server-side caching (Redis, OPcache), optimize backend queries, or place a CDN reverse proxy in front of the origin.',
                'technical_details' => "Measured TTFB: {$ttfbMs}ms. Recommended threshold is below 600ms.",
            ];
        } elseif ($ttfbMs > 600) {
            $penalties += 8;
            $issues[] = [
                'rule_id' => 'perf_slow_response',
                'category' => 'performance',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "Moderate Server Response Time ({$ttfbMs}ms)",
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['ttfb_ms' => $ttfbMs, 'recommended' => '< 600ms'],
                'why_it_matters' => 'Modest server latency adds unnecessary overhead before the browser can begin parsing HTML.',
                'recommendation' => 'Investigate database query performance and consider opcode/page caching.',
                'technical_details' => "TTFB is {$ttfbMs}ms. Ideal performance targets < 400ms.",
            ];
        }

        // 2. Text Compression
        $hasCompression = in_array($encoding, ['gzip', 'br', 'deflate', 'zstd'], true) ||
                          str_contains($encoding, 'gzip') ||
                          str_contains($encoding, 'br');
        if (!$hasCompression && $bodyBytes > 5000) {
            $penalties += 15;
            $issues[] = [
                'rule_id' => 'perf_uncompressed_html',
                'category' => 'performance',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => 'HTML Text Compression Not Enabled',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['content_encoding' => $encoding ?: 'none', 'html_bytes' => $bodyBytes],
                'why_it_matters' => 'Uncompressed HTML takes 3x–5x longer to download across mobile and cellular connections.',
                'recommendation' => 'Enable Gzip or Brotli compression on your web server for text-based resources.',
                'technical_details' => 'Enable `gzip on;` in Nginx or `mod_deflate` in Apache.',
            ];
        }

        // 3. Document Weight
        $bodyKb = round($bodyBytes / 1024, 1);
        if ($bodyBytes > 200 * 1024) {
            $penalties += 12;
            $issues[] = [
                'rule_id' => 'perf_heavy_dom',
                'category' => 'performance',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "Heavy HTML Document Payload ({$bodyKb} KB)",
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['size_kb' => $bodyKb, 'recommended' => '< 100 KB'],
                'why_it_matters' => 'Oversized HTML files degrade main-thread parsing speeds on lower-end mobile devices.',
                'recommendation' => 'Extract large inline SVGs or scripts to external files and paginate heavy lists.',
                'technical_details' => "HTML payload is {$bodyKb} KB.",
            ];
        }

        // 4. Excessive External Scripts
        $externalScripts = array_filter($scripts, fn($s) => !$s['inline']);
        $scriptCount = count($externalScripts);
        if ($scriptCount > 15) {
            $penalties += 10;
            $issues[] = [
                'rule_id' => 'perf_excessive_scripts',
                'category' => 'performance',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => "High Volume of External Scripts ({$scriptCount} files)",
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['external_scripts_count' => $scriptCount],
                'why_it_matters' => 'Numerous third-party scripts cause network contention and lock the main browser thread, damaging interaction responsiveness.',
                'recommendation' => 'Consolidate scripts, eliminate unused libraries, and ensure non-critical scripts use "defer" or "async".',
                'technical_details' => 'Check third-party tracking tags and audit script impact via bundle analyzer.',
            ];
        }

        // 5. Image Lazy Loading Coverage
        $totalImages = count($images);
        $lazyImages = 0;
        foreach ($images as $img) {
            if (($img['loading'] ?? '') === 'lazy') {
                $lazyImages++;
            }
        }
        if ($totalImages > 4 && $lazyImages === 0) {
            $penalties += 5;
            $issues[] = [
                'rule_id' => 'perf_missing_lazy_loading',
                'category' => 'performance',
                'severity' => 'low',
                'confidence' => 'medium',
                'title' => 'Native Image Lazy Loading Not Detected',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['total_images' => $totalImages, 'lazy_count' => 0],
                'why_it_matters' => 'Offscreen images load concurrently during initial page load, consuming bandwidth needed for critical assets.',
                'recommendation' => 'Add loading="lazy" to images situated below the initial fold.',
                'technical_details' => '<img src="..." loading="lazy" decoding="async">',
            ];
        }

        $score = max(10, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'measurement_type' => 'Server-side response measurement (Passive)',
                'ttfb_ms' => $ttfbMs,
                'total_time_ms' => $responseTimeMs,
                'html_size_kb' => $bodyKb,
                'compression' => $encoding ?: 'none',
                'has_compression' => $hasCompression,
                'cache_control' => $cacheControl ?: 'none',
                'scripts_count' => count($scripts),
                'external_scripts_count' => $scriptCount,
                'stylesheets_count' => count($styles),
                'images_count' => $totalImages,
                'lazy_images_count' => $lazyImages,
                'preconnect_hints' => $hints['preconnect'],
                'preload_hints' => $hints['preload'],
                'core_web_vitals_disclaimer' => 'Browser Core Web Vitals (LCP, INP, CLS) are not directly measured by this passive server-side scan. Real field data requires Google Chrome UX Report (CrUX) 28-day user telemetry.',
            ],
        ];
    }
}
