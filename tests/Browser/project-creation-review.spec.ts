import { test } from '@playwright/test';

test('Review project creation page', async ({ page }) => {
  // Navigate to project creation page (auth is handled by global setup)
  await page.goto('/admin/project/projects/create');
  
  // Wait for page to fully load
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  // Take full page screenshot
  await page.screenshot({ 
    path: '/tmp/project-creation-page.png',
    fullPage: true 
  });
  
  console.log('Screenshot saved to: /tmp/project-creation-page.png');
});
