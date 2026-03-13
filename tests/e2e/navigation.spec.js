// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Navigation', () => {

  test('desktop nav has main navigation links', async ({ page }) => {
    await page.goto('/');
    const nav = page.locator('nav[aria-label="Main navigation"]');
    await expect(nav).toBeAttached();

    // Should contain links
    const links = nav.locator('a');
    const count = await links.count();
    expect(count).toBeGreaterThan(0);
  });

  test('all desktop nav links return 200', async ({ page, request }) => {
    await page.goto('/');
    const nav = page.locator('nav[aria-label="Main navigation"]');
    const links = nav.locator('a');
    const count = await links.count();

    for (let i = 0; i < count; i++) {
      const href = await links.nth(i).getAttribute('href');
      if (href && href.startsWith('/')) {
        const response = await request.get(href);
        expect(response.status(), `Nav link ${href} should return 200`).toBe(200);
      }
    }
  });

  test('cart badge element exists', async ({ page }) => {
    await page.goto('/');
    const badge = page.locator('#cart-badge');
    await expect(badge).toBeAttached();
  });

  test('search icon links to /search/', async ({ page }) => {
    await page.goto('/');
    const searchLink = page.locator('a[aria-label="Search"]');
    await expect(searchLink).toBeAttached();
    await expect(searchLink).toHaveAttribute('href', '/search/');
  });

  test('cart icon links to /cart/', async ({ page }) => {
    await page.goto('/');
    const cartLink = page.locator('a[aria-label="Shopping cart"]');
    await expect(cartLink).toBeAttached();
    await expect(cartLink).toHaveAttribute('href', /\/cart\//);
  });

  test('footer links all return 200', async ({ page, request }) => {
    await page.goto('/');
    const footer = page.locator('footer');
    const links = footer.locator('a[href^="/"]');
    const count = await links.count();

    for (let i = 0; i < count; i++) {
      const href = await links.nth(i).getAttribute('href');
      if (href) {
        const response = await request.get(href);
        expect(response.status(), `Footer link ${href} should return 200`).toBe(200);
      }
    }
  });

});

test.describe('Mobile Navigation', () => {

  test.use({ viewport: { width: 375, height: 812 } });

  test('hamburger menu is visible on mobile', async ({ page }) => {
    await page.goto('/');
    const menuToggle = page.locator('#menu-toggle');
    await expect(menuToggle).toBeVisible();
  });

  test('mobile nav is hidden by default', async ({ page }) => {
    await page.goto('/');
    const mobileNav = page.locator('#mobile-nav');
    await expect(mobileNav).toBeHidden();
  });

  test('clicking hamburger opens mobile nav', async ({ page }) => {
    await page.goto('/');
    const menuToggle = page.locator('#menu-toggle');
    const mobileNav = page.locator('#mobile-nav');

    await menuToggle.click();

    await expect(mobileNav).toBeVisible();
    await expect(menuToggle).toHaveAttribute('aria-expanded', 'true');
  });

  test('clicking hamburger again closes mobile nav', async ({ page }) => {
    await page.goto('/');
    const menuToggle = page.locator('#menu-toggle');
    const mobileNav = page.locator('#mobile-nav');

    // Open
    await menuToggle.click();
    await expect(mobileNav).toBeVisible();

    // Close
    await menuToggle.click();
    await expect(mobileNav).toBeHidden();
    await expect(menuToggle).toHaveAttribute('aria-expanded', 'false');
  });

  test('mobile nav contains links', async ({ page }) => {
    await page.goto('/');
    const menuToggle = page.locator('#menu-toggle');
    const mobileNav = page.locator('#mobile-nav');

    await menuToggle.click();

    const links = mobileNav.locator('a');
    const count = await links.count();
    expect(count).toBeGreaterThan(0);
  });

});
