<?php namespace ProcessWire;

/**
 * _func.php — Reusable helper functions
 *
 * Included by _init.php, available in all templates.
 */

// ──────────────────────────────────────────────
// Images
// ──────────────────────────────────────────────

/**
 * Render a responsive image with srcset and lazy loading
 */
function renderImage(?Pageimage $image, array $widths = [400, 800, 1200], string $sizes = '100vw', bool $lazy = true): string {
    if (!$image) return '';

    $srcset = [];
    foreach ($widths as $w) {
        $resized = $image->width($w);
        $srcset[] = "{$resized->url} {$w}w";
    }

    $default = $image->width($widths[1] ?? 800);
    $alt = wire('sanitizer')->entities($image->description);
    $loading = $lazy ? " loading='lazy'" : " fetchpriority='high'";

    return "<img src='{$default->url}' srcset='" . implode(', ', $srcset) . "' sizes='{$sizes}' alt='{$alt}' width='{$default->width}' height='{$default->height}'{$loading}>";
}

// ──────────────────────────────────────────────
// Navigation
// ──────────────────────────────────────────────

/**
 * Render breadcrumb navigation with schema markup
 */
function renderBreadcrumbs(Page $page): string {
    if ($page->id === wire('pages')->get('/')->id) return '';

    $items = [];
    $schemaItems = [];
    $position = 1;

    foreach ($page->parents as $parent) {
        $items[] = "<li><a href='{$parent->url}' class='text-surface-400 hover:text-brand-600'>{$parent->title}</a></li>";
        $schemaItems[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $parent->title,
            'item' => $parent->httpUrl,
        ];
    }

    $items[] = "<li class='text-surface-600' aria-current='page'>{$page->title}</li>";
    $schemaItems[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $page->title,
    ];

    $schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $schemaItems,
    ], JSON_UNESCAPED_SLASHES);

    $out = "<nav aria-label='Breadcrumb' class='mb-6'>";
    $out .= "<ol class='flex items-center gap-2 text-sm'>";
    $out .= implode("<li class='text-surface-300'>/</li>", $items);
    $out .= "</ol></nav>";
    $out .= "<script type='application/ld+json'>{$schema}</script>";

    return $out;
}

// ──────────────────────────────────────────────
// Text
// ──────────────────────────────────────────────

/**
 * Truncate text to a given length, respecting word boundaries
 */
function truncate(string $text, int $length = 160, string $suffix = '&hellip;'): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    $truncated = mb_substr($text, 0, $length);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return $truncated . $suffix;
}

// ──────────────────────────────────────────────
// Products
// ──────────────────────────────────────────────

/**
 * Render a product card for shop listings
 */
function renderProductCard(Page $product): string {
    $config = wire('config');
    $symbol = $config->shopCurrencySymbol;

    $out = "<article class='card group'>";

    if ($product->featured_image) {
        $thumb = $product->featured_image->size(400, 500);
        $out .= "<a href='{$product->url}' class='card-image block relative overflow-hidden'>";
        $out .= "<img src='{$thumb->url}' alt='" . wire('sanitizer')->entities($product->title) . "' width='400' height='500' loading='lazy' class='group-hover:scale-105 transition-transform duration-300'>";

        if ($product->product_in_stock === 0) {
            $out .= "<span class='badge-out-of-stock absolute top-2 right-2'>Sold out</span>";
        }

        $out .= "</a>";
    }

    $out .= "<div class='card-body'>";

    if ($product->product_category && $product->product_category->count()) {
        $out .= "<p class='text-xs uppercase tracking-wide text-surface-400 mb-1'>";
        $cats = [];
        foreach ($product->product_category as $cat) {
            $cats[] = $cat->title;
        }
        $out .= implode(', ', $cats);
        $out .= "</p>";
    }

    $out .= "<h3 class='text-base font-semibold mb-2'><a href='{$product->url}' class='text-surface-900 no-underline hover:text-brand-600'>{$product->title}</a></h3>";
    $out .= "<p class='price'>{$symbol}" . number_format($product->product_price, 2) . "</p>";

    $out .= "</div>";
    $out .= "</article>";

    return $out;
}

/**
 * Render Product JSON-LD schema
 */
function renderProductSchema(Page $product): string {
    $config = wire('config');

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->title,
        'description' => $product->get('seo_description|summary|'),
        'url' => $product->httpUrl,
        'offers' => [
            '@type' => 'Offer',
            'price' => number_format($product->product_price, 2, '.', ''),
            'priceCurrency' => strtoupper($config->shopCurrency),
            'availability' => $product->product_in_stock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ],
    ];

    if ($product->product_sku) $schema['sku'] = $product->product_sku;
    if ($product->featured_image) $schema['image'] = $product->featured_image->httpUrl;

    return "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
}

