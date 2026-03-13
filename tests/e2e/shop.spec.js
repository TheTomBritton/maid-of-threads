// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Shop Page', () => {

  test('loads successfully', async ({ page }) => {
    const response = await page.goto('/shop/');
    expect(response.status()).toBe(200);
  });

  test('displays page heading', async ({ page }) => {
    await page.goto('/shop/');
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
  });

  test('renders product grid with items', async ({ page }) => {
    await page.goto('/shop/');
    const grid = page.locator('#product-grid');
    await expect(grid).toBeVisible();

    // Should have at least one product card
    const cards = grid.locator('.card');
    await expect(cards.first()).toBeVisible();
  });

  test('renders category filter buttons', async ({ page }) => {
    await page.goto('/shop/');
    const filterNav = page.locator('nav[aria-label="Product categories"]');
    await expect(filterNav).toBeVisible();

    // "All" filter should be present
    await expect(filterNav.locator('text=All')).toBeVisible();
  });

  test('HTMX category filter updates product grid', async ({ page }) => {
    await page.goto('/shop/');

    // Find the first category filter link (not "All")
    const filterNav = page.locator('nav[aria-label="Product categories"]');
    const categoryLinks = filterNav.locator('a').filter({ hasNotText: 'All' });

    const count = await categoryLinks.count();
    if (count === 0) {
      test.skip('No category filters available');
      return;
    }

    // Click the first category filter
    const firstCategory = categoryLinks.first();
    await firstCategory.click();

    // Wait for HTMX to swap the content
    await page.waitForTimeout(1000);

    // Product grid should still exist after swap
    const grid = page.locator('#product-grid');
    await expect(grid).toBeVisible();
  });

});
