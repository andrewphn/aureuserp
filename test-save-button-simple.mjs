import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();

try {
  console.log('1. Logging in...');
  await page.goto('http://aureuserp.test/admin/login');
  await page.waitForTimeout(1000);
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  console.log('2. Navigating to project edit page...');
  await page.goto('http://aureuserp.test/admin/project/projects/2/edit');
  await page.waitForTimeout(4000);

  console.log('\n=== CHECKING SAVE BUTTON ===\n');

  // Expand global footer
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  await globalFooter.click();
  await page.waitForTimeout(1000);

  // Find save button in global footer
  const saveButton = globalFooter.locator('button:has-text("Save")');
  const exists = await saveButton.count() > 0;
  console.log('3. Save button exists:', exists);

  if (exists) {
    const isVisible = await saveButton.isVisible();
    console.log('4. Save button isVisible():', isVisible);

    // Get computed styles
    const styles = await saveButton.evaluate(btn => {
      const computed = window.getComputedStyle(btn);
      const rect = btn.getBoundingClientRect();
      return {
        display: computed.display,
        visibility: computed.visibility,
        opacity: computed.opacity,
        width: rect.width,
        height: rect.height,
        top: rect.top,
        left: rect.left,
        xShow: btn.getAttribute('x-show')
      };
    });
    console.log('5. Button styles:', styles);

    // Check URL
    const url = await page.evaluate(() => window.location.pathname);
    console.log('6. Current URL:', url);
    console.log('   Contains /edit:', url.includes('/edit'));
  }

  console.log('\n=== SELECTING CUSTOMER & BRANCH ===\n');

  // Select customer
  const customerField = page.locator('[wire\\:model*="customer"]').first();
  if (await customerField.count() > 0) {
    console.log('7. Selecting customer...');
    await customerField.selectOption({ index: 1 });
    await page.waitForTimeout(2000);
  }

  // Select branch
  const branchField = page.locator('[wire\\:model*="branch"]').first();
  if (await branchField.count() > 0) {
    console.log('8. Selecting branch...');
    await branchField.selectOption({ index: 1 });
    await page.waitForTimeout(3000);
  }

  console.log('\n=== CHECKING SAVE BUTTON AFTER SELECTION ===\n');

  const isVisibleAfter = await saveButton.isVisible();
  console.log('9. Save button isVisible() after:', isVisibleAfter);

  const stylesAfter = await saveButton.evaluate(btn => {
    const computed = window.getComputedStyle(btn);
    const rect = btn.getBoundingClientRect();
    return {
      display: computed.display,
      visibility: computed.visibility,
      opacity: computed.opacity,
      width: rect.width,
      height: rect.height
    };
  });
  console.log('10. Button styles after:', stylesAfter);

  // Take screenshot
  await page.screenshot({ path: 'save-button-simple-test.png', fullPage: false });
  console.log('\n✅ Screenshot saved');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
