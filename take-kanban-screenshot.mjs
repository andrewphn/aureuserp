import { chromium } from '@playwright/test';

async function takeScreenshot() {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({ storageState: 'tests/Browser/auth-state.json' });
  const page = await context.newPage();

  try {
    console.log('Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('Taking screenshot...');
    await page.screenshot({ 
      path: '/tmp/kanban-header-layout.png',
      fullPage: true 
    });
    
    console.log('Screenshot saved to /tmp/kanban-header-layout.png');

  } catch (error) {
    console.error('Error:', error);
  } finally {
    await browser.close();
  }
}

takeScreenshot();
