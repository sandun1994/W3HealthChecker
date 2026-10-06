<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'W3HealthChecker — Know Your Website. Improve Everything.' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Complete website intelligence SaaS platform. Real-time audits across SEO, performance, security, accessibility, and AI search readiness.' }}">
    
    <!-- Search Engine Indexing Controls -->
    @if(isset($initialPage) && $initialPage === 'report')
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    @endif

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ isset($guideData) ? 'article' : 'website' }}">
    <meta property="og:url" content="{{ strtok(url()->current(), '?') }}">
    <meta property="og:title" content="{{ $pageTitle ?? 'W3HealthChecker — Know Your Website. Improve Everything.' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Complete website intelligence SaaS platform.' }}">
    <meta property="og:site_name" content="W3HealthChecker">
    <meta property="og:image" content="{{ asset('og-preview.png') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle ?? 'W3HealthChecker' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Website intelligence and SEO diagnostics.' }}">
    <meta name="twitter:image" content="{{ asset('og-preview.png') }}">

    <!-- Canonical URL (Query-stripped for clean indexing) -->
    <link rel="canonical" href="{{ strtok(url()->current(), '?') }}">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236366f1'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "@id": "{{ url('/') }}#organization",
          "name": "W3HealthChecker",
          "url": "{{ url('/') }}",
          "logo": "{{ url('/') }}/logo.svg",
          "description": "Website Intelligence and Technical Health Auditing Platform"
        },
        {
          "@type": "WebSite",
          "@id": "{{ url('/') }}#website",
          "url": "{{ url('/') }}",
          "name": "W3HealthChecker",
          "publisher": {
            "@id": "{{ url('/') }}#organization"
          }
        },
        {
          "@type": "SoftwareApplication",
          "name": "{{ isset($toolData) ? ($toolData['name'] ?? $toolData['title'] ?? 'Diagnostic Tool') . ' — W3HealthChecker' : 'W3HealthChecker Website Scanner' }}",
          "operatingSystem": "All",
          "applicationCategory": "DeveloperApplication",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
          }
        }
        @if(isset($initialPage) && $initialPage === 'tool_detail' && isset($toolData))
        ,
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Home",
              "item": "{{ url('/') }}"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Tools",
              "item": "{{ url('/tools') }}"
            },
            {
              "@type": "ListItem",
              "position": 3,
              "name": "{{ $toolData['name'] ?? $toolData['title'] ?? 'Diagnostic Tool' }}",
              "item": "{{ strtok(url()->current(), '?') }}"
            }
          ]
        }
        @if(!empty($toolData['faq']))
        ,
        {
          "@type": "FAQPage",
          "mainEntity": [
            @foreach($toolData['faq'] as $index => $item)
            {
              "@type": "Question",
              "name": {{ json_encode($item['q']) }},
              "acceptedAnswer": {
                "@type": "Answer",
                "text": {{ json_encode($item['a']) }}
              }
            }{{ $loop->last ? '' : ',' }}
            @endforeach
          ]
        }
        @endif
        @elseif(isset($initialPage) && $initialPage === 'guide_detail' && isset($guideData))
        ,
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Home",
              "item": "{{ url('/') }}"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Guides",
              "item": "{{ url('/guides') }}"
            },
            {
              "@type": "ListItem",
              "position": 3,
              "name": "{{ $guideData['short_title'] ?? $guideData['title'] }}",
              "item": "{{ strtok(url()->current(), '?') }}"
            }
          ]
        },
        {
          "@type": "TechArticle",
          "headline": "{{ $guideData['title'] }}",
          "description": "{{ $guideData['meta_description'] }}",
          "articleSection": "{{ $guideData['category'] }}",
          "publisher": {
            "@id": "{{ url('/') }}#organization"
          },
          "author": {
            "@type": "Organization",
            "name": "W3HealthChecker Research Team"
          }
        }
        @elseif(isset($initialPage) && $initialPage === 'guides_index')
        ,
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Home",
              "item": "{{ url('/') }}"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Guides",
              "item": "{{ url('/guides') }}"
            }
          ]
        }
        @elseif(isset($initialPage) && $initialPage === 'tools_index')
        ,
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Home",
              "item": "{{ url('/') }}"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Specialized Tools",
              "item": "{{ url('/tools') }}"
            }
          ]
        }
        @endif
      ]
    }
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <script>
        window.__W3_INITIAL_DATA__ = {
            initialPage: @json($initialPage ?? 'home'),
            reportDomain: @json($reportDomain ?? null),
            reportPublicId: @json($reportPublicId ?? null),
            toolSlug: @json($toolSlug ?? null),
            toolData: @json($toolData ?? null),
            toolsData: @json($toolsData ?? null),
            guideSlug: @json($guideSlug ?? null),
            guideData: @json($guideData ?? null),
            guidesData: @json($guidesData ?? null),
            csrfToken: '{{ csrf_token() }}',
            baseUrl: '{{ url('/') }}'
        };
    </script>
</head>
<body class="bg-slate-950 text-slate-100 antialiased min-h-screen flex flex-col font-sans">
    <div id="root" class="flex-grow flex flex-col"></div>
</body>
</html>
