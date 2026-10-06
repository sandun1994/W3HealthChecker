<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class AiReadinessAnalyzer
{
    public function analyze(HtmlDocument $doc, array $fetchData, array $auxiliaryData = []): array
    {
        $issues = [];
        $penalties = 0;
        $finalUrl = $fetchData['final_url'] ?? '';

        $jsonLd = $doc->getStructuredDataJsonLd();
        $headings = $doc->getHeadings();
        $openGraphTitle = $doc->getMetaProperty('og:title');
        $llmsTxt = $auxiliaryData['llms_txt'] ?? ['found' => false];
        $robotsContent = $auxiliaryData['robots']['content'] ?? '';

        // 1. Structured Data Schema Check
        $validSchemas = array_filter($jsonLd, fn($s) => $s['valid']);
        $schemaTypes = array_map(fn($s) => $s['type'] ?? 'Item', $validSchemas);

        if (empty($jsonLd)) {
            $penalties += 25;
            $issues[] = [
                'rule_id' => 'ai_structured_data_missing',
                'category' => 'ai_readiness',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => 'No Machine-Readable Structured Data (Schema.org) Found',
                'affected_resource' => $finalUrl,
                'evidence' => ['json_ld_schemas' => 0],
                'why_it_matters' => 'AI systems and search knowledge graphs rely on structured JSON-LD (such as Organization, WebSite, Article) to disambiguate entities and extract facts without guessing.',
                'recommendation' => 'Embed schema.org JSON-LD markup describing your organization, products, or core entities.',
                'technical_details' => '<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite",...}</script>',
            ];
        }

        // 2. Semantic Heading Hierarchy
        $hasH1 = count($headings['h1']) > 0;
        $hasH2 = count($headings['h2']) > 0;
        if (!$hasH1 || !$hasH2) {
            $penalties += 12;
            $issues[] = [
                'rule_id' => 'ai_semantic_structure_weak',
                'category' => 'ai_readiness',
                'severity' => 'medium',
                'confidence' => 'medium',
                'title' => 'Weak Semantic Document Structure for AI Parsing',
                'affected_resource' => $finalUrl,
                'evidence' => [
                    'h1_present' => $hasH1,
                    'h2_present' => $hasH2,
                ],
                'why_it_matters' => 'Large Language Models (LLMs) and retrieval-augmented generation (RAG) scrapers use heading hierarchies (H1 -> H2 -> H3) to chunk and index textual content.',
                'recommendation' => 'Organize page content with a single primary H1 and logical section subheadings (H2, H3).',
                'technical_details' => 'Structured headings produce superior chunk boundaries in vector search indices.',
            ];
        }

        // 3. llms.txt Discovery
        if (!$llmsTxt['found']) {
            $penalties += 5;
            $issues[] = [
                'rule_id' => 'ai_llms_txt_missing',
                'category' => 'ai_readiness',
                'severity' => 'info',
                'confidence' => 'high',
                'title' => 'No /llms.txt File Detected',
                'affected_resource' => rtrim($finalUrl, '/') . '/llms.txt',
                'evidence' => ['checked_url' => rtrim($finalUrl, '/') . '/llms.txt', 'found' => false],
                'why_it_matters' => 'llms.txt is an emerging convention intended to provide structured markdown guidance for AI search assistants. It is not currently an established Google ranking requirement.',
                'recommendation' => 'Consider publishing a curated /llms.txt markdown file if you publish documentation, APIs, or key knowledge resources.',
                'technical_details' => 'Emerging standard specification: https://llmstxt.org/',
            ];
        }

        // 4. AI Search Crawler Directives in robots.txt
        $mentionsAiBot = false;
        $aiBots = ['gptbot', 'claudebot', 'perplexitybot', 'google-extended', 'ccbot'];
        $lowerRobots = strtolower($robotsContent);
        foreach ($aiBots as $bot) {
            if (str_contains($lowerRobots, $bot)) {
                $mentionsAiBot = true;
                break;
            }
        }

        $score = max(15, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'structured_data_present' => !empty($validSchemas),
                'schema_types' => $schemaTypes,
                'llms_txt_detected' => $llmsTxt['found'],
                'semantic_structure_status' => ($hasH1 && $hasH2) ? 'Strong' : 'Moderate',
                'ai_crawler_directives_detected' => $mentionsAiBot,
                'disclaimer' => 'AI / Search Readiness measures technical discoverability, structured schema depth, and machine-readable markup. It does not guarantee ranking in generative search engines.',
            ],
        ];
    }
}
