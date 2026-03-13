# Template Development Guide

## File Structure

All templates live in `site/templates/`. This project uses the delayed output strategy with prepend/append files.

### Core Files (always present)

| File | Purpose |
|---|---|
| `_init.php` | Auto-prepended. Sets up default variables, includes helpers. |
| `_main.php` | Auto-appended. HTML wrapper — doctype, head, nav, footer. |
| `_func.php` | Reusable helper functions. Included by `_init.php`. |
| `home.php` | Homepage template. |
| `basic-page.php` | Generic content page. |
| `_404.php` | Custom 404 error page. |

### Region Variables

Templates communicate with `_main.php` by setting these variables:

```php
$browser_title     // <title> tag content
$meta_description  // Meta description
$body_class        // CSS class on <body>
$content           // Main content area
$sidebar           // Sidebar content (optional)
$hero              // Hero/banner section (optional)
$extra_head        // Additional <head> content (optional)
$extra_foot        // Additional scripts before </body> (optional)
```

## Building a Template

### Step-by-Step Process

1. **Create the PHP file** in `site/templates/` with a hyphenated name
2. **Add the namespace** — every file starts with `<?php namespace ProcessWire;`
3. **Build content** by setting region variables
4. **Use PW API** to fetch and display data
5. **Define fields** needed by this template in the exports
6. **Register the template** in `site/install/templates.json`

### Example: Service Page Template

```php
<?php namespace ProcessWire;

/**
 * Template: service.php
 * Displays a single service with description, image, and pricing.
 * Fields: title, body, summary, featured_image, service_price, service_features (repeater)
 */

// Build hero section
$hero = "<section class='hero'>";
$hero .= "<h1>{$page->title}</h1>";
if ($page->summary) {
    $hero .= "<p class='lead'>{$page->summary}</p>";
}
$hero .= "</section>";

// Main content
$content = '';

// Featured image
if ($page->featured_image) {
    $img = $page->featured_image;
    $content .= "<figure>";
    $content .= "<img src='{$img->width(1200)->url}' alt='{$img->description}' width='{$img->width(1200)->width}' height='{$img->width(1200)->height}' loading='lazy'>";
    if ($img->description) {
        $content .= "<figcaption>{$img->description}</figcaption>";
    }
    $content .= "</figure>";
}

// Body content
$content .= $page->body;

// Service features (repeater field)
if ($page->service_features->count()) {
    $content .= "<ul class='features'>";
    foreach ($page->service_features as $feature) {
        $content .= "<li>";
        $content .= "<strong>{$feature->feature_title}</strong>";
        $content .= "<p>{$feature->feature_description}</p>";
        $content .= "</li>";
    }
    $content .= "</ul>";
}

// Price
if ($page->service_price) {
    $content .= "<div class='pricing'>";
    $content .= "<p class='price'>From &pound;" . number_format($page->service_price, 2) . "</p>";
    $content .= "</div>";
}

// Sidebar: other services
$siblings = $page->siblings("id!={$page->id}, limit=5");
if ($siblings->count()) {
    $sidebar = "<h3>Other Services</h3><ul>";
    foreach ($siblings as $s) {
        $sidebar .= "<li><a href='{$s->url}'>{$s->title}</a></li>";
    }
    $sidebar .= "</ul>";
}
```

### Example: Listing/Index Template

```php
<?php namespace ProcessWire;

/**
 * Template: services-index.php
 * Lists all child service pages with summaries and images.
 * Fields: title, body
 */

$content = "<h1>{$page->title}</h1>";
$content .= $page->body;

$services = $page->children("sort=sort");

if ($services->count()) {
    $content .= "<div class='services-grid'>";
    foreach ($services as $service) {
        $content .= "<article class='service-card'>";

        if ($service->featured_image) {
            $thumb = $service->featured_image->size(600, 400);
            $content .= "<img src='{$thumb->url}' alt='{$thumb->description}' width='600' height='400' loading='lazy'>";
        }

        $content .= "<h2><a href='{$service->url}'>{$service->title}</a></h2>";

        if ($service->summary) {
            $content .= "<p>{$service->summary}</p>";
        }

        $content .= "<a href='{$service->url}' class='btn'>Learn more</a>";
        $content .= "</article>";
    }
    $content .= "</div>";
} else {
    $content .= "<p>No services found.</p>";
}
```

