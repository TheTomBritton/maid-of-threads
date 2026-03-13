// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Contact Page', () => {

  test('loads successfully', async ({ page }) => {
    const response = await page.goto('/contact/');
    expect(response.status()).toBe(200);
  });

  test('displays page heading', async ({ page }) => {
    await page.goto('/contact/');
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
  });

  test('renders form with required fields', async ({ page }) => {
    await page.goto('/contact/');

    // Check for essential form fields (works with both FrontendForms and fallback)
    const form = page.locator('form');
    await expect(form.first()).toBeVisible();

    // Name field
    const nameInput = page.locator('input[name="name"], input[id="contact-name"]');
    await expect(nameInput.first()).toBeVisible();

    // Email field
    const emailInput = page.locator('input[name="email"], input[id="contact-email"]');
    await expect(emailInput.first()).toBeVisible();

    // Subject field
    const subjectInput = page.locator('input[name="subject"], input[id="contact-subject"]');
    await expect(subjectInput.first()).toBeVisible();

    // Message field
    const messageField = page.locator('textarea[name="message"], textarea[id="contact-message"]');
    await expect(messageField.first()).toBeVisible();
  });

  test('has submit button', async ({ page }) => {
    await page.goto('/contact/');
    const submitBtn = page.locator('button[type="submit"], input[type="submit"]');
    await expect(submitBtn.first()).toBeVisible();
  });

  test('form has CSRF protection', async ({ page }) => {
    await page.goto('/contact/');
    // ProcessWire CSRF token input (name starts with TOKEN)
    const csrfInput = page.locator('input[type="hidden"][name^="TOKEN"], input[type="hidden"][name="_post_token"]');
    const count = await csrfInput.count();
    // At least one CSRF token should be present
    expect(count).toBeGreaterThan(0);
  });

});
