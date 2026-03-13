# Deploying to Krystal Shared Hosting

## Overview

Krystal is a UK-based shared hosting provider running Apache with PHP-FPM. The primary deployment method is **GitHub Actions CI/CD with cPanel Git Version Control**. SFTP is available as a fallback.

## Pre-Deployment

Before deploying, run `/deploy-checklist` to ensure everything is ready.

## Deployment Methods

### Method 1: GitHub Actions + cPanel Git (Recommended)

This is the automated CI/CD pipeline used for Maid of Threads and the recommended approach for all projects.

**How it works:**
1. Push code to the project branch (e.g. `maid-of-threads`)
2. GitHub Actions workflow triggers, builds everything:
   - Installs PHP dependencies via Composer (`--no-dev`)
   - Installs Node dependencies and runs `npm run build`
   - Assembles a deploy directory with wire/, vendor/, templates, built assets
   - Strips vendor markdown files (avoids GitHub secret scanning false positives)
3. Force-pushes the deploy directory to a `deploy/<project>` branch
4. cPanel Git Version Control detects the update and pulls
5. `.cpanel.yml` copies files from the repo to the document root

**Key files:**
- `.github/workflows/deploy-<project>.yml` — GitHub Actions workflow
- `.cpanel.yml` — cPanel deployment manifest (in repo root)
- `sites/<project>/deploy-files/` — .htaccess and index.php for deployment

**Initial setup:**
1. Create the GitHub Actions workflow (see existing `deploy-maid-of-threads.yml` as template)
2. Create `.cpanel.yml` with copy tasks for your document root path
3. Create `deploy-files/.htaccess` with PW rewrite rules, HTTPS redirect, security headers
4. Push to trigger the first build
5. In Krystal cPanel → Git Version Control:
   - Create New → paste GitHub repo URL
   - Set branch to `deploy/<project>`
   - Set deploy path to the document root
6. Click "Deploy HEAD Commit"

**Important notes:**
- `config.php` is **never deployed via CI** — it contains database credentials and auth salts. Upload it manually once via cPanel File Manager to `site/config.php`.
- `site/assets/files/` (user-uploaded images) is not in the deploy. Upload separately.
- After a force-push, cPanel may fail to fast-forward. Fix: delete the repo in cPanel Git Version Control and recreate it pointing to the same branch.

### Method 2: SFTP Upload (Fallback)

**Tools**: FileZilla, WinSCP, Cyberduck, or VS Code SFTP extension.

**Connection details** (from Krystal control panel):
- Host: your-server.krystal.hosting (check cPanel)
- Port: 22 (SFTP) or 21 (FTP — avoid if possible)
- Username: your cPanel username
- Password: your cPanel password

**First deployment — upload everything:**
```
wire/                    ← PW core (from composer install)
vendor/                  ← Composer autoload + dependencies
site/
├── templates/           ← Your template files
├── modules/             ← Installed modules
├── assets/              ← Will need write permissions
├── config.php           ← Production config
├── ready.php
└── init.php
index.php
.htaccess
```

> **Automated option**: Run `scripts/prepare-deploy.sh` to build assets and assemble a clean `deploy/` directory ready for SFTP upload.

**Subsequent deployments — upload only changed files:**
```
site/templates/          ← Updated template files
site/assets/dist/        ← Rebuilt CSS/JS
site/modules/            ← New or updated modules
vendor/                  ← If Composer dependencies changed
```

**Never re-upload on update:**
- `site/assets/files/` — contains uploaded content
- `site/assets/cache/` — will regenerate
- `site/config.php` — unless config has changed

## Build Production Assets

```bash
# From the project directory (e.g. sites/maid-of-threads/)
npm run build
```

This creates minified CSS (and JS if applicable) in `site/assets/dist/`.

Note: GitHub Actions handles this automatically — you only need to run this manually for SFTP deployments.

## Production config.php

Use environment-aware detection so one file works for both local and production:

```php
<?php namespace ProcessWire;

// Detect environment
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8080', '127.0.0.1']);

if ($isLocal) {
    $config->debug = true;
    $config->dbHost = 'db';
    $config->dbName = 'pw_dev';
    $config->dbUser = 'pw_user';
    $config->dbPass = 'pw_password';
    $config->httpHosts = ['localhost:8080'];
} else {
    $config->debug = false;
    $config->dbHost = 'localhost';
    $config->dbName = 'your_prod_db';
    $config->dbUser = 'your_prod_user';
    $config->dbPass = 'your_prod_password';
    $config->httpHosts = ['www.yourdomain.com', 'yourdomain.com'];
    $config->https = true;
    $config->sessionCookieSecure = true;
}

// Shared settings
$config->dbCharset = 'utf8mb4';
$config->dbEngine = 'InnoDB';
$config->timezone = 'Europe/London';
$config->sessionFingerprint = true;
$config->userAuthSalt = 'your-unique-64-char-salt';
```

**Never commit production credentials to git.** The config file stays on the server and is excluded from CI deployment.

## Database Setup

### On Krystal (via cPanel)

1. Log into cPanel
2. Go to **MySQL Databases**
3. Create a new database
4. Create a database user with a strong password
5. Add the user to the database with **All Privileges**
6. Note the details for `site/config.php`

### Exporting from Docker (Local)

```bash
# Export from Docker
docker compose exec db mysqldump -u root -p pw_dev > database-export.sql

# Or via Adminer at http://localhost:8081
# Select database > Export > SQL
```

### Importing to Krystal

**Via cPanel phpMyAdmin:**
1. Open phpMyAdmin in cPanel
2. Select your database
3. Import tab > Choose file > Select your SQL export
4. Execute

### URL Replacement

PW handles URL changes automatically based on `$config->httpHosts` — no manual SQL replacement needed in most cases.

If manual replacement is needed:
```sql
UPDATE field_body SET data = REPLACE(data, 'http://localhost:8080', 'https://www.yourdomain.com');
```

## SSL Certificate

Krystal provides free Let's Encrypt SSL:
1. In cPanel, go to **SSL/TLS** or **Let's Encrypt**
2. Issue a certificate for your domain
3. HTTPS redirect is handled by `.htaccess` (included in deploy)

## DNS Configuration

Point your domain to Krystal's nameservers or set A/CNAME records as provided by Krystal in your welcome email or cPanel.

## Post-Deployment Checks

1. Visit the site — does it load?
2. Check the admin panel — can you log in?
3. Test all forms — do emails send?
4. Check images — are they displaying?
5. Run Lighthouse — performance score acceptable?
6. Check console for JavaScript errors
7. Test on mobile
8. Verify SSL certificate is working (green padlock)
9. Check robots.txt is accessible
10. Verify sitemap.xml generates correctly

## Krystal-Specific Gotchas

- **No external SSH**: Port 22 is blocked from external IPs (e.g. GitHub Actions). Use cPanel Git Version Control instead of SSH/rsync-based deploys.
- **PHP version**: Defaults may be below 8.2. Set via cPanel → MultiPHP Manager.
- **cPanel Git force-push**: cPanel cannot fast-forward after a force-push. Delete and recreate the repo in Git Version Control.
- **GitHub secret scanning**: Stripe SDK and similar packages contain example API keys in their docs. Strip markdown files from vendor before pushing to deploy branches.
