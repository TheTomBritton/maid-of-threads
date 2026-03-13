<?php namespace ProcessWire;

/**
 * Cart — Shopping Basket
 *
 * Handles all cart mutations (add/update/remove/clear) via POST
 * with CSRF protection and Post-Redirect-Get pattern. Also serves
 * an HTMX badge endpoint and JSON API for cart data.
 */

$extra_head = '';
$extra_foot = '';
$hero = '';

// -----------------------------------------------------------------
// POST action handler — mutate cart then redirect (PRG pattern)
// -----------------------------------------------------------------
if ($input->requestMethod('POST')) {

    // Validate CSRF token
    if (!$session->CSRF->validate()) {
        $session->redirect($page->url);
    }

    $action = $input->post('action', 'text');
    $cart = getCart();

    switch ($action) {

        case 'add':
            $product_id = $input->post('product_id', 'int');
            $quantity = $input->post('quantity', 'int') ?: 1;
            $product = $pages->get("template=product, id=$product_id");

            if ($product->id && $product->product_stock > 0) {
                $key = (string) $product->id;
                $existing_qty = isset($cart[$key]) ? (int) $cart[$key] : 0;
                $new_qty = min($existing_qty + $quantity, $product->product_stock);
                $cart[$key] = $new_qty;
                $session->set('cart', $cart);
                $session->set('cart_message', "{$product->title} added to your basket.");
            }
            break;

        case 'update':
            $product_id = $input->post('product_id', 'int');
            $quantity = $input->post('quantity', 'int');
            $key = (string) $product_id;

            if ($quantity > 0 && isset($cart[$key])) {
                $product = $pages->get("template=product, id=$product_id");
                if ($product->id) {
                    $cart[$key] = min($quantity, $product->product_stock);
                    $session->set('cart', $cart);
                }
            } elseif ($quantity <= 0) {
                unset($cart[$key]);
                $session->set('cart', $cart);
            }
            break;

        case 'remove':
            $product_id = $input->post('product_id', 'int');
            $key = (string) $product_id;
            unset($cart[$key]);
            $session->set('cart', $cart);
            $session->set('cart_message', 'Item removed from your basket.');
            break;

        case 'clear':
            $session->set('cart', []);
            $session->set('cart_message', 'Your basket has been cleared.');
            break;
    }

    // Post-Redirect-Get
    $session->redirect($page->url);
}

// -----------------------------------------------------------------
// HTMX badge endpoint — returns an HTML fragment (or empty)
// -----------------------------------------------------------------
$get_action = $sanitizer->text($input->get('action'));

if ($get_action === 'badge') {
    // Prevent _main.php from wrapping this fragment in the full page layout
    header('HX-Reswap: innerHTML');
    $totals = getCartTotals();
    $count = (int) $totals['count'];
    if ($count > 0) {
        echo '<span class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">'
            . $count . '</span>';
    }
    die();
}

// -----------------------------------------------------------------
// JSON API endpoints (for future integrations)
// -----------------------------------------------------------------
if ($get_action && $config->ajax) {
    header('Content-Type: application/json');

    if ($get_action === 'count') {
        $totals = getCartTotals();
        echo json_encode(['count' => $totals['count']]);
        die();
    }

    // Full cart JSON
    $cart = getCart();
    $totals = getCartTotals();
    $items = [];

    foreach ($cart as $product_id => $quantity) {
        $product = $pages->get("template=product, id=$product_id");
        if (!$product->id) continue;

        $image = $product->product_gallery->first();
        $items[] = [
            'id'        => $product->id,
            'title'     => $product->title,
            'url'       => $product->url,
            'sku'       => $product->product_sku ?: '',
            'price'     => (float) $product->product_price,
            'quantity'  => (int) $quantity,
            'lineTotal' => (float) $product->product_price * (int) $quantity,
            'thumbnail' => $image ? $image->size(80, 80)->url : '',
        ];
    }

    echo json_encode([
        'items'    => $items,
        'subtotal' => $totals['subtotal'],
        'shipping' => $totals['shipping'],
        'total'    => $totals['total'],
        'count'    => $totals['count'],
    ]);
    die();
}

// -----------------------------------------------------------------
// Display cart (delayed output pattern)
// -----------------------------------------------------------------

$cart = getCart();
$totals = getCartTotals();
$cart_message = $session->get('cart_message');
$session->remove('cart_message');

// Resolve cart items to page objects
$cart_items = [];
foreach ($cart as $product_id => $quantity) {
    $product = $pages->get("template=product, id=$product_id");
    if ($product->id) {
        $cart_items[] = [
            'product'  => $product,
            'quantity' => (int) $quantity,
        ];
    }
}

$free_threshold = $config->shopFreeShippingThreshold ?? 0;
$checkout_page = $pages->get("template=checkout");

