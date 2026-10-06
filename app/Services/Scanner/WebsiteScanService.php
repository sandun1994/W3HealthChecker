<?php

namespace App\Services\Scanner;

use App\Models\Scan;
use App\Models\ScanIssue;
use App\Models\ScanMetric;
use App\Models\Website;
use App\Services\Pillars\AccessibilityAnalyzer;
use App\Services\Pillars\AiReadinessAnalyzer;
use App\Services\Pillars\MobileAnalyzer;
use App\Services\Pillars\PerformanceAnalyzer;
use App\Services\Pillars\SecurityAnalyzer;
use App\Services\Pillars\SeoAnalyzer;
use App\Services\Pillars\SocialAnalyzer;
use App\Services\Pillars\StructuredDataAnalyzer;
use App\Services\Pillars\TechnicalAnalyzer;
use App\Services\Scoring\RecommendationService;
use App\Services\Scoring\ScoreService;
use App\Services\Security\UrlValidationService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebsiteScanService
{
    public function __construct(
        protected UrlValidationService $urlValidator,
        protected HttpFetchService $fetchService,
        protected SeoAnalyzer $seoAnalyzer,
        protected SecurityAnalyzer $securityAnalyzer,
        protected PerformanceAnalyzer $performanceAnalyzer,
        protected AccessibilityAnalyzer $accessibilityAnalyzer,
        protected MobileAnalyzer $mobileAnalyzer,
        protected TechnicalAnalyzer $technicalAnalyzer,
        protected AiReadinessAnalyzer $aiAnalyzer,
        protected StructuredDataAnalyzer $structuredDataAnalyzer,
        protected SocialAnalyzer $socialAnalyzer,
        protected ScoreService $scoreService,
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Initialize a new scan record.
     */
    public function initiateScan(string $rawUrl, ?int $userId = null): Scan
    {
        // 1. SSRF & URL Validation
        $validated = $this->urlValidator->validateAndNormalize($rawUrl);

        // 2. Find or create Website record
        $website = Website::firstOrCreate(
            ['domain' => $validated['domain']],
            [
                'user_id' => $userId,
                'scheme' => $validated['scheme'],
                'canonical_url' => $validated['normalized_url'],
            ]
        );

        // 3. Create Scan instance
        $scan = Scan::create([
            'website_id' => $website->id,
            'user_id' => $userId,
            'public_id' => 'w3_' . Str::random(12),
            'target_url' => $validated['normalized_url'],
            'status' => 'queued',
            'status_stage' => 'Website reachable',
            'progress_percentage' => 5,
            'started_at' => now(),
        ]);

        return $scan;
    }

    /**
     * Execute full scan pipeline synchronously or within a queue worker.
     */
    public function processScan(Scan $scan): Scan
    {
        try {
            Log::info('Website scan initiated', [
                'scan_id' => $scan->id,
                'public_id' => $scan->public_id,
                'target_url' => $scan->target_url,
            ]);

            $scan->update([
                'status' => 'processing',
                'status_stage' => 'Website reachable',
                'progress_percentage' => 15,
            ]);

            // 1. HTTP Fetch
            $fetchData = $this->fetchService->fetchPage($scan->target_url);
            $finalUrl = $fetchData['final_url'];
            $html = $fetchData['html'];

            $scan->update([
                'final_url' => $finalUrl,
                'http_status_code' => $fetchData['http_status'],
                'response_time_ms' => $fetchData['response_time_ms'],
                'status_stage' => 'Technical analysis',
                'progress_percentage' => 30,
            ]);

            // 2. Auxiliary Discovery (robots.txt, sitemap.xml, llms.txt, sensitive paths)
            $parsed = parse_url($finalUrl);
            $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

            $robots = $this->fetchService->fetchAuxiliary($origin, '/robots.txt');
            $sitemap = $this->fetchService->fetchAuxiliary($origin, '/sitemap.xml');
            $llmsTxt = $this->fetchService->fetchAuxiliary($origin, '/llms.txt');
            $sensitive = $this->fetchService->checkSensitiveExposure($origin);

            $auxiliaryData = [
                'robots' => $robots,
                'sitemap' => $sitemap,
                'llms_txt' => $llmsTxt,
                'sensitive_exposures' => $sensitive,
            ];

            // 3. Parse HTML DOM
            $doc = new HtmlDocument($html);

            // 4. Run Technical Analyzer
            $techResult = $this->technicalAnalyzer->analyze($fetchData);

            // 5. Run SEO Analyzer
            $scan->update([
                'status_stage' => 'SEO analysis',
                'progress_percentage' => 45,
            ]);
            $seoResult = $this->seoAnalyzer->analyze($doc, $fetchData, $auxiliaryData);

            // 6. Run Security Analyzer
            $scan->update([
                'status_stage' => 'Security analysis',
                'progress_percentage' => 60,
            ]);
            $securityResult = $this->securityAnalyzer->analyze($fetchData, $auxiliaryData);

            // 7. Run Performance Analyzer
            $scan->update([
                'status_stage' => 'Performance analysis',
                'progress_percentage' => 75,
            ]);
            $perfResult = $this->performanceAnalyzer->analyze($doc, $fetchData);

            // 8. Run Accessibility & Mobile Analyzers
            $scan->update([
                'status_stage' => 'Accessibility analysis',
                'progress_percentage' => 85,
            ]);
            $a11yResult = $this->accessibilityAnalyzer->analyze($doc, $fetchData);
            $mobileResult = $this->mobileAnalyzer->analyze($doc, $fetchData);

            // 9. Run AI / Search Readiness Analyzer
            $scan->update([
                'status_stage' => 'AI/Search readiness',
                'progress_percentage' => 92,
            ]);
            $aiResult = $this->aiAnalyzer->analyze($doc, $fetchData, $auxiliaryData);

            // 10. Structured Data & Social
            $structuredResult = $this->structuredDataAnalyzer->analyze($doc);
            $socialResult = $this->socialAnalyzer->analyze($doc);

            // 11. Consolidate and Rank Issues
            $scan->update([
                'status_stage' => 'Generating recommendations',
                'progress_percentage' => 97,
            ]);

            $rawIssues = array_merge(
                $seoResult['issues'] ?? [],
                $techResult['issues'] ?? [],
                $securityResult['issues'] ?? [],
                $perfResult['issues'] ?? [],
                $a11yResult['issues'] ?? [],
                $mobileResult['issues'] ?? [],
                $aiResult['issues'] ?? []
            );

            $prioritizedIssues = $this->recommendationService->prioritizeIssues($rawIssues);

            // Persist Issues
            foreach ($prioritizedIssues as $issueData) {
                \App\Models\AuditRule::firstOrCreate(
                    ['id' => $issueData['rule_id']],
                    [
                        'category' => $issueData['category'],
                        'title' => $issueData['title'],
                        'severity' => $issueData['severity'],
                        'default_weight' => 1.0,
                        'impact_description' => $issueData['title'],
                        'why_it_matters' => $issueData['why_it_matters'],
                        'fix_guidance' => $issueData['recommendation'],
                        'technical_fix_guidance' => $issueData['technical_details'] ?? null,
                        'is_active' => true,
                    ]
                );

                ScanIssue::create([
                    'scan_id' => $scan->id,
                    'rule_id' => $issueData['rule_id'],
                    'category' => $issueData['category'],
                    'severity' => $issueData['severity'],
                    'confidence' => $issueData['confidence'] ?? 'high',
                    'title' => $issueData['title'],
                    'affected_resource' => $issueData['affected_resource'] ?? $finalUrl,
                    'evidence' => $issueData['evidence'] ?? null,
                    'why_it_matters' => $issueData['why_it_matters'],
                    'recommendation' => $issueData['recommendation'],
                    'technical_details' => $issueData['technical_details'] ?? null,
                    'priority_order' => $issueData['priority_order'] ?? 0,
                ]);
            }

            // Persist Diagnostic Metrics
            ScanMetric::create([
                'scan_id' => $scan->id,
                'headers' => $fetchData['headers'],
                'ssl_data' => $fetchData['ssl'],
                'dns_records' => $fetchData['dns'] ?? null,
                'open_graph' => $socialResult['open_graph'],
                'twitter_card' => $socialResult['twitter'],
                'structured_data' => $structuredResult['schemas'],
                'meta_summary' => [
                    'title' => $doc->getTitle(),
                    'meta_description' => $doc->getMetaTag('description'),
                    'canonical' => $doc->getCanonical(),
                    'robots' => $doc->getMetaTag('robots'),
                    'html_size' => strlen($fetchData['body'] ?? ''),
                    'content_encoding' => $fetchData['headers']['content-encoding'] ?? null,
                    'http_status' => $fetchData['status'] ?? 200,
                    'ttfb_ms' => $fetchData['ttfb_ms'] ?? 0,
                    'lang' => $doc->getHtmlLang(),
                ],
                'headings_summary' => $doc->getHeadings(),
                'links_summary' => [
                    'total' => count($doc->getLinks()),
                ],
                'assets_summary' => [
                    'scripts_count' => count($doc->getScripts()),
                    'stylesheets_count' => count($doc->getStylesheets()),
                    'images_count' => count($doc->getImages()),
                    'technologies' => app(\App\Services\Intelligence\TechnologyDetector::class)->detect(
                        $fetchData['headers'] ?? [],
                        $html,
                        $scan->target_url
                    )['technologies'] ?? [],
                    'resources' => app(\App\Services\Intelligence\ResourceAnalyzer::class)->analyze(
                        $html,
                        $scan->target_url
                    ),
                    'content_quality' => app(\App\Services\Intelligence\ContentQualityAnalyzer::class)->analyze(
                        $html,
                        $discoveryData['robots_txt'] ?? null
                    ),
                ],
            ]);

            // 12. Calculate Category Scores & Overall Score
            $categoryScores = [
                'seo' => $seoResult['score'],
                'performance' => $perfResult['score'],
                'security' => $securityResult['score'],
                'accessibility' => $a11yResult['score'],
                'mobile' => $mobileResult['score'],
                'technical' => $techResult['score'],
                'ai_readiness' => $aiResult['score'],
            ];

            $overallScore = $this->scoreService->calculateOverallScore($categoryScores);
            $statusLabel = $this->scoreService->getStatusLabel($overallScore);

            $scan->update([
                'status' => 'completed',
                'status_stage' => 'Completed',
                'progress_percentage' => 100,
                'overall_score' => $overallScore,
                'status_label' => $statusLabel,
                'score_seo' => $categoryScores['seo'],
                'score_performance' => $categoryScores['performance'],
                'score_security' => $categoryScores['security'],
                'score_accessibility' => $categoryScores['accessibility'],
                'score_mobile' => $categoryScores['mobile'],
                'score_technical' => $categoryScores['technical'],
                'score_ai_readiness' => $categoryScores['ai_readiness'],
                'completed_at' => now(),
            ]);

            // Update website last scanned timestamp
            $scan->website->update(['last_scanned_at' => now()]);

            Log::info('Website scan completed successfully', [
                'scan_id' => $scan->id,
                'public_id' => $scan->public_id,
                'domain' => $scan->website->domain,
                'overall_score' => $overallScore,
                'duration_ms' => $scan->response_time_ms,
            ]);

            return $scan->fresh(['issues', 'metric', 'website']);
        } catch (Exception $e) {
            Log::warning('Website scan failed', [
                'scan_id' => $scan->id,
                'public_id' => $scan->public_id,
                'target_url' => $scan->target_url,
                'error' => $e->getMessage(),
            ]);

            $scan->update([
                'status' => 'failed',
                'status_stage' => 'Failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return $scan;
        }
    }
}
