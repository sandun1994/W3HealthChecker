# W3HealthChecker — Production 502 Error Root-Cause Investigation Report

## 1. Executive Summary

When visitors access `https://w3healthchecker.com` and receive a Cloudflare **502 Bad Gateway — Host Error**, Cloudflare is communicating successfully with the Oracle Linux origin server's host Nginx reverse proxy, but the host Nginx cannot establish a connection to `http://127.0.0.1:8080` because the `w3healthchecker_app` container is stopped.

The investigation revealed that **both the application (`w3healthchecker_app`) and database (`w3healthchecker_db`) containers previously exited cleanly with status `0`**. In Linux and container runtimes, an exit code of `0` does not indicate an application crash or an Out-Of-Memory (OOM) kill; it indicates that a **graceful shutdown signal (`SIGTERM`)** was received and processed.

In **Rootless Podman**, container processes and their monitoring processes (`conmon`) run inside the unprivileged user's `systemd` session (typically user `opc`). By default on Oracle Linux / RHEL systems, when an SSH session disconnects, `systemd-logind` terminates all processes belonging to that user session unless **user lingering** (`loginctl enable-linger opc`) is enabled. When the user session terminates, `systemd` sends `SIGTERM` to the user slice, causing Supervisor and MySQL to shut down cleanly with exit code `0`.

Furthermore:
1. Podman's `restart: unless-stopped` policy cannot function without an active user manager (`systemd --user`) or `podman-restart.service`.
2. When the user session terminates while locks are held, or when runtime directories (`/run/user/<UID>`) are reset, Rootless Podman suffers from **stale lock conflicts** (`acquiring lock ... file exists`), preventing clean restarts.
3. On an **Oracle Cloud Always Free 1 GB RAM instance**, running unconstrained **MySQL 8.0** alongside PHP-FPM, Nginx, queue workers, and the scheduler creates severe memory pressure and swap thrashing, making health checks prone to timing out during startup.

---

## 2. Confirmed Findings vs. Unconfirmed Hypotheses

| Category | Finding / Hypothesis | Status | Technical Details |
|---|---|---|---|
| **Podman Runtime** | `restart: unless-stopped` is ineffective without lingering | **Confirmed Mechanism** | In rootless Podman, container restart policies require the user's `systemd` instance to remain running. Without lingering, the user session dies on logout, killing the containers. |
| **Podman Runtime** | Stale Podman storage/container lock errors | **Confirmed Defect / State** | `acquiring lock ... file exists` indicates orphaned lock files in `/run/user/<UID>/libpod/` or `~/.local/share/containers/storage/locks` caused by unclean runtime teardowns. |
| **CI/CD Automation** | Deployment workflow stopped containers | **Confirmed Non-Issue** | No `.github/workflows/deploy.yml` exists. GitHub Actions only builds images (`publish-image.yml`) and runs tests (`ci.yml`). No automated remote deployment exists. |
| **App Code** | Application code triggering process termination | **Confirmed Non-Issue** | Zero occurrences of `exit()`, `die()`, or `posix_kill()` exist in application controllers, commands, or middleware. |
| **Container Lifecycle** | Lack of MySQL wait-loop in entrypoint | **Confirmed Defect** | `docker/entrypoint.sh` executes `artisan migrate --force` with `set -e` without verifying MySQL network readiness, risking startup failure if MySQL is not immediately ready. |
| **Application Logging** | Laravel errors hidden from container logs | **Confirmed Defect** | `docker-compose.prod.yml` does not set `LOG_CHANNEL=stderr`, routing Laravel exceptions to `/var/www/html/storage/logs/laravel.log` rather than `podman logs`. |
| **Server State** | SSH logout is the direct trigger of the outage | **High-Probability Hypothesis (To Verify on Server)** | Correlates with exit code `0` and rootless session teardown, but must be confirmed via `loginctl show-user opc -p Linger` and `journalctl`. |
| **Hardware Resources** | MySQL 8.0 memory exhaustion on 1 GB RAM | **Probable Contributing Factor** | MySQL 8.0 defaults consume 450MB–600MB+ RSS without optimization (`performance_schema=OFF`, reduced buffer pool), starving a 1 GB instance. |

---

