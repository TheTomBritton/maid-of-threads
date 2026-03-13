# /new-template — Create a ProcessWire Template

## Purpose
Create a new ProcessWire template file with associated field definitions. Generates the PHP template, updates the field/template exports, and suggests appropriate fields based on the template's purpose.

## Workflow

### Step 1: Gather Information
Ask:
1. **Template name?** (e.g. "team-member", "service", "event")
2. **What is this template for?** (brief description of its purpose)
3. **Any specific fields you already know you need?**
4. **Will this be a listing page, a detail page, or standalone?** (affects whether we also need a parent/index template)

### Step 2: Recommend Fields
Based on the template purpose, suggest a complete set of fields:

- Reference `.claude/instructions/processwire-fundamentals.md` for field type options
- Include field name, type, label, description, and column width
- Group related fields logically
- Suggest appropriate field widths for a sensible admin layout (e.g. two 50% fields side by side)

Present the field list and wait for approval.

### Step 3: Create Template File
Create the PHP template file in `site/templates/` following the conventions in `.claude/instructions/template-development.md`:

- Use the delayed output / _main.php wrapper approach
- Set `$content` and other region variables
- Include proper PW API calls to fetch and display data
- Add helpful comments
- Ensure responsive, semantic HTML output

### Step 4: Update Exports
- Add new fields to `site/install/fields.json`
- Add the new template (with field assignments) to `site/install/templates.json`
- If a listing/index template pair, create both templates

### Step 5: Update Install Script
Append the new field/template definitions to `scripts/install-fields.php` so they'll be created on import.

### Step 6: Create Companion Templates if Needed
If this is a detail page (e.g. `team-member.php`), offer to create:
- A listing/index template (e.g. `team.php`) that lists children
- A corresponding page tree entry in `site/install/pages-tree.json`

## Field Naming Conventions
- All lowercase with underscores: `featured_image`, `phone_number`
- Prefix repeated concepts: `service_icon`, `service_summary`
- Never use reserved PW field names: `name`, `template`, `parent`, `id`, `status`, `created`, `modified`

## Template File Conventions
- Filename: lowercase with hyphens: `team-member.php`
- Always set `$browser_title` and `$body_class` variables for `_main.php`
- Include SEO fields output if the template has `seo_title` / `seo_description`
