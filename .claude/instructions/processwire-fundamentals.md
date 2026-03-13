# ProcessWire Fundamentals

## Architecture Overview

ProcessWire is a PHP content management system built around a simple but powerful concept: everything is a page. Pages have templates, and templates have fields. The API provides a jQuery-like selector syntax for finding and manipulating content.

### Core Concepts

**Pages** — every piece of content is a page in a hierarchical tree. The homepage is the root. Pages have a URL determined by their position in the tree.

**Templates** — define what fields a page has and what PHP file renders it. A template named "basic-page" uses the file `site/templates/basic-page.php`.

**Fields** — individual data containers (text, images, files, references to other pages, etc.). Fields are defined once and can be reused across multiple templates.

**The $page variable** — in any template file, `$page` refers to the current page being viewed. Access fields with `$page->fieldName`.

**The $pages variable** — the global page finder. Use selectors to query content: `$pages->find("template=blog-post, sort=-date, limit=10")`.

## API Essentials

### Selectors
ProcessWire selectors are strings used to find pages:

```php
// Basic selectors
$pages->find("template=blog-post");
$pages->find("template=blog-post, limit=10, sort=-date");
$pages->find("parent=/blog/, template=blog-post");
$pages->find("title%=keyword");  // Contains
$pages->find("title^=Hello");    // Starts with
$pages->find("date>=2024-01-01");
$pages->find("featured_image.count>0");  // Has images

// Get a single page
$page = $pages->get("template=blog-post, sort=-date");  // First match
$page = $pages->get("/about/");  // By path
$page = $pages->get(1024);       // By ID

// Children and siblings
$page->children("template=service, sort=sort");
$page->siblings("template=blog-post");
$page->parent;
$page->parents;
$page->rootParent;
```

### Common API Methods

```php
// Page properties
$page->title        // Title field
$page->name          // URL segment
$page->url           // Full URL path
$page->httpUrl       // Full URL with protocol
$page->template      // Template object
$page->parent        // Parent page
$page->id            // Page ID
$page->created       // Created timestamp
$page->modified      // Modified timestamp
$page->createdStr    // Created as formatted string

// Field access
$page->body                    // Get field value
$page->get('body|summary')     // Get body, or summary if body is empty

// Image fields
$image = $page->featured_image;
$image->url                    // Image URL
$image->width                  // Width
$image->height                 // Height
$image->description            // Alt text
$thumb = $image->size(400, 300);  // Resize
$thumb = $image->width(800);      // Resize by width

// Multi-image fields
foreach ($page->images as $img) {
    echo "<img src='{$img->width(600)->url}' alt='{$img->description}'>";
}

// Page reference fields
$related = $page->related_pages;  // PageArray
foreach ($related as $r) {
    echo "<a href='{$r->url}'>{$r->title}</a>";
}

// Repeater fields
foreach ($page->testimonials as $item) {
    echo $item->quote;
    echo $item->author_name;
}

// Options fields
$page->colour->title;  // Selected option title
$page->colour->value;  // Selected option value
```

### Sanitizer

Always sanitise user input:

```php
$clean = $sanitizer->text($input->get->q);        // Plain text
$clean = $sanitizer->selectorValue($input->get->q); // For use in selectors
$clean = $sanitizer->int($input->get->id);          // Integer
$clean = $sanitizer->email($input->post->email);    // Email
$clean = $sanitizer->url($input->post->url);        // URL
```

### Files and Paths

```php
$config->paths->templates   // /site/templates/
$config->paths->assets      // /site/assets/
$config->paths->root        // Installation root
$config->urls->templates    // /site/templates/ (URL)
$config->urls->assets       // /site/assets/ (URL)
```

## Template File Conventions

### Delayed Output Strategy (Recommended)

This project uses the delayed output approach with `_init.php` and `_main.php`:

1. **`_init.php`** — runs before every template. Sets up variables, includes helpers.
2. **`template-name.php`** — the specific template. Sets region variables like `$content`, `$sidebar`.
3. **`_main.php`** — runs after the template. Contains the HTML wrapper and outputs regions.

Enable in `site/config.php`:
```php
$config->prependTemplateFile = '_init.php';
$config->appendTemplateFile = '_main.php';
```

### Example Template Structure

**_init.php:**
```php
<?php namespace ProcessWire;

// Default region variables (templates override these)
$browser_title = $page->get('seo_title|title');
$meta_description = $page->get('seo_description|summary|');
$body_class = $page->template->name;
$content = '';
$sidebar = '';

// Include helper functions
include_once('./_func.php');
```

**basic-page.php:**
```php
<?php namespace ProcessWire;

// Build the page content
$content = "<h1>{$page->title}</h1>";

if ($page->featured_image) {
    $img = $page->featured_image->width(1200);
    $content .= "<img src='{$img->url}' alt='{$img->description}' width='{$img->width}' height='{$img->height}'>";
}

$content .= $page->body;
```

**_main.php:**
```php
<?php namespace ProcessWire;
?><!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $browser_title ?> | <?= $pages->get('/')->title ?></title>
    <meta name="description" content="<?= $sanitizer->entities($meta_description) ?>">
    <link rel="stylesheet" href="<?= $config->urls->templates ?>assets/dist/app.css">
</head>
<body class="<?= $body_class ?>">

    <header>
        <nav aria-label="Main navigation">
            <?php foreach ($pages->get('/')->children("include=hidden") as $item): ?>
                <a href="<?= $item->url ?>"<?= $item->id === $page->rootParent->id ? ' aria-current="page"' : '' ?>>
                    <?= $item->title ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </header>

    <main id="content">
        <?= $content ?>
    </main>

    <?php if ($sidebar): ?>
    <aside>
        <?= $sidebar ?>
    </aside>
    <?php endif; ?>

    <footer>
        <p>&copy; <?= date('Y') ?> <?= $pages->get('/')->title ?>. All rights reserved.</p>
    </footer>

    <script src="<?= $config->urls->templates ?>assets/dist/app.js" defer></script>
</body>
</html>
```

## Hooks

ProcessWire hooks allow you to modify behaviour without editing core files. Add hooks in `site/ready.php` or `site/init.php`:

```php
// Modify page output before render
$wire->addHookAfter('Page::render', function(HookEvent $event) {
    // Modify rendered output
});

// Add custom page method
$wire->addHook('Page::fullUrl', function(HookEvent $event) {
    $event->return = $event->object->httpUrl;
});

// Before page save
$wire->addHookBefore('Pages::save', function(HookEvent $event) {
    $page = $event->arguments(0);
    // Validate or modify before saving
});
```

## URL Segments

Enable URL segments on a template to handle dynamic URLs:

```php
// In config for the template: urlSegments = true
// In the template file:
$segment = $input->urlSegment1;

if ($segment) {
    $item = $pages->get("template=product, name=$segment");
    if (!$item->id) throw new Wire404Exception();
    // Render single item
} else {
    // Render listing
}
```

## Pagination

```php
// Template must have "Allow Page Numbers" enabled
$items = $pages->find("template=blog-post, sort=-date, limit=12");

foreach ($items as $item) {
    // Render each item
}

// Pagination navigation
echo $items->renderPager();
```

## File Organisation Best Practices

- Keep template files focused — extract reusable markup into `_func.php` or separate include files
- Use `site/templates/partials/` for reusable template fragments
- Keep CSS/JS source in `site/assets/src/` and build to `site/assets/dist/`
- Store site configuration in `site/config.php`, never hard-code values in templates
