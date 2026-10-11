# W3HealthChecker — Production Deployment Architecture

## 1. Executive Summary
W3HealthChecker is built on Laravel 11 (PHP 8.2+), React 19, Tailwind CSS, and MySQL 8.0+.
The platform operates as a Website Intelligence SaaS requiring:
1. Low-latency HTTP web serving (Nginx + PHP-FPM).
2. Outbound network capabilities for passive scanning (cURL, safe DNS resolution, socket connections).
3. Asynchronous queue processing for deep scans, multi-page crawling, and scheduled monitoring.
4. Scheduled background execution (cron for `php artisan schedule:run`).
5. Relational persistence (MySQL with InnoDB, utf8mb4 collation).
6. In-memory caching and queue broker (Redis or database queue).
7. Edge protection, DNS, and SSL termination (Cloudflare).

---

## 2. Infrastructure Topology

```
                  ┌─────────────────────────────────────────┐
                  │       Cloudflare Edge (CDN + WAF)       │
                  │   - DNS: w3healthchecker.com            │
                  │   - SSL/TLS: Full (Strict)              │
                  │   - DDoS & Bot Protection               │
                  └────────────────────┬────────────────────┘
                                       │ HTTPS (Port 443)
                                       ▼
                  ┌─────────────────────────────────────────┐
                  │          Host / Server Layer            │
                  │   (Dedicated VPS or Container Host)     │
                  │                                         │
                  │   ┌─────────────────────────────────┐   │
                  │   │      Nginx Web Server           │   │
                  │   │   - SSL Termination (Origin CA) │   │
                  │   │   - HTTP/2 & Gzip/Brotli        │   │
                  │   │   - Static Asset Caching        │   │
                  │   └───────────────┬─────────────────┘   │
                  │                   │ FastCGI             │
                  │                   ▼                     │
                  │   ┌─────────────────────────────────┐   │
                  │   │      PHP-FPM (PHP 8.2+)         │   │
                  │   │   - Laravel Web & REST API      │   │
                  │   │   - OPcache enabled             │   │
                  │   └───────────────┬─────────────────┘   │
                  │                   │                     │
                  │   ┌───────────────┴─────────────────┐   │
                  │   │    Supervisor Process Manager   │   │
                  │   │   - Queue Worker (queue:work)   │   │
                  │   │   - Scheduler (schedule:run)    │   │
                  │   └───────────────┬─────────────────┘   │
                  │                   │                     │
                  │   ┌───────────────┴─────────────────┐   │
                  │   │       MySQL 8.0 Database        │   │
                  │   │   - InnoDB, utf8mb4_unicode_ci  │   │
                  │   │   - Persistent Volume           │   │
                  │   └─────────────────────────────────┘   │
                  └─────────────────────────────────────────┘
```

---

## 3. Recommended Hosting Options

| Hosting Tier | Target Provider | Architecture | Estimated Monthly Cost | Pros & Cons |
|---|---|---|---|---|
| **Tier 1 (Recommended Beta/Launch)** | DigitalOcean / Hetzner / Linode VPS | 4GB RAM, 2 vCPU, Ubuntu 24.04 LTS (Nginx, PHP-FPM, MySQL, Redis on single host) | $15 – $24/mo | **Pros**: Full control over outbound networking, raw sockets, queue workers, and cron without timeout caps.<br>**Cons**: Requires OS patching and server administration. |
| **Tier 2 (Managed PaaS)** | Railway / Render / Fly.io | Containerized Web Service + Worker Service + Managed MySQL | $25 – $40/mo | **Pros**: Automated git deployments, managed database backups.<br>**Cons**: Outbound IP pools can occasionally be shared with other tenants. |
| **Tier 3 (Cloud Enterprise)** | AWS (EC2/Lightsail + RDS MySQL) | Dedicated EC2 t4g.medium + db.t4g.small MySQL RDS | $50 – $90/mo | **Pros**: High availability, automated RDS snapshots, VPC isolation.<br>**Cons**: Higher operational complexity. |

---

## 4. System Requirements

### PHP Extensions
- `php8.2` or `php8.3`
- `php-fpm`
- `php-cli`
- `php-mysql` (PDO MySQL)
- `php-curl` (Essential for `HttpFetchService` and outbound scans)
- `php-mbstring`
- `php-xml` & `php-dom` (Required for DOM parsing and sitemap processing)
- `php-zip`
- `php-bcmath`
- `php-redis` (Optional, recommended for high-volume caching)
- `php-opcache` (Crucial for production performance)

### Outbound Network Permissions
W3HealthChecker scans target websites using passive HTTP/HTTPS requests on ports 80 and 443.
- **Firewall Rules**: Outbound traffic to ports 80/443 must be allowed.
- **SSRF Restrictions**: The application's `UrlValidationService` strictly drops requests to private IP ranges (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.0/8`, `169.254.169.254`, `::1`) before socket connection.
