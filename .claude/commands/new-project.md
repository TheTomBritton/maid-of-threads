# /new-project — Full Project Scaffold Wizard

## Purpose
Scaffold a new ProcessWire website from this template repository. Gather requirements, suggest the right stack, install dependencies, create templates, define fields, and prepare the local dev environment.

## Workflow

### Step 1: Gather Requirements
Ask the operator these questions (all at once, don't drip-feed):

1. **What is the site for?** (e.g. business brochure, portfolio, blog, online shop, directory, membership site)
2. **Client/project name?** (used for naming, Docker container, database, etc.)
3. **Roughly how many pages?** (helps determine template complexity)
4. **Any specific features needed?** (contact forms, galleries, search, maps, events, etc.)
5. **Is there an existing design/brand to work from, or building from scratch?**
6. **Any preference on CSS framework, or should I recommend one?**

### Step 2: Recommend Stack
Based on answers, consult these instruction files and propose a stack:

- Read `.claude/instructions/frontend-stack.md` — recommend CSS framework with reasoning
- Read `.claude/instructions/module-recommendations.md` — list modules to install with brief justification for each
- If ecommerce: read `.claude/instructions/ecommerce-guide.md`
- If blog/news: read `.claude/instructions/blog-setup.md`

Present the recommendation as a clear summary and wait for approval before proceeding.

### Step 3: Configure Project
Once approved:

1. Update `composer.json` with the project name
2. Update `package.json` with the project name and any additional frontend dependencies
3. Update `docker/.env.example` → copy to `docker/.env` with project-specific values:
   - `PROJECT_NAME` — sanitised project name
   - `DB_NAME` — database name
   - `DB_USER` / `DB_PASS` — dev credentials
   - `PW_ADMIN_USER` / `PW_ADMIN_PASS` — default admin credentials for dev
4. If a different CSS framework was chosen, swap out Tailwind config files accordingly
5. Run `composer install`
6. Run `npm install`

### Step 4: Scaffold Templates
Based on the site type, create the appropriate template files in `site/templates/`:

**Always create:**
- `_init.php` — API variable setup, helper includes
- `_main.php` — HTML wrapper/shell (doctype, head, nav, footer)
- `_func.php` — Reusable helper functions
- `home.php` — Homepage
- `basic-page.php` — Generic content page
- `_404.php` — Custom 404 page

**Create as needed:**
- `blog-index.php` + `blog-post.php` — if blog features needed
- `product-list.php` + `product.php` — if ecommerce
- `contact.php` — if contact form needed
- `search.php` — if search functionality needed
- `sitemap.xml.php` — XML sitemap template

### Step 5: Define Fields
Generate the field export JSON in `site/install/fields.json` containing all fields needed for the chosen templates. Always include:

- `title` (built-in, but configure per template)
- `body` — CKEditor rich text
- `summary` — plain textarea for excerpts/meta descriptions
- `featured_image` — single image field
- `images` — multi-image gallery field
- `seo_title` — plain text, max 60 chars
- `seo_description` — textarea, max 160 chars

Add template-specific fields based on the site type.

### Step 6: Define Templates Export
Generate `site/install/templates.json` with all template definitions and their field assignments.

### Step 7: Generate Page Tree
Create `site/install/pages-tree.json` defining the initial page structure with:
- Page name (URL slug)
- Template assignment
- Parent page
- Status (published/hidden)
- Placeholder content

### Step 8: Create Install Script
Generate `scripts/install-fields.php` — a PHP script that can be run via the PW API (or placed in `site/templates/` temporarily) to import all fields, templates, and pages from the JSON exports.

### Step 9: Summary
Output a clear summary of everything created:
- Templates created (with field assignments)
- Modules to install
- Frontend stack
- Next steps (Docker setup, PW installation, field import)

## Important Notes
- Always consult the instruction files before making recommendations
- All field names use lowercase_with_underscores
- All template filenames use lowercase-with-hyphens.php
- Generate complete, production-ready code — not stubs or placeholders
- Include helpful comments in all PHP files
