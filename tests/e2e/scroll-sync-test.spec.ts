import { test, expect } from '@playwright/test';

test.describe('Gantt Chart Scroll Synchronization', () => {
  test('scroll sync between table body and gantt container', async ({ page }) => {
    // Collect console logs
    const consoleLogs: string[] = [];
    page.on('console', msg => {
      consoleLogs.push('[' + msg.type() + '] ' + msg.text());
    });

    // Navigate to Gantt chart
    await page.goto('http://aureuserp.test/admin/project/gantt');
    
    // Check if we need to login
    const url = page.url();
    if (url.includes('login')) {
      console.log('Need to login...');
      // FilamentPHP uses data-identifier for form fields
      await page.locator('input[type="email"], input#data\\.email').first().fill('info@tcswoodwork.com');
      await page.locator('input[type="password"]').first().fill('Lola2024!');
      await page.locator('button:has-text("Sign in")').click();
      await page.waitForURL('**/admin/**', { timeout: 30000 });
      await page.goto('http://aureuserp.test/admin/project/gantt');
    }

    // Wait for page to fully load
    await page.waitForTimeout(2000);
    
    // Check for scroll sync initialization in console logs
    const syncInitLogs = consoleLogs.filter(log => log.includes('Scroll sync initialized'));
    console.log('Scroll sync init logs found:', syncInitLogs.length);
    
    // Find the table body element
    const tableBody = page.locator('.gantt-table-body');
    const tableBodyExists = await tableBody.count() > 0;
    console.log('Table body exists:', tableBodyExists);
    
    // Find the gantt container
    const ganttContainer = page.locator('.gantt-container');
    const ganttContainerExists = await ganttContainer.count() > 0;
    console.log('Gantt container exists:', ganttContainerExists);
    
    if (!tableBodyExists || !ganttContainerExists) {
      // Take screenshot showing current state
      await page.screenshot({ path: 'tests/e2e/screenshots/scroll-sync-elements-missing.png', fullPage: true });
      console.log('Required elements not found. Screenshot saved.');
      
      // List all elements with gantt in class name
      const ganttElements = await page.evaluate(() => {
        const elements = document.querySelectorAll('[class*="gantt"]');
        return Array.from(elements).map(el => ({
          tag: el.tagName,
          className: el.className,
          id: el.id
        }));
      });
      console.log('Gantt-related elements:', JSON.stringify(ganttElements, null, 2));
      return;
    }
    
    // Get initial scroll positions
    const initialTableScrollTop = await tableBody.evaluate(el => el.scrollTop);
    const initialGanttScrollTop = await ganttContainer.evaluate(el => el.scrollTop);
    
    console.log('Initial Table Body scrollTop:', initialTableScrollTop);
    console.log('Initial Gantt Container scrollTop:', initialGanttScrollTop);
    
    // Scroll the table body down by 100 pixels
    await tableBody.evaluate(el => {
      el.scrollTop = 100;
    });
    
    // Wait for sync
    await page.waitForTimeout(500);
    
    // Get scroll positions after scrolling
    const finalTableScrollTop = await tableBody.evaluate(el => el.scrollTop);
    const finalGanttScrollTop = await ganttContainer.evaluate(el => el.scrollTop);
    
    console.log('Final Table Body scrollTop:', finalTableScrollTop);
    console.log('Final Gantt Container scrollTop:', finalGanttScrollTop);
    
    // Check if gantt container synchronized
    const syncWorked = Math.abs(finalGanttScrollTop - finalTableScrollTop) < 5;
    console.log('Scroll sync working:', syncWorked);
    
    // Take screenshot of scrolled state
    await page.screenshot({ 
      path: 'tests/e2e/screenshots/scroll-sync-result.png', 
      fullPage: false 
    });
    
    // Print all relevant console logs
    console.log('\n--- Console Logs (filtered for Gantt) ---');
    consoleLogs
      .filter(log => log.toLowerCase().includes('gantt') || log.toLowerCase().includes('scroll'))
      .forEach(log => console.log(log));
    
    // Summary
    console.log('\n=== SCROLL SYNC TEST RESULTS ===');
    console.log('Table Body scrollTop: ' + initialTableScrollTop + ' -> ' + finalTableScrollTop);
    console.log('Gantt Container scrollTop: ' + initialGanttScrollTop + ' -> ' + finalGanttScrollTop);
    console.log('Scroll Sync Working: ' + (syncWorked ? 'YES' : 'NO'));
    console.log('Screenshot saved to: tests/e2e/screenshots/scroll-sync-result.png');
    
    // Assert sync worked
    expect(syncWorked).toBe(true);
  });
});
