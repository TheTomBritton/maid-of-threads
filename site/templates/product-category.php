<?php namespace ProcessWire;

/**
 * Product Category — Filtered Product Listing
 *
 * Displays products belonging to the current category page.
 * Supports HTMX partial responses for pagination without
 * full page reloads, mirroring the shop.php pattern.
 */

// Build product selector filtered to this category
$selector = "template=product, product_category=$page, sort=-created, limit=12";
$products = $pages->find($selector);

// Sibling categories for cross-navigation
$categories = $page->siblings("template=product-category");

// -----------------------------------------------------------------
// AJAX partial: return only the grid HTML, skip _main.php wrapper
// -----------------------------------------------------------------
if ($input->get->ajax) {
    ob_start();
    if ($products->count) {
        foreach ($products as $product) {
            echo renderProductCard($product);
        }

        // Pagination (HTMX-aware)
        if ($products->getTotal() > $products->getLimit()) {
            $pager = $products->renderPager([
                'nextItemLabel'     => 'Next &rarr;',
                'previousItemLabel' => '&larr; Previous',
                'listMarkup'        => '<nav aria-label="Pagination" class="mt-10 flex justify-center"><ul class="inline-flex items-center gap-1">{out}</ul></nav>',
                'itemMarkup'        => '<li>{out}</li>',
                'linkMarkup'        => '<a href="{url}&ajax=1" hx-get="{url}&ajax=1" hx-target="#product-grid" hx-swap="innerHTML" class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium text-stone-600 hover:bg-rose-50 hover:text-rose-700 transition-colors">{out}</a>',
                'currentItemMarkup' => '<li><span class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium bg-rose-600 text-white">{out}</span></li>',
            ]);
            echo $pager;
        }
    } else {
        echo '<div class="col-span-full py-16 text-center">';
        echo '  <p class="text-stone-500 text-lg">No products found in this category.</p>';
        echo '</div>';
    }
    echo ob_get_clean();
    return; // Skip _main.php
}

// -----------------------------------------------------------------
// Full page output (delayed output pattern)
// -----------------------------------------------------------------

$extra_head = '';
$extra_foot = '';
$hero = '';

ob_start();
?>
<section class="py-8 lg:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumbs -->
        <?= renderBreadcrumbs($page) ?>

        <!-- Page heading -->
        <div class="mb-8">
            <h1 class="text-3xl lg:text-4xl font-bold text-stone-900"><?= $page->title ?></h1>
            <?php if ($page->summary): ?>
                <p class="mt-2 text-lg text-stone-600"><?= $page->summary ?></p>
            <?php endif; ?>
            <p class="mt-1 text-sm text-stone-400"><?= $products->getTotal() ?> product<?= $products->getTotal() !== 1 ? 's' : '' ?></p>
        </div>

        <!-- Category navigation -->
        <nav aria-label="Product categories" class="mb-8 flex flex-wrap gap-2">
            <a href="<?= $shop_page->url ?>"
               class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-stone-100 text-stone-700 hover:bg-rose-50 hover:text-rose-700 transition-colors">
                All Products
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= $cat->url ?>"
                   class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors
                          <?= $cat->id === $page->id ? 'bg-rose-600 text-white' : 'bg-stone-100 text-stone-700 hover:bg-rose-50 hover:text-rose-700' ?>">
                    <?= $cat->title ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Product grid -->
        <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php if ($products->count): ?>
                <?php foreach ($products as $product): ?>
                    <?= renderProductCard($product) ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-16 text-center">
                    <p class="text-stone-500 text-lg">No products found in this category.</p>
                    <a href="<?= $shop_page->url ?>" class="mt-4 inline-flex items-center text-rose-600 hover:text-rose-700 font-medium">
                        &larr; Browse all products
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($products->getTotal() > $products->getLimit()): ?>
            <?= $products->renderPager([
                'nextItemLabel'     => 'Next &rarr;',
                'previousItemLabel' => '&larr; Previous',
                'listMarkup'        => '<nav aria-label="Pagination" class="mt-10 flex justify-center"><ul class="inline-flex items-center gap-1">{out}</ul></nav>',
                'itemMarkup'        => '<li>{out}</li>',
                'linkMarkup'        => '<a href="{url}" hx-get="{url}?ajax=1" hx-target="#product-grid" hx-swap="innerHTML" class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium text-stone-600 hover:bg-rose-50 hover:text-rose-700 transition-colors">{out}</a>',
                'currentItemMarkup' => '<li><span class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium bg-rose-600 text-white">{out}</span></li>',
            ]) ?>
        <?php endif; ?>

    </div>
</section>
<?php
$content = ob_get_clean();
