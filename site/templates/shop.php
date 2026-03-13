<?php namespace ProcessWire;

/**
 * Shop — Product Listing
 *
 * Displays the full product catalogue with category filtering via HTMX
 * and responsive grid layout. Supports AJAX partial responses for
 * seamless filter/pagination without full page reloads.
 */

// Gather all product categories for filter nav
$categories = $pages->find("template=product-category, sort=title");

// Determine active category filter (if any)
$active_category = $input->get('category', 'int');

// Build product selector
$selector = "template=product, sort=-created, limit=12";
if ($active_category) {
    $category_page = $pages->get($active_category);
    if ($category_page->id) {
        $selector .= ", product_category=$category_page";
    }
}

$products = $pages->find($selector);

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

// Hero / breadcrumbs
$hero = '';

// Build content
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
        </div>

        <!-- Category filter nav -->
        <nav aria-label="Product categories" class="mb-8 flex flex-wrap gap-2">
            <a href="<?= $page->url ?>?ajax=1"
               hx-get="<?= $page->url ?>?ajax=1"
               hx-target="#product-grid"
               hx-swap="innerHTML"
               class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors
                      <?= !$active_category ? 'bg-rose-600 text-white' : 'bg-stone-100 text-stone-700 hover:bg-rose-50 hover:text-rose-700' ?>">
                All
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= $page->url ?>?category=<?= $cat->id ?>&ajax=1"
                   hx-get="<?= $page->url ?>?category=<?= $cat->id ?>&ajax=1"
                   hx-target="#product-grid"
                   hx-swap="innerHTML"
                   class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium transition-colors
                          <?= $active_category == $cat->id ? 'bg-rose-600 text-white' : 'bg-stone-100 text-stone-700 hover:bg-rose-50 hover:text-rose-700' ?>">
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
                    <p class="text-stone-500 text-lg">No products found.</p>
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
                'linkMarkup'        => '<a href="{url}" hx-get="{url}&ajax=1" hx-target="#product-grid" hx-swap="innerHTML" class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium text-stone-600 hover:bg-rose-50 hover:text-rose-700 transition-colors">{out}</a>',
                'currentItemMarkup' => '<li><span class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium bg-rose-600 text-white">{out}</span></li>',
            ]) ?>
        <?php endif; ?>

    </div>
</section>
<?php
$content = ob_get_clean();
