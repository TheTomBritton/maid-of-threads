<?php namespace ProcessWire;

/**
 * ready.php — Runs after the PW API is fully loaded
 *
 * Add hooks and runtime customisations here.
 * This file runs on every request after PW is bootstrapped.
 */

// Example hooks (uncomment and modify as needed):

// Custom 404 handling for URL segments
// $wire->addHookAfter('ProcessPageView::pageNotFound', function(HookEvent $event) {
//     // Custom 404 logic
// });

// Auto-set page sort order on save
// $wire->addHookBefore('Pages::save', function(HookEvent $event) {
//     $page = $event->arguments(0);
//     // Custom save logic
// });