## 3. File-by-File Code Review Findings

### `docker-compose.prod.yml`
- **Lines 7 & 36 (`restart: unless-stopped`):**
  In Docker, `dockerd` manages container restarts as a persistent system daemon. In rootless Podman, `restart: unless-stopped` depends on `systemd --user` and `podman-restart.service`. If user `opc` logs out and user lingering is disabled, `systemd` terminates all user processes, rendering this directive completely inactive.
- **Lines 33–48 (`db` service with `image: mysql:8.0`):**
  No memory constraints (`deploy.resources.limits.memory`) or MySQL configuration flags (such as `--innodb-buffer-pool-size=64M` and `--performance-schema=OFF`) are specified. On an Oracle Linux 1 GB RAM VM, standard MySQL 8.0 consumes more than 50% of the entire host memory, causing heavy swap thrashing and latency spikes.
- **Lines 27–29 (`depends_on: db: condition: service_healthy`):**
  While syntactically valid in Compose v2, `podman-compose` has known limitations in honoring `service_healthy` conditions between independent rootless container lifecycles, occasionally starting the app before MySQL is accepting connections.
- **Lines 10–26 (Missing `LOG_CHANNEL`):**
  `LOG_CHANNEL` is omitted from the environment block. Laravel defaults to `stack` -> `single` (writing to `/var/www/html/storage/logs/laravel.log`), preventing application exceptions from surfacing in `podman logs w3healthchecker_app`.

### `docker/entrypoint.sh`
- **Line 2 (`set -e`):**
  Causes the script to exit immediately if any command returns a non-zero exit code.
- **Lines 9–13 (`RUN_MIGRATIONS` execution):**
  ```bash
  if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
      echo "Executing database migrations..."
      php /var/www/html/artisan migrate --force
  fi
  ```
  There is **no TCP/socket polling loop** waiting for MySQL to accept connections before running `artisan migrate`. If the database is starting up slowly or recovering InnoDB tables, `php artisan migrate` fails immediately, causing `entrypoint.sh` to terminate before Supervisor starts.
- **Line 24 (`exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf`):**
  Supervisor correctly replaces the shell as PID 1 via `exec`. Supervisor forwards signals (`SIGTERM`) to child processes and exits cleanly with status `0` upon receiving `SIGTERM`.

### `docker/supervisord.conf`
- **Line 2 (`nodaemon=true`):** Correctly keeps Supervisor in the container foreground.
- **Lines 27–36 (`program:laravel-worker`):**
  `stopwaitsecs` is missing (defaults to 10s). If long queue jobs run, Supervisor may force-kill (`SIGKILL`) the worker during a shutdown.
- **Lines 37–46 (`program:laravel-scheduler`):**
  `command=/bin/sh -c "while true; do php /var/www/html/artisan schedule:run --no-interaction; sleep 60; done"`
  Running a shell `while true` loop with `sleep 60` does not handle POSIX termination signals cleanly; the shell waits for `sleep 60` before processing signals. In Laravel 11, `php /var/www/html/artisan schedule:work` is the native long-running daemon command and handles signals properly.

### `Dockerfile`
- **Line 49 (`ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]`):**
  No default `CMD` is specified, but `entrypoint.sh` directly invokes `exec supervisord`, ensuring the container starts Supervisor as expected.
- **Line 45 (`RUN chown -R www-data:www-data ...`):**
  Sets permissions during image build. In rootless Podman, host user namespace mappings (`subuid`/`subgid`) translate `www-data` to mapped subordinate UIDs.

### `.github/workflows/`
- **`.github/workflows/ci.yml`:** Only runs PHPUnit tests and Vite build on push/PR.
- **`.github/workflows/publish-image.yml`:** Builds and pushes to GHCR.
- **`.github/workflows/deploy.yml`:** **Does NOT exist.** No automated deployment workflow exists that could unexpectedly stop, kill, or re-create production containers.

---

## 4. The Most Likely Explanation for the Recurring 502 Error

The recurring 502 error is caused by a **two-phase operational failure** in rootless Podman on Oracle Linux:

