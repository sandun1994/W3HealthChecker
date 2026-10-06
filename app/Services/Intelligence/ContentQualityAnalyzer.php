<?php

namespace App\Services\Intelligence;

use DOMDocument;
use DOMXPath;

class ContentQualityAnalyzer
{
    /**
     * Analyze content structure, text-to-HTML ratio, and AI crawler visibility signals.
     */
    public function analyze(string $html, ?string $robotsTxt = null): array
    {
        $htmlLength = strlen($html);
        $cleanText = trim(strip_tags(preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html)));
        $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText));
        $wordCount = str_word_count($cleanText);
        $textLength = strlen($cleanText);

        $textToHtmlRatio = $htmlLength > 0 ? round(($textLength / $htmlLength) * 100, 1) : 0;

        // Heading Structure
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        $h1Count = $xpath->query('//h1')->length;
        $h2Count = $xpath->query('//h2')->length;
        $h3Count = $xpath->query('//h3')->length;

        $hasMain = $xpath->query('//main')->length > 0;
        $hasArticle = $xpath->query('//article')->length > 0;
        $hasNav = $xpath->query('//nav')->length > 0;
        $hasFooter = $xpath->query('//footer')->length > 0;

        libxml_clear_errors();

        // AI Crawler Directives in robots.txt
        $aiCrawlers = $this->analyzeAiBots($robotsTxt);

        return [
            'content_metrics' => [
                'word_count' => $wordCount,
                'html_bytes' => $htmlLength,
                'text_bytes' => $textLength,
                'text_to_html_ratio_percentage' => $textToHtmlRatio,
                'thin_content_flag' => $wordCount < 150,
            ],
            'document_hierarchy' => [
                'h1_count' => $h1Count,
                'h2_count' => $h2Count,
                'h3_count' => $h3Count,
                'single_h1_present' => $h1Count === 1,
            ],
            'semantic_landmarks' => [
                'main_tag' => $hasMain,
                'article_tag' => $hasArticle,
                'nav_tag' => $hasNav,
                'footer_tag' => $hasFooter,
            ],
            'ai_search_readiness' => [
                'disclaimer' => 'AI/Search Readiness represents technical and structural signals that may improve machine-readable understanding.',
                'crawlers' => $aiCrawlers,
            ],
        ];
    }

    /**
     * Check robots.txt for AI agent directives.
     */
    protected function analyzeAiBots(?string $robotsTxt): array
    {
        $bots = [
            'GPTBot' => 'OpenAI model training',
            'ClaudeBot' => 'Anthropic Claude ingestion',
            'Google-Extended' => 'Google Gemini model training',
            'CCBot' => 'Common Crawl AI datasets',
            'PerplexityBot' => 'Perplexity AI search index',
        ];

        $results = [];
        $robotsLower = strtolower($robotsTxt ?? '');

        foreach ($bots as $bot => $description) {
            $botLower = strtolower($bot);
            $status = 'Allowed by default';

            if (str_contains($robotsLower, "user-agent: {$botLower}")) {
                if (preg_match("/user-agent:\s*" . preg_quote($botLower, '/') . ".*?disallow:\s*\//s", $robotsLower)) {
                    $status = 'Blocked (Disallow: /)';
                } else {
                    $status = 'Explicitly configured';
                }
            }

            $results[] = [
                'bot' => $bot,
                'purpose' => $description,
                'status' => $status,
                'is_blocked' => str_contains($status, 'Blocked'),
            ];
        }

        return $results;
    }
}
