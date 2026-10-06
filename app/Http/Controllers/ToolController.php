<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolController extends Controller
{
    public function index(): View
    {
        $tools = self::getToolsList();
        return view('app', [
            'initialPage' => 'tools_index',
            'pageTitle' => 'Free Website Intelligence Tools | W3HealthChecker',
            'metaDescription' => 'Explore 18+ professional, free online audit tools for SEO, security headers, SSL certificates, accessibility, performance, and AI search readiness.',
            'toolsData' => $tools,
        ]);
    }

    public function show(string $slug): View
    {
        $tools = self::getToolsList();
        $tool = $tools[$slug] ?? null;

        if (!$tool) {
            abort(404);
        }

        return view('app', [
            'initialPage' => 'tool_detail',
            'toolSlug' => $slug,
            'toolData' => $tool,
            'pageTitle' => "{$tool['name']} — Free Online Audit Tool | W3HealthChecker",
            'metaDescription' => $tool['meta_description'],
            'toolsData' => $tools,
        ]);
    }

    public static function getToolsList(): array
    {
        return [
            'seo-checker' => [
                'name' => 'Free SEO Checker & On-Page Audit',
                'short_name' => 'SEO Checker',
                'category' => 'SEO',
                'headline' => 'Audit On-Page SEO, Titles, Meta Tags & Indexability in Seconds',
                'meta_description' => 'Test your website for missing title tags, meta descriptions, heading structure, canonical correctness, and indexing barriers.',
                'features' => [
                    'Title tag length and character count check',
                    'Meta description snippet optimization analysis',
                    'Heading hierarchy verification (H1, H2, H3)',
                    'Canonical tag consistency check',
                    'Alt attribute audit for image search readiness',
                ],
                'why_it_matters' => 'Search engines favor cleanly structured, indexable pages with clear topical hierarchy. A single missing title or improper canonical can drop organic visibility.',
                'related_tools' => ['title-tag-checker', 'meta-description-checker', 'canonical-checker', 'heading-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'What does this SEO checker test?',
                        'a' => 'It evaluates technical on-page signals: title tag length, meta description quality, H1 hierarchy, canonical consistency, robots noindex flags, and image alt text coverage.',
                    ],
                    [
                        'q' => 'Does this guarantee a higher Google ranking?',
                        'a' => 'No tool can guarantee Google rankings. This checker identifies objective technical barriers and search hygiene issues that can impede search engine crawling and indexing.',
                    ],
                ],
            ],
            'meta-tag-checker' => [
                'name' => 'Meta Tag & Snippet Preview Checker',
                'short_name' => 'Meta Tag Checker',
                'category' => 'SEO',
                'headline' => 'Audit HTML Meta Tags, Titles, Descriptions & Search Snippets',
                'meta_description' => 'Inspect title tags and meta descriptions to optimize SERP snippet presentation and avoid search engine truncation.',
                'features' => [
                    'Title length validation (30–60 characters)',
                    'Meta description coverage and length evaluation',
                    'SERP snippet rendering preview',
                    'Duplicate and empty meta tag detection',
                ],
                'why_it_matters' => 'Compelling meta descriptions and well-crafted titles directly drive organic search click-through rates by providing clear user value on search result cards.',
                'related_tools' => ['title-tag-checker', 'meta-description-checker', 'open-graph-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'Why are meta tags important?',
                        'a' => 'Meta tags communicate the subject of your page to search engines and format your search result snippets on Google, Bing, and social platforms.',
                    ],
                ],
            ],
            'title-tag-checker' => [
                'name' => 'Title Tag Optimization Checker',
                'short_name' => 'Title Tag Checker',
                'category' => 'SEO',
                'headline' => 'Validate Title Tag Length, Keywords & Truncation Risks',
                'meta_description' => 'Test your HTML <title> tag for pixel and character length limits to ensure clear search snippet headlines.',
                'features' => [
                    'Character count target analysis (30–60 characters)',
                    'Snippet truncation warning detection',
                    'Missing and duplicate title checks',
                ],
                'why_it_matters' => 'The title tag is the primary headline displayed on search engine result pages and browser tabs. Truncated titles cut off important value propositions.',
                'related_tools' => ['meta-description-checker', 'meta-tag-checker', 'seo-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'What is the ideal title tag length?',
                        'a' => 'Between 30 and 60 characters (~580 pixels). Titles under 30 lack detail, while titles over 60 are truncated with an ellipsis (...) by search engines.',
                    ],
                ],
            ],
            'meta-description-checker' => [
                'name' => 'Meta Description Checker & SERP Snippet Preview',
                'short_name' => 'Meta Description',
                'category' => 'SEO',
                'headline' => 'Optimize Meta Description Length and Organic Click-Through Rates',
                'meta_description' => 'Verify your page meta description length (120–160 characters) and preview how your search snippet appears to prospective visitors.',
                'features' => [
                    'Target character length check (120–160 characters)',
                    'Missing meta description detection',
                    'Click-through rate optimization recommendations',
                ],
                'why_it_matters' => 'A persuasive meta description acts as organic ad copy in search results, directly increasing the proportion of searchers who click your link.',
                'related_tools' => ['title-tag-checker', 'meta-tag-checker', 'seo-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'Does meta description length affect ranking directly?',
                        'a' => 'No. Google does not use meta description text as a direct ranking factor, but it directly impacts click-through rate, which drives traffic.',
                    ],
                ],
            ],
            'heading-checker' => [
                'name' => 'Heading Hierarchy & H1 Outline Checker',
                'short_name' => 'Heading Checker',
                'category' => 'SEO',
                'headline' => 'Audit H1, H2 & H3 Semantic Document Outline',
                'meta_description' => 'Analyze heading outlines to ensure clear topical hierarchy for search crawlers and screen readers.',
                'features' => [
                    'Primary <h1> presence and uniqueness check',
                    'Logical section outline verification',
                    'Heading count and content extraction',
                ],
                'why_it_matters' => 'A clean heading outline allows search bots, AI systems, and assistive technology to understand topical page structure.',
                'related_tools' => ['seo-checker', 'accessibility-checker', 'ai-search-readiness-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'Can a page have more than one H1 heading?',
                        'a' => 'HTML5 technically permits multiple H1s, but best practice for SEO and screen-reader accessibility is to have a single primary H1 for the page topic.',
                    ],
                ],
            ],
            'canonical-checker' => [
                'name' => 'Canonical Tag & Duplicate Content Checker',
                'short_name' => 'Canonical Checker',
                'category' => 'SEO',
                'headline' => 'Verify Self-Referencing Canonical Tags & Prevent Content Dilution',
                'meta_description' => 'Inspect canonical link tags to prevent duplicate content issues, protocol mismatches, and URL parameter splitting.',
                'features' => [
                    'rel="canonical" presence validation',
                    'Absolute vs relative URL structure check',
                    'Host and protocol consistency audit',
                ],
                'why_it_matters' => 'Without canonical tags, search engines may index duplicate variations of URLs (HTTP/HTTPS, with/without trailing slash, tracking parameters), dividing ranking equity.',
                'related_tools' => ['redirect-checker', 'seo-checker', 'xml-sitemap-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'What is a canonical tag?',
                        'a' => 'A <link rel="canonical" href="..."> element tells search engines which version of a page is the master authoritative copy.',
                    ],
                ],
            ],
            'robots-txt-checker' => [
                'name' => 'Robots.txt Directive & Crawlability Checker',
                'short_name' => 'Robots.txt Checker',
                'category' => 'Technical',
                'headline' => 'Verify Search Engine Crawl Directives & Prevent Accidental De-indexing',
                'meta_description' => 'Test whether your robots.txt file is publicly accessible and verify Disallow rules are not blocking Googlebot from critical content.',
                'features' => [
                    'robots.txt public availability check',
                    'Disallow directive syntax analysis',
                    'XML sitemap reference detection',
                    'AI crawler bot directive inspection',
                ],
                'why_it_matters' => 'An erroneous "Disallow: /" rule can inadvertently erase your entire website from Google and major search engines in hours.',
                'related_tools' => ['xml-sitemap-checker', 'redirect-checker', 'seo-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'Does robots.txt stop pages from being indexed?',
                        'a' => 'No. Robots.txt prevents crawling. If external websites link to a blocked page, Google may still index the URL without snippet content. Use "noindex" to prevent indexing.',
                    ],
                ],
            ],
            'xml-sitemap-checker' => [
                'name' => 'XML Sitemap & Discovery Validator',
                'short_name' => 'XML Sitemap',
                'category' => 'Technical',
                'headline' => 'Verify Search Engine Crawl Directives & XML Sitemap Health',
                'meta_description' => 'Ensure Googlebot and search crawlers can discover your pages without encountering crawl traps or unintended Disallow rules.',
                'features' => [
                    'Automatic /sitemap.xml location detection',
                    'robots.txt sitemap reference verification',
                    'HTTP status code response validation',
                ],
                'why_it_matters' => 'If search bots cannot find your XML sitemap, new and updated pages may experience prolonged delays before being indexed.',
                'related_tools' => ['robots-txt-checker', 'canonical-checker', 'seo-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'Where should an XML sitemap be placed?',
                        'a' => 'The standard location is at the root: https://yourdomain.com/sitemap.xml, declared in robots.txt.',
                    ],
                ],
            ],
            'broken-link-checker' => [
                'name' => 'Broken Link & Dead URL Detector',
                'short_name' => 'Broken Link Checker',
                'category' => 'Technical',
                'headline' => 'Detect Missing Anchor Text, Malformed Links & 404 Targets',
                'meta_description' => 'Scan HTML documents for empty anchor tags, missing href attributes, and link hygiene issues that degrade crawl efficiency.',
                'features' => [
                    'Empty link text detection',
                    'Relative and absolute link format check',
                    'Total links count and internal navigation structure',
                ],
                'why_it_matters' => 'Dead links frustrate visitors, increase bounce rates, and break the flow of PageRank across your domain.',
                'related_tools' => ['redirect-checker', 'seo-checker', 'canonical-checker'],
                'related_guide' => 'seo',
                'faq' => [
                    [
                        'q' => 'Why does link text matter for accessibility?',
                        'a' => 'Screen reader users often browse pages by listening to a list of links. Links without text or labeled "click here" offer zero context.',
                    ],
                ],
            ],
            'redirect-checker' => [
                'name' => 'Redirect Chain & HTTP Status Code Checker',
                'short_name' => 'Redirect Checker',
                'category' => 'Technical',
                'headline' => 'Trace HTTP 301, 302 & Multi-Hop Redirect Chains',
                'meta_description' => 'Audit redirect hop counts and ensure pages resolve in a single hop to conserve search engine crawl budget.',
                'features' => [
                    'Hop-by-hop redirect path tracing',
                    'HTTP status code identification (301 vs 302)',
                    'Multi-hop latency impact analysis',
                ],
                'why_it_matters' => 'Each redirect hop introduces an extra network round trip, delaying page load by 100–300ms and wasting crawl budget.',
                'related_tools' => ['canonical-checker', 'website-speed-checker', 'ssl-checker'],
                'related_guide' => 'performance',
                'faq' => [
                    [
                        'q' => 'What is the difference between 301 and 302 redirects?',
                        'a' => 'A 301 is a permanent redirect that passes link equity; a 302 is temporary and indicates the original URL may return.',
                    ],
                ],
            ],
            'security-headers-checker' => [
                'name' => 'HTTP Security Headers & Protection Audit',
                'short_name' => 'Security Headers',
                'category' => 'Security',
                'headline' => 'Inspect HSTS, CSP, X-Frame-Options & Modern Defense Headers',
                'meta_description' => 'Verify your website protection against Clickjacking, XSS, and MIME-sniffing with our automated defensive security header analyzer.',
                'features' => [
                    'Strict-Transport-Security (HSTS) validation',
                    'Content-Security-Policy (CSP) evaluation',
                    'X-Frame-Options clickjacking defense inspection',
                    'X-Content-Type-Options: nosniff verification',
                    'Referrer-Policy & Permissions-Policy audit',
                ],
                'why_it_matters' => 'Public web applications are constantly probed. Modern security headers add browser-enforced isolation layers against injection attacks.',
                'related_tools' => ['ssl-checker', 'http-header-checker'],
                'related_guide' => 'security',
                'faq' => [
                    [
                        'q' => 'What are HTTP security headers?',
                        'a' => 'They are response directives sent by your web server instructing the visitor browser to activate built-in defenses like blocking framing or enforcing HTTPS.',
                    ],
                ],
            ],
            'ssl-checker' => [
                'name' => 'SSL/TLS Certificate & HTTPS Health Checker',
                'short_name' => 'SSL Checker',
                'category' => 'Security',
                'headline' => 'Verify Certificate Validity, Expiry & Encryption Protocols',
                'meta_description' => 'Inspect SSL/TLS handshake status, certificate validity, expiration dates, and HTTPS redirection enforcement.',
                'features' => [
                    'Certificate validity and days until expiry',
                    'Certificate Authority (CA) issuer details',
                    'HTTPS enforcement and redirect check',
                ],
                'why_it_matters' => 'Expired or misconfigured certificates cause full-page browser security blocks that immediately repel prospective customers.',
                'related_tools' => ['security-headers-checker', 'redirect-checker', 'http-header-checker'],
                'related_guide' => 'security',
                'faq' => [
                    [
                        'q' => 'How often should SSL certificates be renewed?',
                        'a' => 'Modern TLS certificates have a maximum validity of 398 days, with automated solutions like Let\'s Encrypt renewing every 60–90 days.',
                    ],
                ],
            ],
            'http-header-checker' => [
                'name' => 'HTTP Response Header & Server Fingerprint Checker',
                'short_name' => 'HTTP Headers',
                'category' => 'Security',
                'headline' => 'Inspect Server Response Headers & Uncover Technology Leaks',
                'meta_description' => 'Analyze raw server response headers, caching directives, compression encoding, and version disclosures (Server, X-Powered-By).',
                'features' => [
                    'Server software version disclosure check',
                    'Content-Encoding (Gzip/Brotli) inspection',
                    'Cache-Control and Expires header validation',
                ],
                'why_it_matters' => 'Disclosing exact server and runtime versions (e.g. Apache/2.4.41, PHP/7.4) assists automated reconnaissance bots in targeting known vulnerabilities.',
                'related_tools' => ['security-headers-checker', 'website-speed-checker', 'ssl-checker'],
                'related_guide' => 'security',
                'faq' => [
                    [
                        'q' => 'Why should I hide server version tokens?',
                        'a' => 'Hiding technology versions follows the principle of defense-in-depth, making automated scanning and targeted exploitation harder.',
                    ],
                ],
            ],
            'schema-markup-checker' => [
                'name' => 'Schema Markup & Structured Data Validator',
                'short_name' => 'Schema Checker',
                'category' => 'Structured Data',
                'headline' => 'Validate Schema.org JSON-LD Entities & Rich Result Eligibility',
                'meta_description' => 'Test your website for Schema.org JSON-LD structured data (Organization, WebSite, Article) to unlock Google rich snippet cards.',
                'features' => [
                    'JSON-LD script block extraction',
                    'Schema.org type recognition and entity mapping',
                    'JSON syntax error and malformed schema detection',
                ],
                'why_it_matters' => 'Structured data disambiguates business entities for search engines and generative AI agents, earning rich SERP badges and sitelinks.',
                'related_tools' => ['open-graph-checker', 'ai-search-readiness-checker', 'seo-checker'],
                'related_guide' => 'structured-data',
                'faq' => [
                    [
                        'q' => 'What format is recommended for structured data?',
                        'a' => 'Google explicitly recommends JSON-LD embedded within a <script type="application/ld+json"> tag in the HTML head or body.',
                    ],
                ],
            ],
            'open-graph-checker' => [
                'name' => 'Open Graph & Social Share Preview Tool',
                'short_name' => 'Open Graph Checker',
                'category' => 'Structured Data',
                'headline' => 'Preview Social Sharing Cards for Facebook, LinkedIn & Twitter/X',
                'meta_description' => 'Inspect og:title, og:description, and og:image tags to ensure your links appear polished when shared across social platforms.',
                'features' => [
                    'Open Graph (og:title, og:description, og:image) extraction',
                    'Twitter Card format verification (summary_large_image)',
                    'Live social share card preview',
                ],
                'why_it_matters' => 'When shared on Slack, WhatsApp, or Twitter, links with missing Open Graph tags appear as plain text, drastically lowering click engagement.',
                'related_tools' => ['schema-markup-checker', 'meta-tag-checker', 'title-tag-checker'],
                'related_guide' => 'structured-data',
                'faq' => [
                    [
                        'q' => 'What is the recommended Open Graph image size?',
                        'a' => '1200 x 630 pixels (1.91:1 aspect ratio) ensures sharp display on high-DPI displays across Facebook, LinkedIn, and Twitter.',
                    ],
                ],
            ],
            'accessibility-checker' => [
                'name' => 'Automated Accessibility (A11y) Checker',
                'short_name' => 'Accessibility Checker',
                'category' => 'Accessibility',
                'headline' => 'Audit Form Labels, Button Names, Image Alt & ARIA Indicators',
                'meta_description' => 'Scan HTML for common accessibility barriers including missing lang attributes, unlabelled form inputs, and duplicate IDs.',
                'features' => [
                    'HTML lang attribute verification',
                    'Accessible button names and link anchor text audit',
                    'Form control label pairing check',
                    'Duplicate ID attribute detection',
                    'Iframe title attribute inspection',
                ],
                'why_it_matters' => 'Accessible websites provide equal access to users of all abilities and avoid legal compliance liabilities.',
                'related_tools' => ['mobile-readiness-checker', 'heading-checker', 'seo-checker'],
                'related_guide' => 'accessibility',
                'faq' => [
                    [
                        'q' => 'Does this test replace a manual WCAG audit?',
                        'a' => 'No. Automated tools detect common technical syntax issues. Comprehensive compliance requires manual assistive testing with screen readers.',
                    ],
                ],
            ],
            'mobile-readiness-checker' => [
                'name' => 'Mobile Readiness & Responsive Viewport Checker',
                'short_name' => 'Mobile Readiness',
                'category' => 'Mobile',
                'headline' => 'Audit Viewport Meta Tags, Scaling & Touch Targets',
                'meta_description' => 'Test whether your website adapts cleanly to smartphone screens without awkward horizontal scrolling or inaccessible zoom restrictions.',
                'features' => [
                    'Mobile viewport meta tag presence check',
                    'width=device-width & initial-scale=1 verification',
                    'Pinch-to-zoom accessibility audit',
                    'Responsive layout indicators',
                ],
                'why_it_matters' => 'Over 60% of web traffic originates from mobile devices. Google enforces mobile-first indexing for all sites.',
                'related_tools' => ['accessibility-checker', 'website-speed-checker', 'seo-checker'],
                'related_guide' => 'accessibility',
                'faq' => [
                    [
                        'q' => 'What is the correct responsive viewport meta tag?',
                        'a' => '<meta name="viewport" content="width=device-width, initial-scale=1.0"> is the standard configuration for all modern responsive websites.',
                    ],
                ],
            ],
            'website-speed-checker' => [
                'name' => 'Website Speed & Server Latency Checker',
                'short_name' => 'Speed Checker',
                'category' => 'Performance',
                'headline' => 'Measure Server TTFB, Compression, DOM Weight & Script Overhead',
                'meta_description' => 'Analyze your initial server latency, Gzip/Brotli compression, and render-blocking script volume with transparent lab diagnostics.',
                'features' => [
                    'Time-To-First-Byte (TTFB) server latency measurement',
                    'Gzip / Brotli text compression detection',
                    'Document byte weight analysis',
                    'External JavaScript and CSS bundle count audit',
                    'Resource hints (preconnect, preload) detection',
                ],
                'why_it_matters' => 'Every 100ms of latency impacts bounce rates and conversion. Fast initial HTML delivery is foundational to great user experience.',
                'related_tools' => ['http-header-checker', 'redirect-checker', 'mobile-readiness-checker'],
                'related_guide' => 'performance',
                'faq' => [
                    [
                        'q' => 'Does this tool measure Core Web Vitals directly?',
                        'a' => 'No. Passive server scans measure initial response latency (TTFB) and document hygiene. Real browser Core Web Vitals (LCP, INP, CLS) require field user telemetry.',
                    ],
                ],
            ],
            'ai-search-readiness-checker' => [
                'name' => 'AI & Generative Search Readiness Analyzer',
                'short_name' => 'AI Search Readiness',
                'category' => 'AI Readiness',
                'headline' => 'Analyze Machine Discoverability, Schema Depth & llms.txt Signals',
                'meta_description' => 'Check how well LLMs, generative search engines, and citation crawlers can parse and disambiguate your website entities.',
                'features' => [
                    'Schema.org structured data depth analysis',
                    'Semantic markdown and HTML document structure verification',
                    '/llms.txt discovery and syntax check',
                    'AI bot crawlability directives in robots.txt',
                ],
                'why_it_matters' => 'As search shifts to AI overviews and answer engines, clean structured data and unambiguous entity definitions ensure your brand is cited accurately.',
                'related_tools' => ['schema-markup-checker', 'heading-checker', 'robots-txt-checker'],
                'related_guide' => 'ai-search',
                'faq' => [
                    [
                        'q' => 'Is llms.txt a Google ranking factor?',
                        'a' => 'No. llms.txt is an emerging convention designed for LLMs and AI assistants. It is not currently an official Google ranking requirement.',
                    ],
                ],
            ],
        ];
    }
}
