<?php namespace ProcessWire;

/**
 * Blog Category Template — blog-category.php
 *
 * Displays all blog posts within a specific category
 * with breadcrumbs, body intro, 3-column grid, and pagination.
 */

// Breadcrumbs
$hero = renderBreadcrumbs($page);

// Find posts that are children of this category
$posts = $page->children("template=blog-post, sort=-date, limit=12");

ob_start(); ?>

<section class="py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= $page->title ?></h1>

        <?php if ($page->body): ?>
            <div class="prose prose-lg text-gray-600 mb-8 max-w-3xl">
                <?= $page->body ?>
            </div>
        <?php endif; ?>

        <p class="text-sm text-gray-500 mb-8">
            <?= $posts->getTotal() ?> <?= $posts->getTotal() === 1 ? 'post' : 'posts' ?> in this category
        </p>

        <?php if ($posts->count): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <?php foreach ($posts as $post): ?>
                    <?= renderPostCard($post) ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($posts->getTotal() > $posts->getLimit()): ?>
                <nav class="flex justify-center" aria-label="Category pagination">
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
                <p class="text-gray-500 text-lg">No posts in this category yet.</p>
                <a href="<?= $blog_page->url ?>"
                   class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                    &larr; Back to all posts
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php $content = ob_get_clean();
