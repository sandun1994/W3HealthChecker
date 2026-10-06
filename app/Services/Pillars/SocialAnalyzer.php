<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class SocialAnalyzer
{
    public function analyze(HtmlDocument $doc): array
    {
        $og = [
            'title' => $doc->getMetaProperty('og:title') ?? $doc->getMetaTag('og:title'),
            'description' => $doc->getMetaProperty('og:description') ?? $doc->getMetaTag('og:description'),
            'image' => $doc->getMetaProperty('og:image') ?? $doc->getMetaTag('og:image'),
            'url' => $doc->getMetaProperty('og:url') ?? $doc->getMetaTag('og:url'),
            'type' => $doc->getMetaProperty('og:type') ?? $doc->getMetaTag('og:type'),
            'site_name' => $doc->getMetaProperty('og:site_name') ?? $doc->getMetaTag('og:site_name'),
        ];

        $twitter = [
            'card' => $doc->getMetaTag('twitter:card'),
            'title' => $doc->getMetaTag('twitter:title'),
            'description' => $doc->getMetaTag('twitter:description'),
            'image' => $doc->getMetaTag('twitter:image'),
            'site' => $doc->getMetaTag('twitter:site'),
        ];

        $hasOpenGraph = !empty($og['title']) && !empty($og['image']);
        $hasTwitterCard = !empty($twitter['card']);

        return [
            'has_open_graph' => $hasOpenGraph,
            'has_twitter_card' => $hasTwitterCard,
            'open_graph' => $og,
            'twitter' => $twitter,
            'social_preview' => [
                'title' => $og['title'] ?: ($twitter['title'] ?: $doc->getTitle()),
                'description' => $og['description'] ?: ($twitter['description'] ?: $doc->getMetaTag('description')),
                'image' => $og['image'] ?: $twitter['image'],
            ],
        ];
    }
}
