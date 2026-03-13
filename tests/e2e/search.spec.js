// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Search Page', () => {

  test('loads successfully', async ({ page }) => {
    const response = await page.goto('/search/');
    expect(response.status()).toBe(200);
  });

  test('has search input with HTMX attributes', async ({ page }) => {
    await page.goto('/search/');
    const searchInput = page.locator('input[name="q"]');
    await expect(searchInput).toBeVisible();

    // Verify HTMX attributes are present
    await expect(searchInput).toHaveAttribute('hx-get', /ajax/);
    await expect(searchInput).toHaveAttribute('hx-target', '#live-results');
    await expect(searchInput).toHaveAttribute('hx-trigger', /input/);
  });

  test('has live results container', async ({ page }) => {
    await page.goto('/search/');
    const liveResults = page.locator('#live-results');
    await expect(liveResults).toBeAttached();
  });

  test('full-page search returns results section', async ({ page }) => {
    await page.goto('/search/?q=embroidery');
    // Should show result count or "no results" message
    const hasResults = page.locator('text=/\\d+ results?/');
    const noResults = page.locator('text=No results found');

    // One of these should be visible
    const resultsVisible = await hasResults.count() > 0;
    const noResultsVisible = await noResults.count() > 0;
    expect(resultsVisible || noResultsVisible).toBeTruthy();
  });

  test('empty search shows prompt message', async ({ page }) => {
    await page.goto('/search/');
    await expect(page.locator('text=Enter a search term')).toBeVisible();
  });

});
