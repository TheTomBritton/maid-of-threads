# Module Recommendations (Free Only)

All modules listed here are free and compatible with ProcessWire 3.0.229+ and PHP 8.2+. Only recommend modules from this list unless you've verified a new module meets these criteria.

## Always Install (Every Project)

### SeoMaestro
- **Purpose**: Complete SEO meta tags, Open Graph, Twitter Cards, JSON-LD structured data
- **Install**: `site/modules/SeoMaestro/`
- **URL**: https://github.com/wanze/SeoMaestro
- **Setup**: Creates SEO fieldgroup. Add to all public-facing templates.
- **Usage**:
  ```php
  // In _main.php <head>
  echo $page->seo->render();
  ```

### MarkupSitemap
- **Purpose**: Auto-generates XML sitemap at /sitemap.xml
- **URL**: https://github.com/mikerockett/markup-sitemap
- **Setup**: Install and configure. Exclude admin, 404, and utility templates.

### CronjobDatabaseBackup
- **Purpose**: Automated database backups on a schedule
- **URL**: https://github.com/kixe/CronjobDatabaseBackup
- **Setup**: Configure backup frequency and retention.

### TracyDebugger
- **Purpose**: Development debug toolbar (disable on production)
- **URL**: https://github.com/adrianbj/TracyDebugger
- **Setup**: Install, use `bd()` and `d()` for debugging. **Always disable on production.**

## Conditional (Project-Dependent)

### AllInOneMinify
- **Purpose**: Combines and minifies CSS/JS files
- **URL**: https://modules.processwire.com/modules/all-in-one-minify/
- **When to use**: Only when the project has no build pipeline (no Tailwind/Vite). If using a CSS framework with its own build step, this module is redundant and adds unnecessary complexity.

## SEO & Analytics

### FieldtypeRuntimeMarkup
- **Purpose**: Computed fields that generate values dynamically (useful for SEO previews in admin)
- **URL**: https://modules.processwire.com/modules/fieldtype-runtime-markup/

## Forms

### FrontendForms
- **Purpose**: Build and validate frontend forms with CSRF protection and spam prevention
- **URL**: https://github.com/juergenweb/FrontendForms
- **Why over FormBuilder**: FormBuilder is a Pro module. FrontendForms is free and highly capable.
- **Features**: Validation, CSRF, honeypot, rate limiting, file uploads, email notifications
- **Usage**:
  ```php
  $form = new \FrontendForms\Form('contact');
  $form->setMinTime(3);  // Spam prevention: minimum time to fill
  $form->setMaxTime(3600);

  $name = new \FrontendForms\InputText('name');
  $name->setLabel('Your Name');
  $name->setRule('required');
  $form->add($name);

  $email = new \FrontendForms\InputEmail('email');
  $email->setLabel('Email Address');
  $email->setRule('required');
  $email->setRule('email');
  $form->add($email);

  $message = new \FrontendForms\Textarea('message');
  $message->setLabel('Message');
  $message->setRule('required');
  $form->add($message);

  $submit = new \FrontendForms\Button('submit');
  $submit->setAttribute('value', 'Send Message');
  $form->add($submit);

  if ($form->isValid()) {
      // Send email, redirect, etc.
  }

  echo $form->render();
  ```

## Images

### CroppableImage3
- **Purpose**: Manual image cropping with focal point in the admin
- **URL**: https://github.com/horst-n/CroppableImage3
- **Setup**: Use instead of the default image field when editors need precise crop control.

### TextformatterVideoEmbed
- **Purpose**: Auto-embed YouTube/Vimeo videos from URLs in textarea fields
- **URL**: https://modules.processwire.com/modules/textformatter-video-embed/

## Navigation & Structure

### Breadcrumb Dropdowns
- **Purpose**: Enhanced admin breadcrumb navigation for deep page trees
- **URL**: https://modules.processwire.com/modules/admin-on-steroids/ (part of AdminOnSteroids)

### AdminOnSteroids
- **Purpose**: Collection of admin UI improvements — sticky header, collapsible sidebar, page list tweaks
- **URL**: https://github.com/rolandtoth/AdminOnSteroids

## Content & Editing

### TextformatterMarkdownExtra
- **Purpose**: Markdown support in textarea fields
- **URL**: Ships with ProcessWire (core module, just enable it)

### PageTableExtended
- **Purpose**: Enhanced page table field for building flexible content sections
- **URL**: https://modules.processwire.com/modules/fieldtype-page-table-extended/
- **Use case**: Modular page builder approach — editors can add/reorder content blocks.

### RepeaterImages
- **Purpose**: Enhanced repeater with image support and drag-and-drop sorting
- **URL**: https://modules.processwire.com/modules/fieldtype-repeater-images/

## Security

### LoginRegister
- **Purpose**: Frontend user registration and login (if user accounts needed)
- **URL**: Ships with ProcessWire (core module)

### SessionHandlerDB
- **Purpose**: Store sessions in database rather than filesystem
- **URL**: Ships with ProcessWire (core module, enable it)
- **Why**: Better security, especially on shared hosting. Enable on every production site.

## Utility

### WireMailSMTP
- **Purpose**: Send email via SMTP instead of PHP mail()
- **URL**: https://github.com/horst-n/WireMailSmtp
- **Why**: Reliable email delivery. PHP mail() is often blocked or unreliable on shared hosting. Configure with the host's SMTP settings.

### ProcessPageClone
- **Purpose**: Clone pages with all their content (core module)
- **URL**: Ships with ProcessWire (enable it)
- **Tip**: Especially useful for ecommerce — cloning a product pre-fills all fields.

### ProcessRedirects
- **Purpose**: Manage 301/302 redirects from the PW admin (core module)
- **URL**: Ships with ProcessWire (enable it)
- **Why**: Essential when replacing an existing site or restructuring URLs. No `.htaccess` editing needed.

### ProcessPagesExportImport
- **Purpose**: Export and import pages between PW installations
- **URL**: https://modules.processwire.com/modules/process-pages-export-import/

## Ecommerce (When Needed)

See `.claude/instructions/ecommerce-guide.md` for full details.

### Padloper
- **Purpose**: Full ecommerce module for ProcessWire
- **Note**: Padloper 2 is free. Check current availability.
- **Alternative**: Snipcart integration (external service, simpler setup)

## Blog (When Needed)

See `.claude/instructions/blog-setup.md` for architecture details.

No specific module needed — ProcessWire's native page structure handles blogs perfectly with the right template setup.

## Module Installation Checklist

When installing any module:

1. Check PHP 8.2+ compatibility
2. Check PW 3.0.229+ compatibility
3. Verify it's free (no Pro dependency)
4. Check GitHub for recent activity (avoid abandoned modules)
5. Read the module's README for configuration requirements
6. Test locally before deploying
7. Document the module and its configuration in the project README
