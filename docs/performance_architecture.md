# Performance Architecture

## 1. Principles
- **Measure First, Optimize Second**: We profile memory, TTFB, and database latency before caching.
- **Eager Loading by Default**: All report and workspace controllers utilize eager loading (`with(['latestScan', 'project', 'monitoredWebsite'])`) to eliminate N+1 query overhead.
- **Indexed Columns**: Primary foreign keys, status filters, public UUIDs, and lookup domains are backed by database indexes.

## 2. Caching Strategy
- **Scan Result Caching**: Reusable completed audits within the cache window (default 1 hour) prevent redundant network hits to the target.
- **Tool & Guide Catalog**: Static metadata for 19 tools and 6 educational guides are cached in memory for sub-10ms response times.
- **Zero Cache Leakage**: Authenticated user data, projects, billing states, and API keys are NEVER cached in public caches.
