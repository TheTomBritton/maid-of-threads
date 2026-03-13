<?php namespace ProcessWire;

/**
 * Blog RSS Feed Template — blog-rss.php
 *
 * Outputs an RSS 2.0 XML feed of the latest 20 blog posts.
 *
 * IMPORTANT: This template requires the following settings in
 * ProcessWire admin (Setup > Templates > blog-rss > Files):
 *   - noPrependTemplateFile = 1
 *   - noAppendTemplateFile = 1
 *
 * These prevent _init.php and _main.php from wrapping the output.
 */

// Self-contained: _init.php is skipped, so load helpers and vars here
include_once(__DIR__ . '/_func.php');
$site_name = 'Maid of Threads';
$blog_page = $pages->get('/blog/');

header('Content-Type: application/rss+xml; charset=utf-8');

$posts = $pages->find("template=blog-post, sort=-date, limit=20");

// Channel metadata
$channel_title = $site_name . ' — Blog';
$channel_desc = $page->body ? $sanitizer->text($page->body) : "Latest posts from {$site_name}";
$channel_url = $blog_page->httpUrl;
$feed_url = $page->httpUrl;
$build_date = date('r');

// Determine the most recent post date for lastBuildDate
if ($posts->count) {
    $build_date = date('r', $posts->first->getUnformatted('date'));
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title><?= htmlspecialchars($channel_title) ?></title>
        <link><?= $channel_url ?></link>
        <description><?= htmlspecialchars($channel_desc) ?></description>
        <language>en-gb</language>
        <lastBuildDate><?= $build_date ?></lastBuildDate>
        <atom:link href="<?= $feed_url ?>" rel="self" type="application/rss+xml"/>

        <?php foreach ($posts as $post):
            $pub_date = date('r', $post->getUnformatted('date'));
            $summary = $post->summary
                ? $sanitizer->text($post->summary)
                : truncate($sanitizer->text($post->body), 300);

            // Featured image as enclosure
            $enclosure = '';
            if ($post->featured_image && $post->featured_image->count) {
                $img = $post->featured_image->first;
                $enclosure = sprintf(
                    '<enclosure url="%s" length="%d" type="%s"/>',
                    htmlspecialchars($img->httpUrl),
                    $img->filesize,
                    'image/' . pathinfo($img->filename, PATHINFO_EXTENSION)
                );
            }

            // Categories
            $cats = $post->parents("template=blog-category");
        ?>
        <item>
            <title><?= htmlspecialchars($post->title) ?></title>
            <link><?= $post->httpUrl ?></link>
            <guid isPermaLink="true"><?= $post->httpUrl ?></guid>
            <pubDate><?= $pub_date ?></pubDate>
            <description><![CDATA[<?= $summary ?>]]></description>
            <?= $enclosure ?>
            <?php foreach ($cats as $cat): ?>
            <category><?= htmlspecialchars($cat->title) ?></category>
            <?php endforeach; ?>
        </item>
        <?php endforeach; ?>

    </channel>
</rss>
