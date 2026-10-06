# Controlled Site Crawling Architecture

## 1. Principles & Defensive Safeguards
W3HealthChecker implements a **strictly bounded, controlled site crawler**:
- **Not a Wild Web Spider**: Crawling is limited strictly to internal links belonging to the verified root target domain.
- **Strict Depth & Page Limits**:
  - Free: Maximum 3 pages, Depth 1.
  - Pro / Agency: Maximum 10–15 pages, Depth 2.
- **SSRF Defenses on Every Hop**: Every discovered URL passes through `UrlValidationService` to guarantee no internal loopbacks (`127.0.0.1`), LAN subnets (`10.0.0.0/8`, `192.168.0.0/16`), cloud metadata endpoints (`169.254.169.254`), or private hostnames can be reached.
- **Resource Constraints**: Crawling does not follow external redirects, media binary files, or query loops.

## 2. Intelligence Extracted
1. **Crawl Map**: Generates directed parent-to-child crawl linkages (`from_url` -> `to_url`).
2. **Internal Link Graphs**: Counts inlinks per page to discover well-linked hubs vs isolated endpoints.
3. **Orphan Candidates**: Flags internal pages with 1 or fewer incoming links.
4. **Broken Internal Links**: Identifies dead 404 links or connection failures during navigation.
