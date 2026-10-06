<?php

namespace App\Services\Intelligence;

class TechnologyDetector
{
    /**
     * Detect technology signals from headers, HTML, and script tags.
     * Output is explicitly labeled as detected signals rather than guaranteed stack.
     */
    public function detect(array $headers, string $html, string $url): array
    {
        $detected = [];

        // 1. Web Servers & Hosting
        $serverHeader = strtolower($headers['server'] ?? '');
        if (str_contains($serverHeader, 'nginx')) {
            $detected[] = ['name' => 'Nginx', 'category' => 'Web Server', 'confidence' => 'high'];
        } elseif (str_contains($serverHeader, 'apache')) {
            $detected[] = ['name' => 'Apache HTTP Server', 'category' => 'Web Server', 'confidence' => 'high'];
        } elseif (str_contains($serverHeader, 'caddy')) {
            $detected[] = ['name' => 'Caddy', 'category' => 'Web Server', 'confidence' => 'high'];
        } elseif (str_contains($serverHeader, 'litespeed')) {
            $detected[] = ['name' => 'LiteSpeed', 'category' => 'Web Server', 'confidence' => 'high'];
        }

        // 2. CDNs & Reverse Proxies
        if (isset($headers['cf-ray']) || isset($headers['cf-cache-status']) || str_contains($serverHeader, 'cloudflare')) {
            $detected[] = ['name' => 'Cloudflare', 'category' => 'CDN / Proxy', 'confidence' => 'high'];
        }
        if (isset($headers['x-amz-cf-id']) || isset($headers['x-amz-cf-pop'])) {
            $detected[] = ['name' => 'Amazon CloudFront', 'category' => 'CDN', 'confidence' => 'high'];
        }
        if (isset($headers['x-fastly-request-id'])) {
            $detected[] = ['name' => 'Fastly', 'category' => 'CDN', 'confidence' => 'high'];
        }

        $htmlLower = strtolower($html);

        // 3. CMS & Application Platforms
        $xPoweredBy = strtolower($headers['x-powered-by'] ?? '');
        if (str_contains($xPoweredBy, 'php')) {
            $detected[] = ['name' => 'PHP', 'category' => 'Programming Language', 'confidence' => 'high'];
        }

        if (str_contains($htmlLower, 'wp-content') || str_contains($htmlLower, 'wp-includes') || str_contains($htmlLower, 'content="wordpress')) {
            $detected[] = ['name' => 'WordPress', 'category' => 'CMS', 'confidence' => 'high'];
        } elseif (str_contains($htmlLower, 'cdn.shopify.com') || str_contains($htmlLower, 'shopify.theme')) {
            $detected[] = ['name' => 'Shopify', 'category' => 'Ecommerce CMS', 'confidence' => 'high'];
        } elseif (str_contains($htmlLower, 'ghost-')) {
            $detected[] = ['name' => 'Ghost', 'category' => 'CMS', 'confidence' => 'medium'];
        }

        // 4. JavaScript Frameworks & UI Libraries
        if (str_contains($htmlLower, '__next') || str_contains($htmlLower, '/_next/static')) {
            $detected[] = ['name' => 'Next.js', 'category' => 'JavaScript Framework', 'confidence' => 'high'];
            $detected[] = ['name' => 'React', 'category' => 'UI Library', 'confidence' => 'high'];
        } elseif (str_contains($htmlLower, '__nuxt') || str_contains($htmlLower, '/_nuxt/')) {
            $detected[] = ['name' => 'Nuxt.js', 'category' => 'JavaScript Framework', 'confidence' => 'high'];
            $detected[] = ['name' => 'Vue.js', 'category' => 'UI Framework', 'confidence' => 'high'];
        } elseif (str_contains($htmlLower, 'data-reactroot') || str_contains($htmlLower, 'react-dom')) {
            $detected[] = ['name' => 'React', 'category' => 'UI Library', 'confidence' => 'medium'];
        } elseif (str_contains($htmlLower, 'data-v-') || str_contains($htmlLower, 'vue.runtime')) {
            $detected[] = ['name' => 'Vue.js', 'category' => 'UI Framework', 'confidence' => 'medium'];
        }

        // CSS Frameworks
        if (str_contains($htmlLower, 'tailwind') || preg_match('/class="[^"]*(?:flex|grid|hidden|p-\d|m-\d|text-\w+-\d{2,3})[^"]*"/i', $html)) {
            $detected[] = ['name' => 'Tailwind CSS', 'category' => 'CSS Framework', 'confidence' => 'medium'];
        } elseif (str_contains($htmlLower, 'bootstrap.min.css') || str_contains($htmlLower, 'class="container-fluid')) {
            $detected[] = ['name' => 'Bootstrap', 'category' => 'CSS Framework', 'confidence' => 'high'];
        }

        // 5. Analytics & Tag Managers
        if (str_contains($htmlLower, 'googletagmanager.com') || str_contains($htmlLower, 'gtag(')) {
            $detected[] = ['name' => 'Google Analytics / Tag Manager', 'category' => 'Analytics', 'confidence' => 'high'];
        }
        if (str_contains($htmlLower, 'plausible.io')) {
            $detected[] = ['name' => 'Plausible Analytics', 'category' => 'Privacy Analytics', 'confidence' => 'high'];
        }
        if (str_contains($htmlLower, 'posthog.com')) {
            $detected[] = ['name' => 'PostHog', 'category' => 'Product Analytics', 'confidence' => 'high'];
        }
        if (str_contains($htmlLower, 'hotjar.com')) {
            $detected[] = ['name' => 'Hotjar', 'category' => 'Heatmap Analytics', 'confidence' => 'high'];
        }

        // Deduplicate detected signals by name
        $unique = [];
        foreach ($detected as $item) {
            $unique[$item['name']] = $item;
        }

        return [
            'total_detected' => count($unique),
            'disclaimer' => 'Detected signals represent passive heuristic matches, not guaranteed vendor configurations.',
            'technologies' => array_values($unique),
        ];
    }
}