## Helper Functions (_func.php)

Keep reusable rendering logic in `_func.php`:

```php
<?php namespace ProcessWire;

/**
 * Render a responsive image with srcset
 *
 * @param Pageimage $image The image to render
 * @param array $sizes Widths to generate [400, 800, 1200]
 * @param string $defaultSize Default size attribute
 * @return string HTML img tag
 */
function renderImage($image, array $sizes = [400, 800, 1200], string $defaultSize = '100vw'): string {
    if (!$image) return '';

    $srcset = [];
    foreach ($sizes as $w) {
        $resized = $image->width($w);
        $srcset[] = "{$resized->url} {$w}w";
    }

    $default = $image->width($sizes[1] ?? 800);
    $alt = wire('sanitizer')->entities($image->description);

    return "<img src='{$default->url}' srcset='" . implode(', ', $srcset) . "' sizes='{$defaultSize}' alt='{$alt}' width='{$default->width}' height='{$default->height}' loading='lazy'>";
}

/**
 * Render breadcrumb navigation
 *
 * @param Page $page Current page
 * @return string HTML breadcrumb markup
 */
function renderBreadcrumbs($page): string {
    $out = "<nav aria-label='Breadcrumb'><ol class='breadcrumbs'>";
    foreach ($page->parents as $parent) {
        $out .= "<li><a href='{$parent->url}'>{$parent->title}</a></li>";
    }
    $out .= "<li aria-current='page'>{$page->title}</li>";
    $out .= "</ol></nav>";
    return $out;
}

/**
 * Truncate text to a given length, respecting word boundaries
 *
 * @param string $text Text to truncate
 * @param int $length Maximum character length
 * @param string $suffix Appended when truncated
 * @return string
 */
function truncate(string $text, int $length = 160, string $suffix = '&hellip;'): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, $length), ' ')) . $suffix;
}

/**
 * Get the primary navigation pages
 *
 * @return PageArray
 */
function getNavPages(): PageArray {
    return wire('pages')->get('/')->children('include=hidden');
}
```

## Image Handling Best Practices

1. **Always set width AND height** on `<img>` tags to prevent layout shift
2. **Use `loading="lazy"`** on below-fold images
3. **Provide alt text** via the image description field
4. **Use srcset** for responsive images (see helper function above)
5. **Optimise dimensions** — don't serve a 4000px image when 1200px is the maximum display size
6. **Use WebP** when the hosting environment supports it

```php
// Generate WebP variant (PW 3.0.132+)
$webp = $page->featured_image->width(1200, ['webpAdd' => true]);
// This creates both the original format and a .webp version
```

## Conditional Content Patterns

```php
// Field with fallback
$title = $page->get('seo_title|title');

// Only show section if field has content
if ($page->body) {
    $content .= "<div class='body-content'>{$page->body}</div>";
}

// Check for images before rendering gallery
if ($page->images->count()) {
    // Render gallery
}

// Check template type for conditional sections
if ($page->template->name === 'blog-post') {
    // Show date, author, etc.
}
```

## Error Handling

```php
// Custom 404 template (_404.php)
<?php namespace ProcessWire;

$browser_title = "Page Not Found";
$content = "<h1>Page not found</h1>";
$content .= "<p>Sorry, the page you're looking for doesn't exist.</p>";
$content .= "<p><a href='/'>Return to the homepage</a></p>";

// Throw 404 from within a template
if (!$item->id) {
    throw new Wire404Exception();
}
```
