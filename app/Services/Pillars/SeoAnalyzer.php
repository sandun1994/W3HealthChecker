<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class SeoAnalyzer
{
    public function analyze(HtmlDocument $doc, array $fetchData, array $auxiliaryData): array
    {
        $issues = [];
        $penalties = 0;

        $title = $doc->getTitle();
        $metaDesc = $doc->getMetaTag('description');
        $canonical = $doc->getCanonical();
        $robotsMeta = strtolower($doc->getMetaTag('robots') ?? '');
        $headings = $doc->getHeadings();
        $images = $doc->getImages();
        $links = $doc->getLinks();
        $lang = $doc->getHtmlLang();

        // 1. Title Checks
        if (empty($title)) {
            $penalties += 25;
            $issues[] = [
                'rule_id' => 'seo_title_missing',
                'category' => 'seo',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'Missing HTML Title Tag',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['tag' => '<title>', 'found' => false],
                'why_it_matters' => 'Search engines use the title tag as the primary headline in search snippets and browser tabs. Missing titles severely damage click-through rates.',
                'recommendation' => 'Add a concise, unique <title> tag between 30 and 60 characters to the document <head>.',
                'technical_details' => '<head>\n  <title>Descriptive Topic Title - Brand Name</title>\n</head>',
            ];
        } else {
            $titleLength = mb_strlen($title);
            if ($titleLength < 30 || $titleLength > 60) {
                $penalties += 6;
                $issues[] = [
                    'rule_id' => 'seo_title_length',
                    'category' => 'seo',
                    'severity' => 'medium',
                    'confidence' => 'high',
                    'title' => "Title Tag Length Suboptimal ({$titleLength} characters)",
                    'affected_resource' => $fetchData['final_url'],
                    'evidence' => ['title' => $title, 'length' => $titleLength, 'recommended' => '30-60 characters'],
                    'why_it_matters' => $titleLength > 60
                        ? 'Titles exceeding 60 characters are typically truncated with an ellipsis (...) in search engine result snippets. Note: Length affects snippet display, not ranking directly.'
                        : 'Titles under 30 characters miss valuable descriptive context and user intent matching.',
                    'recommendation' => 'Refine your title length to fall within 30 to 60 characters (~580 pixels) to avoid snippet truncation.',
                    'technical_details' => 'Front-load important keywords and append brand suffix: "Primary Value Proposition | Brand"',
                ];
            }
        }

        // 2. Meta Description Checks
        if (empty($metaDesc)) {
            $penalties += 15;
            $issues[] = [
                'rule_id' => 'seo_meta_desc_missing',
                'category' => 'seo',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => 'Missing Meta Description Tag',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['found' => false],
                'why_it_matters' => 'Without a meta description, search engines pull arbitrary page text into snippets. A compelling description directly boosts organic click-through rates.',
                'recommendation' => 'Add a unique meta description between 120 and 160 characters summarizing the page value.',
                'technical_details' => '<meta name="description" content="Your 140-160 character summary with a clear call-to-action.">',
            ];
        } else {
            $descLength = mb_strlen($metaDesc);
            if ($descLength < 70 || $descLength > 165) {
                $penalties += 5;
                $issues[] = [
                    'rule_id' => 'seo_meta_desc_length',
                    'category' => 'seo',
                    'severity' => 'medium',
                    'confidence' => 'high',
                    'title' => "Meta Description Length Suboptimal ({$descLength} characters)",
                    'affected_resource' => $fetchData['final_url'],
                    'evidence' => ['description' => $metaDesc, 'length' => $descLength, 'recommended' => '120-160 characters'],
                    'why_it_matters' => $descLength > 165
                        ? 'Descriptions over 160 characters risk truncation on search snippet cards. Note: Meta description length affects snippet presentation, not search ranking.'
                        : 'Descriptions under 70 characters lack substance to persuade searchers to click.',
                    'recommendation' => 'Adjust the meta description to between 120 and 160 characters with a clear proposition.',
                    'technical_details' => 'Keep core benefit visible within the first 120 characters.',
                ];
            }
        }

        // 3. H1 Headings
        $h1Count = count($headings['h1']);
        if ($h1Count === 0) {
            $penalties += 15;
            $issues[] = [
                'rule_id' => 'seo_h1_missing',
                'category' => 'seo',
                'severity' => 'high',
                'confidence' => 'high',
                'title' => 'Missing Primary Heading (H1)',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['h1_count' => 0],
                'why_it_matters' => 'The H1 heading is the semantic cornerstone of page hierarchy for search engines and assistive technology.',
                'recommendation' => 'Add exactly one clear, topic-focused <h1> element representing the main page subject.',
                'technical_details' => 'Place the H1 inside the primary <main> content area: <h1>Primary Page Topic</h1>',
            ];
        } elseif ($h1Count > 1) {
            $penalties += 4;
            $issues[] = [
                'rule_id' => 'seo_h1_multiple',
                'category' => 'seo',
                'severity' => 'low',
                'confidence' => 'high',
                'title' => "Multiple H1 Headings Detected ({$h1Count} found)",
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['h1_count' => $h1Count, 'headings' => array_slice($headings['h1'], 0, 3)],
                'why_it_matters' => 'While HTML5 permits multiple H1 elements, best practice for clear topical hierarchy and screen readers is having a single H1 per document.',
                'recommendation' => 'Reserve <h1> for the main page title and demote secondary section headings to <h2>.',
                'technical_details' => 'Convert sub-headings to <h2> and <h3> according to logical outline structure.',
            ];
        }

        // 4. Canonical URL
        if (empty($canonical)) {
            $penalties += 10;
            $issues[] = [
                'rule_id' => 'seo_canonical_missing',
                'category' => 'seo',
                'severity' => 'medium',
                'confidence' => 'high',
                'title' => 'Missing Canonical URL Tag',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['canonical' => null],
                'why_it_matters' => 'Canonical tags instruct search engines on the authoritative URL version, preventing duplicate content dilution across URL parameters.',
                'recommendation' => 'Add a self-referencing <link rel="canonical" href="..."> pointing to the preferred URL format.',
                'technical_details' => '<link rel="canonical" href="' . htmlspecialchars($fetchData['final_url']) . '">',
            ];
        } else {
            $parsedCanonical = parse_url($canonical);
            $parsedFinal = parse_url($fetchData['final_url']);
            if (($parsedCanonical['host'] ?? '') !== ($parsedFinal['host'] ?? '')) {
                $penalties += 12;
                $issues[] = [
                    'rule_id' => 'seo_canonical_mismatch',
                    'category' => 'seo',
                    'severity' => 'high',
                    'confidence' => 'high',
                    'title' => 'Canonical Host Mismatch',
                    'affected_resource' => $fetchData['final_url'],
                    'evidence' => ['page_url' => $fetchData['final_url'], 'canonical' => $canonical],
                    'why_it_matters' => 'The canonical tag points to a different domain or subdomain, transferring indexing signals away from this URL.',
                    'recommendation' => 'Confirm whether this cross-domain canonical is intentional or update it to match the active domain.',
                    'technical_details' => 'Canonical host (' . ($parsedCanonical['host'] ?? '') . ') differs from current host (' . ($parsedFinal['host'] ?? '') . ').',
                ];
            }
        }

        // 5. Image Alt Coverage
        $totalImages = count($images);
        $missingAlt = 0;
        foreach ($images as $img) {
            if (!$img['has_alt'] || trim($img['alt']) === '') {
                $missingAlt++;
            }
        }
        $withAltCount = $totalImages - $missingAlt;
        $coveragePercent = $totalImages > 0 ? (int) round(($withAltCount / $totalImages) * 100) : 100;

        if ($missingAlt > 0) {
            $penalty = min(15, (int) ceil($missingAlt * 2));
            $penalties += $penalty;
            $issues[] = [
                'rule_id' => 'seo_images_missing_alt',
                'category' => 'seo',
                'severity' => $missingAlt > 5 ? 'high' : 'medium',
                'confidence' => 'high',
                'title' => "{$missingAlt} Image(s) Missing Alt Text ({$coveragePercent}% coverage)",
                'affected_resource' => $fetchData['final_url'],
                'evidence' => [
                    'total_images' => $totalImages,
                    'images_with_alt' => $withAltCount,
                    'missing_alt' => $missingAlt,
                    'coverage_percentage' => $coveragePercent . '%',
                ],
                'why_it_matters' => 'Missing alt text prevents search engines from indexing images for image search and deprives visually impaired users of image descriptions.',
                'recommendation' => 'Provide clear, concise alt descriptions for all informative images. Use alt="" only for purely decorative graphics.',
                'technical_details' => '<img src="/product.webp" alt="Detailed description of visual content">',
            ];
        }

        // 6. Robots Directives
        if (str_contains($robotsMeta, 'noindex')) {
            $penalties += 35;
            $issues[] = [
                'rule_id' => 'seo_robots_blocking',
                'category' => 'seo',
                'severity' => 'critical',
                'confidence' => 'high',
                'title' => 'Page Marked with "noindex" Directive',
                'affected_resource' => $fetchData['final_url'],
                'evidence' => ['meta_robots' => $robotsMeta],
                'why_it_matters' => 'Search engines will completely exclude or remove this page from organic search indexes.',
                'recommendation' => 'Remove the "noindex" directive from <meta name="robots"> if this page is intended to receive search traffic.',
                'technical_details' => 'Found: <meta name="robots" content="' . htmlspecialchars($robotsMeta) . '">',
            ];
        }

        // 7. Sitemap Discovery
        $sitemapData = $auxiliaryData['sitemap'] ?? ['found' => false, 'status' => 0];
        $sitemapFound = $sitemapData['found'] ?? false;
        if (!$sitemapFound) {
            $statusCode = $sitemapData['status'] ?? 0;
            $title = $statusCode === 403
                ? 'XML Sitemap Inaccessible (Server Returned HTTP 403 Forbidden)'
                : 'XML Sitemap Not Detected at /sitemap.xml';

            $penalties += 8;
            $issues[] = [
                'rule_id' => 'seo_sitemap_missing',
                'category' => 'seo',
                'severity' => 'medium',
                'confidence' => 'medium',
                'title' => $title,
                'affected_resource' => rtrim($fetchData['final_url'], '/') . '/sitemap.xml',
                'evidence' => ['checked_path' => '/sitemap.xml', 'http_status' => $statusCode, 'found' => false],
                'why_it_matters' => 'An XML sitemap helps search engine crawlers discover and index canonical URLs and detect content updates.',
                'recommendation' => 'Generate a valid XML sitemap and declare "Sitemap: https://yourdomain.com/sitemap.xml" in your robots.txt.',
                'technical_details' => 'Ensure /sitemap.xml is publicly accessible with text/xml or application/xml Content-Type.',
            ];
        }

        $score = max(10, 100 - $penalties);

        return [
            'score' => $score,
            'issues' => $issues,
            'summary' => [
                'title' => $title,
                'title_length' => $title ? mb_strlen($title) : 0,
                'meta_description' => $metaDesc,
                'meta_description_length' => $metaDesc ? mb_strlen($metaDesc) : 0,
                'canonical' => $canonical,
                'h1_count' => $h1Count,
                'h1_headings' => $headings['h1'],
                'h2_count' => count($headings['h2']),
                'total_images' => $totalImages,
                'images_with_alt' => $withAltCount,
                'images_missing_alt' => $missingAlt,
                'alt_coverage_percentage' => $coveragePercent,
                'total_links' => count($links),
                'sitemap_detected' => $sitemapFound,
                'html_lang' => $lang,
            ],
        ];
    }
}