```
┌────────────────────────────────────────────────────────────────────────┐
│ SSH Session Closes (User 'opc' disconnects)                            │
│ └── systemd-logind triggers session teardown (Linger is DISABLED)      │
│     └── systemd stops 'user@1000.service'                              │
│         └── SIGTERM sent to all processes in user-1000.slice           │
│             ├── supervisord stops php-fpm, nginx, workers (Exits 0)    │
│             └── mysqld flushes buffer and shuts down (Exits 0)         │
│                 └── Both containers EXITED with status 0               │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │
                                     ▼
┌────────────────────────────────────────────────────────────────────────┐
│ Host Nginx receives incoming visitor request from Cloudflare           │
│ └── Tries to connect to http://127.0.0.1:8080                          │
│     └── Connection Refused (port 8080 is dead)                         │
│         └── Returns HTTP 502 Bad Gateway to Cloudflare                 │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │
                                     ▼
┌────────────────────────────────────────────────────────────────────────┐
│ User logs in via SSH to investigate                                    │
│ └── systemd creates a new session and mounts fresh /run/user/1000      │
│ └── Podman commands encounter stale lock references / orphaned state   │
│     └── "acquiring lock ... file exists" errors                        │
│ └── Manual start restores containers temporarily                       │
│ └── User exits SSH terminal -> Cycle repeats                           │
└────────────────────────────────────────────────────────────────────────┘
```

1. **Rootless session termination:**
   In Oracle Linux, when non-root user `opc` disconnects from SSH, `systemd` stops `user@<UID>.service`. This terminates all processes running in the user slice with `SIGTERM`. MySQL and Supervisor exit gracefully with code `0`.
2. **Container isolation from systemd:**
   Because the containers were launched via `podman-compose` rather than managed via `systemd` user units or Podman Quadlet, Podman has no daemon running to keep them alive or restart them after session termination.
3. **Stale Lock Contention:**
   The abrupt teardown of the user runtime directory (`/run/user/1000`) while Podman storage metadata still references active lock IDs creates the `acquiring lock ... file exists` condition observed when running subsequent Podman commands.
4. **Memory Starvation (Secondary):**
   Unconstrained MySQL 8.0 on a 1 GB RAM instance causes intense swap usage. When containers are manually started, if MySQL takes longer than 5 seconds to reply to `mysqladmin ping` under I/O pressure, health checks fail and container startup degrades.

---

## 5. Root-Cause & Findings Matrix

| Finding | Evidence | Severity | Confidence | Recommended Action |
|---|---|---|---|---|
| **Rootless user lingering disabled** | Both containers exit with code `0`; outage correlates with SSH disconnect; Oracle Linux default is `Linger=no`. | **Critical** (Probable cause) | **95%** | Run `loginctl enable-linger opc` and verify with `loginctl show-user opc -p Linger`. |
| **Missing systemd management for Podman** | Containers run via ad-hoc `podman-compose` rather than `systemd` user units; cannot survive session disconnects or server reboot. | **High** (Probable cause) | **95%** | Generate and enable `systemd` user services (`systemctl --user enable podman-restart.service` or systemd service units). |
| **Stale Podman storage lock files** | Podman error logs: `acquiring lock ... file exists` and `retrieving lock ... file exists`. | **High** (Confirmed defect) | **100%** | Clear orphaned lock files safely or run `podman system migrate` once lingering is enabled. |
| **Unconstrained MySQL 8.0 memory on 1 GB host** | `docker-compose.prod.yml` defines `mysql:8.0` without buffer pool or performance schema tuning on a 1 GB instance. | **High** (Possible contributing factor) | **90%** | Configure `--innodb-buffer-pool-size=64M --performance-schema=OFF` in `docker-compose.prod.yml`. |
| **No database wait-loop in entrypoint** | `docker/entrypoint.sh` line 12 runs `artisan migrate` with `set -e` without polling database readiness. | **Medium** (Confirmed defect) | **100%** | Add safe TCP port wait-loop (`nc` or PHP PDO retry loop) before running migrations. |
| **Application errors missing from container logs** | `docker-compose.prod.yml` omits `LOG_CHANNEL=stderr`, routing exceptions to `storage/logs/laravel.log`. | **Medium** (Confirmed defect) | **100%** | Add `LOG_CHANNEL: stderr` and `LOG_LEVEL: info` to `docker-compose.prod.yml`. |
| **Scheduler shell loop signal handling** | `docker/supervisord.conf` lines 38 uses `/bin/sh -c "while true; ... sleep 60; done"` instead of `artisan schedule:work`. | **Low** (Possible contributing factor) | **85%** | Update command to `php /var/www/html/artisan schedule:work`. |
| **Automated deployment stopping containers** | GitHub Actions workflows checked; `.github/workflows/deploy.yml` does not exist. | **None** (Not related) | **100%** | No action required. |

