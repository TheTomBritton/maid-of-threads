<?php namespace ProcessWire;

/**
 * Product — Single Product Page
 *
 * Two-column layout with image gallery, product details,
 * add-to-cart form, full description, related products, and
 * structured data for SEO.
 */

$extra_head = '';
$extra_foot = '';
$hero = '';

// Gather product images (main image + gallery)
$images = $page->product_gallery;
$main_image = $images->first();

// Related products: same category, exclude current, limit 4
$related = new PageArray();
if ($page->product_category && $page->product_category->count) {
    $cat_ids = $page->product_category->implode('|', 'id');
    $related = $pages->find("template=product, product_category=$cat_ids, id!=$page->id, sort=random, limit=4");
}

// Stock status
$in_stock = $page->product_stock > 0;
$low_stock = $in_stock && $page->product_stock <= 5;

// Shipping info
$free_threshold = $config->shopFreeShippingThreshold ?? 0;
$flat_shipping = $config->shopFlatShipping ?? 0;

// Schema markup
$extra_head .= renderProductSchema($page);

ob_start();
?>
<section class="py-8 lg:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumbs -->
        <?= renderBreadcrumbs($page) ?>

        <!-- Product detail -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mt-6">

            <!-- Left column: Image gallery -->
            <div id="product-gallery" class="space-y-4">
                <!-- Main image -->
                <div class="aspect-square bg-stone-100 rounded-2xl overflow-hidden">
                    <?php if ($images->count):
                        $first = $images->first(); ?>
                        <img id="gallery-main"
                             src="<?= $first->size(800, 800)->url ?>"
                             alt="<?= $sanitizer->entities($first->description ?: $page->title) ?>"
                             class="w-full h-full object-cover transition-opacity duration-200">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-stone-400">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thumbnail strip -->
                <?php if ($images->count > 1): ?>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        <?php foreach ($images as $i => $img): ?>
                            <button class="gallery-thumb flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden transition-all focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 <?= $i === 0 ? 'ring-2 ring-rose-500 ring-offset-2' : 'ring-1 ring-stone-200 hover:ring-rose-300' ?>"
                                    data-src="<?= $img->size(800, 800)->url ?>"
                                    data-alt="<?= $sanitizer->entities($img->description ?: $page->title) ?>">
                                <?= renderImage($img, [80, 160], '80px', true) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right column: Product info -->
            <div class="flex flex-col">

                <!-- Title & SKU -->
                <div class="mb-4">
                    <h1 class="text-2xl lg:text-3xl font-bold text-stone-900"><?= $page->title ?></h1>
                    <?php if ($page->product_sku): ?>
                        <p class="mt-1 text-sm text-stone-400">SKU: <?= $page->product_sku ?></p>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <div class="mb-6">
                    <span class="text-3xl font-bold text-stone-900"><?= formatPrice($page->product_price) ?></span>
                    <span class="ml-2 text-sm text-stone-500">inc. VAT</span>
                </div>

                <!-- Stock badge -->
                <div class="mb-6">
                    <?php if ($in_stock): ?>
                        <?php if ($low_stock): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                Low stock &mdash; only <?= $page->product_stock ?> left
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                In stock
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-stone-100 text-stone-500">
                            <span class="w-2 h-2 rounded-full bg-stone-400"></span>
                            Out of stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Add to cart form -->
                <?php if ($in_stock): ?>
                    <form action="<?= $cart_page->url ?>" method="post" class="mb-8">
                        <?= $session->CSRF->renderInput() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $page->id ?>">

                        <div class="flex items-end gap-4">
                            <div>
                                <label for="quantity" class="block text-sm font-medium text-stone-700 mb-1">Quantity</label>
                                <select name="quantity" id="quantity"
                                        class="block w-20 rounded-lg border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500">
                                    <?php for ($i = 1; $i <= min(10, $page->product_stock); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                                Add to Basket
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mb-8">
                        <button disabled
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-stone-300 px-6 py-2.5 text-sm font-semibold text-stone-500 cursor-not-allowed">
                            Out of Stock
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Summary -->
                <?php if ($page->summary): ?>
                    <div class="mb-6 text-stone-700 leading-relaxed">
                        <?= $page->summary ?>
                    </div>
                <?php endif; ?>

                <!-- Features list -->
                <?php if ($page->product_features): ?>
                    <div class="mb-6">
                        <h2 class="text-sm font-semibold text-stone-900 uppercase tracking-wider mb-3">Features</h2>
                        <ul class="space-y-2">
                            <?php foreach (explode("\n", $page->product_features) as $feature): ?>
                                <?php $feature = trim($feature); if (!$feature) continue; ?>
                                <li class="flex items-start gap-2 text-sm text-stone-700">
                                    <svg class="w-4 h-4 text-rose-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= $sanitizer->entities($feature) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Categories -->
                <?php if ($page->product_category && $page->product_category->count): ?>
                    <div class="mb-6">
                        <h2 class="text-sm font-semibold text-stone-900 uppercase tracking-wider mb-3">Categories</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($page->product_category as $cat): ?>
                                <a href="<?= $cat->url ?>"
                                   class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-stone-100 text-stone-700 hover:bg-rose-50 hover:text-rose-700 transition-colors">
                                    <?= $cat->title ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Shipping info box -->
                <div class="mt-auto p-4 rounded-xl bg-stone-50 border border-stone-200">
                    <h3 class="text-sm font-semibold text-stone-900 mb-2">Delivery Information</h3>
                    <ul class="space-y-1.5 text-sm text-stone-600">
                        <?php if ($free_threshold > 0): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Free delivery on orders over <?= formatPrice($free_threshold) ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($flat_shipping > 0): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Standard delivery: <?= formatPrice($flat_shipping) ?>
                            </li>
                        <?php endif; ?>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Dispatched within 1&ndash;3 working days
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <!-- Full description -->
        <?php if ($page->body): ?>
            <div class="mt-12 lg:mt-16">
                <h2 class="text-xl font-bold text-stone-900 mb-4">Description</h2>
                <div class="prose prose-stone max-w-none">
                    <?= $page->body ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Related products -->
        <?php if ($related->count): ?>
            <div class="mt-12 lg:mt-16">
                <h2 class="text-xl font-bold text-stone-900 mb-6">You Might Also Like</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <?php foreach ($related as $product): ?>
                        <?= renderProductCard($product) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php
$content = ob_get_clean();

// Vanilla JS gallery — swap main image on thumbnail click
if ($images->count > 1) {
    $extra_foot .= <<<'GALLERY'
<script>
(function() {
    var main = document.getElementById('gallery-main');
    if (!main) return;

    document.querySelectorAll('.gallery-thumb').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Crossfade the main image
            main.style.opacity = '0';
            setTimeout(function() {
                main.src = btn.dataset.src;
                main.alt = btn.dataset.alt;
                main.style.opacity = '1';
            }, 150);

            // Update active ring on thumbnails
            document.querySelectorAll('.gallery-thumb').forEach(function(b) {
                b.classList.remove('ring-2', 'ring-rose-500', 'ring-offset-2');
                b.classList.add('ring-1', 'ring-stone-200');
            });
            btn.classList.remove('ring-1', 'ring-stone-200');
            btn.classList.add('ring-2', 'ring-rose-500', 'ring-offset-2');
        });
    });
})();
</script>
GALLERY;
}
