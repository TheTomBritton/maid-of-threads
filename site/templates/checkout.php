<?php namespace ProcessWire;

/**
 * Checkout — Order Review & Stripe Payment
 *
 * Redirects to cart if basket is empty. On POST, creates a Stripe
 * Checkout Session with line items (prices in pence) and redirects
 * the customer to Stripe's hosted payment page. GB-only shipping.
 */

$extra_head = '';
$extra_foot = '';
$hero = '';

$cart = getCart();
$totals = getCartTotals();

// Redirect to cart if empty
if (empty($cart)) {
    $session->redirect($cart_page->url);
}

$confirmation_page = $pages->get("template=order-confirmation");
$currency = strtolower($config->shopCurrency ?? 'GBP');
$currency_symbol = $config->shopCurrencySymbol ?? '£';

// -----------------------------------------------------------------
// POST handler — create Stripe Checkout Session
// -----------------------------------------------------------------
if ($input->requestMethod('POST')) {

    // Validate CSRF
    if (!$session->CSRF->validate()) {
        $session->redirect($page->url);
    }

    // Load Stripe SDK
    require_once $config->paths->root . 'vendor/autoload.php';
    \Stripe\Stripe::setApiKey($config->stripeSecretKey);

    // Build line items from cart
    $line_items = [];
    foreach ($cart as $product_id => $quantity) {
        $product = $pages->get("template=product, id=$product_id");
        if (!$product->id) continue;

        $image = $product->product_gallery->first();
        $base_url = rtrim($pages->get('/')->httpUrl, '/');
        $images = $image ? [$base_url . $image->size(400, 400)->url] : [];

        $line_items[] = [
            'price_data' => [
                'currency'     => $currency,
                'unit_amount'  => (int) round($product->product_price * 100), // Convert to pence
                'product_data' => [
                    'name'        => $product->title,
                    'description' => $product->product_sku ? "SKU: {$product->product_sku}" : '',
                    'images'      => $images,
                ],
            ],
            'quantity' => (int) $quantity,
        ];
    }

    // Add shipping as a line item if applicable
    if ($totals['shipping'] > 0) {
        $line_items[] = [
            'price_data' => [
                'currency'     => $currency,
                'unit_amount'  => (int) round($totals['shipping'] * 100),
                'product_data' => [
                    'name' => 'Standard Delivery',
                ],
            ],
            'quantity' => 1,
        ];
    }

    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $line_items,
            'mode'                 => 'payment',
            'customer_email'       => $input->post('email', 'email') ?: null,
            'success_url'          => $confirmation_page->httpUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'           => $page->httpUrl,
            'shipping_address_collection' => [
                'allowed_countries' => ['GB'],
            ],
        ]);

        // Redirect to Stripe
        header('Location: ' . $checkout_session->url, true, 303);
        exit;

    } catch (\Exception $e) {
        $error_message = 'Something went wrong creating your payment session. Please try again.';
        $log->error("Stripe checkout error: " . $e->getMessage());
    }
}

// -----------------------------------------------------------------
// Display checkout page (delayed output pattern)
// -----------------------------------------------------------------

// Resolve cart items for display
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

ob_start();
?>
<section class="py-8 lg:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">

        <!-- Breadcrumbs -->
        <?= renderBreadcrumbs($page) ?>

        <h1 class="text-3xl lg:text-4xl font-bold text-stone-900 mt-4 mb-8">Checkout</h1>

        <!-- Error message -->
        <?php if (!empty($error_message)): ?>
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <?= $sanitizer->entities($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

            <!-- Order review (3/5 width) -->
            <div class="lg:col-span-3">
                <h2 class="text-lg font-bold text-stone-900 mb-4">Order Review</h2>

                <div class="space-y-3">
                    <?php foreach ($cart_items as $item):
                        $product = $item['product'];
                        $qty = $item['quantity'];
                        $line_total = $product->product_price * $qty;
                        $image = $product->product_gallery->first();
                    ?>
                        <div class="flex items-center gap-4 p-3 rounded-lg bg-white border border-stone-200">
                            <!-- Thumbnail -->
                            <div class="flex-shrink-0 w-14 h-14 rounded-md overflow-hidden bg-stone-100">
                                <?php if ($image): ?>
                                    <?= renderImage($image, [56, 112], '56px', true) ?>
                                <?php endif; ?>
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-stone-900 line-clamp-1"><?= $product->title ?></p>
                                <p class="text-xs text-stone-500">Qty: <?= $qty ?></p>
                            </div>

                            <!-- Price -->
                            <p class="text-sm font-semibold text-stone-900"><?= formatPrice($line_total) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Edit basket link -->
                <a href="<?= $cart_page->url ?>"
                   class="mt-4 inline-flex items-center text-sm text-stone-500 hover:text-rose-600 transition-colors">
                    &larr; Edit basket
                </a>
            </div>

            <!-- Payment form & summary (2/5 width) -->
            <div class="lg:col-span-2">
                <div class="sticky top-24 p-6 rounded-xl bg-stone-50 border border-stone-200">
                    <h2 class="text-lg font-bold text-stone-900 mb-4">Payment</h2>

                    <!-- Totals -->
                    <dl class="space-y-2 text-sm mb-6">
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
                        <div class="flex justify-between pt-2 border-t border-stone-200">
                            <dt class="text-base font-bold text-stone-900">Total</dt>
                            <dd class="text-base font-bold text-stone-900"><?= formatPrice($totals['total']) ?></dd>
                        </div>
                    </dl>

                    <!-- Checkout form -->
                    <form action="<?= $page->url ?>" method="post">
                        <?= $session->CSRF->renderInput() ?>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-stone-700 mb-1">
                                Email address
                            </label>
                            <input type="email" name="email" id="email" required
                                   placeholder="you@example.com"
                                   class="block w-full rounded-lg border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm placeholder:text-stone-400 focus:border-rose-500 focus:ring-rose-500">
                            <p class="mt-1 text-xs text-stone-400">We'll send your order confirmation here.</p>
                        </div>

                        <!-- Pay button -->
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Pay with Stripe
                        </button>
                    </form>

                    <!-- Security note -->
                    <p class="mt-4 text-xs text-center text-stone-400">
                        Secure payment powered by Stripe. Your card details are never stored on our servers.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>
<?php
$content = ob_get_clean();
