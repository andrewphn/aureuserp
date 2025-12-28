import { chromium } from '@playwright/test';

async function captureHeader() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ 
    storageState: 'tests/Browser/auth-state.json' 
  });
  const page = await context.newPage();

  try {
    console.log('Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('Taking full page screenshot...');
    await page.screenshot({ 
      path: '/tmp/kanban-page-screenshot.png',
      fullPage: true 
    });

    console.log('Screenshot saved to /tmp/kanban-page-screenshot.png');
  } catch (error) {
    console.error('Error:', error);
    await page.screenshot({ 
      path: '/tmp/kanban-error-screenshot.png',
      fullPage: true 
    });
  } finally {
    await browser.close();
  }
}

captureHeader();
