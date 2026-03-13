// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Cart Page', () => {

  test('shows empty basket message when cart is empty', async ({ page }) => {
    await page.goto('/cart/');
    await expect(page.locator('text=Your basket is empty')).toBeVisible();
    await expect(page.locator('text=Start Shopping')).toBeVisible();
  });

  test('add-to-cart flow works end-to-end', async ({ page }) => {
    // Navigate to shop and find a product
    await page.goto('/shop/');
    const firstProduct = page.locator('#product-grid .card a').first();
    await expect(firstProduct).toBeVisible();
    await firstProduct.click();
    await page.waitForLoadState('domcontentloaded');

    // Look for add-to-cart form
    const addForm = page.locator('form:has(input[name="action"][value="add"])');
    const formCount = await addForm.count();

    if (formCount === 0) {
      test.skip('Product is out of stock, cannot test add-to-cart');
      return;
    }

    // Get the product title for verification
    const productTitle = await page.locator('h1').textContent();

    // Submit the add-to-cart form
    await addForm.locator('button[type="submit"]').click();

    // Should redirect to cart page (PRG pattern)
    await page.waitForURL(/\/cart\//);

    // Cart should now contain the product
    await expect(page.locator('text=Your Basket')).toBeVisible();

    // Should NOT show "empty" message
    await expect(page.locator('text=Your basket is empty')).not.toBeVisible();

    // Order summary should be visible
    await expect(page.locator('text=Order Summary')).toBeVisible();
  });

  test('remove item from cart', async ({ page }) => {
    // First add a product
    await page.goto('/shop/');
    const firstProduct = page.locator('#product-grid .card a').first();
    await firstProduct.click();
    await page.waitForLoadState('domcontentloaded');

    const addForm = page.locator('form:has(input[name="action"][value="add"])');
    if (await addForm.count() === 0) {
      test.skip('Product out of stock');
      return;
    }

    await addForm.locator('button[type="submit"]').click();
    await page.waitForURL(/\/cart\//);

    // Now remove the item
    const removeButton = page.locator('button[aria-label*="Remove"]').first();
    await removeButton.click();
    await page.waitForLoadState('domcontentloaded');

    // Cart should now be empty
    await expect(page.locator('text=Your basket is empty')).toBeVisible();
  });

  test('clear cart empties all items', async ({ page }) => {
    // Add a product first
    await page.goto('/shop/');
    const firstProduct = page.locator('#product-grid .card a').first();
    await firstProduct.click();
    await page.waitForLoadState('domcontentloaded');

    const addForm = page.locator('form:has(input[name="action"][value="add"])');
    if (await addForm.count() === 0) {
      test.skip('Product out of stock');
      return;
    }

    await addForm.locator('button[type="submit"]').click();
    await page.waitForURL(/\/cart\//);

    // Click "Clear basket"
    const clearButton = page.locator('button:has-text("Clear basket")');
    await clearButton.click();
    await page.waitForLoadState('domcontentloaded');

    // Should show empty state
    await expect(page.locator('text=Your basket is empty')).toBeVisible();
  });

});
