import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  // Navigate to login page
  await page.goto('http://aureuserp.test/admin/login');
  
  // Login with credentials
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  
  // Wait for navigation after login
  await page.waitForURL('**/admin**', { timeout: 10000 });
  
  // Navigate to kanban board
  await page.goto('http://aureuserp.test/admin/project/kanban');
  
  // Wait for the page to fully load
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  // Take full page screenshot
  await page.screenshot({ 
    path: '/tmp/kanban-page-screenshot.png',
    fullPage: true 
  });
  
  await browser.close();
  console.log('Screenshot saved');
})();
