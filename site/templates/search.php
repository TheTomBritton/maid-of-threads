<?php namespace ProcessWire;

/**
 * Search Template — search.php
 *
 * Full site search with HTMX live results. When the request
 * includes ?ajax=1, a partial HTML fragment is returned (skipping
 * _main.php layout). Otherwise renders the full search results page
 * with form, result count, typed results, and pagination.
 */

// Breadcrumbs (only needed for full page render)
$hero = renderBreadcrumbs($page);

$q = $sanitizer->selectorValue($input->get->q);
$q_display = $sanitizer->entities($input->get->q);
$results = new PageArray();
$total = 0;

if ($q) {
    $results = $pages->find("title|body|summary%=$q, template!=admin, template!=blog-rss, has_parent!=" . $config->adminRootPageID . ", limit=20, sort=-modified");
    $total = $results->getTotal();
}

// -------------------------------------------------------------------
// HTMX partial response — returns result fragment only
// -------------------------------------------------------------------
if ($input->get->ajax) {

    if (!$q) {
        echo '<p class="text-sm text-gray-400 py-4">Start typing to search&hellip;</p>';
        return; // Skip _main.php
    }

    if (!$total) {
        echo '<p class="text-sm text-gray-500 py-4">No results for &ldquo;' . $q_display . '&rdquo;</p>';
        return;
    }

    echo '<ul class="divide-y divide-gray-100">';
    foreach ($results->slice(0, 8) as $r) {
        // Determine result type label
        $type = 'Page';
        if ($r->template->name === 'blog-post')      $type = 'Blog';
        if ($r->template->name === 'product')         $type = 'Product';
        if ($r->template->name === 'product-category') $type = 'Category';

        // Thumbnail
        $thumb = '';
        if ($r->featured_image && $r->featured_image->count) {
            $img = $r->featured_image->first->size(80, 80);
            $thumb = '<img src="' . $img->url . '" alt="" class="w-10 h-10 rounded object-cover flex-shrink-0" loading="lazy">';
        }

        $summary = truncate($sanitizer->text($r->summary ?: $r->body), 120);
        ?>
        <li>
            <a href="<?= $r->url ?>" class="flex items-center gap-3 px-3 py-3 hover:bg-gray-50 transition-colors rounded-lg">
                <?= $thumb ?>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium uppercase tracking-wider text-indigo-600"><?= $type ?></span>
                        <span class="font-medium text-gray-900 truncate"><?= $r->title ?></span>
                    </div>
                    <?php if ($summary): ?>
                        <p class="text-sm text-gray-500 truncate"><?= $summary ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </li>
        <?php
    }
    echo '</ul>';

    if ($total > 8) {
        echo '<div class="pt-3 pb-1 text-center">';
        echo '<a href="' . $page->url . '?q=' . urlencode($input->get->q) . '" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View all ' . $total . ' results &rarr;</a>';
        echo '</div>';
    }

    return; // Skip _main.php
}

// -------------------------------------------------------------------
// Full page render
// -------------------------------------------------------------------
ob_start(); ?>

<section class="py-12 lg:py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <h1 class="text-3xl font-bold text-gray-900 mb-6"><?= $page->title ?></h1>

        <!-- Search form with HTMX live results -->
        <div class="relative mb-10">
            <form action="<?= $page->url ?>" method="get" class="relative">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="search" name="q"
                           value="<?= $q_display ?>"
                           placeholder="Search products, posts, pages&hellip;"
                           autocomplete="off"
                           hx-get="<?= $page->url ?>?ajax=1"
                           hx-trigger="input changed delay:300ms, search"
                           hx-target="#live-results"
                           hx-include="this"
                           hx-indicator="#search-spinner"
                           class="w-full pl-12 pr-12 py-4 rounded-xl border border-gray-300 shadow-sm text-lg
                                  focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                    <div id="search-spinner" class="htmx-indicator absolute right-4 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                </div>
            </form>

            <!-- Live results dropdown -->
            <div id="live-results"
                 class="absolute z-30 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-gray-200 max-h-96 overflow-y-auto empty:hidden">
            </div>
        </div>

        <!-- Full results (only when query is present and not AJAX) -->
        <?php if ($q): ?>

            <p class="text-sm text-gray-500 mb-6">
                <?= $total ?>
                <?= $total === 1 ? 'result' : 'results' ?>
                for &ldquo;<strong class="text-gray-900"><?= $q_display ?></strong>&rdquo;
            </p>

            <?php if ($total): ?>
                <div class="space-y-6">
                    <?php foreach ($results as $r):
                        // Determine result type
                        $type = 'Page';
                        $type_colour = 'bg-gray-100 text-gray-700';
                        if ($r->template->name === 'blog-post') {
                            $type = 'Blog';
                            $type_colour = 'bg-blue-100 text-blue-700';
                        }
                        if ($r->template->name === 'product') {
                            $type = 'Product';
                            $type_colour = 'bg-indigo-100 text-indigo-700';
                        }

                        $summary = truncate($sanitizer->text($r->summary ?: $r->body), 200);
                    ?>
                        <article class="group">
                            <a href="<?= $r->url ?>" class="block p-5 rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all">
                                <div class="flex items-start gap-4">
                                    <?php if ($r->featured_image && $r->featured_image->count): ?>
                                        <?php $thumb = $r->featured_image->first->size(120, 80); ?>
                                        <img src="<?= $thumb->url ?>" alt=""
                                             class="w-24 h-16 rounded-lg object-cover flex-shrink-0" loading="lazy">
                                    <?php endif; ?>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="inline-block text-xs font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full <?= $type_colour ?>">
                                                <?= $type ?>
                                            </span>
                                        </div>
                                        <h2 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                            <?= $r->title ?>
                                        </h2>
                                        <?php if ($summary): ?>
                                            <p class="mt-1 text-sm text-gray-600 line-clamp-2"><?= $summary ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total > $results->getLimit()): ?>
                    <nav class="flex justify-center mt-10" aria-label="Search pagination">
                        <?= $results->renderPager([
                            'listMarkup' => '<ul class="flex items-center gap-1">{out}</ul>',
                            'itemMarkup' => '<li>{out}</li>',
                            'linkMarkup' => '<a href="{url}" class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">{out}</a>',
                            'currentItemClass' => 'font-bold',
                            'currentLinkMarkup' => '<span class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium bg-indigo-600 text-white">{out}</span>',
                            'baseUrl' => $page->url . '?q=' . urlencode($input->get->q),
                        ]) ?>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-16">
                    <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-gray-500 text-lg">No results found for your search.</p>
                    <p class="text-sm text-gray-400 mt-2">Try different keywords or check your spelling.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-16 text-gray-400">
                <p class="text-lg">Enter a search term above to find products, blog posts, and pages.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php $content = ob_get_clean();
