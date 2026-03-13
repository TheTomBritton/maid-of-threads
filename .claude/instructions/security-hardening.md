# Security Hardening for ProcessWire

## Configuration (site/config.php)

### Production Settings
```php
// ALWAYS set these for production
$config->debug = false;
$config->advanced = false;
$config->adminEmail = 'admin@yourdomain.com';

// Strong auth salt (generate a unique random string)
$config->userAuthSalt = 'generate-a-unique-64-char-random-string-here';

// Force HTTPS
$config->https = true;

// Session security
$config->sessionFingerprint = true;
$config->sessionCookieSecure = true;  // Only send cookie over HTTPS

// Database charset
$config->dbCharset = 'utf8mb4';
```

### Custom Admin URL
Change the default admin URL from `/processwire/`:
```php
$config->urls->admin = '/your-custom-admin-path/';
```

Also rename the directory:
```bash
mv processwire/ your-custom-admin-path/
```

## .htaccess Security Rules

Add these to the root `.htaccess` (after the ProcessWire rules):

```apache
# Block access to sensitive files
<FilesMatch "(^\.ht|\.inc$|\.info$|\.module$|\.sql$|\.sqlite$)">
    Require all denied
</FilesMatch>

# Block access to backup and config files
<FilesMatch "(config\.php|config-dev\.php|\.env|composer\.json|composer\.lock|package\.json)">
    Require all denied
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Block access to site directories that shouldn't be web-accessible
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Block access to /site/assets/backups/
    RewriteRule ^site/assets/backups/ - [F,L]

    # Block access to /site/assets/logs/
    RewriteRule ^site/assets/logs/ - [F,L]

    # Block access to /site/assets/sessions/
    RewriteRule ^site/assets/sessions/ - [F,L]

    # Block PHP execution in uploads
    RewriteRule ^site/assets/files/.*\.php$ - [F,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "camera=(), microphone=(), geolocation=()"

    # Content Security Policy (adjust per project)
    # Header set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';"
</IfModule>

# Force HTTPS
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

## File Permissions

### Standard Permissions
```
Directories:  755 (drwxr-xr-x)
Files:        644 (-rw-r--r--)
```

### Writable Directories (ProcessWire needs write access)
```
site/assets/          755
site/assets/files/    755
site/assets/cache/    755
site/assets/logs/     755
site/assets/sessions/ 755
```

### Script to Set Permissions
```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 755 site/assets/
```

## Session Security

Enable database sessions instead of file-based sessions:

1. Install the **SessionHandlerDB** core module
2. This stores sessions in the database, preventing session file access on shared hosting

## Admin Security

- Use a strong, unique password (16+ characters)
- Change the default admin username from "admin"
- Limit admin access by IP if possible (via .htaccess):
  ```apache
  <Directory "/path/to/your-admin-path">
      Require ip 123.456.789.0
  </Directory>
  ```
- Enable two-factor authentication if a module is available

## Input Sanitisation

Always sanitise user input — never trust `$input->get` or `$input->post` directly:

```php
// Text input
$clean = $sanitizer->text($input->get->q);

// For use in selectors
$clean = $sanitizer->selectorValue($input->get->category);

// Integer
$id = $sanitizer->int($input->get->id);

// Email
$email = $sanitizer->email($input->post->email);

// Filename
$file = $sanitizer->filename($input->get->file);
```

## CSRF Protection

ProcessWire includes built-in CSRF protection. Use it on all forms:

```php
// In form template
<form method="post">
    <?= $session->CSRF->renderInput() ?>
    <!-- form fields -->
</form>

// In processing code
if ($session->CSRF->hasValidToken()) {
    // Process form
} else {
    // Invalid token — reject
}
```

## Pre-Deployment Security Checklist

- [ ] `$config->debug = false`
- [ ] `$config->advanced = false`
- [ ] Admin URL changed from default
- [ ] Strong admin password set
- [ ] Strong `userAuthSalt` set
- [ ] HTTPS forced
- [ ] `.htaccess` security rules in place
- [ ] File permissions correct
- [ ] SessionHandlerDB enabled
- [ ] No test accounts or default credentials
- [ ] `site/assets/backups/` is empty or protected
- [ ] PHP error display disabled in php.ini
- [ ] No sensitive data in version control
- [ ] Database credentials not using root/default
