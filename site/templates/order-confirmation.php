<?php namespace ProcessWire;

/**
 * Order Confirmation — Post-Payment Landing Page
 *
 * Retrieves the Stripe Checkout Session via the session_id query
 * parameter, verifies payment was successful, clears the cart,
 * and displays a confirmation or error message.
 */

$extra_head = '';
$extra_foot = '';
$hero = '';

$session_id = $input->get('session_id', 'text');
$payment_verified = false;
$stripe_session = null;
$error_message = '';

if ($session_id) {
    try {
        // Load Stripe SDK
        require_once $config->paths->root . 'vendor/autoload.php';
        \Stripe\Stripe::setApiKey($config->stripeSecretKey);

        $stripe_session = \Stripe\Checkout\Session::retrieve($session_id);

        if ($stripe_session->payment_status === 'paid') {
            $payment_verified = true;

            // Clear the cart now that payment is confirmed
            $session->set('cart', []);
        } else {
            $error_message = 'Your payment has not been confirmed yet. If you believe this is an error, please contact us.';
        }

    } catch (\Exception $e) {
        $error_message = 'We could not verify your payment. Please contact us with your order details.';
        $log->error("Stripe session retrieval error: " . $e->getMessage());
    }
} else {
    $error_message = 'No order session found. If you have just completed a purchase, please check your email for confirmation.';
}

$contact_page = $pages->get("template=contact");

ob_start();
?>
<section class="py-12 lg:py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-2xl">

        <?php if ($payment_verified): ?>

            <!-- Success state -->
            <div class="text-center">
                <!-- Green checkmark -->
                <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-3xl lg:text-4xl font-bold text-stone-900 mb-3">Order Confirmed!</h1>
                <p class="text-lg text-stone-600 mb-2">
                    Thank you for your purchase. Your order has been received and is being processed.
                </p>

                <?php if ($stripe_session && $stripe_session->customer_details->email): ?>
                    <p class="text-sm text-stone-500 mb-8">
                        A confirmation email has been sent to
                        <strong class="text-stone-700"><?= $sanitizer->entities($stripe_session->customer_details->email) ?></strong>.
                    </p>
                <?php endif; ?>

                <!-- Order details summary -->
                <div class="mt-8 p-6 rounded-xl bg-stone-50 border border-stone-200 text-left">
                    <h2 class="text-sm font-semibold text-stone-900 uppercase tracking-wider mb-4">Order Details</h2>
                    <dl class="space-y-2 text-sm">
                        <?php if ($stripe_session): ?>
                            <div class="flex justify-between">
                                <dt class="text-stone-600">Amount paid</dt>
                                <dd class="font-medium text-stone-900">
                                    <?= formatPrice($stripe_session->amount_total / 100) ?>
                                </dd>
                            </div>
                            <?php if ($stripe_session->customer_details->name): ?>
                                <div class="flex justify-between">
                                    <dt class="text-stone-600">Name</dt>
                                    <dd class="font-medium text-stone-900"><?= $sanitizer->entities($stripe_session->customer_details->name) ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($stripe_session->shipping_details && $stripe_session->shipping_details->address): ?>
                                <div class="flex justify-between">
                                    <dt class="text-stone-600">Delivery address</dt>
                                    <dd class="font-medium text-stone-900 text-right">
                                        <?php
                                        $addr = $stripe_session->shipping_details->address;
                                        $parts = array_filter([
                                            $addr->line1,
                                            $addr->line2,
                                            $addr->city,
                                            $addr->postal_code,
                                        ]);
                                        echo $sanitizer->entities(implode(', ', $parts));
                                        ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="<?= $shop_page->url ?>"
                       class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition-colors">
                        Continue Shopping
                    </a>
                    <?php if ($contact_page->id): ?>
                        <a href="<?= $contact_page->url ?>"
                           class="inline-flex items-center text-sm text-stone-600 hover:text-rose-600 transition-colors">
                            Need help? Contact us
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>

            <!-- Error state -->
            <div class="text-center">
                <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h1 class="text-3xl lg:text-4xl font-bold text-stone-900 mb-3">Something Went Wrong</h1>
                <p class="text-lg text-stone-600 mb-8">
                    <?= $sanitizer->entities($error_message) ?>
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <?php if ($contact_page->id): ?>
                        <a href="<?= $contact_page->url ?>"
                           class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition-colors">
                            Contact Us
                        </a>
                    <?php endif; ?>
                    <a href="<?= $shop_page->url ?>"
                       class="inline-flex items-center text-sm text-stone-600 hover:text-rose-600 transition-colors">
                        &larr; Back to shop
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</section>
<?php
$content = ob_get_clean();
