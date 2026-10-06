<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = rtrim(url('/'), '/');
        $tools = ToolController::getToolsList();
        $guides = GuideController::getGuidesList();
        $today = date('Y-m-d');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // 1. Core Platform Pages
        $corePages = [
            ['loc' => "{$baseUrl}", 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => "{$baseUrl}/tools", 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => "{$baseUrl}/compare", 'changefreq' => 'weekly', 'priority' => '0.85'],
            ['loc' => "{$baseUrl}/guides", 'changefreq' => 'weekly', 'priority' => '0.85'],
            ['loc' => "{$baseUrl}/about", 'changefreq' => 'monthly', 'priority' => '0.7'],
        ];

        foreach ($corePages as $page) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$page['loc']}</loc>\n";
            $xml .= "    <lastmod>{$today}</lastmod>\n";
            $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$page['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        // 2. Individual Specialized Tool Pages
        foreach ($tools as $slug => $tool) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/tools/{$slug}</loc>\n";
            $xml .= "    <lastmod>{$today}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.85</priority>\n";
            $xml .= "  </url>\n";
        }

        // 3. Educational Technical Guides
        foreach ($guides as $slug => $guide) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$baseUrl}/guides/{$slug}</loc>\n";
            $xml .= "    <lastmod>{$today}</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
