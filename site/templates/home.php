<?php namespace ProcessWire;

/**
 * home.php — Homepage template
 *
 * Delayed output: sets $hero and $content for _main.php
 * Displays hero banner, featured products, body content, and latest blog posts.
 */

$shop_page = $pages->get('/shop/');
$blog_page = $pages->get('/blog/');

// ─────────────────────────────────────────────
// Hero
// ─────────────────────────────────────────────

$hero = '
<section class="bg-brand-50">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8 lg:py-36">
        <div class="max-w-2xl">
            <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl lg:text-6xl">
                ' . $page->title . '
            </h1>';

if ($page->summary) {
    $hero .= '
            <p class="mt-6 text-lg leading-relaxed text-stone-600 sm:text-xl">
                ' . $page->summary . '
            </p>';
}

$hero .= '
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="' . ($shop_page->id ? $shop_page->url : '/shop/') . '"
                   class="inline-flex items-center rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Shop Now
                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="/about/"
                   class="inline-flex items-center rounded-md border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition-colors hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Our Story
                </a>
            </div>
        </div>
    </div>
</section>';

// ─────────────────────────────────────────────
// Content
// ─────────────────────────────────────────────

ob_start();

// Featured Products — 8 latest
$featured_products = $pages->find("template=product, sort=-created, limit=8");

if ($featured_products->count): ?>

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="flex items-end justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                Featured Products
            </h2>
            <p class="mt-2 text-stone-600">Our latest additions to the collection</p>
        </div>
        <?php if ($shop_page->id): ?>
            <a href="<?= $shop_page->url ?>"
               class="hidden text-sm font-medium text-brand-600 transition-colors hover:text-brand-700 sm:inline-flex sm:items-center">
                View all
                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        <?php endif; ?>
    </div>

    <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4 lg:gap-8">
        <?php foreach ($featured_products as $product): ?>
            <?= renderProductCard($product) ?>
        <?php endforeach; ?>
    </div>

    <?php if ($shop_page->id): ?>
        <div class="mt-8 text-center sm:hidden">
            <a href="<?= $shop_page->url ?>"
               class="inline-flex items-center text-sm font-medium text-brand-600 transition-colors hover:text-brand-700">
                View all products
                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    <?php endif; ?>
</section>

<?php endif;

// Body Content — rendered from the body field
if ($page->body): ?>

<section class="border-t border-stone-100">
    <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="prose prose-stone prose-brand mx-auto max-w-none">
            <?= $page->body ?>
        </div>
    </div>
</section>

<?php endif;

// Latest Blog Posts — 3 most recent
$latest_posts = $pages->find("template=blog-post, sort=-date, limit=3");

if ($latest_posts->count): ?>

<section class="border-t border-stone-100 bg-stone-50">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                    From the Blog
                </h2>
                <p class="mt-2 text-stone-600">News, tips, and behind-the-scenes stories</p>
            </div>
            <?php if ($blog_page->id): ?>
                <a href="<?= $blog_page->url ?>"
                   class="hidden text-sm font-medium text-brand-600 transition-colors hover:text-brand-700 sm:inline-flex sm:items-center">
                    All posts
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>

        <div class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($latest_posts as $post): ?>
                <?= renderPostCard($post) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php endif;

$content = ob_get_clean();
