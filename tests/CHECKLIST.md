# Pre-Push Testing Checklist

This is the canonical checklist that must pass before any code is pushed to `main`.
The Tester agent follows this checklist exactly. **A single failure blocks the push.**

## 1. PHP Syntax Validation
- [ ] `php -l` passes on all files in `site/templates/*.php`
- [ ] Exit code 0 for every file

## 2. Frontend Build
- [ ] `npm run build` completes successfully (exit code 0)
- [ ] `site/assets/dist/app.css` exists and is non-empty
- [ ] `site/assets/dist/app.js` exists and is non-empty

## 3. Docker Environment
- [ ] Docker containers running (`docker compose ps` shows web + db healthy)
- [ ] Site responds at http://localhost:8080

## 4. Page Load Tests (Playwright)
- [ ] Homepage loads (200), has H1, has featured products
- [ ] Shop page loads (200), shows product grid with items
- [ ] Product page loads (200), shows title/price/add-to-cart
- [ ] Cart page loads (200), handles empty state correctly
- [ ] Search page loads (200), search input has HTMX attributes
- [ ] Contact page loads (200), form renders with all fields
- [ ] Blog index loads (200), shows post cards
- [ ] Blog post loads (200), has article schema

## 5. User Journey Tests (Playwright)
- [ ] Cart add/update/remove/clear works end-to-end
- [ ] Shop category filter updates product grid via HTMX
- [ ] Mobile hamburger menu opens and closes correctly
- [ ] Full-page search returns results or "no results" message

## 6. Link Validation
- [ ] All desktop nav links return 200
- [ ] All footer links return 200

## 7. Structured Data
- [ ] Product pages contain JSON-LD with `@type: Product`
- [ ] Blog posts contain JSON-LD with `@type: Article`

---

## Result
- [ ] **ALL items above pass**
- [ ] **Ready to push to main**

## Running the Checklist

```bash
# Full checklist (requires Docker running)
npm run test

# Quick checks only (PHP lint + build, no browser tests)
npm run test:quick

# Browser tests only
npm run test:e2e
```

## Notes

- Docker must be running with a seeded database (products, blog posts, categories)
- Stripe checkout is excluded from tests — the flow redirects to Stripe's hosted page
- Playwright tests run in both desktop (1280x720) and mobile (iPhone 13) viewports
