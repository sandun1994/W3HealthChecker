# Disaster Recovery & Backup Runbook

## 1. Database Backups
- **Automated Snapshots**: Daily point-in-time recovery snapshots with 30-day retention.
- **Pre-Migration Backups**: Scheduled backup before running schema migrations in production.
- **Zero Raw Secret Exposure**: Backups are encrypted at rest with AES-256.

## 2. Recovery Procedure (RTO < 30 mins, RPO < 1 hour)
1. Provision target database instance from snapshot.
2. Verify schema integrity and foreign keys.
3. Update environment configuration (`DB_HOST`, `DB_PORT`).
4. Execute `php artisan migrate --force` to apply any delta migrations.
5. Hit `/health` diagnostic endpoint to verify operational status.
6. Reconnect application workers and load balancers.
