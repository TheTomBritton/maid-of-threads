<?php namespace ProcessWire;

/**
 * basic-page.php — Generic page template
 *
 * Delayed output: sets $content and optionally $sidebar for _main.php
 * Renders breadcrumbs, featured image, body content, image gallery,
 * and a sidebar with related/child pages.
 */

// ─────────────────────────────────────────────
// Sidebar — related pages or child pages
// ─────────────────────────────────────────────

$sidebar_pages = $page->children->count
    ? $page->children
    : $page->siblings("id!=$page->id, limit=5");

if ($sidebar_pages->count) {
    ob_start(); ?>

    <div class="rounded-lg border border-stone-200 bg-white p-6">
        <h3 class="font-display text-lg font-semibold text-stone-900">
            <?= $page->children->count ? 'In this section' : 'Related pages' ?>
        </h3>
        <ul class="mt-4 space-y-3">
            <?php foreach ($sidebar_pages as $related): ?>
                <li>
                    <a href="<?= $related->url ?>"
                       class="flex items-center text-sm text-stone-600 transition-colors hover:text-brand-700<?= $related->id === $page->id ? ' font-semibold text-brand-700' : '' ?>">
                        <svg class="mr-2 h-4 w-4 flex-shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                        <?= $related->title ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php
    $sidebar = ob_get_clean();
}

// ─────────────────────────────────────────────
// Content
// ─────────────────────────────────────────────

ob_start();

// Breadcrumbs
$breadcrumbs = $page->parents; ?>

<?php if ($breadcrumbs->count > 1): ?>
<nav aria-label="Breadcrumb" class="mb-6">
    <ol class="flex flex-wrap items-center gap-1 text-sm text-stone-500">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <li class="flex items-center">
                <a href="<?= $crumb->url ?>" class="transition-colors hover:text-brand-700">
                    <?= $crumb->title ?>
                </a>
                <svg class="mx-2 h-4 w-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </li>
        <?php endforeach; ?>
        <li class="font-medium text-stone-800" aria-current="page">
            <?= $page->title ?>
        </li>
    </ol>
</nav>
<?php endif; ?>

<!-- Page Title -->
<h1 class="font-display text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">
    <?= $page->title ?>
</h1>

<?php if ($page->summary): ?>
    <p class="mt-4 text-lg leading-relaxed text-stone-600">
        <?= $page->summary ?>
    </p>
<?php endif; ?>

<!-- Featured Image -->
<?php if ($page->featured_image && $page->featured_image->count): ?>
    <figure class="mt-8 overflow-hidden rounded-lg">
        <img src="<?= $page->featured_image->first->size(1200, 0)->url ?>"
             alt="<?= $page->featured_image->first->description ?: $page->title ?>"
             class="h-auto w-full object-cover"
             loading="lazy"
             width="1200"
             height="<?= $page->featured_image->first->size(1200, 0)->height ?>">
        <?php if ($page->featured_image->first->description): ?>
            <figcaption class="mt-2 text-sm text-stone-500">
                <?= $page->featured_image->first->description ?>
            </figcaption>
        <?php endif; ?>
    </figure>
<?php endif; ?>

<!-- Body Content -->
<?php if ($page->body): ?>
    <div class="prose prose-stone prose-brand mt-8 max-w-none">
        <?= $page->body ?>
    </div>
<?php endif; ?>

<!-- Image Gallery -->
<?php if ($page->images && $page->images->count): ?>
    <section class="mt-12">
        <h2 class="font-display text-xl font-bold text-stone-900">Gallery</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <?php foreach ($page->images as $image): ?>
                <a href="<?= $image->url ?>"
                   class="group overflow-hidden rounded-lg"
                   target="_blank"
                   rel="noopener">
                    <img src="<?= $image->size(400, 400)->url ?>"
                         alt="<?= $image->description ?: $page->title ?>"
                         class="h-auto w-full object-cover transition-transform duration-300 group-hover:scale-105"
                         loading="lazy"
                         width="400"
                         height="400">
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php
$content = ob_get_clean();
