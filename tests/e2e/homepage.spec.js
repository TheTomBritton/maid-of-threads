// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Homepage', () => {

  test('loads successfully with correct title', async ({ page }) => {
    const response = await page.goto('/');
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(/Maid of Threads/);
  });

  test('displays H1 heading', async ({ page }) => {
    await page.goto('/');
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
    await expect(h1).not.toBeEmpty();
  });

  test('has Shop Now CTA linking to /shop/', async ({ page }) => {
    await page.goto('/');
    const cta = page.locator('a:has-text("Shop Now")');
    await expect(cta).toBeVisible();
    await expect(cta).toHaveAttribute('href', /\/shop\//);
  });

  test('displays featured products section', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('text=Featured Products');
    await expect(section).toBeVisible();
  });

  test('renders footer with site name', async ({ page }) => {
    await page.goto('/');
    const footer = page.locator('footer');
    await expect(footer).toBeVisible();
    await expect(footer).toContainText('Maid of Threads');
  });

  test('footer contains Shop and Information columns', async ({ page }) => {
    await page.goto('/');
    const footer = page.locator('footer');
    await expect(footer.locator('text=Shop')).toBeVisible();
    await expect(footer.locator('text=Information')).toBeVisible();
  });

});
