<?php

namespace App\Services\Intelligence;

use App\Services\Scanner\HttpFetchService;
use App\Services\Security\UrlValidationService;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;

class ControlledSiteCrawler
{
    public function __construct(
        protected UrlValidationService $urlValidator,
        protected HttpFetchService $fetchService
    ) {}

    /**
     * Perform a controlled, defensive same-domain crawl.
     * Strict bounds: max pages (default 5, max 15), max depth 2.
     */
    public function crawl(string $startUrl, int $maxPages = 5, int $maxDepth = 2): array
    {
        $maxPages = min(15, max(1, $maxPages));
        $maxDepth = min(2, max(1, $maxDepth));

        try {
            $normalizedStart = $this->urlValidator->validateAndNormalize($startUrl);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Initial URL rejected: ' . $e->getMessage(),
            ];
        }

        $baseDomain = strtolower($normalizedStart['domain']);
        $baseScheme = $normalizedStart['scheme'];
        $cleanStartUrl = rtrim($normalizedStart['normalized_url'], '/');

        $queue = [
            ['url' => $cleanStartUrl, 'depth' => 0, 'parent' => null],
        ];

        $visited = [];
        $crawlMap = [];
        $internalLinkCounts = [];
        $brokenLinks = [];

        while (!empty($queue) && count($visited) < $maxPages) {
            $current = array_shift($queue);
            $currentUrl = $current['url'];
            $currentDepth = $current['depth'];

            if (isset($visited[$currentUrl])) {
                continue;
            }

            try {
                // Defensive SSRF validation on every target
                $valid = $this->urlValidator->validateAndNormalize($currentUrl);
                if (strtolower($valid['domain']) !== $baseDomain) {
                    continue; // Strict same-domain enforcement
                }

                $fetch = $this->fetchService->fetchPage($currentUrl);
                $visited[$currentUrl] = [
                    'url' => $currentUrl,
                    'depth' => $currentDepth,
                    'status_code' => $fetch['http_status'],
                    'response_time_ms' => $fetch['response_time_ms'],
                ];

                if ($current['parent']) {
                    $crawlMap[] = [
                        'from' => $current['parent'],
                        'to' => $currentUrl,
                        'depth' => $currentDepth,
                    ];
                }

                if ($fetch['http_status'] >= 400) {
                    $brokenLinks[] = [
                        'url' => $currentUrl,
                        'status' => $fetch['http_status'],
                        'found_on' => $current['parent'],
                    ];
                    continue;
                }

                // If within depth limit, extract same-domain links
                if ($currentDepth < $maxDepth) {
                    $links = $this->extractLinks($fetch['html'], $currentUrl, $baseDomain, $baseScheme);

                    foreach ($links as $link) {
                        $internalLinkCounts[$link] = ($internalLinkCounts[$link] ?? 0) + 1;

                        if (!isset($visited[$link]) && !in_array($link, array_column($queue, 'url'), true)) {
                            $queue[] = [
                                'url' => $link,
                                'depth' => $currentDepth + 1,
                                'parent' => $currentUrl,
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::debug('Controlled crawler skipped URL: ' . $currentUrl . ' - ' . $e->getMessage());
                $visited[$currentUrl] = [
                    'url' => $currentUrl,
                    'depth' => $currentDepth,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Identify orphan candidates (pages with few internal incoming links)
        $orphanCandidates = [];
        foreach ($visited as $url => $info) {
            if ($url !== $normalizedStart['normalized_url'] && ($internalLinkCounts[$url] ?? 0) <= 1) {
                $orphanCandidates[] = $url;
            }
        }

        return [
            'success' => true,
            'root_url' => $normalizedStart['normalized_url'],
            'total_crawled' => count($visited),
            'max_depth_reached' => max(array_column($visited, 'depth') ?: [0]),
            'pages' => array_values($visited),
            'crawl_map' => $crawlMap,
            'broken_links' => $brokenLinks,
            'orphan_candidates' => array_slice($orphanCandidates, 0, 5),
        ];
    }

    /**
     * Extract unique internal links from page HTML.
     */
    protected function extractLinks(string $html, string $pageUrl, string $baseDomain, string $baseScheme): array
    {
        if (empty(trim($html))) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        $discovered = [];

        foreach ($xpath->query('//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));

            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Resolve relative URLs
            $resolved = $this->resolveUrl($href, $pageUrl, $baseDomain, $baseScheme);

            if ($resolved) {
                $discovered[$resolved] = true;
            }
        }

        libxml_clear_errors();

        return array_keys($discovered);
    }

    protected function resolveUrl(string $href, string $pageUrl, string $baseDomain, string $baseScheme): ?string
    {
        if (str_starts_with($href, '//')) {
            $href = $baseScheme . ':' . $href;
        } elseif (str_starts_with($href, '/')) {
            $href = $baseScheme . '://' . $baseDomain . $href;
        } elseif (!str_starts_with($href, 'http://') && !str_starts_with($href, 'https://')) {
            $basePath = parse_url($pageUrl, PHP_URL_PATH) ?? '/';
            $dir = dirname($basePath);
            $href = $baseScheme . '://' . $baseDomain . ($dir === '/' ? '' : $dir) . '/' . $href;
        }

        $host = parse_url($href, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        $cleanHost = strtolower(preg_replace('/^www\./', '', $host));
        if ($cleanHost !== $baseDomain) {
            return null; // External domain filtered
        }

        // Strip fragments and normalize trailing slash
        $parts = parse_url($href);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        $resolved = $baseScheme . '://' . $cleanHost . $path;
        return rtrim($resolved, '/') . $query;
    }
}
