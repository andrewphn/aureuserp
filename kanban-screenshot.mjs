import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban', { waitUntil: 'networkidle', timeout: 15000 });

    // Check if we're on login page
    const currentUrl = page.url();
    console.log('Current URL:', currentUrl);
    
    if (currentUrl.includes('login') || await page.locator('input[type="email"]').count() > 0) {
      console.log('Login required, authenticating...');
      
      // Fill and submit login form
      await page.fill('input[type="email"]', 'info@tcswoodwork.com');
      await page.fill('input[type="password"]', 'Lola2024!');
      
      // Click sign in button and wait for navigation
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 }),
        page.click('button[type="submit"]')
      ]);
      
      console.log('Login complete, URL:', page.url());
      
      // If not already on kanban page, navigate there
      if (!page.url().includes('/kanban')) {
        console.log('Navigating to kanban board after login...');
        await page.goto('http://aureuserp.test/admin/project/kanban', { waitUntil: 'networkidle', timeout: 15000 });
      }
    }

    console.log('Waiting for page to fully load...');
    await page.waitForLoadState('networkidle');
    
    // Wait for FilamentPHP UI elements
    await page.waitForTimeout(3000);

    console.log('Taking screenshot...');
    await page.screenshot({ 
      path: '/Users/andrewphan/tcsadmin/aureuserp/kanban-screenshot.png', 
      fullPage: true 
    });
    console.log('Screenshot saved successfully!');

  } catch (error) {
    console.error('Error taking screenshot:', error.message);
    console.log('Current URL:', page.url());
    
    // Take screenshot anyway for debugging
    try {
      await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/kanban-screenshot-error.png', fullPage: true });
      console.log('Error screenshot saved');
    } catch (e) {}
    
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
