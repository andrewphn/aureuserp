import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  try {
    console.log('🔐 Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Logged in successfully');

    // Navigate to annotation page
    console.log('📄 Navigating to annotation page...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Page loaded');

    // Check for any error messages
    const errorElements = await page.locator('.fi-section-content:has-text("Error")').count();
    if (errorElements > 0) {
      console.log('⚠️  Error elements found on page');
    } else {
      console.log('✅ No error messages visible');
    }

    // Take screenshot
    await page.screenshot({
      path: 'test-translation-check.png',
      fullPage: true
    });
    console.log('📸 Screenshot saved: test-translation-check.png');

    console.log('✅ Test complete - please check the screenshot and browser for any translation issues');

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'test-translation-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
