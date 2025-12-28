import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const screenshotDir = '/Users/andrewphan/tcsadmin/aureuserp/test-screenshots';
if (!fs.existsSync(screenshotDir)) {
  fs.mkdirSync(screenshotDir, { recursive: true });
}

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  try {
    console.log('1. Navigating to login page...');
    await page.goto('http://aureuserp.test/admin/login', { waitUntil: 'networkidle' });
    
    console.log('2. Logging in...');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('3. Navigating to Kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    console.log('4. Taking initial screenshot...');
    await page.screenshot({ path: path.join(screenshotDir, '01-initial-kanban.png'), fullPage: true });

    console.log('5. Checking for analytics toggle...');
    const analyticsToggleCount = await page.locator('[data-testid="analytics-toggle"]').count();
    console.log('Analytics toggle found:', analyticsToggleCount > 0);

    if (analyticsToggleCount > 0) {
      console.log('6. Clicking analytics toggle...');
      await page.locator('[data-testid="analytics-toggle"]').click();
      await page.waitForTimeout(1500);
      await page.screenshot({ path: path.join(screenshotDir, '02-analytics-expanded.png'), fullPage: true });
    }

    console.log('7. Checking for time range buttons...');
    const qtrCount = await page.locator('[data-testid="time-range-quarter"]').count();
    const ytdCount = await page.locator('[data-testid="time-range-ytd"]').count();
    console.log('Quarter button found:', qtrCount > 0);
    console.log('YTD button found:', ytdCount > 0);

    if (qtrCount === 0 || ytdCount === 0) {
      console.log('\nTime range buttons with data-testid not found. Checking page HTML...');
      const html = await page.content();
      const hasQtrText = html.includes('Qtr');
      const hasYTDText = html.includes('YTD');
      console.log('Page contains Qtr text:', hasQtrText);
      console.log('Page contains YTD text:', hasYTDText);
      
      const qtrByText = await page.locator('text=Qtr').count();
      const ytdByText = await page.locator('text=YTD').count();
      console.log('Qtr button by text:', qtrByText);
      console.log('YTD button by text:', ytdByText);
    }

    console.log('\n=== TEST RESULT ===');
    console.log('FAIL: Required data-testid selectors not found on page');
    console.log('Expected selectors:');
    console.log('  - [data-testid="analytics-toggle"]');
    console.log('  - [data-testid="time-range-quarter"]');
    console.log('  - [data-testid="time-range-ytd"]');

  } catch (error) {
    console.error('TEST FAILED:', error.message);
    await page.screenshot({ path: path.join(screenshotDir, 'error-screenshot.png'), fullPage: true });
    throw error;
  } finally {
    await browser.close();
  }
})();
