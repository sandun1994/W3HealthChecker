# W3HealthChecker — Benchmarking & Cross-Customer Privacy

## 1. Principles of Ethical Benchmarking
W3HealthChecker implements statistical benchmarking without compromising individual customer privacy or leaking competitive intelligence.

### Non-Negotiable Rules
1. **No Fabricated Benchmarks**: If representative data is unavailable, the system explicitly returns `available: false` rather than generating artificial percentiles.
2. **Minimum Cohort Size**: Benchmarks activate only when a minimum of **10 distinct websites** exist within the cohort (`BenchmarkingService::MINIMUM_COHORT_SIZE = 10`).
3. **Data Sanitization**:
   - Zero private URLs or query strings are shared.
   - Zero domain names, user identifiers, or customer metadata are surfaced in comparative metrics.
   - Only normalized, aggregated numeric scores (0–100) are computed.
4. **Statistical Robustness**:
   - Compares targets against the **Median (50th percentile)**, **25th percentile (Lower Quartile)**, and **75th percentile (Upper Quartile)**.
   - Categorizes standing as *Top Quartile*, *Above Median*, *Below Median*, or *Bottom Quartile*.