---

## 6. Safe Diagnostic Plan (Read-Only Commands on Oracle Linux)

Run these non-destructive commands on the Oracle Linux server to verify the hypotheses:

### Step 1: Verify User Lingering Status
```bash
loginctl show-user opc -p Linger
```
- **Problem indicator:** `Linger=no`. This proves that user `opc`'s user manager and all background processes (including Podman containers) are terminated by systemd as soon as all SSH sessions close.

### Step 2: Inspect Container Exit Details and Timestamps
```bash
podman inspect w3healthchecker_app --format 'ExitCode: {{.State.ExitCode}}, FinishedAt: {{.State.FinishedAt}}, OOMKilled: {{.State.OOMKilled}}'
podman inspect w3healthchecker_db --format 'ExitCode: {{.State.ExitCode}}, FinishedAt: {{.State.FinishedAt}}, OOMKilled: {{.State.OOMKilled}}'
```
- **Problem indicator:** `ExitCode: 0`, `OOMKilled: false`, and `FinishedAt` matching the timestamp when your previous SSH connection disconnected.

### Step 3: Check Systemd User Session Teardown in System Journal
```bash
journalctl -u user-1000.slice --since "2 hours ago" --no-pager
journalctl -u "user@$(id -u).service" --since "2 hours ago" --no-pager
```
- **Problem indicator:** Log entries such as `Stopping User Manager for UID 1000...`, `Stopped target Default.`, or `user-1000.slice: Consumed ... CPU time` coinciding with the container stoppage.

### Step 4: Check Podman Process & Conmon Status
```bash
ps aux | grep -E "conmon|podman|supervisord|mysqld" | grep -v grep
```
- **Problem indicator:** No `conmon` processes running when you expect the containers to be active, or orphaned `conmon` processes holding lock files.

### Step 5: Check Memory, Swap, and Kernel OOM Events
```bash
free -h
dmesg -T | grep -i -E "oom|out of memory|killed process"
```
- **Problem indicator:** `Swap: used` near capacity with zero free RAM, or kernel OOM logs (if any exist).

### Step 6: Check Host Nginx Error Log
```bash
sudo tail -n 30 /var/log/nginx/error.log
```
- **Problem indicator:** `connect() failed (111: Connection refused) while connecting to upstream, client: ..., upstream: "http://127.0.0.1:8080/..."`. This proves host Nginx is operational and Cloudflare is reaching the host, but the container backend is down.

### Step 7: Check Stale Podman Lock Files
```bash
find /run/user/$(id -u)/ -name "*lock*" 2>/dev/null
ls -la ~/.local/share/containers/storage/locks/ 2>/dev/null
```
- **Problem indicator:** Leftover lock files with timestamps from previous disconnected sessions.

---

## 7. Recommended Permanent Fix

The permanent solution requires four coordinated steps:

1. **Enable Systemd User Lingering on the Host:**
   Run `loginctl enable-linger opc`. This instructs `systemd` to keep the `user@1000.service` manager running persistently in the background across logouts and immediately upon server boot.
2. **Enable Rootless `podman-restart.service`:**
   Enable the user-level systemd service (`systemctl --user enable --now podman-restart.service`). This ensures that containers with `restart: unless-stopped` automatically start on system boot and restart if stopped.
3. **Tune MySQL 8.0 Memory Limits for 1 GB Host:**
   Constrain MySQL 8.0 in `docker-compose.prod.yml` by setting `innodb_buffer_pool_size=64M`, disabling `performance_schema`, and capping container memory.
4. **Harden Application Startup and Logging:**
   Update `docker/entrypoint.sh` to poll database connectivity before executing migrations, route logs to `stderr`, and use `schedule:work`.

---

