import { test } from '@playwright/test';

test('capture project creation page screenshot', async ({ page }) => {
  // Set viewport to desktop size
  await page.setViewportSize({ width: 1440, height: 900 });
  
  // Navigate directly to project creation page (auth already handled)
  await page.goto('/admin/project/projects/create');
  await page.waitForLoadState('networkidle');
  
  // Wait for dynamic content
  await page.waitForTimeout(3000);
  
  // Take full-page screenshot
  await page.screenshot({ 
    path: '/tmp/project-creation-after-ux-fixes.png',
    fullPage: true
  });
});
