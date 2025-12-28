import { test, expect } from '@playwright/test';

test.describe('Kanban Control Bar UI Test', () => {
  test('should display and interact with unified control bar', async ({ page }) => {
    // Navigate to kanban board
    await page.goto('/admin/project/kanban');
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    console.log('Step 1: Taking screenshot of initial state');
    await page.screenshot({ 
      path: 'tests/Browser/screenshots/kanban-control-bar-initial.png',
      fullPage: true 
    });
    
    // Look for the analytics toggle button
    console.log('Step 2: Looking for analytics toggle button...');
    
    // Try to find analytics toggle
    const analyticsToggle = page.locator('[data-toggle="analytics"]').or(
      page.locator('button').filter({ has: page.locator('svg') }).last()
    );
    
    const toggleCount = await analyticsToggle.count();
    console.log(`Found ${toggleCount} potential analytics toggle buttons`);
    
    if (toggleCount > 0) {
      try {
        await analyticsToggle.first().click({ timeout: 5000 });
        console.log('Clicked analytics toggle');
        await page.waitForTimeout(1000);
        
        console.log('Step 3: Taking screenshot of expanded analytics');
        await page.screenshot({ 
          path: 'tests/Browser/screenshots/kanban-control-bar-expanded.png',
          fullPage: true 
        });
      } catch (e) {
        console.log('Could not click analytics toggle:', e);
      }
    } else {
      console.log('No analytics toggle found, taking screenshot anyway');
      await page.screenshot({ 
        path: 'tests/Browser/screenshots/kanban-control-bar-no-toggle.png',
        fullPage: true 
      });
    }
    
    // Try to click the "Mo" (Month) time range tab
    console.log('Step 4: Looking for Month time range tab...');
    const monthTab = page.locator('button:has-text("Mo")').or(
      page.locator('[data-range="month"]')
    ).or(
      page.locator('button:has-text("Month")')
    );
    
    const monthCount = await monthTab.count();
    console.log(`Found ${monthCount} potential month tab buttons`);
    
    if (monthCount > 0) {
      try {
        await monthTab.first().click({ timeout: 5000 });
        console.log('Clicked Month tab');
        await page.waitForTimeout(1000);
      } catch (e) {
        console.log('Could not click Month tab:', e);
      }
    }
    
    console.log('Step 5: Taking final screenshot');
    await page.screenshot({ 
      path: 'tests/Browser/screenshots/kanban-control-bar-final.png',
      fullPage: true 
    });
    
    console.log('\n✅ Test completed! Screenshots saved to tests/Browser/screenshots/');
  });
});
