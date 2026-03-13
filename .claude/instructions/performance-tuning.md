# Performance Tuning for ProcessWire

## Image Optimisation

### Responsive Images with srcset
Always serve appropriately sized images. Never load a 4000px image when 1200px is the maximum display width.

```php
// In _func.php — responsive image helper
function renderImage($image, array $widths = [400, 800, 1200], string $sizes = '100vw'): string {
    if (!$image) return '';

    $srcset = [];
    foreach ($widths as $w) {
        $resized = $image->width($w);
        $srcset[] = "{$resized->url} {$w}w";
    }

    $default = $image->width($widths[1] ?? 800);
    $alt = wire('sanitizer')->entities($image->description);

    return "<img src='{$default->url}' srcset='" . implode(', ', $srcset) . "' sizes='{$sizes}' alt='{$alt}' width='{$default->width}' height='{$default->height}' loading='lazy'>";
}
```

### WebP Generation
ProcessWire 3.0.132+ supports WebP:
```php
$config->imageSizerOptions('webpAdd', true);
// Creates .webp alongside original format
```

Use in templates with `<picture>` element:
```php
$webp = $image->width(1200);
$webpUrl = str_replace(['.jpg', '.png'], '.webp', $webp->url);

echo "<picture>";
echo "<source srcset='{$webpUrl}' type='image/webp'>";
echo "<img src='{$webp->url}' alt='{$image->description}' loading='lazy'>";
echo "</picture>";
```

### Lazy Loading
- Add `loading="lazy"` to all images below the fold
- First visible image (hero, banner) should NOT be lazy loaded
- Add `fetchpriority="high"` to the LCP image:
  ```html
  <img src="hero.jpg" fetchpriority="high" width="1200" height="600" alt="...">
  ```

## CSS & JavaScript Optimisation

### Critical CSS
Inline critical above-the-fold CSS in `<head>`:
```html
<style>/* Critical CSS here */</style>
<link rel="stylesheet" href="/site/assets/dist/app.css" media="print" onload="this.media='all'">
```

### JavaScript Loading
```html
<!-- Defer non-critical JS -->
<script src="app.js" defer></script>

<!-- Async for independent scripts -->
<script src="analytics.js" async></script>

<!-- Never block rendering with JS in <head> without defer/async -->
```

### Minification
- Use Tailwind's production build (PurgeCSS removes unused classes)
- Or use **AllInOneMinify** module
- Production build command in `package.json`:
  ```json
  "scripts": {
      "build": "tailwindcss -i ./site/assets/src/app.css -o ./site/assets/dist/app.css --minify"
  }
  ```

## Caching

### Template Cache (Built-in — No Module Required)
Enable template caching for pages that don't change often. This is PW's built-in caching — no module needed.

Configure per template in PW admin: Setup > Templates > [template] > Cache tab. Set cache time in seconds (3600 = 1 hour is a good default for static pages).

**Good candidates for caching**: Homepage, about pages, service pages, blog listings, shop index, product categories.
**Never cache**: Cart, checkout, order confirmation, search results, forms, pages with user-specific content, RSS feeds.

> **Note**: Do NOT use ProCache — it's a paid Pro module. The built-in template cache is free and sufficient for most sites. For ecommerce, also consider that product pages with stock counts may need shorter cache times or no cache.

### Browser Caching via .htaccess
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
</IfModule>

# Cache-Control headers
<IfModule mod_headers.c>
    <FilesMatch "\.(css|js|jpg|jpeg|png|webp|gif|svg|woff2|woff)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
</IfModule>
```

**Important**: Use cache-busting in asset URLs when files change:
```php
// Built assets live in site/assets/dist/, NOT site/templates/assets/dist/
$cssPath = $config->paths->assets . 'dist/app.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : '';
echo "<link rel='stylesheet' href='{$config->urls->assets}dist/app.css?v={$cssVersion}'>";
```

> **Note**: `$config->urls->assets` points to `site/assets/`, while `$config->urls->templates` points to `site/templates/`. The build pipeline outputs to `site/assets/dist/` — always use the `assets` path for built files.

### GZIP Compression
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json text/xml image/svg+xml
</IfModule>
```

## Database Query Optimisation

### Avoid N+1 Queries
```php
// BAD — queries database for each child page's image separately
foreach ($page->children as $child) {
    echo $child->featured_image->url;  // New query per iteration
}

// GOOD — tell PW to autojoin the image field
// Configure in template settings: Setup > Templates > [template] > Advanced > Autojoin fields
// Or use the field's autojoin setting
```

### Efficient Selectors
```php
// BAD — fetches all fields for all pages
$pages->find("template=blog-post");

// BETTER — only fetch what you need
$pages->find("template=blog-post, sort=-date, limit=10");

// Even better for large sites — specify fields to load
$pages->findMany("template=blog-post, sort=-date");
```

### Page Reference Fields
Set "Autojoin" on page reference fields that are always needed (like categories on blog posts).

## Fonts

### Self-Host Fonts
Don't use Google Fonts CDN. Self-host for performance and privacy:
```css
@font-face {
    font-family: 'Inter';
    src: url('/site/templates/assets/fonts/inter-regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
```

### Preload Critical Fonts
```html
<link rel="preload" href="/site/templates/assets/fonts/inter-regular.woff2" as="font" type="font/woff2" crossorigin>
```

## Preloading & Prefetching
```html
<!-- Preload LCP image -->
<link rel="preload" as="image" href="/site/assets/files/hero.webp">

<!-- DNS prefetch for external resources (if any) -->
<link rel="dns-prefetch" href="//cdn.example.com">
```

## Performance Monitoring

After deployment:
1. Run Google Lighthouse (aim for 90+ on all categories)
2. Test with WebPageTest.org for real-world loading
3. Check Core Web Vitals in Google Search Console
4. Monitor with the browser Network tab — look for large files, slow requests
