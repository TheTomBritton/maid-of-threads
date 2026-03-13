<?php namespace ProcessWire;

/**
 * _main.php — HTML shell wrapper (delayed output)
 *
 * Receives variables set by individual templates:
 *   $content   — main page content (required)
 *   $hero      — optional hero section markup
 *   $sidebar   — optional sidebar markup
 *   $extra_head — optional extra <head> content (styles, meta, etc.)
 *   $extra_foot — optional extra scripts before </body>
 *
 * @var string $content
 * @var string|null $hero
 * @var string|null $sidebar
 * @var string|null $extra_head
 * @var string|null $extra_foot
 */

// Ensure optional vars have defaults
$hero       = $hero ?? '';
$sidebar    = $sidebar ?? '';
$extra_head = $extra_head ?? '';
$extra_foot = $extra_foot ?? '';

// Commonly referenced pages
$home      = $pages->get('/');
// $site_name is set in _init.php
$shop_page = $pages->get('/shop/');
$cart_page = $pages->get('/cart/');
$blog_page = $pages->get('/blog/');

// Asset paths with cache busting
$dist_url  = $config->urls->assets . 'dist/';
$dist_path = $config->paths->assets . 'dist/';

$css_file  = 'app.css';
$js_file   = 'app.js';
$css_ver   = file_exists($dist_path . $css_file) ? filemtime($dist_path . $css_file) : time();
$js_ver    = file_exists($dist_path . $js_file)  ? filemtime($dist_path . $js_file)  : time();

// Free shipping threshold display
$free_shipping_threshold = isset($config->shopFreeShippingThreshold)
    ? formatPrice($config->shopFreeShippingThreshold)
    : '£50.00';

?><!doctype html>
<html lang="en-GB" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page->title ?> — <?= $site_name ?></title>
    <meta name="description" content="<?= $page->summary ?: $home->summary ?>">

    <!-- Google Fonts: Inter (body) + Playfair Display (headings) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap">

    <!-- Tailwind / App CSS -->
    <link rel="stylesheet" href="<?= $dist_url . $css_file ?>?v=<?= $css_ver ?>">

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.4" crossorigin="anonymous"></script>

    <?= $extra_head ?>
