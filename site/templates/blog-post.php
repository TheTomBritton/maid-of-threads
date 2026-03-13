<?php namespace ProcessWire;

/**
 * Blog Post Template — blog-post.php
 *
 * Single blog post with featured image, body content,
 * tag pills, related posts, and JSON-LD article schema.
 */

// Breadcrumbs
$hero = renderBreadcrumbs($page);

// Article schema markup in <head>
$extra_head = renderArticleSchema($page);

// Format the publish date
$date_formatted = date('j F Y', $page->getUnformatted('date'));
$date_iso = date('Y-m-d', $page->getUnformatted('date'));

// Gather categories (parent pages using blog-category template)
$categories = $page->parents("template=blog-category");

// Gather tags
$tags = $page->blog_tags;

ob_start(); ?>

<article class="py-12 lg:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Article header -->
        <header class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight mb-4">
                <?= $page->title ?>
            </h1>

            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <time datetime="<?= $date_iso ?>"><?= $date_formatted ?></time>

                <?php if ($categories->count): ?>
                    <span class="text-gray-300">&bull;</span>
                    <?php foreach ($categories as $i => $cat): ?>
                        <a href="<?= $cat->url ?>"
                           class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                            <?= $cat->title ?>
                        </a>
                        <?php if ($i < $categories->count - 1): ?>
                            <span class="text-gray-300">,</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </header>

        <!-- Featured image -->
        <?php if ($page->featured_image && $page->featured_image->count): ?>
            <?php $image = $page->featured_image->first; ?>
            <figure class="mb-10 -mx-4 sm:mx-0">
                <?= renderImage($image, [480, 768, 1024], '(max-width: 768px) 100vw, 768px', false) ?>
                <?php if ($image->description): ?>
                    <figcaption class="mt-3 text-center text-sm text-gray-500 italic">
                        <?= $image->description ?>
                    </figcaption>
                <?php endif; ?>
            </figure>
        <?php endif; ?>

        <!-- Article body -->
        <div class="prose prose-lg prose-content max-w-none
                    prose-headings:text-gray-900 prose-headings:font-bold
                    prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
                    prose-img:rounded-lg">
            <?= $page->body ?>
        </div>

        <!-- Tags -->
        <?php if ($tags && $tags->count): ?>
            <div class="mt-10 pt-8 border-t border-gray-200">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Tags</h2>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?= $blog_page->url . $tag->name ?>/"
                           class="inline-block px-3 py-1 rounded-full text-sm font-medium
                                  bg-gray-100 text-gray-700 hover:bg-indigo-100 hover:text-indigo-700
                                  transition-colors">
                            <?= $tag->title ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Related posts -->
        <?php
        // Find posts in the same category, excluding the current one
        $related = new PageArray();
        if ($categories->count) {
            $parent_cat = $categories->last();
            $related = $pages->find("template=blog-post, parent=$parent_cat, id!=$page, sort=-date, limit=3");
        }

        // Fall back to latest posts if not enough related ones
        if ($related->count < 3) {
            $fallback = $pages->find("template=blog-post, id!=$page, id!={$related->implode('|', 'id')}, sort=-date, limit=" . (3 - $related->count));
            $related->import($fallback);
        }
        ?>

        <?php if ($related->count): ?>
            <div class="mt-12 pt-8 border-t border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Related Posts</h2>
                <div class="grid sm:grid-cols-3 gap-6">
                    <?php foreach ($related as $post): ?>
                        <?= renderPostCard($post) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</article>

<?php $content = ob_get_clean();
