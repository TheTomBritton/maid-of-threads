# /deploy-checklist — Pre-Launch Audit & Deployment Guide

## Purpose
Run a comprehensive pre-launch audit and provide step-by-step deployment instructions for Krystal shared hosting.

## Pre-Launch Audit

Work through each section, reporting pass/fail/warning for each item.

### 1. Security
- [ ] `$config->debug` is set to `false` in production config
- [ ] `$config->advanced` is `false`
- [ ] Admin URL has been changed from `/processwire/` to something custom
- [ ] `.htaccess` blocks access to sensitive files (see `.claude/instructions/security-hardening.md`)
- [ ] File permissions: directories 755, files 644
- [ ] `site/config.php` has strong `$config->userAuthSalt`
- [ ] No test/dev credentials left in code
- [ ] HTTPS is configured and forced
- [ ] `site/assets/backups/` is protected or empty
- [ ] Admin login has a strong password

### 2. SEO
- [ ] Every template outputs `<title>` and `<meta name="description">`
- [ ] Open Graph tags present (og:title, og:description, og:image)
- [ ] XML sitemap is generated and accessible
- [ ] `robots.txt` exists and is correct
- [ ] Canonical URLs are set
- [ ] Heading hierarchy is correct (single H1 per page)
- [ ] Image alt attributes are populated
- [ ] 301 redirects configured for any old URLs (if site migration)
- [ ] Structured data (JSON-LD) for business/organisation if applicable
- [ ] See `.claude/instructions/seo-checklist.md` for full details

### 3. Performance
- [ ] Images are optimised (WebP where supported, appropriate dimensions)
- [ ] CSS/JS is minified for production
- [ ] Browser caching headers configured in `.htaccess`
- [ ] Lazy loading on below-fold images
- [ ] No render-blocking resources in `<head>` (defer/async JS)
- [ ] Template caching enabled where appropriate
- [ ] Database queries are efficient (no N+1 in listings)
- [ ] See `.claude/instructions/performance-tuning.md` for full details

### 4. Accessibility
- [ ] Keyboard navigation works throughout
- [ ] Colour contrast meets WCAG AA minimum
- [ ] Form fields have associated labels
- [ ] Skip navigation link present
- [ ] ARIA landmarks used appropriately
- [ ] Focus styles are visible

### 5. Functionality
- [ ] All forms submit correctly and send notifications
- [ ] 404 page works and is styled
- [ ] Search functions correctly (if applicable)
- [ ] All internal links work (no broken links)
- [ ] Mobile responsive across breakpoints
- [ ] Print stylesheet (if needed)
- [ ] Cookie consent (if required — check GDPR applicability)

### 6. Content
- [ ] No placeholder/lorem ipsum text remaining
- [ ] All images are final (no placeholders)
- [ ] Contact details are correct
- [ ] Legal pages present (Privacy Policy, Terms if needed)
- [ ] Copyright year is dynamic

## Deployment Steps

After the audit passes, follow `.claude/instructions/deployment-krystal.md` for the full deployment procedure. Summary:

1. Export database from Docker/local
2. Update `site/config.php` with production database credentials
3. Upload files via SFTP (or rsync if SSH available)
4. Import database on hosting
5. Update file permissions
6. Run search/replace on database for URL changes
7. Test live site
8. Configure SSL
9. Set up redirects
10. Submit sitemap to Google Search Console

## Post-Launch
- [ ] Google Search Console verified
- [ ] Google Analytics / privacy-respecting analytics installed
- [ ] Uptime monitoring configured
- [ ] Backup schedule confirmed on hosting
- [ ] Client handover documentation prepared