</head>
<body class="flex min-h-screen flex-col bg-white text-stone-800 antialiased">

    <!-- ═══════════════════════════════════════════
         Announcement Bar
         ═══════════════════════════════════════════ -->
    <div class="bg-brand-700 text-center text-sm font-medium text-white py-2 px-4">
        Free delivery on orders over <?= $free_shipping_threshold ?>
    </div>

    <!-- ═══════════════════════════════════════════
         Header
         ═══════════════════════════════════════════ -->
    <header class="sticky top-0 z-50 border-b border-stone-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">

        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

            <!-- Logo / Site Name -->
            <a href="<?= $home->url ?>" class="font-display text-2xl font-bold tracking-tight text-brand-700 hover:text-brand-800 transition-colors">
                <?= $site_name ?>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden items-center gap-8 lg:flex" aria-label="Main navigation">
                <?php
                // Only show visible, published top-level pages (exclude utility/system pages)
                $nav_templates = 'basic-page|shop|blog-index|contact';
                foreach ($home->children("template=$nav_templates, sort=sort") as $child): ?>
                    <a href="<?= $child->url ?>"
                       class="text-sm font-medium text-stone-600 transition-colors hover:text-brand-700<?= $child->id === $page->rootParent->id ? ' text-brand-700' : '' ?>">
                        <?= $child->title ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Icon Actions -->
            <div class="flex items-center gap-4">

                <!-- Search -->
                <a href="/search/" class="text-stone-500 transition-colors hover:text-brand-700" aria-label="Search">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </a>

                <!-- Cart (badge loaded via HTMX) -->
                <a href="<?= $cart_page->id ? $cart_page->url : '/cart/' ?>" class="relative text-stone-500 transition-colors hover:text-brand-700" aria-label="Shopping cart">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <!-- Cart count badge — loaded once via fetch (not HTMX, to avoid infinite loop) -->
                    <span id="cart-badge"></span>
                </a>

                <!-- Mobile hamburger -->
                <button id="menu-toggle"
                        class="text-stone-500 transition-colors hover:text-brand-700 lg:hidden"
                        aria-expanded="false"
                        aria-label="Toggle menu">
                    <svg id="icon-menu" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg id="icon-close" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <nav id="mobile-nav"
             class="border-t border-stone-200 bg-white px-4 pb-6 pt-4 lg:hidden hidden"
             aria-label="Mobile navigation">
            <div class="flex flex-col gap-4">
                <?php foreach ($home->children("template=$nav_templates, sort=sort") as $child): ?>
                    <a href="<?= $child->url ?>"
                       class="mobile-nav-link text-base font-medium text-stone-700 transition-colors hover:text-brand-700<?= $child->id === $page->rootParent->id ? ' text-brand-700' : '' ?>">
                        <?= $child->title ?>
                    </a>
                <?php endforeach; ?>
                <a href="/search/"
                   class="mobile-nav-link text-base font-medium text-stone-700 transition-colors hover:text-brand-700">
                    Search
                </a>
            </div>
        </nav>
    </header>

    <!-- ═══════════════════════════════════════════
         Hero Region
         ═══════════════════════════════════════════ -->
    <?php if ($hero): ?>
        <?= $hero ?>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════
         Main Content
         ═══════════════════════════════════════════ -->
    <main class="flex-1">
        <?php if ($sidebar): ?>
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div class="lg:col-span-3">
                    <?= $content ?>
                </div>
                <aside class="lg:col-span-1">
                    <?= $sidebar ?>
                </aside>
            </div>
        <?php else: ?>
            <?= $content ?>
        <?php endif; ?>
    </main>

    <!-- ═══════════════════════════════════════════
         Footer
         ═══════════════════════════════════════════ -->
    <footer class="border-t border-stone-200 bg-stone-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

                <!-- Col 1: Site Info -->
                <div>
                    <h3 class="font-display text-lg font-bold text-brand-700"><?= $site_name ?></h3>
                    <?php if ($home->summary): ?>
                        <p class="mt-3 text-sm leading-relaxed text-stone-600"><?= $home->summary ?></p>
                    <?php endif; ?>
                </div>

                <!-- Col 2: Shop Categories -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-stone-900">Shop</h4>
                    <ul class="mt-3 space-y-2">
                        <?php if ($shop_page->id):
                            foreach ($shop_page->children as $category): ?>
                                <li>
                                    <a href="<?= $category->url ?>" class="text-sm text-stone-600 transition-colors hover:text-brand-700">
                                        <?= $category->title ?>
                                    </a>
                                </li>
                            <?php endforeach;
                        endif; ?>
                    </ul>
                </div>

                <!-- Col 3: Info Links -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-stone-900">Information</h4>
                    <ul class="mt-3 space-y-2">
                        <?php
                        $info_links = [
                            '/about/'              => 'About Us',
                            '/contact/'            => 'Contact',
                            '/privacy-policy/'     => 'Privacy Policy',
                            '/terms-and-conditions/' => 'Terms & Conditions',
                        ];
                        foreach ($info_links as $url => $label):
                            $link_page = $pages->get($url);
                            if ($link_page->id): ?>
                                <li>
                                    <a href="<?= $link_page->url ?>" class="text-sm text-stone-600 transition-colors hover:text-brand-700">
                                        <?= $label ?>
                                    </a>
                                </li>
                            <?php endif;
                        endforeach; ?>
                    </ul>
                </div>

                <!-- Col 4: Contact -->
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-stone-900">Get in Touch</h4>
                    <p class="mt-3 text-sm leading-relaxed text-stone-600">
                        Have a question about an order or want to discuss a custom commission?
                    </p>
                    <?php $contact_page = $pages->get('/contact/'); ?>
                    <?php if ($contact_page->id): ?>
                        <a href="<?= $contact_page->url ?>"
                           class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors">
                            Send us a message
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="mt-12 border-t border-stone-200 pt-6 text-center text-xs text-stone-500">
                <p>&copy; <?= date('Y') ?> <?= $site_name ?>. All rights reserved.</p>
                <p class="mt-1">All prices include VAT where applicable.</p>
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════════
         Back-to-Top FAB
         ═══════════════════════════════════════════ -->
    <div class="fixed bottom-6 right-6 z-50">
        <button id="back-to-top"
                class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all duration-200 opacity-0 translate-y-4 pointer-events-none"
                aria-label="Back to top">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
            </svg>
        </button>
    </div>

    <!-- ═══════════════════════════════════════════
         Cookie Consent Banner (GDPR)
         ═══════════════════════════════════════════ -->
    <div id="cookie-banner" class="fixed inset-x-0 bottom-0 z-[9998] border-t border-stone-200 bg-white shadow-[0_-4px_24px_rgba(0,0,0,0.1)] hidden">
        <div class="mx-auto max-w-5xl px-4 py-4 sm:px-6">

            <!-- Main banner -->
            <div class="sm:flex sm:items-center sm:justify-between sm:gap-6">
                <p class="text-sm text-stone-600">
                    We use cookies to improve your experience. Essential cookies are always active. You can choose which additional cookies to allow.
                    <a href="/privacy-policy/" class="font-medium text-brand-600 hover:text-brand-700 underline">Privacy Policy</a>
                </p>
                <div class="mt-3 flex flex-wrap gap-2 sm:mt-0 sm:flex-shrink-0">
                    <button data-cookie="accept-all" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition-colors">Accept All</button>
                    <button data-cookie="reject-all" class="rounded-md bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-200 transition-colors">Reject All</button>
                    <button data-cookie="manage" class="rounded-md bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-200 transition-colors">Manage</button>
                </div>
            </div>

            <!-- Preferences panel (server-rendered categories) -->
            <div id="cookie-prefs" class="mt-4 border-t border-stone-200 pt-4 hidden">
                <div class="grid gap-3 sm:grid-cols-2">
                    <!-- Essential (always on, cannot disable) -->
                    <label class="flex items-center justify-between rounded-lg border border-stone-200 p-3">
                        <div class="mr-4">
                            <span class="text-sm font-medium text-stone-900">Essential</span>
                            <span class="block text-xs text-stone-500">Required for the site to function. Cannot be disabled.</span>
                        </div>
                        <button type="button" role="switch" aria-checked="true" disabled
                                class="cookie-toggle opacity-60 cursor-not-allowed" data-cookie-cat="essential">
                            <span class="toggle-dot"></span>
                        </button>
                    </label>
                    <!-- Analytics -->
                    <label class="flex items-center justify-between rounded-lg border border-stone-200 p-3">
                        <div class="mr-4">
                            <span class="text-sm font-medium text-stone-900">Analytics</span>
                            <span class="block text-xs text-stone-500">Help us understand how visitors use the site.</span>
                        </div>
                        <button type="button" role="switch" aria-checked="false"
                                class="cookie-toggle" data-cookie-cat="analytics">
                            <span class="toggle-dot"></span>
                        </button>
                    </label>
                    <!-- Marketing -->
                    <label class="flex items-center justify-between rounded-lg border border-stone-200 p-3">
                        <div class="mr-4">
                            <span class="text-sm font-medium text-stone-900">Marketing</span>
                            <span class="block text-xs text-stone-500">Used to deliver personalised advertising.</span>
                        </div>
                        <button type="button" role="switch" aria-checked="false"
                                class="cookie-toggle" data-cookie-cat="marketing">
                            <span class="toggle-dot"></span>
                        </button>
                    </label>
                </div>
                <div class="mt-3 text-right">
                    <button data-cookie="save-prefs" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition-colors">Save Preferences</button>
                </div>
            </div>
        </div>
    </div>

    <!-- App JS with cache busting -->
    <script src="<?= $dist_url . $js_file ?>?v=<?= $js_ver ?>"></script>

    <!-- Page enhancements (vanilla JS — no framework dependency) -->
    <script>
    (function() {
        'use strict';

        // ── Mobile menu toggle ───────────────────────
        var menuToggle = document.getElementById('menu-toggle');
        var mobileNav  = document.getElementById('mobile-nav');
        var iconMenu   = document.getElementById('icon-menu');
        var iconClose  = document.getElementById('icon-close');

        function closeMenu() {
            mobileNav.classList.add('hidden');
            iconMenu.classList.remove('hidden');
            iconClose.classList.add('hidden');
            menuToggle.setAttribute('aria-expanded', 'false');
        }

        if (menuToggle && mobileNav) {
            menuToggle.addEventListener('click', function() {
                var isOpen = !mobileNav.classList.contains('hidden');
                if (isOpen) {
                    closeMenu();
                } else {
                    mobileNav.classList.remove('hidden');
                    iconMenu.classList.add('hidden');
                    iconClose.classList.remove('hidden');
                    menuToggle.setAttribute('aria-expanded', 'true');
                }
            });

            // Close when a nav link is tapped
            mobileNav.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', closeMenu);
            });

            // Auto-close on resize to desktop breakpoint
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) closeMenu();
            });
        }

        // ── Back-to-top FAB ──────────────────────────
        var backToTop = document.getElementById('back-to-top');
        if (backToTop) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 400) {
                    backToTop.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    backToTop.classList.add('opacity-100', 'translate-y-0');
                } else {
                    backToTop.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                    backToTop.classList.remove('opacity-100', 'translate-y-0');
                }
            }, { passive: true });

            backToTop.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // ── Cookie consent (GDPR) ───────────────────
        var banner = document.getElementById('cookie-banner');
        var prefs  = document.getElementById('cookie-prefs');

        if (banner) {
            // Show banner if no consent stored
            if (!localStorage.getItem('mot_cookie_consent')) {
                banner.classList.remove('hidden');
            }

            function closeBanner() {
                banner.classList.add('hidden');
            }

            // Accept All
            banner.querySelector('[data-cookie="accept-all"]').addEventListener('click', function() {
                banner.querySelectorAll('.cookie-toggle:not([disabled])').forEach(function(btn) {
                    btn.setAttribute('aria-checked', 'true');
                });
                localStorage.setItem('mot_cookie_consent', 'all');
                closeBanner();
            });

            // Reject All
            banner.querySelector('[data-cookie="reject-all"]').addEventListener('click', function() {
                banner.querySelectorAll('.cookie-toggle:not([disabled])').forEach(function(btn) {
                    btn.setAttribute('aria-checked', 'false');
                });
                localStorage.setItem('mot_cookie_consent', 'essential');
                closeBanner();
            });

            // Manage — toggle preferences panel
            banner.querySelector('[data-cookie="manage"]').addEventListener('click', function() {
                prefs.classList.toggle('hidden');
            });

            // Individual category toggles
            banner.querySelectorAll('.cookie-toggle:not([disabled])').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var checked = btn.getAttribute('aria-checked') === 'true';
                    btn.setAttribute('aria-checked', String(!checked));
                });
            });

            // Save Preferences
            banner.querySelector('[data-cookie="save-prefs"]').addEventListener('click', function() {
                var accepted = [];
                banner.querySelectorAll('.cookie-toggle[aria-checked="true"]').forEach(function(btn) {
                    accepted.push(btn.getAttribute('data-cookie-cat'));
                });
                localStorage.setItem('mot_cookie_consent', accepted.join(','));
                closeBanner();
            });
        }

        // ── Section reveal on scroll ─────────────────
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('mot-visible');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
            document.querySelectorAll('.mot-reveal').forEach(function(s) { io.observe(s); });
        } else {
            document.querySelectorAll('.mot-reveal').forEach(function(s) { s.classList.add('mot-visible'); });
        }

        // ── Image fade-in on lazy load ───────────────
        document.querySelectorAll('img[loading="lazy"]').forEach(function(img) {
            if (img.complete && img.naturalWidth) {
                img.classList.add('mot-img-fade', 'mot-img-loaded');
            } else {
                img.classList.add('mot-img-fade');
                img.addEventListener('load', function() { img.classList.add('mot-img-loaded'); });
            }
        });

        // ── Flash message auto-dismiss ───────────────
        document.querySelectorAll('.flash-dismiss').forEach(function(el) {
            setTimeout(function() {
                el.style.transition = 'opacity 0.3s ease';
                el.style.opacity = '0';
                setTimeout(function() { el.remove(); }, 300);
            }, 5000);
        });

        // ── Cart badge (one-shot fetch, no HTMX) ─────
        // Using plain fetch instead of HTMX to guarantee no infinite loop.
        // The old hx-trigger="load" approach could loop when _main.php
        // accidentally wrapped the badge response in a full page.
        var badge = document.getElementById('cart-badge');
        if (badge) {
            fetch('/cart/?action=badge')
                .then(function(r) { return r.text(); })
                .then(function(html) { badge.innerHTML = html; })
                .catch(function() { /* badge stays empty on error */ });
        }
    })();
    </script>

    <?= $extra_foot ?>
</body>
</html>
