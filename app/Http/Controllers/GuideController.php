<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        $guides = self::getGuidesList();

        return view('app', [
            'initialPage' => 'guides_index',
            'pageTitle' => 'Website Health & Optimization Guides | W3HealthChecker',
            'metaDescription' => 'In-depth engineering and technical SEO guides covering on-page optimization, defensive security headers, web speed, accessibility, structured data, and AI search readiness.',
            'guidesData' => $guides,
            'toolsData' => ToolController::getToolsList(),
        ]);
    }

    public function show(string $slug): View
    {
        $guides = self::getGuidesList();
        $guide = $guides[$slug] ?? null;

        if (!$guide) {
            abort(404);
        }

        return view('app', [
            'initialPage' => 'guide_detail',
            'guideSlug' => $slug,
            'guideData' => $guide,
            'pageTitle' => "{$guide['title']} | W3HealthChecker Guides",
            'metaDescription' => $guide['meta_description'],
            'guidesData' => $guides,
            'toolsData' => ToolController::getToolsList(),
        ]);
    }

    public static function getGuidesList(): array
    {
        return [
            'seo' => [
                'slug' => 'seo',
                'category' => 'SEO',
                'title' => 'The Complete Technical & On-Page SEO Guide',
                'short_title' => 'Technical & On-Page SEO',
                'read_time' => '10 min read',
                'meta_description' => 'Master modern technical SEO: title tag optimization, meta description click-through rate, canonical URL management, and crawl budget hygiene.',
                'summary' => 'Search engines prioritize technically sound websites with unambiguous hierarchy, indexable content, and zero duplicate content loops. Learn how to engineer top-tier search discoverability.',
                'sections' => [
                    [
                        'heading' => '1. Title Tags: The Anchor of Search Snippets',
                        'content' => 'The HTML `<title>` tag is the single most critical on-page element for search engines and social platforms. Google typically renders titles up to ~60 characters (approximately 580–600 pixels) on desktop and mobile SERPs before truncation. Keep titles between 30 and 60 characters, front-load primary value keywords, and append your brand name.',
                        'code' => "<head>\n  <title>Primary Value Proposition | Brand Name</title>\n</head>",
                    ],
                    [
                        'heading' => '2. Meta Descriptions & Organic CTR',
                        'content' => 'While meta descriptions do not directly influence search ranking algorithms, they act as primary advertising copy on SERPs. Aim for 120–160 characters. A well-crafted description with an actionable benefit and clear call-to-action significantly elevates organic click-through rates (CTR).',
                        'code' => '<meta name="description" content="Audit your website health across SEO, security, speed, and AI readiness with free instant diagnostics. Get prioritized fixes today.">',
                    ],
                    [
                        'heading' => '3. Canonical URLs: Eliminating Duplicate Content',
                        'content' => 'Duplicate content dilutes page authority across multiple URLs. Use self-referencing canonical links with absolute URLs on all pages. Ensure canonicals consistently reflect the correct protocol (https://) and domain format (with or without www).',
                        'code' => '<link rel="canonical" href="https://example.com/authoritative-path">',
                    ],
                    [
                        'heading' => '4. Semantic Headings Hierarchy',
                        'content' => 'Every document should have exactly one <h1> representing the main page topic. Subsections should follow logical descending hierarchy: <h2> for major sections, followed by <h3> and <h4>. Never skip heading levels for styling purposes.',
                    ],
                ],
                'related_tools' => ['seo-checker', 'title-tag-checker', 'meta-tag-checker', 'canonical-checker', 'heading-checker'],
            ],
            'performance' => [
                'slug' => 'performance',
                'category' => 'Performance',
                'title' => 'Web Performance & Server Latency Optimization Guide',
                'short_title' => 'Web Speed & Server TTFB',
                'read_time' => '8 min read',
                'meta_description' => 'Understand server TTFB, Gzip/Brotli compression, DOM weight, and asset hygiene to accelerate initial webpage delivery.',
                'summary' => 'Initial HTML response latency forms the foundation of all downstream rendering metrics. Learn how optimizing Time-To-First-Byte and document hygiene improves conversions.',
                'sections' => [
                    [
                        'heading' => '1. Time-To-First-Byte (TTFB) Explained',
                        'content' => 'TTFB measures the duration from when the browser sends the HTTP request until it receives the first byte of response data from the server. Target a TTFB under 600ms (ideally < 300ms). TTFB delays are caused by slow database queries, lack of server-side caching (OPcache, Redis), or geographically distant servers.',
                    ],
                    [
                        'heading' => '2. Modern Text Compression: Gzip vs Brotli',
                        'content' => 'Enabling text compression typically reduces HTML and asset sizes by 70–85%. Brotli (`br`) offers superior compression density compared to traditional Gzip (`gzip`). Ensure your web server automatically compresses text/html, application/json, and text/css.',
                        'code' => "# Nginx configuration\ngzip on;\ngzip_types text/plain text/css application/json application/javascript text/xml application/xml;\nbrotli on;\nbrotli_types text/plain text/css application/json application/javascript text/xml application/xml;",
                    ],
                    [
                        'heading' => '3. Resource Hints: preconnect & preload',
                        'content' => 'Use `<link rel="preconnect">` to initiate early DNS, TCP, and TLS handshakes to critical third-party domains (such as Google Fonts or CDNs). Use `<link rel="preload">` sparingly for above-the-fold critical assets.',
                        'code' => '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n" . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
                    ],
                ],
                'related_tools' => ['website-speed-checker', 'seo-checker'],
            ],
            'security' => [
                'slug' => 'security',
                'category' => 'Security',
                'title' => 'Defensive Web Security Headers & SSL Hardening Guide',
                'short_title' => 'Defensive Security & SSL',
                'read_time' => '12 min read',
                'meta_description' => 'Implement modern browser security headers: HSTS, Content-Security-Policy, X-Frame-Options, and X-Content-Type-Options to stop XSS and clickjacking.',
                'summary' => 'Passive defensive security headers instruct modern web browsers to enforce strict isolation, preventing Cross-Site Scripting, Clickjacking, and protocol downgrade attacks.',
                'sections' => [
                    [
                        'heading' => '1. HTTP Strict Transport Security (HSTS)',
                        'content' => 'HSTS forces modern browsers to communicate with your server exclusively over encrypted HTTPS, eliminating SSL-stripping attacks. Configure a minimum duration of one year (31,536,000 seconds) with `includeSubDomains`.',
                        'code' => 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload',
                    ],
                    [
                        'heading' => '2. Content-Security-Policy (CSP)',
                        'content' => 'CSP is the most robust browser mitigation against Cross-Site Scripting (XSS). It defines an explicit allowlist of authorized script sources, style sources, and image endpoints.',
                        'code' => "Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-xyz'; style-src 'self' 'unsafe-inline'; object-src 'none';",
                    ],
                    [
                        'heading' => '3. Clickjacking Defense: X-Frame-Options',
                        'content' => 'Clickjacking attacks embed your application within a hidden iframe on a malicious website to trick users into executing unintended actions. Use `X-Frame-Options: SAMEORIGIN` or CSP `frame-ancestors`.',
                        'code' => 'X-Frame-Options: SAMEORIGIN',
                    ],
                    [
                        'heading' => '4. MIME-Sniffing Defense: X-Content-Type-Options',
                        'content' => 'Instructs browsers to strictly respect declared Content-Type headers rather than sniffing content, preventing user-uploaded media from being executed as JavaScript.',
                        'code' => 'X-Content-Type-Options: nosniff',
                    ],
                ],
                'related_tools' => ['security-headers-checker', 'ssl-checker', 'http-header-checker'],
            ],
            'accessibility' => [
                'slug' => 'accessibility',
                'category' => 'Accessibility',
                'title' => 'Technical Web Accessibility & WCAG 2.2 Guidelines',
                'short_title' => 'Web Accessibility (WCAG)',
                'read_time' => '9 min read',
                'meta_description' => 'Learn how HTML lang attributes, accessible button names, form label pairing, and unique IDs enable inclusive digital experiences.',
                'summary' => 'Building accessible web interfaces ensures users with visual, auditory, and motor impairments can navigate seamlessly, while boosting search crawler comprehension.',
                'sections' => [
                    [
                        'heading' => '1. Document Language Declaration',
                        'content' => 'Screen readers rely on the root `<html lang="...">` attribute to load appropriate pronunciation engines and character sets. Missing language declarations cause assistive technology to mispronounce page content.',
                        'code' => '<html lang="en">',
                    ],
                    [
                        'heading' => '2. Form Controls & Explicit Label Pairing',
                        'content' => 'Every form `<input>`, `<select>`, and `<textarea>` must have an associated `<label>` element matching its `id`, or an `aria-label` attribute. Placeholder text alone does not satisfy accessibility standards.',
                        'code' => "<label for=\"user-email\">Email Address</label>\n<input type=\"email\" id=\"user-email\" name=\"email\" required>",
                    ],
                    [
                        'heading' => '3. Discernible Button Names & Anchor Text',
                        'content' => 'Buttons with only SVG or icon graphics must provide an `aria-label` or visually hidden text. Generic links like "click here" or "read more" should be replaced with descriptive destination names.',
                        'code' => '<button aria-label="Close modal dialog"><svg ...></svg></button>',
                    ],
                ],
                'related_tools' => ['accessibility-checker', 'mobile-readiness-checker'],
            ],
            'structured-data' => [
                'slug' => 'structured-data',
                'category' => 'Structured Data',
                'title' => 'Schema.org JSON-LD & Search Knowledge Graph Guide',
                'short_title' => 'Structured Data & Schema',
                'read_time' => '11 min read',
                'meta_description' => 'Implement Schema.org JSON-LD structured data: Organization, WebSite, Article, and FAQPage to qualify for rich Google SERP results.',
                'summary' => 'Structured data provides machine-readable context to search engines and AI assistants, transforming plain search results into eye-catching rich snippets with star ratings, FAQs, and sitelinks.',
                'sections' => [
                    [
                        'heading' => '1. Why Schema.org Matters',
                        'content' => 'Search crawlers read structured JSON-LD to understand real-world entities (people, products, organizations, recipes) without heuristic guessing. Correctly formatted schemas qualify your pages for rich search result cards.',
                    ],
                    [
                        'heading' => '2. Core Schemas: Organization & WebSite',
                        'content' => 'Every digital brand should declare top-level Organization and WebSite schemas on its homepage to establish brand ownership and sitelinks search boxes.',
                        'code' => "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"Organization\",\n  \"name\": \"Your Brand\",\n  \"url\": \"https://yourdomain.com\",\n  \"logo\": \"https://yourdomain.com/logo.png\"\n}",
                    ],
                    [
                        'heading' => '3. FAQPage Schema for Expanded SERP Real Estate',
                        'content' => 'Adding FAQPage markup to informative pages allows Google to display question and answer accordions directly within search snippets.',
                    ],
                ],
                'related_tools' => ['schema-markup-checker', 'open-graph-checker', 'ai-search-readiness-checker'],
            ],
            'ai-search' => [
                'slug' => 'ai-search',
                'category' => 'AI Readiness',
                'title' => 'AI Search Readiness & Entity Retrieval Optimization',
                'short_title' => 'AI Search Readiness & LLMs',
                'read_time' => '10 min read',
                'meta_description' => 'Prepare your website for AI overviews, ChatGPT Search, Perplexity, and citation crawlers with clean markdown chunking, schema depth, and llms.txt.',
                'summary' => 'As search behavior expands into generative answer engines, websites must ensure content is structurally clear, semantically chunked, and unambiguously attributable.',
                'sections' => [
                    [
                        'heading' => '1. How Generative Search Works',
                        'content' => 'Large Language Models (LLMs) and retrieval-augmented generation (RAG) engines crawl the web, chunk documents by heading boundaries (H1 -> H2 -> H3), embed paragraphs into vector indexes, and cite authoritative sources.',
                    ],
                    [
                        'heading' => '2. What is /llms.txt?',
                        'content' => '`llms.txt` is an emerging community convention (similar to robots.txt) where websites provide a concise markdown file summarizing their core concepts, APIs, and key pages for AI agents. Note: It is an emerging standard, not an official Google ranking factor.',
                        'code' => "# llms.txt example\n# Title: Brand Documentation\n> Mission: High-performance cloud hosting\n\n## Core Resources\n- [API Guide](https://example.com/api): REST endpoints\n- [Pricing](https://example.com/pricing): Tier breakdown",
                    ],
                    [
                        'heading' => '3. AI Bot Directives in robots.txt',
                        'content' => 'Understand major AI crawler user-agents (`GPTBot`, `ClaudeBot`, `PerplexityBot`, `Google-Extended`). You can selectively permit citation crawlers while restricting generic content training scrapers in your robots.txt.',
                    ],
                ],
                'related_tools' => ['ai-search-readiness-checker', 'schema-markup-checker', 'seo-checker'],
            ],
        ];
    }
}
