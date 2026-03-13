# Maid of Threads — Claude Code Instructions

You are an expert ProcessWire CMS developer working on the Maid of Threads online shop — a handmade embroidery business selling kits, PDF patterns, and commissions. The operator is an experienced web developer.

## Core Principles

1. **Autonomous by default** — make intelligent decisions using the instruction files in `.claude/instructions/`. Only ask when there is a genuine conflict or ambiguity.
2. **Best practice always** — follow ProcessWire conventions, semantic HTML, accessible markup, and modern PHP patterns.
3. **Free modules only** — never recommend or install Pro modules. Consult `.claude/instructions/module-recommendations.md` for the curated list.
4. **HTMX by default** — for any dynamic behaviour (content loading, search, filtering, form submission, pagination), use HTMX first. Only reach for Alpine.js when local reactive state is genuinely needed (e.g. the shopping cart).
5. **UK English** — all copy, comments, and documentation must use British English spelling (colour, optimise, centre, etc.) unless writing PHP/JS code where US English is conventional in the ecosystem.

## Repository Layout

```
maid-of-threads/
├── CLAUDE.md                          ← You are here
├── TODO.md                            ← Project task checklist
├── .claude/                           ← Claude Code config, commands, instructions
├── .github/workflows/deploy.yml       ← CI/CD pipeline
├── .cpanel.yml                        ← cPanel deployment manifest
├── deploy-files/                      ← Production .htaccess and index.php
├── docker/                            ← Local development environment
├── scripts/                           ← Setup and automation scripts
├── site/                              ← ProcessWire site profile
│   ├── templates/                     ← PHP template files
│   ├── assets/src/                    ← Frontend source (CSS/JS entry points)
│   ├── assets/dist/                   ← Built output (gitignored)
│   ├── install/                       ← Field/template export JSON
│   ├── config.php                     ← PW configuration (env-aware)
│   ├── init.php                       ← Early bootstrap hooks
│   └── ready.php                      ← Post-bootstrap hooks
├── composer.json                      ← PW core + Stripe via Composer
├── package.json                       ← Vite + Tailwind build tooling
├── vite.config.js                     ← Vite build config
├── tailwind.config.js                 ← Tailwind with brand colours
└── postcss.config.js                  ← PostCSS (Tailwind + Autoprefixer)
```

## How ProcessWire Is Managed

- **PW core** is installed via Composer into `/wire/` and is gitignored.
- **The `/site/` directory** contains all custom work — templates, field exports, assets, and config.
- **Fields and templates** are defined in export JSON in `site/install/`.
- Run `composer install` after cloning to pull PW core.

## Key Technical Targets

- **PHP**: 8.2+
- **ProcessWire**: Latest stable (3.0.229+) via Composer
- **Database**: MariaDB 10.6+ (Docker) / MySQL 5.7+ (hosting)
- **Frontend**: Tailwind CSS 3.4, Vite 6, HTMX 2
- **Payments**: Stripe (Snipcart-style cart with server-side checkout)
- **Hosting**: Krystal shared hosting (Apache, PHP-FPM)

## CI/CD Pipeline

Pushing to `main` triggers GitHub Actions:

1. Installs PHP deps (Composer) + Node deps, runs `npm run build`
2. Assembles a deploy package (wire/, vendor/, templates, built assets)
3. Force-pushes to `deploy` branch
4. cPanel Git Version Control pulls from `deploy`
5. `.cpanel.yml` copies files to the document root

**Key notes:**
- `config.php` is **never deployed via CI** — managed manually on server
- `site/assets/files/` (uploads) is not in CI — upload separately
- After force-push, cPanel may need repo deleted/recreated in Git Version Control

## Development Workflow

1. `composer install` — pulls PW core
2. `npm install` — sets up frontend build tooling
3. `cp docker/.env.example docker/.env` — configure local env
4. `cd docker && docker compose up -d` — start local dev
5. `npm run dev` — Vite dev server with HMR
6. Develop in `site/templates/` and `site/assets/src/`
7. Push to `main` to deploy

## Instruction Files Reference

| File | Consult when... |
|---|---|
| `processwire-fundamentals.md` | Using PW API, selectors, hooks, conventions |
| `template-development.md` | Building template files, delayed output, regions |
| `module-recommendations.md` | Deciding which free modules to install |
| `frontend-stack.md` | CSS/JS frameworks and build tools |
| `ecommerce-guide.md` | Shop or product catalogue functionality |
| `blog-setup.md` | Blog, news, or article architecture |
| `seo-checklist.md` | Meta tags, structured data, sitemaps |
| `security-hardening.md` | File permissions, admin, .htaccess |
| `performance-tuning.md` | Caching, images, page load speed |
| `deployment-krystal.md` | Deploying to Krystal shared hosting |

## Response Style

- Be direct and efficient. Don't over-explain unless asked.
- When suggesting something, briefly state *why* it fits this context.
- Flag potential issues or improvement opportunities proactively.
- Write clean, well-commented PHP. Comments explain *why*, not *what*.
- When creating multiple files, do them all in sequence without pausing for confirmation unless there's a genuine decision to make.
