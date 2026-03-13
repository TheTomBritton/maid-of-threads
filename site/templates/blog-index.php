<?php namespace ProcessWire;

/**
 * Blog Index Template — blog-index.php
 *
 * Lists all blog posts with tag filtering via URL segments,
 * 3-column responsive grid, pagination, and a sidebar
 * showing categories with post counts.
 */

// Breadcrumbs
$hero = renderBreadcrumbs($page);

// Tag filtering via URL segment
$tag_filter = $sanitizer->pageName($input->urlSegment1);
$selector = "template=blog-post, sort=-date, limit=12";

if ($tag_filter) {
    // Find posts tagged with this tag name
    $selector .= ", tags.name=$tag_filter";
    $tag_label = str_replace('-', ' ', $tag_filter);
}

$posts = $pages->find($selector);

// Build main content
ob_start(); ?>

<section class="py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-12">

            <!-- Main column -->
            <div class="lg:col-span-2">
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= $page->title ?></h1>

                <?php if ($page->body): ?>
                    <div class="prose prose-lg text-gray-600 mb-8">
                        <?= $page->body ?>
                    </div>
                <?php endif; ?>

                <?php if ($tag_filter): ?>
                    <div class="mb-8 flex items-center gap-3">
                        <span class="text-sm text-gray-500">
                            Posts tagged: <strong class="text-gray-900"><?= ucwords($tag_label) ?></strong>
                        </span>
                        <a href="<?= $page->url ?>"
                           class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            View all posts
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($posts->count): ?>
                    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
                        <?php foreach ($posts as $post): ?>
                            <?= renderPostCard($post) ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($posts->getTotal() > $posts->getLimit()): ?>
                        <nav class="flex justify-center" aria-label="Blog pagination">
                            <?= $posts->renderPager([
                                'listMarkup' => '<ul class="flex items-center gap-1">{out}</ul>',
                                'itemMarkup' => '<li>{out}</li>',
                                'linkMarkup' => '<a href="{url}" class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">{out}</a>',
                                'currentItemClass' => 'font-bold',
                                'currentLinkMarkup' => '<span class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium bg-indigo-600 text-white">{out}</span>',
                            ]) ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-16">
                        <p class="text-gray-500 text-lg">No posts found.</p>
                        <?php if ($tag_filter): ?>
                            <a href="<?= $page->url ?>"
                               class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                                &larr; Browse all posts
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="mt-12 lg:mt-0">
                <div class="sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Categories</h2>
                    <ul class="space-y-2">
                        <?php
                        $categories = $pages->find("template=blog-category, sort=title");
                        foreach ($categories as $cat):
                            $count = $pages->count("template=blog-post, parent=$cat");
                            if (!$count) continue;
                        ?>
                            <li>
                                <a href="<?= $cat->url ?>"
                                   class="flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-100 transition-colors <?= $cat->id === $page->id ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700' ?>">
                                    <span><?= $cat->title ?></span>
                                    <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5"><?= $count ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

        </div>
    </div>
</section>

<?php $content = ob_get_clean();
