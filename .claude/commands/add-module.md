# /add-module — Install and Configure a ProcessWire Module

## Purpose
Install a free ProcessWire module, configure it with sensible defaults, and document the setup.

## Workflow

### Step 1: Identify the Module
If the operator specifies a module, look it up. If they describe a need (e.g. "I need a form builder"), consult `.claude/instructions/module-recommendations.md` and recommend the best free option with reasoning.

### Step 2: Installation Method
ProcessWire modules can be installed in several ways:

**Via Composer (preferred if available):**
```bash
composer require <vendor>/<module-name>
```

**Via PW Modules Directory:**
Provide the download URL and instruct to place in `site/modules/<ModuleName>/`

**Via Git clone (preferred for GitHub modules):**
```bash
git clone <repo-url> site/modules/<ModuleName>
```

**Important:** Clone modules on the host machine, NOT inside Docker — the container may have DNS resolution issues with GitHub. Modules are gitignored so they won't be committed to the project repo.

Always check if the module is available via Composer first. If not, provide the GitHub/modules directory URL.

### Step 3: Configuration
After installation, provide:
1. Any required module configuration settings
2. Recommended configuration for the project context
3. Template code snippets showing how to use the module
4. Any hooks or API usage patterns

### Step 4: Update Documentation
- Add the module to the project's module list in `README.md`
- Note any template dependencies or required fields
- If the module requires fields, add them to the exports

### Step 5: Verify Compatibility
Check:
- PHP 8.2+ compatibility
- ProcessWire 3.0.229+ compatibility
- No conflicts with already-installed modules
- No Pro module dependencies

## Common Module Tasks

### "I need a contact form"
→ Recommend FormBuilder alternative: **FrontendForms** module
→ Create the form template code
→ Set up email notification configuration

### "I need better image handling"
→ Recommend **Croppable Image 3** for focal-point cropping
→ Configure image field settings for responsive output

### "I need SEO tools"
→ Recommend **SeoMaestro** for meta tags and social sharing
→ Set up fields and template integration

### "I need a sitemap"
→ Recommend **MarkupSitemap** module
→ Configure with appropriate change frequencies and priorities

Always reference `.claude/instructions/module-recommendations.md` for the full curated list.
