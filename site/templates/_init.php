<?php namespace ProcessWire;

/**
 * _init.php — Auto-prepended before every template
 *
 * Sets up default region variables that templates can override.
 */

// Include helper functions
include_once('./_func.php');

// ──────────────────────────────────────────────
// Default region variables
// Templates override these as needed
// ──────────────────────────────────────────────

// Browser title — falls back through SEO title, then page title
$browser_title = $page->get('seo_title|title');

// Meta description — falls back through SEO description, summary, then empty
$meta_description = $page->get('seo_description|summary|');

// Body class — template name by default
$body_class = $page->template->name;

// Content regions — templates populate these
$content = '';
$sidebar = '';
$hero = '';

// Extra head/foot — for template-specific CSS/JS or structured data
$extra_head = '';
$extra_foot = '';

// Site-wide variables
$site_name = 'Maid of Threads';
$home = $pages->get('/');

// Shop pages — used in navigation and cart
$shop_page = $pages->get('/shop/');
$blog_page = $pages->get('/blog/');
$cart_page = $pages->get('/cart/');

// Cart item count from session (for header badge)
// Cart is stored as [ product_id => quantity ]
$cart = $session->get('cart') ?: [];
$cart_count = 0;
foreach ($cart as $qty) {
    $cart_count += (int) $qty;
}
