import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  try {
    // Login
    console.log('🔐 Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Logged in');

    // Navigate to a project page
    console.log('📄 Navigating to project page...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/edit');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Page loaded');

    // Wait a bit for footer to fully render
    await page.waitForTimeout(2000);

    // Take screenshot with footer minimized
    await page.screenshot({ 
      path: 'test-footer-minimized.png',
      fullPage: true 
    });
    console.log('📸 Screenshot saved: test-footer-minimized.png');

    // Click to expand footer (click anywhere on the toggle bar)
    console.log('🔽 Expanding footer...');
    const footerSection = page.locator('.fi-section').first();
    await footerSection.click();
    await page.waitForTimeout(500); // Wait for animation
    
    // Take screenshot with footer expanded
    await page.screenshot({ 
      path: 'test-footer-expanded.png',
      fullPage: true 
    });
    console.log('📸 Screenshot saved: test-footer-expanded.png');

    console.log('✅ Footer padding test complete - check screenshots for transparent gap');

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'test-footer-error.png', fullPage: true });
    console.log('📸 Error screenshot saved');
  } finally {
    await browser.close();
  }
})();
