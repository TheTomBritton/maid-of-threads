// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Blog', () => {

  test('blog index loads successfully', async ({ page }) => {
    const response = await page.goto('/blog/');
    expect(response.status()).toBe(200);
  });

  test('blog index displays heading', async ({ page }) => {
    await page.goto('/blog/');
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
  });

  test('blog index shows post cards', async ({ page }) => {
    await page.goto('/blog/');
    // Posts are rendered via renderPostCard — look for card elements or article links
    const posts = page.locator('.card, article a');
    const count = await posts.count();
    expect(count).toBeGreaterThan(0);
  });

  test('blog index has categories sidebar', async ({ page, isMobile }) => {
    await page.goto('/blog/');
    const sidebar = page.locator('aside');
    // Sidebar is always in DOM but might be below fold on mobile
    await expect(sidebar).toBeAttached();
    if (!isMobile) {
      await expect(sidebar.locator('text=Categories')).toBeVisible();
    }
  });

  test('blog post page loads from index', async ({ page }) => {
    await page.goto('/blog/');

    // Find and click the first blog post link
    const postLink = page.locator('.card a, article a').first();
    const linkCount = await postLink.count();
    if (linkCount === 0) {
      test.skip('No blog posts available');
      return;
    }

    await postLink.click();
    await page.waitForLoadState('domcontentloaded');

    // Post page should have an H1
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
    await expect(h1).not.toBeEmpty();
  });

  test('blog post has article schema', async ({ page }) => {
    await page.goto('/blog/');
    const postLink = page.locator('.card a, article a').first();
    if (await postLink.count() === 0) {
      test.skip('No blog posts available');
      return;
    }

    await postLink.click();
    await page.waitForLoadState('domcontentloaded');

    // Check for JSON-LD schema
    const schema = page.locator('script[type="application/ld+json"]');
    const count = await schema.count();
    expect(count).toBeGreaterThan(0);

    const content = await schema.first().textContent();
    const json = JSON.parse(content);
    expect(json['@type']).toBe('Article');
  });

  test('blog post displays publish date', async ({ page }) => {
    await page.goto('/blog/');
    const postLink = page.locator('.card a, article a').first();
    if (await postLink.count() === 0) {
      test.skip('No blog posts available');
      return;
    }

    await postLink.click();
    await page.waitForLoadState('domcontentloaded');

    // Should have a <time> element
    const timeEl = page.locator('time[datetime]');
    await expect(timeEl).toBeVisible();
  });

});
