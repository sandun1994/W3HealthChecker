# W3HealthChecker — GitHub Integration and CI/CD Pipeline

## 1. Pipeline Overview
The CI/CD pipeline is implemented via GitHub Actions in [`.github/workflows/ci.yml`](file:///c:/xampp/htdocs/W3HealthChecker/.github/workflows/ci.yml).
Every commit pushed to `main` and all pull requests undergo automated verification:
1. **PHP Runtime Setup**: PHP 8.2 with requisite cryptographic, curl, XML, and database extensions.
2. **Composer Strict Validation**: Verifies `composer.json` and `composer.lock` consistency.
3. **Frontend Asset Build**: Executes `npm ci` and `npm run build` with Vite, ensuring no broken JSX or CSS compilation errors.
4. **Backend Test Suite**: Executes all unit and feature tests (`php artisan test`) with SQLite in-memory database.
5. **Secret Leak Detection**: Fails the build if `.env.production` or uncommitted credential files are detected.

---

## 2. Secrets Management & Environment Separation
Production credentials must never be committed to git:
- Use GitHub Repository Secrets (`Settings` -> `Secrets and variables` -> `Actions`):
  - `SSH_HOST`: IP address of production server.
  - `SSH_USER`: Deployment user (e.g., `deployer`).
  - `SSH_KEY`: Ed25519 private key for SSH access.
  - `APP_KEY`: Laravel encryption key.
  - `DB_PASSWORD`: Production MySQL password.

---

## 3. Deployment Workflow Trigger
Automated continuous deployment can be configured via GitHub Actions by triggering `deploy.sh` over SSH upon a successful test run on the `main` branch:
```yaml
deploy:
  needs: test
  if: github.ref == 'refs/heads/main'
  runs-on: ubuntu-latest
  steps:
    - name: Trigger Remote Deployment
      uses: appleboy/ssh-action@v1.0.3
      with:
        host: ${{ secrets.SSH_HOST }}
        username: ${{ secrets.SSH_USER }}
        key: ${{ secrets.SSH_KEY }}
        script: /var/www/w3healthchecker/deploy.sh
```
