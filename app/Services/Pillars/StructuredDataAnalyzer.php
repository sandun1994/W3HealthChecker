<?php

namespace App\Services\Pillars;

use App\Services\Scanner\HtmlDocument;

class StructuredDataAnalyzer
{
    public function analyze(HtmlDocument $doc): array
    {
        $jsonLd = $doc->getStructuredDataJsonLd();
        $schemas = [];
        $hasErrors = false;

        foreach ($jsonLd as $item) {
            if ($item['valid']) {
                $type = $item['type'] ?? 'Unknown';
                $schemas[] = [
                    'type' => $type,
                    'valid' => true,
                    'data' => $item['data'],
                ];
            } else {
                $hasErrors = true;
                $schemas[] = [
                    'type' => 'Invalid JSON-LD',
                    'valid' => false,
                    'error' => $item['error'],
                    'raw' => $item['raw'],
                ];
            }
        }

        return [
            'total_schemas' => count($schemas),
            'valid_count' => count(array_filter($schemas, fn($s) => $s['valid'])),
            'has_syntax_errors' => $hasErrors,
            'schemas' => $schemas,
        ];
    }
}