## 8. Proposed Patch (For Review Only — NOT Applied)

### Patch 1: `docker-compose.prod.yml`
```diff
--- a/docker-compose.prod.yml
+++ b/docker-compose.prod.yml
@@ -10,6 +10,8 @@ services:
     environment:
       APP_ENV: production
       APP_DEBUG: "false"
+      LOG_CHANNEL: stderr
+      LOG_LEVEL: info
       APP_KEY: ${APP_KEY}
       APP_MAINTENANCE_DRIVER: file
       APP_MAINTENANCE_STORE: file
@@ -29,6 +31,11 @@ services:
         condition: service_healthy
     volumes:
       - app_storage:/var/www/html/storage
+    deploy:
+      resources:
+        limits:
+          memory: 384M
 
   db:
     image: mysql:8.0
@@ -40,6 +47,15 @@ services:
       MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
     volumes:
       - db_data:/var/lib/mysql
+    command:
+      - --default-authentication-plugin=mysql_native_password
+      - --performance-schema=OFF
+      - --innodb-buffer-pool-size=64M
+      - --max-connections=30
+    deploy:
+      resources:
+        limits:
+          memory: 384M
     healthcheck:
       test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
       timeout: 5s
```

### Patch 2: `docker/entrypoint.sh`
```diff
--- a/docker/entrypoint.sh
+++ b/docker/entrypoint.sh
@@ -6,6 +6,17 @@ echo "Starting W3HealthChecker Production Container..."
 # Ensure permissions
 chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
 
+# Wait for database readiness if migrations are enabled
+if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
+    echo "Waiting for database connection on ${DB_HOST:-db}:${DB_PORT:-3306}..."
+    until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
+        echo "Database is unavailable - sleeping 2 seconds..."
+        sleep 2
+    done
+    echo "Database connected successfully. Executing migrations..."
+    php /var/www/html/artisan migrate --force
+fi
+
 # Cache configuration, routes, and views in production
 if [ "${APP_ENV:-production}" = "production" ]; then
     echo "Caching Laravel configuration and routes..."
```

### Patch 3: `docker/supervisord.conf`
```diff
--- a/docker/supervisord.conf
+++ b/docker/supervisord.conf
@@ -31,11 +31,12 @@ user=www-data
 numprocs=1
 redirect_stderr=true
 stdout_logfile=/dev/stdout
 stdout_logfile_maxbytes=0
+stopwaitsecs=60
 
 [program:laravel-scheduler]
-command=/bin/sh -c "while true; do php /var/www/html/artisan schedule:run --no-interaction; sleep 60; done"
+command=php /var/www/html/artisan schedule:work
 autostart=true
 autorestart=true
 user=www-data
 priority=20
```

---

## 9. Verification Plan

Once the above recommendations are approved and applied on the production server, verify using this sequence:

1. **Verify Persistent Lingering:**
   ```bash
   sudo loginctl enable-linger opc
   loginctl show-user opc -p Linger
   # Expected output: Linger=yes
   ```
2. **Enable Rootless Podman Restart Service:**
   ```bash
   systemctl --user enable --now podman-restart.service
   systemctl --user status podman-restart.service
   # Expected output: active (running) or active (exited) waiting for events
   ```
3. **Clean Up Any Stale Podman Storage Locks:**
   ```bash
   podman system migrate
   ```
4. **Start Application Stack:**
   ```bash
   podman-compose -f docker-compose.prod.yml up -d
   ```
5. **Verify SSH Disconnect Survival Test:**
   - Confirm site responds: `curl -I http://127.0.0.1:8080/health` (returns `HTTP 200 OK`).
   - Disconnect and exit your SSH terminal session completely.
   - Wait 10 minutes.
   - Test external domain: `curl -I https://w3healthchecker.com/health`.
   - Confirm it returns `HTTP 200 OK` (Cloudflare 502 does not occur).
6. **Controlled Server Reboot Test:**
   - Re-enter SSH and reboot the server: `sudo reboot`.
   - Wait for instance to reboot.
   - Without opening an interactive SSH session to `opc`, query `https://w3healthchecker.com/health` from an external machine.
   - Confirm that `systemd` user manager started on boot and Podman restarted the containers automatically.