ob_start();
?>
<section class="py-8 lg:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumbs -->
        <?= renderBreadcrumbs($page) ?>

        <h1 class="text-3xl lg:text-4xl font-bold text-stone-900 mt-4 mb-8">Your Basket</h1>

        <!-- Flash message (auto-dismisses via vanilla JS in _main.php) -->
        <?php if ($cart_message): ?>
            <div class="flash-dismiss mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                <?= $sanitizer->entities($cart_message) ?>
            </div>
        <?php endif; ?>

        <?php if (count($cart_items)): ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Cart items (2/3 width) -->
                <div class="lg:col-span-2 space-y-4">

                    <?php foreach ($cart_items as $item):
                        $product = $item['product'];
                        $qty = $item['quantity'];
                        $image = $product->product_gallery->first();
                        $line_total = $product->product_price * $qty;
                    ?>
                        <div class="flex gap-4 p-4 rounded-xl border border-stone-200 bg-white">

                            <!-- Thumbnail -->
                            <a href="<?= $product->url ?>" class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-stone-100">
                                <?php if ($image): ?>
                                    <?= renderImage($image, [80, 160], '80px', true) ?>
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-stone-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <a href="<?= $product->url ?>" class="font-medium text-stone-900 hover:text-rose-600 transition-colors line-clamp-1">
                                            <?= $product->title ?>
                                        </a>
                                        <?php if ($product->product_sku): ?>
                                            <p class="text-xs text-stone-400 mt-0.5">SKU: <?= $product->product_sku ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-stone-600 mt-1"><?= formatPrice($product->product_price) ?> each</p>
                                    </div>
                                    <p class="text-sm font-semibold text-stone-900 whitespace-nowrap"><?= formatPrice($line_total) ?></p>
                                </div>

                                <!-- Quantity & remove -->
                                <div class="flex items-center justify-between mt-3">
                                    <form action="<?= $page->url ?>" method="post" class="flex items-center gap-2">
                                        <?= $session->CSRF->renderInput() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= $product->id ?>">
                                        <label for="qty-<?= $product->id ?>" class="sr-only">Quantity</label>
                                        <select name="quantity" id="qty-<?= $product->id ?>"
                                                onchange="this.form.submit()"
                                                class="block w-16 rounded-md border-stone-300 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500">
                                            <?php for ($i = 1; $i <= min(10, $product->product_stock); $i++): ?>
                                                <option value="<?= $i ?>" <?= $i === $qty ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </form>

                                    <form action="<?= $page->url ?>" method="post">
                                        <?= $session->CSRF->renderInput() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= $product->id ?>">
                                        <button type="submit"
                                                class="text-sm text-stone-400 hover:text-rose-600 transition-colors"
                                                aria-label="Remove <?= $sanitizer->entities($product->title) ?> from basket">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Clear cart -->
                    <form action="<?= $page->url ?>" method="post" class="text-right">
                        <?= $session->CSRF->renderInput() ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit"
                                class="text-sm text-stone-400 hover:text-rose-600 transition-colors">
                            Clear basket
                        </button>
                    </form>
                </div>

                <!-- Order summary sidebar (1/3 width) -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 p-6 rounded-xl bg-stone-50 border border-stone-200">
                        <h2 class="text-lg font-bold text-stone-900 mb-4">Order Summary</h2>

                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-stone-600">Subtotal</dt>
                                <dd class="font-medium text-stone-900"><?= formatPrice($totals['subtotal']) ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-600">Delivery</dt>
                                <dd class="font-medium text-stone-900">
                                    <?= $totals['shipping'] > 0 ? formatPrice($totals['shipping']) : '<span class="text-emerald-600">Free</span>' ?>
                                </dd>
                            </div>

                            <?php if ($free_threshold > 0 && $totals['shipping'] > 0): ?>
                                <?php $remaining = $free_threshold - $totals['subtotal']; ?>
                                <?php if ($remaining > 0): ?>
                                    <div class="pt-2 border-t border-stone-200">
                                        <p class="text-xs text-stone-500">
                                            Spend <?= formatPrice($remaining) ?> more for free delivery
                                        </p>
                                        <div class="mt-1.5 w-full bg-stone-200 rounded-full h-1.5">
                                            <div class="bg-rose-500 h-1.5 rounded-full transition-all"
                                                 style="width: <?= min(100, ($totals['subtotal'] / $free_threshold) * 100) ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="flex justify-between pt-3 border-t border-stone-200">
                                <dt class="text-base font-bold text-stone-900">Total</dt>
                                <dd class="text-base font-bold text-stone-900"><?= formatPrice($totals['total']) ?></dd>
                            </div>
                        </dl>

                        <!-- Checkout button -->
                        <?php if ($checkout_page->id): ?>
                            <a href="<?= $checkout_page->url ?>"
                               class="mt-6 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors">
                                Proceed to Checkout
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        <?php endif; ?>

                        <!-- Continue shopping -->
                        <a href="<?= $shop_page->url ?>"
                           class="mt-3 w-full inline-flex items-center justify-center text-sm text-stone-600 hover:text-rose-600 transition-colors">
                            &larr; Continue shopping
                        </a>
                    </div>
                </div>

            </div>

        <?php else: ?>

            <!-- Empty cart -->
            <div class="py-16 text-center">
                <svg class="mx-auto w-16 h-16 text-stone-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <h2 class="text-xl font-semibold text-stone-700 mb-2">Your basket is empty</h2>
                <p class="text-stone-500 mb-6">Looks like you haven't added anything yet.</p>
                <a href="<?= $shop_page->url ?>"
                   class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition-colors">
                    Start Shopping
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>

        <?php endif; ?>

    </div>
</section>
<?php
$content = ob_get_clean();
