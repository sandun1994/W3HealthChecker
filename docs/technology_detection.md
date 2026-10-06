# Technology Detection Engine

## 1. Ethical Transparency
We state clearly in UI and API output:
> *"Detected signals represent passive heuristic matches, not guaranteed vendor configurations."*

W3HealthChecker never claims to possess proprietary server-level insight or infallible fingerprinting. We analyze publicly accessible HTTP headers, HTML generator tags, script signatures, and DOM attributes.

## 2. Detected Categories
- **Web Servers**: Nginx, Apache HTTP Server, LiteSpeed, Caddy (via `Server` header patterns).
- **CDNs & Proxies**: Cloudflare, Fastly, Amazon CloudFront (via `cf-ray`, `x-amz-cf-id`, `x-fastly-request-id`).
- **Content Management Systems (CMS)**: WordPress, Shopify, Ghost (via meta generator tags, theme patterns, `wp-content` references).
- **JavaScript & Frontend Frameworks**: Next.js, React, Nuxt.js, Vue.js (via script paths, `__next`, `data-reactroot`, `data-v-`).
- **CSS Frameworks**: Tailwind CSS, Bootstrap (via compiled class attribute heuristics).
- **Analytics & Tag Managers**: Google Tag Manager / Analytics, Plausible Analytics, PostHog, Hotjar.