// ──────────────────────────────────────────────
// Cart
// ──────────────────────────────────────────────

/**
 * Get current cart from session
 */
function getCart(): array {
    return wire('session')->get('cart') ?: [];
}

/**
 * Calculate cart totals
 *
 * Cart is stored as [ product_id => quantity ] so prices must be
 * looked up from the database to prevent client-side tampering.
 */
function getCartTotals(): array {
    $config = wire('config');
    $pages = wire('pages');
    $cart = getCart();
    $subtotal = 0;
    $itemCount = 0;

    foreach ($cart as $product_id => $quantity) {
        $product = $pages->get("template=product, id=$product_id");
        if (!$product->id) continue;
        $subtotal += $product->product_price * (int) $quantity;
        $itemCount += (int) $quantity;
    }

    $shipping = ($subtotal >= $config->shopFreeShippingThreshold || $subtotal === 0)
        ? 0
        : $config->shopFlatShipping;

    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $subtotal + $shipping,
        'count' => $itemCount,
    ];
}

/**
 * Format a price with currency symbol
 */
function formatPrice(float $amount): string {
    $symbol = wire('config')->shopCurrencySymbol;
    return $symbol . number_format($amount, 2);
}

// ──────────────────────────────────────────────
// Blog
// ──────────────────────────────────────────────

/**
 * Render a blog post card for listings
 */
function renderPostCard(Page $post): string {
    $out = "<article class='card group'>";

    if ($post->featured_image) {
        $thumb = $post->featured_image->size(600, 400);
        $out .= "<a href='{$post->url}' class='card-image block overflow-hidden'>";
        $out .= "<img src='{$thumb->url}' alt='" . wire('sanitizer')->entities($post->title) . "' width='600' height='400' loading='lazy' class='group-hover:scale-105 transition-transform duration-300'>";
        $out .= "</a>";
    }

    $out .= "<div class='card-body'>";

    $date = date('j F Y', $post->getUnformatted('date'));
    $out .= "<div class='flex items-center gap-3 text-sm text-surface-400 mb-2'>";
    $out .= "<time datetime='" . date('Y-m-d', $post->getUnformatted('date')) . "'>{$date}</time>";

    if ($post->blog_categories && $post->blog_categories->count()) {
        $out .= "<span>&middot;</span>";
        $cats = [];
        foreach ($post->blog_categories as $cat) {
            $cats[] = "<a href='{$cat->url}' class='hover:text-brand-600'>{$cat->title}</a>";
        }
        $out .= implode(', ', $cats);
    }
    $out .= "</div>";

    $out .= "<h3 class='text-lg font-semibold mb-2'><a href='{$post->url}' class='text-surface-900 no-underline hover:text-brand-600'>{$post->title}</a></h3>";

    if ($post->summary) {
        $out .= "<p class='text-surface-500 text-sm'>" . truncate($post->summary, 120) . "</p>";
    }

    $out .= "</div>";
    $out .= "</article>";

    return $out;
}

/**
 * Render Article JSON-LD schema
 */
function renderArticleSchema(Page $page): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $page->title,
        'description' => $page->get('seo_description|summary|'),
        'datePublished' => date('c', $page->getUnformatted('date')),
        'dateModified' => date('c', $page->modified),
        'url' => $page->httpUrl,
    ];

    if ($page->featured_image) $schema['image'] = $page->featured_image->httpUrl;

    return "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
}

// ──────────────────────────────────────────────
// Generic
// ──────────────────────────────────────────────

/**
 * Render a generic page card for listings
 */
function renderPageCard(Page $item, bool $showImage = true, bool $showSummary = true): string {
    $out = "<article class='card group'>";

    if ($showImage && $item->featured_image) {
        $thumb = $item->featured_image->size(600, 400);
        $out .= "<a href='{$item->url}' class='card-image block overflow-hidden'>";
        $out .= "<img src='{$thumb->url}' alt='" . wire('sanitizer')->entities($item->title) . "' width='600' height='400' loading='lazy' class='group-hover:scale-105 transition-transform duration-300'>";
        $out .= "</a>";
    }

    $out .= "<div class='card-body'>";
    $out .= "<h3 class='text-lg font-semibold'><a href='{$item->url}' class='text-surface-900 no-underline hover:text-brand-600'>{$item->title}</a></h3>";

    if ($showSummary && $item->summary) {
        $out .= "<p class='text-surface-500 mt-2'>" . truncate($item->summary, 120) . "</p>";
    }

    $out .= "</div>";
    $out .= "</article>";

    return $out;
}
