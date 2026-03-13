# Ecommerce Guide for ProcessWire

## Overview

ProcessWire doesn't have built-in ecommerce, but its flexible page-based architecture makes it excellent for building shops. There are several approaches depending on complexity.

## Approach 1: Native Stripe Checkout (Recommended for Small Catalogues)

**Best for**: Small catalogues (under ~50 products), single currency, flat-rate shipping, no user accounts needed.

This is the simplest and most cost-effective approach. ProcessWire handles the product catalogue and session-based cart; Stripe Checkout handles payment on Stripe's hosted page.

### Architecture

```
/shop/                          (shop.php — product listing)
├── /shop/category-name/        (product-category.php — filtered listing)
│   ├── /shop/category/product/ (product.php — single product)
/cart/                          (cart.php — session-based cart)
/checkout/                      (checkout.php — Stripe redirect)
/order-confirmation/            (order-confirmation.php)
```

### Cart Using Sessions (Minimal Pattern)

Store only the page ID and quantity — look up prices from the database at checkout time. This avoids stale price data in the session.

```php
// Cart structure: [ page_id => quantity, ... ]
$cart = $session->get('cart') ?: [];
$productId = (int) $input->post->product_id;

if (isset($cart[$productId])) {
    $cart[$productId]++;
} else {
    $cart[$productId] = 1;
}

$session->set('cart', $cart);

// Calculate totals by looking up current prices
$total = 0;
foreach ($cart as $id => $qty) {
    $product = $pages->get($id);
    if ($product->id) {
        $total += $product->product_price * $qty;
    }
}
```

> **Gotcha**: Earlier versions of this guide stored price/title in the session alongside quantity. This creates bugs when prices change and adds complexity to the cart logic. The minimal `id => qty` pattern is simpler and always accurate.

### Stripe Checkout Integration

```bash
composer require stripe/stripe-php
```

```php
// In config.php — use env vars in production
$config->stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_...';
$config->stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_...';
$config->stripeWebhookSecret = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_...';
```

```php
// In checkout.php
\Stripe\Stripe::setApiKey($config->stripeSecretKey);

$lineItems = [];
foreach ($cart as $id => $qty) {
    $product = $pages->get($id);
    $lineItems[] = [
        'price_data' => [
            'currency' => 'gbp',
            'product_data' => ['name' => $product->title],
            'unit_amount' => (int)($product->product_price * 100),
        ],
        'quantity' => $qty,
    ];
}

$stripeSession = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => $lineItems,
    'mode' => 'payment',
    'success_url' => $pages->get('/order-confirmation/')->httpUrl . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $pages->get('/cart/')->httpUrl,
]);

// Redirect to Stripe
header("Location: {$stripeSession->url}");
exit;
```

### Stripe Webhooks (Essential for Production)

The success redirect is not reliable — customers may close their browser before reaching the confirmation page. Always implement webhooks for:
- Confirming payment and updating order status
- Decrementing stock counts
- Sending order confirmation emails
- Handling refunds

Create a `stripe-webhook.php` template:
```php
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sigHeader, $config->stripeWebhookSecret
    );
} catch (\Exception $e) {
    http_response_code(400);
    exit;
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    // Update stock, send confirmation email, log order
}

http_response_code(200);
```

> **Important**: The webhook template needs `noPrepend` and `noAppend` flags in the template settings, and should output no HTML.

## Approach 2: Snipcart (External Cart Service)

**Best for**: Small to medium product catalogues (under 500 products), clients who need quick setup.

Snipcart is an external service that adds cart and checkout to any website via JavaScript. ProcessWire handles the product catalogue; Snipcart handles cart, checkout, and payments.

### Setup

1. Sign up at snipcart.com (transaction-based pricing, no monthly fee for small volume)
2. Add the Snipcart script to `_main.php`:
   ```html
   <link rel="stylesheet" href="https://cdn.snipcart.com/themes/v3.6.1/default/snipcart.css" />
   <script async src="https://cdn.snipcart.com/themes/v3.6.1/default/snipcart.js"></script>
   <div hidden id="snipcart" data-api-key="YOUR_PUBLIC_API_KEY"></div>
   ```
3. Add buy buttons to product templates:
   ```html
   <button class="snipcart-add-item"
       data-item-id="<?= $page->name ?>"
       data-item-price="<?= $page->product_price ?>"
       data-item-url="<?= $page->httpUrl ?>"
       data-item-name="<?= $page->title ?>"
       data-item-description="<?= $page->summary ?>"
       data-item-image="<?= $page->featured_image->httpUrl ?>">
       Add to Cart — &pound;<?= number_format($page->product_price, 2) ?>
   </button>
   ```

### Required Fields for Snipcart
- `product_price` (FieldtypeFloat)
- `product_sku` (FieldtypeText) — unique identifier
- `product_weight` (FieldtypeFloat) — for shipping calculation
- `product_stock` (FieldtypeInteger) — optional stock tracking
- `featured_image` (FieldtypeImage)
- `summary` (FieldtypeTextarea)
- `body` (FieldtypeTextarea with CKEditor)

### Templates Needed
- `shop.php` — product listing page
- `product.php` — single product page
- `product-category.php` — category page (optional)

## Approach 3: Native PW Ecommerce (Complex Custom Build)

**Best for**: When you need complex pricing, multi-currency, discount codes, or user accounts beyond what Approach 1 provides.

This extends Approach 1 with additional features. Only use this level of complexity when the simpler Stripe Checkout pattern genuinely doesn't meet requirements.

### Additional Fields for Complex Builds
- `product_variations` (Repeater) — size, colour options with price adjustments
- `product_gallery` (FieldtypeImage, multiple) — product photos
- `product_category` (Page reference) — link to category pages
- `related_products` (Page reference, multiple) — cross-selling
- `product_in_stock` (FieldtypeToggle) — availability flag

## Approach 4: Padloper 2

**Best for**: When you want a pre-built PW-native ecommerce solution.

Padloper is a ProcessWire module specifically designed for ecommerce. Version 2 is free.

**Note**: Check current availability and maintenance status before recommending. If actively maintained, it's a good middle ground between Snipcart and a full custom build.

## Product Catalogue Fields (All Approaches)

These fields should be created regardless of the checkout approach:

| Field | Type | Purpose |
|---|---|---|
| `product_price` | Float | Base price in GBP |
| `product_sku` | Text | Unique product identifier |
| `product_weight` | Float | Weight in kg (for shipping) |
| `product_stock` | Integer | Stock quantity (0 = unlimited) |
| `product_in_stock` | Toggle | Availability flag |
| `product_gallery` | Image (multiple) | Product photos |
| `product_category` | Page reference | Category assignment |
| `related_products` | Page reference (multiple) | Cross-sell links |
| `product_features` | Textarea | Bullet-point features |
| `product_specifications` | Repeater | Key-value spec pairs |

## Tax & Shipping Considerations

- UK VAT: 20% standard rate. Decide if prices are inclusive or exclusive.
- Display: Always show "inc. VAT" or "ex. VAT" clearly.
- Shipping: Flat rate is simplest. Weight-based if products vary significantly.
- Digital products: Different VAT rules. Flag digital vs physical.

## SEO for Ecommerce

- Product schema (JSON-LD) on every product page
- Category pages should have unique descriptions (not just filtered listings)
- Product URLs should be clean: `/shop/category/product-name/`
- Image alt text should include product name
- See `.claude/instructions/seo-checklist.md` for full details
