# Frontend Stack Selection Guide

## Decision Framework

Choose the frontend stack based on the project's needs, not habit. Use this guide to recommend the right tools for each project.

## CSS Frameworks

### Tailwind CSS (Default Recommendation)
**Best for**: Most projects. Brochure sites, blogs, dashboards, custom designs.
**Why**: Utility-first, highly customisable, small production builds with PurgeCSS, excellent documentation.
**Avoid when**: The project is very small (under 5 pages) with minimal styling, or when the client needs to edit CSS directly.

**Setup included in this repo by default:**
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init
```

Entry point: `site/assets/src/app.css`
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

Build command: `npx tailwindcss -i ./site/assets/src/app.css -o ./site/assets/dist/app.css --watch`

### Bootstrap 5
**Best for**: Projects where the client or another developer may maintain the site. Admin panels. Sites needing robust component library quickly.
**Why**: Well-known, comprehensive components (modals, dropdowns, carousels), good documentation.
**Avoid when**: You need a highly custom design (Bootstrap sites tend to look similar), or file size is a concern.

**Setup:**
```bash
npm install bootstrap @popperjs/core
```

### Pico CSS
**Best for**: Very small sites (under 10 pages), documentation sites, projects where you want semantic HTML to look good without classes.
**Why**: Classless or minimal-class approach. Drop it in and HTML looks good immediately. ~10KB.
**Avoid when**: Complex layouts, heavy customisation needed.

**Setup:**
```bash
npm install @picocss/pico
```

### No Framework (Custom CSS)
**Best for**: When the design is very specific, performance is critical, or you want full control.
**Why**: Smallest possible CSS, no bloat, complete design freedom.
**Avoid when**: Speed of development is a priority.

**Approach**: Use CSS custom properties, modern CSS features (grid, container queries, nesting).

## JavaScript

### HTMX (Default)
**Best for**: All projects with any dynamic behaviour. ProcessWire's server-side rendering pairs naturally with HTMX's hypermedia approach.
**Why**: ~14KB, progressive enhancement, no build step, no client-side state to manage. Let the server do the work.
**Use for**: Live search, infinite scroll, form submission without reload, tab content loading, filtering, pagination, partial page updates.

```html
<button hx-get="/api/load-more/" hx-target="#results" hx-swap="beforeend">
    Load More
</button>
```

**Setup (CDN for prototyping, local for production):**
```html
<script src="https://unpkg.com/htmx.org@2.x.x/dist/htmx.min.js"></script>
```

**ProcessWire endpoint pattern** — return a rendered partial from a PW template, not JSON:
```php
// templates/partials/search-results.php
if ($config->ajax) {
    // Return rendered HTML fragment
    echo $pages->find("title%={$input->get->q}, limit=12")->render('/partials/card');
    exit;
}
```

```html
<input type="search" name="q"
       hx-get="/search/"
       hx-target="#results"
       hx-trigger="input changed delay:300ms"
       hx-swap="innerHTML">
<div id="results"></div>
```

**Default to HTMX. Only escalate if:**
1. You need local reactive state (e.g. a client-side cart) → use Alpine.js
2. HTMX genuinely cannot meet the requirement → then consider a JS framework

### Alpine.js
**Best for**: Local reactive UI state that doesn't need a server round-trip.
**Why**: ~17KB, works directly in HTML with directives, no build step needed. Complements HTMX well — use them together.
**Use for**: Dropdowns, modals, toggles, accordions, client-side cart state.

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Menu</button>
    <nav x-show="open" x-transition>...</nav>
</div>
```

**Setup:**
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

### Vanilla JS
**Best for**: Truly minimal enhancement where even HTMX is overkill — a single event listener, a scroll effect.
**Why**: No dependency, no build step. Fine for one-off behaviours.
**Avoid when**: You need dynamic content loading or any server interaction — use HTMX instead.

### Swiper
**Best for**: When you need a carousel/slider. The gold standard.
**Why**: Touch-friendly, accessible, highly configurable, well-maintained.
**Setup:**
```bash
npm install swiper
```

### GSAP (GreenSock)
**Best for**: Projects needing polished animations.
**Why**: Industry standard for web animation, excellent performance, timeline sequencing.
**Setup:**
```bash
npm install gsap
```

## Build Tools

### Vite (Recommended for Tailwind projects)
**Best for**: Most projects. Fast dev server, efficient builds, HMR.
**Setup:**
```bash
npm install -D vite
```

**vite.config.js:**
```js
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'site/assets/dist',
        rollupOptions: {
            input: 'site/assets/src/app.css',
        },
    },
});
```

### Tailwind CLI (Simple alternative)
**Best for**: When Tailwind is the only build tool needed and you want to avoid Vite complexity.
**Setup**: Already configured via `package.json` scripts in this repo.

## Recommendations by Project Type

### Business Brochure (5–20 pages)
- **CSS**: Tailwind CSS
- **JS**: HTMX (contact form, nav) + Alpine.js if local state needed
- **Build**: Tailwind CLI
- **Why**: Fast to build, easy to customise, small output

### Blog / News Site
- **CSS**: Tailwind CSS
- **JS**: Vanilla JS + HTMX for infinite scroll / live search
- **Build**: Vite
- **Why**: HTMX makes dynamic listing pages feel modern without SPA complexity

### Portfolio / Creative Site
- **CSS**: Tailwind CSS or Custom CSS (depends on design complexity)
- **JS**: GSAP for animations, Swiper for galleries
- **Build**: Vite
- **Why**: Creative sites need animation control and custom visual treatment

### Ecommerce
- **CSS**: Tailwind CSS
- **JS**: Alpine.js for cart/product interactions, HTMX for dynamic filtering
- **Build**: Vite
- **Why**: Alpine handles reactive cart state, HTMX manages product filtering without full page reloads

### Directory / Listing Site
- **CSS**: Tailwind CSS
- **JS**: Alpine.js + HTMX
- **Build**: Vite
- **Why**: Filter-heavy sites benefit from HTMX's server-driven updates

### Simple/Small Site (under 5 pages)
- **CSS**: Pico CSS or Custom CSS
- **JS**: HTMX if any dynamic behaviour needed, otherwise none
- **Build**: None needed
- **Why**: Minimal overhead for minimal sites

## Font Loading

Always self-host fonts for performance and privacy (no Google Fonts CDN):

```css
@font-face {
    font-family: 'Inter';
    src: url('/site/templates/assets/fonts/inter-v13-latin-regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
```

Use [google-webfonts-helper](https://gwfh.mranftl.com/fonts) to download self-hosted font files.

## CDN vs Local Assets

**Default: local assets.** Self-host everything for:
- Privacy (no third-party tracking)
- Reliability (no external dependency)
- Performance (reduced DNS lookups)

Only use CDN for:
- Alpine.js / HTMX (small enough that CDN is acceptable for prototyping)
- Font Awesome icons (if using — consider Heroicons or Lucide as lighter alternatives)

For production, always bundle everything locally.
