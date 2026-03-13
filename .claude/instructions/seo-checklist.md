# SEO Checklist for ProcessWire

## Essential Meta Tags (Every Page)

### In _main.php `<head>`
```php
<title><?= $browser_title ?> | <?= $pages->get('/')->title ?></title>
<meta name="description" content="<?= $sanitizer->entities($meta_description) ?>">
<link rel="canonical" href="<?= $page->httpUrl ?>">

<!-- Open Graph -->
<meta property="og:title" content="<?= $sanitizer->entities($browser_title) ?>">
<meta property="og:description" content="<?= $sanitizer->entities($meta_description) ?>">
<meta property="og:url" content="<?= $page->httpUrl ?>">
<meta property="og:type" content="<?= $page->template->name === 'blog-post' ? 'article' : 'website' ?>">
<?php if ($page->featured_image): ?>
<meta property="og:image" content="<?= $page->featured_image->width(1200)->httpUrl ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="<?= $page->featured_image->width(1200)->height ?>">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $sanitizer->entities($browser_title) ?>">
<meta name="twitter:description" content="<?= $sanitizer->entities($meta_description) ?>">
```

### Using SeoMaestro Module (Preferred)
If SeoMaestro is installed, replace the above with:
```php
<?= $page->seo->render() ?>
```

## Structured Data (JSON-LD)

### Organisation / Local Business
Add to the homepage template:
```php
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',  // or 'LocalBusiness'
    'name' => $pages->get('/')->title,
    'url' => $config->httpHost,
    'logo' => $config->urls->httpTemplates . 'assets/img/logo.png',
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+44-XXXX-XXXXXX',
        'contactType' => 'customer service',
    ],
];
$extra_head .= "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
```

### Breadcrumbs Schema
```php
function renderBreadcrumbSchema($page): string {
    $items = [];
    $position = 1;
    foreach ($page->parents as $parent) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $parent->title,
            'item' => $parent->httpUrl,
        ];
    }
    $items[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $page->title,
    ];

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ];

    return "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
}
```

## XML Sitemap

Use the **MarkupSitemap** module (auto-generates at `/sitemap.xml`).

Configure to exclude:
- Admin pages
- 404 template
- Utility/system pages
- Any `noindex` pages

## robots.txt

Place in the site root:
```
User-agent: *
Allow: /
Disallow: /processwire/
Disallow: /site/assets/files/
Disallow: /site/assets/cache/

Sitemap: https://www.yourdomain.com/sitemap.xml
```

## Heading Hierarchy

- **One H1 per page** — always the page title
- **H2** for main content sections
- **H3** for subsections within H2
- Never skip levels (H1 → H3 without H2)
- Navigation links are not headings

## Image SEO

- Every image must have an `alt` attribute (use PW image description field)
- File names should be descriptive: `team-photo-office.jpg` not `IMG_4523.jpg`
- Use `width` and `height` attributes to prevent layout shift
- Lazy load below-fold images with `loading="lazy"`
- Serve appropriately sized images (don't serve 4000px when 1200px max needed)

## URL Structure

ProcessWire handles this naturally via the page tree:
- Clean, readable URLs: `/services/web-design/`
- No file extensions, no query parameters for content pages
- Keep URLs shallow (max 3 levels where possible)
- Use hyphens, not underscores

## Redirects (Site Migration)

If replacing an existing site, map old URLs to new ones. Use the **ProcessRedirects** module or `.htaccess`:

```apache
# In .htaccess
Redirect 301 /old-page/ /new-page/
Redirect 301 /old-section/old-page/ /new-section/new-page/
```

## Performance Impact on SEO

- Core Web Vitals directly affect rankings
- See `.claude/instructions/performance-tuning.md` for optimisation details
- Key metrics: LCP (Largest Contentful Paint), CLS (Cumulative Layout Shift), INP (Interaction to Next Paint)

## Google Search Console Setup

After launch:
1. Verify domain ownership (DNS TXT record or HTML file)
2. Submit sitemap URL
3. Check for crawl errors
4. Monitor Core Web Vitals
5. Set preferred domain (www vs non-www)

## Analytics

Recommend privacy-respecting options:
- **Plausible Analytics** — lightweight, GDPR-friendly, no cookie banner needed
- **Fathom Analytics** — similar to Plausible
- **Google Analytics 4** — if the client specifically requires it (needs cookie consent)
