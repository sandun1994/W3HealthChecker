<?php

namespace App\Services\Intelligence;

use DOMDocument;
use DOMXPath;

class ResourceAnalyzer
{
    /**
     * Parse HTML and extract an inventory of internal and third-party assets.
     */
    public function analyze(string $html, string $pageUrl): array
    {
        $baseHost = parse_url($pageUrl, PHP_URL_HOST) ?? '';
        $baseHost = strtolower(preg_replace('/^www\./', '', $baseHost));

        $scripts = [];
        $stylesheets = [];
        $images = [];
        $fonts = [];
        $thirdPartyDomains = [];

        if (empty(trim($html))) {
            return $this->formatResult([], [], [], [], [], $baseHost);
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        // 1. Scripts
        foreach ($xpath->query('//script') as $script) {
            $src = $script->getAttribute('src');
            $isAsync = $script->hasAttribute('async');
            $isDefer = $script->hasAttribute('defer');

            if ($src) {
                $host = $this->extractHost($src, $baseHost);
                $isThirdParty = $this->isThirdParty($host, $baseHost);
                $scripts[] = [
                    'src' => $src,
                    'is_third_party' => $isThirdParty,
                    'is_async' => $isAsync,
                    'is_defer' => $isDefer,
                ];
                if ($isThirdParty && $host) {
                    $thirdPartyDomains[$host] = ($thirdPartyDomains[$host] ?? 0) + 1;
                }
            } else {
                $scripts[] = [
                    'src' => '[inline script]',
                    'is_third_party' => false,
                    'is_async' => false,
                    'is_defer' => false,
                ];
            }
        }

        // 2. Stylesheets
        foreach ($xpath->query('//link[@rel="stylesheet"]') as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $host = $this->extractHost($href, $baseHost);
                $isThirdParty = $this->isThirdParty($host, $baseHost);
                $stylesheets[] = [
                    'href' => $href,
                    'is_third_party' => $isThirdParty,
                ];
                if ($isThirdParty && $host) {
                    $thirdPartyDomains[$host] = ($thirdPartyDomains[$host] ?? 0) + 1;
                }
            }
        }

        // 3. Images
        foreach ($xpath->query('//img') as $img) {
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
            $loading = $img->getAttribute('loading');
            $hasAlt = $img->hasAttribute('alt') && trim($img->getAttribute('alt')) !== '';

            if ($src) {
                $host = $this->extractHost($src, $baseHost);
                $isThirdParty = $this->isThirdParty($host, $baseHost);
                $images[] = [
                    'src' => substr($src, 0, 120),
                    'is_third_party' => $isThirdParty,
                    'loading' => $loading ?: 'eager',
                    'has_alt' => $hasAlt,
                ];
                if ($isThirdParty && $host) {
                    $thirdPartyDomains[$host] = ($thirdPartyDomains[$host] ?? 0) + 1;
                }
            }
        }

        // 4. Web Fonts
        foreach ($xpath->query('//link[contains(@href, "font") or contains(@href, "type")]') as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $host = $this->extractHost($href, $baseHost);
                $fonts[] = ['href' => $href, 'host' => $host];
                if ($this->isThirdParty($host, $baseHost) && $host) {
                    $thirdPartyDomains[$host] = ($thirdPartyDomains[$host] ?? 0) + 1;
                }
            }
        }

        libxml_clear_errors();

        return $this->formatResult($scripts, $stylesheets, $images, $fonts, $thirdPartyDomains, $baseHost);
    }

    protected function formatResult(array $scripts, array $stylesheets, array $images, array $fonts, array $thirdParties, string $baseHost): array
    {
        return [
            'summary' => [
                'total_scripts' => count($scripts),
                'total_stylesheets' => count($stylesheets),
                'total_images' => count($images),
                'total_fonts' => count($fonts),
                'third_party_domains_count' => count($thirdParties),
            ],
            'third_party_domains' => array_keys($thirdParties),
            'scripts' => array_slice($scripts, 0, 20),
            'stylesheets' => array_slice($stylesheets, 0, 20),
            'images_sample' => array_slice($images, 0, 10),
        ];
    }

    protected function extractHost(string $url, string $defaultHost): string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return $defaultHost;
        }

        return strtolower(preg_replace('/^www\./', '', $host));
    }

    protected function isThirdParty(string $resourceHost, string $pageHost): bool
    {
        if (empty($resourceHost) || empty($pageHost)) {
            return false;
        }

        return !($resourceHost === $pageHost || str_ends_with($resourceHost, '.' . $pageHost));
    }
}
