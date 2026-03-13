<?php namespace ProcessWire;

/**
 * _404.php — Page not found template
 *
 * Delayed output: sets $content for _main.php
 * Shows a friendly 404 page with search form and suggested links.
 */

$shop_page = $pages->get('/shop/');
$blog_page = $pages->get('/blog/');

ob_start(); ?>

<section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 sm:py-32 lg:px-8">

    <!-- Large 404 text -->
    <p class="font-display text-8xl font-bold text-brand-200 sm:text-9xl">
        404
    </p>

    <h1 class="mt-4 font-display text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">
        Page not found
    </h1>

    <p class="mt-4 text-lg text-stone-600">
        Sorry, we couldn't find the page you're looking for. It may have been moved or no longer exists.
    </p>

    <!-- Search Form -->
    <form action="/search/" method="get" class="mx-auto mt-8 flex max-w-md gap-2">
        <label for="search-404" class="sr-only">Search</label>
        <input type="search"
               id="search-404"
               name="q"
               placeholder="Search our site…"
               required
               class="min-w-0 flex-1 rounded-md border border-stone-300 px-4 py-2.5 text-sm placeholder:text-stone-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        <button type="submit"
                class="rounded-md bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
            Search
        </button>
    </form>

    <!-- Suggested Links -->
    <div class="mt-12">
        <p class="text-sm font-medium text-stone-500">Or try one of these pages:</p>
        <ul class="mt-4 flex flex-wrap items-center justify-center gap-4">
            <li>
                <a href="/" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition-colors hover:bg-stone-50 hover:text-brand-700">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Home
                </a>
            </li>
            <?php if ($shop_page->id): ?>
                <li>
                    <a href="<?= $shop_page->url ?>" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition-colors hover:bg-stone-50 hover:text-brand-700">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        Shop
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($blog_page->id): ?>
                <li>
                    <a href="<?= $blog_page->url ?>" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition-colors hover:bg-stone-50 hover:text-brand-700">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5" />
                        </svg>
                        Blog
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="/contact/" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition-colors hover:bg-stone-50 hover:text-brand-700">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    Contact
                </a>
            </li>
        </ul>
    </div>
</section>

<?php
$content = ob_get_clean();
