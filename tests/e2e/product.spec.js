// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Product Page', () => {

  test.beforeEach(async ({ page }) => {
    // Navigate to shop and click the first product
    await page.goto('/shop/');
    const firstProduct = page.locator('#product-grid .card a').first();
    await expect(firstProduct).toBeVisible();
    await firstProduct.click();
    await page.waitForLoadState('domcontentloaded');
  });

  test('loads successfully', async ({ page }) => {
    // Should be on a product page
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
    await expect(h1).not.toBeEmpty();
  });

  test('displays price with currency symbol', async ({ page }) => {
    // Price should contain £
    const price = page.locator('text=£');
    await expect(price.first()).toBeVisible();
  });

  test('has add-to-cart form with product_id input', async ({ page }) => {
    // Look for the add-to-cart form (may not exist if out of stock)
    const addForm = page.locator('form:has(input[name="product_id"])');
    const formCount = await addForm.count();

    if (formCount > 0) {
      await expect(addForm.first()).toBeVisible();
      // Should have action, product_id, and quantity
      await expect(addForm.locator('input[name="action"][value="add"]')).toHaveCount(1);
      await expect(addForm.locator('input[name="product_id"]')).toHaveCount(1);
    } else {
      // Product is out of stock — "Out of Stock" button should be visible
      await expect(page.locator('text=Out of Stock')).toBeVisible();
    }
  });

  test('has image gallery', async ({ page }) => {
    const gallery = page.locator('#product-gallery');
    await expect(gallery).toBeVisible();
  });

  test('contains JSON-LD product schema', async ({ page }) => {
    const schema = page.locator('script[type="application/ld+json"]');
    const count = await schema.count();
    expect(count).toBeGreaterThan(0);

    // Verify it contains Product type
    const content = await schema.first().textContent();
    const json = JSON.parse(content);
    expect(json['@type']).toBe('Product');
  });

  test('displays breadcrumbs', async ({ page }) => {
    const breadcrumbs = page.locator('nav[aria-label="Breadcrumb"]');
    // Breadcrumbs may use different aria-label — check for any breadcrumb nav
    const navCount = await breadcrumbs.count();
    if (navCount === 0) {
      // Fallback: check for breadcrumb-like structure
      const crumbs = page.locator('[itemtype*="BreadcrumbList"]');
      await expect(crumbs).toBeVisible();
    } else {
      await expect(breadcrumbs).toBeVisible();
    }
  });

});
