# /export-fields — Export Field & Template Definitions

## Purpose
Generate ProcessWire-compatible JSON export files for all custom fields, templates, and page tree structure defined in this project. These files allow a fresh PW installation to be configured programmatically.

## Workflow

### Step 1: Scan Current State
Read through all template files in `site/templates/` and identify:
- Every field referenced in template code (via `$page->field_name`)
- Every template file that corresponds to a PW template
- Parent-child relationships implied by the template structure

### Step 2: Generate Field Export
Create/update `site/install/fields.json` with every custom field.

Each field entry must include:
```json
{
  "name": "field_name",
  "type": "FieldtypeText",
  "label": "Field Label",
  "description": "Help text shown to content editors",
  "required": false,
  "columnWidth": 100,
  "tags": "project",
  "settings": {}
}
```

**Field type mappings:**
| Common need | Fieldtype | Notes |
|---|---|---|
| Short text | FieldtypeText | Set maxlength |
| Long text (plain) | FieldtypeTextarea | Set rows |
| Rich text | FieldtypeTextarea | Set inputfield to InputfieldCKEditor |
| Single image | FieldtypeImage | Set maxFiles=1 |
| Multiple images | FieldtypeImage | Set maxFiles=0 (unlimited) |
| File uploads | FieldtypeFile | Set extensions |
| Checkbox | FieldtypeCheckbox | |
| Select dropdown | FieldtypeOptions | Include options list |
| Page reference | FieldtypePage | Set parent, template, inputfield |
| URL | FieldtypeURL | |
| Email | FieldtypeEmail | |
| Integer | FieldtypeInteger | |
| Float | FieldtypeFloat | |
| Date/time | FieldtypeDatetime | Set dateOutputFormat |
| Repeater | FieldtypeRepeater | Define sub-fields |
| Toggle | FieldtypeToggle | On/off with labels |

### Step 3: Generate Template Export
Create/update `site/install/templates.json` with every template.

Each template entry must include:
```json
{
  "name": "template-name",
  "label": "Template Label",
  "fields": ["title", "body", "summary", "featured_image"],
  "fieldWidths": {
    "title": 100,
    "body": 100,
    "summary": 50,
    "featured_image": 50
  },
  "noChildren": false,
  "noParents": false,
  "allowedChildTemplates": [],
  "allowedParentTemplates": [],
  "urlSegments": false,
  "https": true,
  "tags": "project"
}
```

### Step 4: Generate Page Tree Export
Create/update `site/install/pages-tree.json` with the initial page structure:
```json
[
  {
    "name": "about",
    "title": "About Us",
    "template": "basic-page",
    "parent": "/",
    "status": "published",
    "sort": 1
  }
]
```

### Step 5: Update Install Script
Regenerate `scripts/install-fields.php` to import all three JSON files via the PW API.

### Step 6: Output Summary
List all fields, templates, and pages that were exported, with counts:
- X fields exported
- X templates exported
- X pages in tree
- Any warnings (orphaned fields, missing references, etc.)

## Validation Rules
- Every field referenced in a template file must exist in fields.json
- Every template file must have a corresponding entry in templates.json
- No duplicate field names
- No reserved PW field names used
- All page references point to valid templates
