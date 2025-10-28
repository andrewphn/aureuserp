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
    console.log('✅ Logged in');

    console.log('📄 Navigating to project page...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/edit');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Page loaded');

    await page.waitForTimeout(2000);

    // Take screenshot minimized
    await page.screenshot({
      path: 'footer-minimized-state.png',
      fullPage: true
    });
    console.log('📸 Minimized screenshot saved');

    // Find and click the footer toggle (look for fixed positioned element at bottom)
    console.log('🔽 Expanding footer...');
    const footer = await page.locator('div[x-data*="contextFooter"]').first();
    await footer.click();
    await page.waitForTimeout(1000);

    // Take screenshot expanded
    await page.screenshot({
      path: 'footer-expanded-state.png',
      fullPage: true
    });
    console.log('📸 Expanded screenshot saved');

    // Get the computed styles of the footer
    const footerStyles = await page.evaluate(() => {
      const footer = document.querySelector('div[x-data*="contextFooter"]');
      if (!footer) return null;

      const styles = window.getComputedStyle(footer);
      return {
        paddingBottom: styles.paddingBottom,
        marginBottom: styles.marginBottom,
        height: styles.height,
        background: styles.background,
        position: styles.position,
        bottom: styles.bottom
      };
    });

    console.log('Footer styles:', footerStyles);
    console.log('✅ Test complete');

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'footer-test-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
