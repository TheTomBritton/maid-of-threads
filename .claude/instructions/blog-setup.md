# Blog / News Architecture in ProcessWire

## Page Structure

```
/blog/                          (blog-index.php — paginated listing)
├── /blog/post-title/           (blog-post.php — single article)
├── /blog/another-post/
/blog-categories/               (hidden page, parent for categories)
├── /blog-categories/design/    (blog-category.php — filtered listing)
├── /blog-categories/dev/
/blog-authors/                  (hidden page, parent for authors — optional)
├── /blog-authors/tom/          (blog-author.php)
```

## Templates

### blog-index.php
```php
<?php namespace ProcessWire;

$content = "<h1>{$page->title}</h1>";
$content .= $page->body;

// Get paginated posts, newest first
$posts = $pages->find("template=blog-post, sort=-date, limit=12");

$content .= "<div class='posts-grid'>";
foreach ($posts as $post) {
    $content .= renderPostCard($post);
}
$content .= "</div>";

// Pagination
$content .= $posts->renderPager([
    'nextItemLabel' => 'Next &rarr;',
    'previousItemLabel' => '&larr; Previous',
    'listMarkup' => '<nav aria-label="Pagination"><ul class="pagination">{out}</ul></nav>',
    'itemMarkup' => '<li class="{class}">{out}</li>',
    'linkMarkup' => '<a href="{url}">{out}</a>',
    'currentItemClass' => 'active',
]);
```

### blog-post.php
```php
<?php namespace ProcessWire;

$browser_title = $page->get('seo_title|title');
$meta_description = $page->get('seo_description|summary');

// Article header
$content = "<article class='blog-post'>";
$content .= "<header>";
$content .= "<h1>{$page->title}</h1>";

// Meta information
$date = date('j F Y', $page->getUnformatted('date'));
$content .= "<time datetime='" . date('Y-m-d', $page->getUnformatted('date')) . "'>{$date}</time>";

// Categories
if ($page->blog_categories->count()) {
    $content .= "<div class='categories'>";
    foreach ($page->blog_categories as $cat) {
        $content .= "<a href='{$cat->url}'>{$cat->title}</a> ";
    }
    $content .= "</div>";
}

// Author (if using author pages)
if ($page->blog_author && $page->blog_author->id) {
    $content .= "<span class='author'>By {$page->blog_author->title}</span>";
}

$content .= "</header>";

// Featured image
if ($page->featured_image) {
    $img = $page->featured_image;
    $content .= renderImage($img, [600, 900, 1200]);
}

// Post body
$content .= "<div class='post-content'>{$page->body}</div>";

// Tags
if ($page->blog_tags->count()) {
    $content .= "<footer class='post-tags'><p>Tagged: ";
    $tags = [];
    foreach ($page->blog_tags as $tag) {
        $tags[] = "<a href='{$page->parent->url}?tag={$tag->name}'>{$tag->title}</a>";
    }
    $content .= implode(', ', $tags);
    $content .= "</p></footer>";
}

$content .= "</article>";

// Related posts
$related = $pages->find("template=blog-post, blog_categories={$page->blog_categories}, id!={$page->id}, sort=-date, limit=3");
if ($related->count()) {
    $content .= "<section class='related-posts'>";
    $content .= "<h2>Related Articles</h2>";
    $content .= "<div class='posts-grid'>";
    foreach ($related as $post) {
        $content .= renderPostCard($post);
    }
    $content .= "</div></section>";
}

// JSON-LD structured data
$extra_head = renderArticleSchema($page);
```

## Required Fields

| Field | Type | Purpose |
|---|---|---|
| `title` | Text | Post title (built-in) |
| `date` | Datetime | Publication date |
| `body` | Textarea (CKEditor) | Post content |
| `summary` | Textarea | Excerpt for listings and meta |
| `featured_image` | Image (single) | Hero/card image |
| `blog_categories` | Page reference (multiple) | Category assignments |
| `blog_tags` | Page reference (multiple) | Tag assignments |
| `blog_author` | Page reference (single) | Author page link (optional) |
| `seo_title` | Text | SEO override title |
| `seo_description` | Textarea | SEO override description |

## Category Fields

| Field | Type | Purpose |
|---|---|---|
| `title` | Text | Category name |
| `body` | Textarea | Category description (for SEO) |
| `featured_image` | Image | Category image (optional) |

## Helper Functions (add to _func.php)

```php
/**
 * Render a blog post card for listings
 */
function renderPostCard($post): string {
    $out = "<article class='post-card'>";

    if ($post->featured_image) {
        $thumb = $post->featured_image->size(600, 400);
        $out .= "<a href='{$post->url}'>";
        $out .= "<img src='{$thumb->url}' alt='{$thumb->description}' width='600' height='400' loading='lazy'>";
        $out .= "</a>";
    }

    $out .= "<div class='post-card-body'>";
    $date = date('j F Y', $post->getUnformatted('date'));
    $out .= "<time datetime='" . date('Y-m-d', $post->getUnformatted('date')) . "'>{$date}</time>";
    $out .= "<h2><a href='{$post->url}'>{$post->title}</a></h2>";

    if ($post->summary) {
        $out .= "<p>{$post->summary}</p>";
    }

    $out .= "<a href='{$post->url}' class='read-more'>Read more</a>";
    $out .= "</div></article>";

    return $out;
}

/**
 * Render Article JSON-LD schema
 */
function renderArticleSchema($page): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $page->title,
        'description' => $page->get('seo_description|summary|'),
        'datePublished' => date('c', $page->getUnformatted('date')),
        'dateModified' => date('c', $page->modified),
        'url' => $page->httpUrl,
    ];

    if ($page->featured_image) {
        $schema['image'] = $page->featured_image->httpUrl;
    }

    if ($page->blog_author && $page->blog_author->id) {
        $schema['author'] = [
            '@type' => 'Person',
            'name' => $page->blog_author->title,
        ];
    }

    return "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
}
```

## RSS Feed

Create a `blog-rss.php` template:

```php
<?php namespace ProcessWire;

header('Content-Type: application/rss+xml; charset=UTF-8');

$posts = $pages->find("template=blog-post, sort=-date, limit=20");
$blogUrl = $pages->get('/blog/')->httpUrl;
$siteTitle = $pages->get('/')->title;

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?= $siteTitle ?> Blog</title>
    <link><?= $blogUrl ?></link>
    <description><?= $pages->get('/blog/')->get('summary|body|') ?></description>
    <language>en-gb</language>
    <atom:link href="<?= $page->httpUrl ?>" rel="self" type="application/rss+xml" />
    <?php foreach ($posts as $post): ?>
    <item>
        <title><?= $sanitizer->entities($post->title) ?></title>
        <link><?= $post->httpUrl ?></link>
        <guid isPermaLink="true"><?= $post->httpUrl ?></guid>
        <pubDate><?= date('r', $post->getUnformatted('date')) ?></pubDate>
        <description><?= $sanitizer->entities($post->get('summary|')) ?></description>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
```

Set this template's content type to `application/rss+xml` and disable `_main.php` append for it.

## Tagging with URL Segments

For tag filtering without separate tag pages, enable URL segments on the blog-index template:

```php
// In blog-index.php
$tag = $sanitizer->selectorValue($input->urlSegment1);
if ($tag) {
    $posts = $pages->find("template=blog-post, blog_tags.name=$tag, sort=-date, limit=12");
    $content = "<h1>Posts tagged: " . $sanitizer->entities($tag) . "</h1>";
} else {
    $posts = $pages->find("template=blog-post, sort=-date, limit=12");
    $content = "<h1>{$page->title}</h1>";
}
```
