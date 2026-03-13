# /new-page — Create Page Tree Entries

## Purpose
Define new pages in the site's page tree structure with appropriate templates, parent assignments, and placeholder content.

## Workflow

### Step 1: Gather Information
Ask:
1. **Page title?** (human-readable title)
2. **Parent page?** (where in the tree — e.g. root `/`, under `/about/`, etc.)
3. **Template?** (which template to use — offer available templates from `site/install/templates.json`)
4. **Will this page have children?** (e.g. a "Services" page with individual service sub-pages)
5. **How many child pages, and what are they?** (if applicable)

### Step 2: Generate Page Entries
Add entries to `site/install/pages-tree.json`:

```json
{
  "name": "url-slug",
  "title": "Page Title",
  "template": "template-name",
  "parent": "/parent-path/",
  "status": "published",
  "sort": 1,
  "content": {
    "body": "<p>Placeholder content for this page.</p>",
    "summary": "Brief description for listings and meta."
  }
}
```

### Step 3: Create Child Pages (if needed)
If the page will have children, generate entries for each child page with:
- Correct parent path
- Appropriate template
- Sequential sort order
- Placeholder content relevant to the page topic

### Step 4: Verify Template Exists
Check that the specified template exists in `site/install/templates.json`. If not, offer to create it using the `/new-template` workflow.

### Step 5: Update Navigation
If the page should appear in main navigation, flag this for the operator. Navigation in PW is typically handled by querying children of the homepage or pages with a specific template/status.

### Step 6: Output Summary
Display the updated page tree as an indented hierarchy:
```
/ (home)
├── /about/ (basic-page)
├── /services/ (services-index)
│   ├── /services/web-design/ (service)
│   ├── /services/branding/ (service)
│   └── /services/hosting/ (service)
├── /blog/ (blog-index)
└── /contact/ (contact)
```

## Conventions
- Page names (URL slugs): lowercase, hyphens, no special characters
- Keep the tree shallow where possible (max 3 levels deep for most sites)
- Use index/listing templates for parent pages with multiple children
- Always include sort order for predictable menu ordering
