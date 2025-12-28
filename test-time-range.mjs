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

    console.log('5. Clicking analytics toggle...');
    const analyticsToggle = await page.locator('[data-testid="analytics-toggle"]');
    await analyticsToggle.waitFor({ state: 'visible', timeout: 10000 });
    await analyticsToggle.click();
    await page.waitForTimeout(1500);

    console.log('6. Taking screenshot with analytics expanded...');
    await page.screenshot({ path: path.join(screenshotDir, '02-analytics-expanded.png'), fullPage: true });

    // Capture initial KPI values
    console.log('7. Capturing initial KPI values...');
    const getKPIValues = async () => {
      const kpis = {};
      const kpiElements = await page.locator('[class*="text-"][class*="font-bold"]').all();
      for (let i = 0; i < Math.min(kpiElements.length, 5); i++) {
        const text = await kpiElements[i].textContent();
        kpis[`kpi_${i}`] = text?.trim();
      }
      return kpis;
    };
    
    const initialKPIs = await getKPIValues();
    console.log('Initial KPI values:', initialKPIs);

    console.log('8. Clicking Quarter (Qtr) time range button...');
    const qtrButton = await page.locator('[data-testid="time-range-quarter"]');
    await qtrButton.waitFor({ state: 'visible', timeout: 10000 });
    await qtrButton.click();
    
    console.log('9. Waiting for Livewire update...');
    await page.waitForTimeout(3000); // Wait for Livewire to process
    
    console.log('10. Taking screenshot after Qtr click...');
    await page.screenshot({ path: path.join(screenshotDir, '03-time-range-quarter.png'), fullPage: true });
    
    const qtrKPIs = await getKPIValues();
    console.log('Quarter KPI values:', qtrKPIs);

    console.log('11. Clicking YTD time range button...');
    const ytdButton = await page.locator('[data-testid="time-range-ytd"]');
    await ytdButton.waitFor({ state: 'visible', timeout: 10000 });
    await ytdButton.click();
    
    console.log('12. Waiting for Livewire update...');
    await page.waitForTimeout(3000); // Wait for Livewire to process
    
    console.log('13. Taking screenshot after YTD click...');
    await page.screenshot({ path: path.join(screenshotDir, '04-time-range-ytd.png'), fullPage: true });
    
    const ytdKPIs = await getKPIValues();
    console.log('YTD KPI values:', ytdKPIs);

    // Verify KPI changes
    console.log('\n=== VERIFICATION ===');
    const kpiChanged = JSON.stringify(initialKPIs) !== JSON.stringify(qtrKPIs) || 
                       JSON.stringify(qtrKPIs) !== JSON.stringify(ytdKPIs);
    
    if (kpiChanged) {
      console.log('✅ PASS: KPI values changed when time range buttons were clicked');
    } else {
      console.log('❌ FAIL: KPI values did not change');
    }

    console.log('\n=== TEST SUMMARY ===');
    console.log('Screenshots saved to:', screenshotDir);
    console.log('Test completed successfully');

  } catch (error) {
    console.error('❌ TEST FAILED:', error.message);
    await page.screenshot({ path: path.join(screenshotDir, 'error-screenshot.png'), fullPage: true });
    throw error;
  } finally {
    await browser.close();
  }
})();
