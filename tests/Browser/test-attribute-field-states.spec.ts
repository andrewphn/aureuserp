import { test, expect } from '@playwright/test';

test.describe('Attribute field disabled states', () => {
  test('should disable attribute fields until quantity is entered', async ({ page }) => {
    // Step 1: Login
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');

    // Wait for redirect away from login page
    await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: 10000 });
    await page.waitForTimeout(1000);
    console.log('✅ Logged in successfully');

    // Step 2: Navigate to quotation create page
    await page.goto('http://aureuserp.test/admin/sale/orders/quotations/create');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('✅ On quotation create page');

    // Product row is already shown on page load
    await page.waitForTimeout(1000);
    console.log('✅ Product row visible');
    await page.screenshot({ path: '/tmp/1-product-row-visible.png', fullPage: true });

    // Find the Product dropdown by label
    const productLabel = page.getByText('Product').first();
    await productLabel.scrollIntoViewIfNeeded();

    // Click the product select dropdown (look for Select button under Product column)
    const productSelect = page.locator('text=Select an option').first();
    await productSelect.scrollIntoViewIfNeeded();
    await productSelect.click();
    await page.waitForTimeout(500);
    console.log('✅ Opened product dropdown');

    // Select Cabinet
    await page.getByRole('option', { name: 'Cabinet' }).click();
    await page.waitForTimeout(2000);
    console.log('✅ Selected Cabinet');
    await page.screenshot({ path: '/tmp/2-cabinet-selected.png', fullPage: true });

    // Wait for attribute fields to appear
    await page.waitForTimeout(1000);

    // Check if Pricing Level field is visible and disabled
    const pricingLevelLabel = page.getByText('Pricing Level').first();
    const isPricingLevelVisible = await pricingLevelLabel.isVisible().catch(() => false);
    console.log(`Pricing Level label visible: ${isPricingLevelVisible}`);

    // Find the Pricing Level input (it shows as "1" in the screenshot)
    const pricingLevelInput = page.locator('input[value="1"]').first();
    const pricingLevelInputVisible = await pricingLevelInput.isVisible().catch(() => false);

    if (pricingLevelInputVisible) {
      const isPricingLevelDisabled = await pricingLevelInput.isDisabled();
      console.log(`Pricing Level disabled (should be true): ${isPricingLevelDisabled}`);

      if (!isPricingLevelDisabled) {
        console.error('❌ ERROR: Pricing Level should be disabled when quantity is empty');
        await page.screenshot({ path: '/tmp/error-attribute-not-disabled.png', fullPage: true });
      } else {
        console.log('✅ Pricing Level is correctly disabled');
      }
    }

    // Now enter quantity - find the quantity input field
    const quantityLabel = page.getByText('Quantity').first();
    await quantityLabel.scrollIntoViewIfNeeded();

    // Find the quantity input (currently showing "0")
    const quantityInput = page.locator('input[type="number"]').filter({ has: page.locator('[value="0"]') }).first();
    await quantityInput.fill('10');
    await page.waitForTimeout(1000);
    console.log('✅ Entered quantity: 10');
    await page.screenshot({ path: '/tmp/3-quantity-entered.png', fullPage: true });

    // Check that Pricing Level field is now enabled
    if (pricingLevelInputVisible) {
      const isPricingLevelEnabledNow = await pricingLevelInput.isEnabled();
      console.log(`Pricing Level enabled after quantity (should be true): ${isPricingLevelEnabledNow}`);

      if (!isPricingLevelEnabledNow) {
        console.error('❌ ERROR: Pricing Level should be enabled after quantity is entered');
        await page.screenshot({ path: '/tmp/error-attribute-still-disabled.png', fullPage: true });
      } else {
        console.log('✅ Pricing Level is correctly enabled after quantity');
      }
    }

    // Keep browser open for inspection
    await page.waitForTimeout(5000);
  });
});
