<?php

namespace App\Services\Scanner;

use DOMDocument;
use DOMXPath;
use DOMElement;

class HtmlDocument
{
    protected DOMDocument $dom;
    protected DOMXPath $xpath;
    protected string $rawHtml;

    public function __construct(string $html)
    {
        $this->rawHtml = $html;
        $this->dom = new DOMDocument();

        // Suppress HTML5 parse warnings
        $internalErrors = libxml_use_internal_errors(true);
        if (!empty($html)) {
            // Encode HTML for multibyte compatibility
            $this->dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        }
        libxml_use_internal_errors($internalErrors);

        $this->xpath = new DOMXPath($this->dom);
    }

    public function getTitle(): ?string
    {
        $nodes = $this->xpath->query('//title');
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return null;
    }

    public function getMetaTag(string $name): ?string
    {
        $nodes = $this->xpath->query("//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = '{$name}']/@content");
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }
        return null;
    }

    public function getMetaProperty(string $property): ?string
    {
        $nodes = $this->xpath->query("//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = '{$property}']/@content");
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }
        return null;
    }

    public function getCanonical(): ?string
    {
        $nodes = $this->xpath->query("//link[translate(@rel, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'canonical']/@href");
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }
        return null;
    }

    public function getHtmlLang(): ?string
    {
        $nodes = $this->xpath->query('//html/@lang');
        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }
        return null;
    }

    public function getHeadings(): array
    {
        $headings = [
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
        ];

        for ($i = 1; $i <= 6; $i++) {
            $nodes = $this->xpath->query("//h{$i}");
            if ($nodes) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if ($text !== '') {
                        $headings["h{$i}"][] = $text;
                    }
                }
            }
        }

        return $headings;
    }

    public function getImages(): array
    {
        $images = [];
        $nodes = $this->xpath->query('//img');
        if ($nodes) {
            foreach ($nodes as $img) {
                if ($img instanceof DOMElement) {
                    $src = $img->getAttribute('src');
                    $hasAlt = $img->hasAttribute('alt');
                    $alt = $img->getAttribute('alt');
                    $loading = $img->getAttribute('loading');
                    $images[] = [
                        'src' => $src,
                        'has_alt' => $hasAlt,
                        'alt' => $alt,
                        'loading' => $loading,
                    ];
                }
            }
        }
        return $images;
    }

    public function getLinks(): array
    {
        $links = [];
        $nodes = $this->xpath->query('//a');
        if ($nodes) {
            foreach ($nodes as $a) {
                if ($a instanceof DOMElement) {
                    $href = $a->getAttribute('href');
                    $rel = $a->getAttribute('rel');
                    $text = trim($a->textContent);
                    $ariaLabel = $a->getAttribute('aria-label');
                    $links[] = [
                        'href' => $href,
                        'rel' => $rel,
                        'text' => $text,
                        'aria_label' => $ariaLabel,
                    ];
                }
            }
        }
        return $links;
    }

    public function getScripts(): array
    {
        $scripts = [];
        $nodes = $this->xpath->query('//script');
        if ($nodes) {
            foreach ($nodes as $s) {
                if ($s instanceof DOMElement) {
                    $src = $s->getAttribute('src');
                    $type = $s->getAttribute('type');
                    $async = $s->hasAttribute('async');
                    $defer = $s->hasAttribute('defer');
                    $scripts[] = [
                        'src' => $src,
                        'type' => $type,
                        'async' => $async,
                        'defer' => $defer,
                        'inline' => empty($src),
                    ];
                }
            }
        }
        return $scripts;
    }

    public function getStylesheets(): array
    {
        $styles = [];
        $nodes = $this->xpath->query("//link[translate(@rel, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'stylesheet']");
        if ($nodes) {
            foreach ($nodes as $link) {
                if ($link instanceof DOMElement) {
                    $styles[] = [
                        'href' => $link->getAttribute('href'),
                    ];
                }
            }
        }
        return $styles;
    }

    public function getButtons(): array
    {
        $buttons = [];
        $nodes = $this->xpath->query('//button');
        if ($nodes) {
            foreach ($nodes as $btn) {
                if ($btn instanceof DOMElement) {
                    $buttons[] = [
                        'text' => trim($btn->textContent),
                        'aria_label' => $btn->getAttribute('aria-label'),
                        'aria_labelledby' => $btn->getAttribute('aria-labelledby'),
                    ];
                }
            }
        }
        return $buttons;
    }

    public function getStructuredDataJsonLd(): array
    {
        $schemas = [];
        $nodes = $this->xpath->query("//script[@type='application/ld+json']");
        if ($nodes) {
            foreach ($nodes as $node) {
                $rawJson = trim($node->textContent);
                $decoded = json_decode($rawJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $schemas[] = [
                        'valid' => true,
                        'data' => $decoded,
                        'type' => $decoded['@type'] ?? 'Unknown',
                    ];
                } else {
                    $schemas[] = [
                        'valid' => false,
                        'error' => json_last_error_msg(),
                        'raw' => substr($rawJson, 0, 500),
                    ];
                }
            }
        }
        return $schemas;
    }

    public function getFormInputs(): array
    {
        $inputs = [];
        $nodes = $this->xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="reset")] | //textarea | //select');
        if ($nodes) {
            foreach ($nodes as $input) {
                if ($input instanceof DOMElement) {
                    $id = $input->getAttribute('id');
                    $name = $input->getAttribute('name');
                    $ariaLabel = $input->getAttribute('aria-label');
                    $ariaLabelledBy = $input->getAttribute('aria-labelledby');

                    // Check for matching <label for="id">
                    $hasExplicitLabel = false;
                    if (!empty($id)) {
                        $labels = $this->xpath->query("//label[@for='{$id}']");
                        if ($labels && $labels->length > 0) {
                            $hasExplicitLabel = true;
                        }
                    }

                    // Check for wrapping <label> parent
                    $parent = $input->parentNode;
                    $hasWrappingLabel = false;
                    while ($parent && $parent instanceof DOMElement) {
                        if (strtolower($parent->tagName) === 'label') {
                            $hasWrappingLabel = true;
                            break;
                        }
                        $parent = $parent->parentNode;
                    }

                    $hasLabel = $hasExplicitLabel || $hasWrappingLabel || !empty($ariaLabel) || !empty($ariaLabelledBy);

                    $inputs[] = [
                        'tag' => $input->tagName,
                        'id' => $id,
                        'name' => $name,
                        'has_label' => $hasLabel,
                    ];
                }
            }
        }
        return $inputs;
    }

    public function getIframes(): array
    {
        $iframes = [];
        $nodes = $this->xpath->query('//iframe');
        if ($nodes) {
            foreach ($nodes as $iframe) {
                if ($iframe instanceof DOMElement) {
                    $src = $iframe->getAttribute('src');
                    $title = $iframe->getAttribute('title');
                    $ariaLabel = $iframe->getAttribute('aria-label');
                    $hasTitle = !empty(trim($title)) || !empty(trim($ariaLabel));

                    $iframes[] = [
                        'src' => $src,
                        'title' => $title,
                        'has_title' => $hasTitle,
                    ];
                }
            }
        }
        return $iframes;
    }

    public function getResourceHints(): array
    {
        $hints = [
            'preconnect' => [],
            'preload' => [],
            'dns_prefetch' => [],
        ];

        $nodes = $this->xpath->query('//link[@rel]');
        if ($nodes) {
            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $rel = strtolower($node->getAttribute('rel'));
                    $href = $node->getAttribute('href');
                    if (str_contains($rel, 'preconnect')) {
                        $hints['preconnect'][] = $href;
                    }
                    if (str_contains($rel, 'preload')) {
                        $hints['preload'][] = $href;
                    }
                    if (str_contains($rel, 'dns-prefetch')) {
                        $hints['dns_prefetch'][] = $href;
                    }
                }
            }
        }

        return $hints;
    }

    public function getDuplicateIds(): array
    {
        $idCounts = [];
        $duplicates = [];
        $nodes = $this->xpath->query('//*[@id]');
        if ($nodes) {
            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $id = trim($node->getAttribute('id'));
                    if ($id !== '') {
                        $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
                    }
                }
            }
        }

        foreach ($idCounts as $id => $count) {
            if ($count > 1) {
                $duplicates[$id] = $count;
            }
        }

        return $duplicates;
    }

    public function getRawHtml(): string
    {
        return $this->rawHtml;
    }
}
