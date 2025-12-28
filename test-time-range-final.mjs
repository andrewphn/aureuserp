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
  let testPassed = true;
  const failureReasons = [];

  try {
    console.log('=== TIME RANGE BUTTON TEST ===\n');
    
    console.log('Step 1: Navigating to login page...');
    await page.goto('http://aureuserp.test/admin/login', { waitUntil: 'networkidle' });
    
    console.log('Step 2: Logging in...');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('Step 3: Navigating to Kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    console.log('Step 4: Taking initial screenshot...');
    await page.screenshot({ path: path.join(screenshotDir, '01-initial-kanban.png'), fullPage: true });

    console.log('Step 5: Clicking analytics toggle...');
    const analyticsToggle = await page.locator('[data-testid="analytics-toggle"]');
    await analyticsToggle.click();
    await page.waitForTimeout(2000);

    console.log('Step 6: Taking screenshot with analytics expanded...');
    await page.screenshot({ path: path.join(screenshotDir, '02-analytics-expanded.png'), fullPage: true });

    // Capture initial KPI values
    console.log('Step 7: Capturing initial KPI values...');
    const getKPIData = async () => {
      const kpiCards = await page.locator('[class*="rounded"][class*="shadow"], .stat, [class*="metric"]').all();
      const values = [];
      for (const card of kpiCards) {
        const text = await card.textContent();
        if (text && text.trim()) {
          values.push(text.trim());
        }
      }
      return values.slice(0, 10); // Get first 10 KPI-like elements
    };
    
    const initialKPIs = await getKPIData();
    console.log('Initial KPIs captured:', initialKPIs.length, 'elements');

    console.log('\nStep 8: Clicking Quarter (Qtr) time range button...');
    const qtrButton = await page.locator('[data-testid="time-range-quarter"]');
    await qtrButton.click();
    
    console.log('Step 9: Waiting for Livewire update...');
    await page.waitForTimeout(3500);
    
    console.log('Step 10: Taking screenshot after Qtr click...');
    await page.screenshot({ path: path.join(screenshotDir, '03-time-range-quarter.png'), fullPage: true });
    
    const qtrKPIs = await getKPIData();
    console.log('Quarter KPIs captured:', qtrKPIs.length, 'elements');

    console.log('\nStep 11: Clicking YTD time range button...');
    const ytdButton = await page.locator('[data-testid="time-range-ytd"]');
    await ytdButton.click();
    
    console.log('Step 12: Waiting for Livewire update...');
    await page.waitForTimeout(3500);
    
    console.log('Step 13: Taking screenshot after YTD click...');
    await page.screenshot({ path: path.join(screenshotDir, '04-time-range-ytd.png'), fullPage: true });
    
    const ytdKPIs = await getKPIData();
    console.log('YTD KPIs captured:', ytdKPIs.length, 'elements\n');

    // Verification
    console.log('=== VERIFICATION ===');
    
    console.log('\nInitial KPIs:', JSON.stringify(initialKPIs.slice(0, 3)));
    console.log('Quarter KPIs:', JSON.stringify(qtrKPIs.slice(0, 3)));
    console.log('YTD KPIs:', JSON.stringify(ytdKPIs.slice(0, 3)));
    
    const initialToQtr = JSON.stringify(initialKPIs) !== JSON.stringify(qtrKPIs);
    const qtrToYTD = JSON.stringify(qtrKPIs) !== JSON.stringify(ytdKPIs);
    
    console.log('\nKPI Changes:');
    console.log('  Initial -> Quarter:', initialToQtr ? 'CHANGED' : 'NO CHANGE');
    console.log('  Quarter -> YTD:', qtrToYTD ? 'CHANGED' : 'NO CHANGE');
    
    if (!initialToQtr && !qtrToYTD) {
      testPassed = false;
      failureReasons.push('KPI values did not change when time range buttons were clicked');
    }

    console.log('\n=== TEST SUMMARY ===');
    if (testPassed) {
      console.log('✅ PASS: Time range functionality works correctly');
      console.log('  - Analytics toggle found and clicked');
      console.log('  - Quarter button found and clicked');
      console.log('  - YTD button found and clicked');
      console.log('  - KPI values updated based on time range selection');
    } else {
      console.log('❌ FAIL: Time range functionality has issues');
      failureReasons.forEach(reason => console.log('  -', reason));
    }
    
    console.log('\nScreenshots saved to:', screenshotDir);

  } catch (error) {
    console.error('\n❌ TEST FAILED WITH ERROR:', error.message);
    await page.screenshot({ path: path.join(screenshotDir, 'error-screenshot.png'), fullPage: true });
    testPassed = false;
  } finally {
    await browser.close();
  }
  
  process.exit(testPassed ? 0 : 1);
})();
