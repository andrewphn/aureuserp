import { test } from '@playwright/test';

test('capture project creation page screenshot', async ({ page }) => {
  // Set viewport
  await page.setViewportSize({ width: 1440, height: 900 });
  
  // Navigate to login page
  await page.goto('http://aureuserp.test/admin/login');
  
  // Fill in login credentials
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  
  // Click login button
  await page.click('button[type="submit"]');
  
  // Wait for navigation after login
  await page.waitForURL(/\/admin/);
  await page.waitForLoadState('networkidle');
  
  // Navigate to project creation page
  await page.goto('http://aureuserp.test/admin/project/projects/create');
  await page.waitForLoadState('networkidle');
  
  // Wait a moment for any dynamic content to load
  await page.waitForTimeout(2000);
  
  // Take full-page screenshot
  await page.screenshot({ 
    path: '/tmp/project-creation-after-ux-fixes.png',
    fullPage: true
  });
  
  console.log('Screenshot saved to /tmp/project-creation-after-ux-fixes.png');
});
