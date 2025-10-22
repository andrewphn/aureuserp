import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
  ignoreHTTPSErrors: true,
  viewport: { width: 1280, height: 800 }
});
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

  console.log('\n=== FINDING GLOBAL FOOTER ===\n');

  // Find the global footer
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');

  // Get its position
  const footerInfo = await globalFooter.evaluate(el => {
    const rect = el.getBoundingClientRect();
    const style = window.getComputedStyle(el);
    return {
      top: rect.top,
      left: rect.left,
      bottom: rect.bottom,
      height: rect.height,
      width: rect.width,
      position: style.position,
      zIndex: style.zIndex,
      backgroundColor: style.backgroundColor,
      isFixed: style.position === 'fixed'
    };
  });

  console.log('3. Global footer position:', footerInfo);

  // Click to expand it
  console.log('\n4. Expanding global footer...');
  await globalFooter.click();
  await page.waitForTimeout(1000);

  // Check for save button
  const saveButton = globalFooter.locator('button.fi-btn-color-success:has-text("Save")');
  const saveExists = await saveButton.count() > 0;
  console.log('5. Save button exists in global footer:', saveExists);

  if (saveExists) {
    const saveInfo = await saveButton.evaluate(btn => {
      const rect = btn.getBoundingClientRect();
      return {
        text: btn.textContent.trim(),
        top: rect.top,
        left: rect.left,
        width: rect.width,
        height: rect.height,
        visible: rect.width > 0 && rect.height > 0
      };
    });
    console.log('   Save button info:', saveInfo);
  }

  // Take screenshot showing just the bottom portion with the footer
  console.log('\n6. Taking screenshot of bottom area...');
  await page.evaluate(() => {
    window.scrollTo(0, 0); // Scroll to top first
  });
  await page.waitForTimeout(500);

  // Screenshot showing the global footer at bottom
  await page.screenshot({
    path: 'global-footer-at-bottom.png',
    fullPage: false // Just the viewport
  });

  console.log('\n=== COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(2000);
  await browser.close();
}
