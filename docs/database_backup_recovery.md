# W3HealthChecker — Database Backup and Disaster Recovery

## 1. Overview
The MySQL database stores all user accounts, projects, historical scans, monitoring configurations, delta changes, subscriptions, and audit logs.
Data integrity and automated disaster recovery procedures are critical.

---

## 2. Automated Backup Strategy

### Daily Full Backups via `mysqldump`
Create `/usr/local/bin/backup-w3db.sh`:
```bash
#!/usr/bin/env bash
set -e

BACKUP_DIR="/var/backups/w3healthchecker"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/w3healthchecker_${TIMESTAMP}.sql.gz"

mkdir -p "${BACKUP_DIR}"

# Dump database with transactions and compress on the fly
mysqldump --defaults-extra-file=/etc/mysql/backup.cnf \
          --single-transaction \
          --quick \
          --routines \
          --triggers \
          w3healthchecker | gzip > "${BACKUP_FILE}"

# Enforce 30-day retention policy (prune older files)
find "${BACKUP_DIR}" -type f -name "w3healthchecker_*.sql.gz" -mtime +30 -delete

echo "Backup successful: ${BACKUP_FILE}"
```

Create `/etc/mysql/backup.cnf` with restricted permissions (`chmod 600`):
```ini
[client]
user = root
password = "YOUR_SECURE_ROOT_PASSWORD"
```

Schedule daily backup at 02:00 UTC via root crontab:
```cron
0 2 * * * /usr/local/bin/backup-w3db.sh >> /var/log/db-backup.log 2>&1
```

---

## 3. Disaster Recovery & Restoration Procedure

To restore from a backup:
```bash
# 1. Put application into maintenance mode
cd /var/www/w3healthchecker
php artisan down

# 2. Decompress and restore into database
gunzip -c /var/backups/w3healthchecker/w3healthchecker_YYYYMMDD_HHMMSS.sql.gz | mysql -u root -p w3healthchecker

# 3. Verify database migrations status
php artisan migrate:status

# 4. Bring application back up
php artisan up
```

---

## 4. Pre-Migration Verification Rule
Before executing any destructive migration (`dropTable`, `dropColumn`, or column alterations):
1. An explicit snapshot dump must be triggered:
   ```bash
   mysqldump --single-transaction -u root -p w3healthchecker | gzip > /var/backups/w3healthchecker/pre_migration_$(date +%s).sql.gz
   ```
2. Migrations must be run in staging with production-equivalent volume to verify execution time and lock impact.
