import { chromium } from 'playwright';

(async () => {
  console.log('Starting kanban multi-select final test...\n');
  
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    storageState: '/Users/andrewphan/tcsadmin/aureuserp/tests/Browser/auth-state.json'
  });
  const page = await context.newPage();
  
  try {
    // Navigate to kanban board
    console.log('Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    
    // Wait for kanban board to fully load
    console.log('Waiting for kanban board to load...');
    await page.waitForSelector('text=Discovery', { timeout: 10000 });
    await page.waitForTimeout(3000);
    
    // Look for project cards by their visible content
    console.log('Looking for project cards...');
    
    // The cards appear to be in the Discovery column, let's find them by their title text
    const testActiveCard = page.locator('text=Test Active Folder Project').first();
    const kitchenCabinetsCard = page.locator('text=Kitchen Cabinets for Test Company LLC').first();
    
    await testActiveCard.waitFor({ state: 'visible', timeout: 10000 });
    await kitchenCabinetsCard.waitFor({ state: 'visible', timeout: 10000 });
    
    console.log('Found cards:');
    console.log('  - Test Active Folder Project');
    console.log('  - Kitchen Cabinets for Test Company LLC');
    
    // Take screenshot before selection
    await page.screenshot({ 
      path: '/private/tmp/kanban-before-selection.png',
      fullPage: true 
    });
    console.log('Saved: /private/tmp/kanban-before-selection.png');
    
    console.log('\nCmd+Click on card 1 (Test Active Folder Project)...');
    await testActiveCard.click({ modifiers: ['Meta'] });
    await page.waitForTimeout(1000);
    
    console.log('Cmd+Click on card 2 (Kitchen Cabinets)...');
    await kitchenCabinetsCard.click({ modifiers: ['Meta'] });
    await page.waitForTimeout(2000);
    
    // Take screenshot after selection
    console.log('\nTaking screenshot after selection...');
    await page.screenshot({ 
      path: '/private/tmp/kanban-multi-select-final.png',
      fullPage: true 
    });
    console.log('Saved: /private/tmp/kanban-multi-select-final.png');
    
    // Look for bulk actions bar
    console.log('\nLooking for bulk actions bar...');
    
    // Try multiple strategies
    let bulkActionsBar = page.locator('[data-bulk-actions-bar]').first();
    let barVisible = await bulkActionsBar.isVisible({ timeout: 3000 }).catch(() => false);
    
    if (!barVisible) {
      bulkActionsBar = page.locator('text=selected').first();
      barVisible = await bulkActionsBar.isVisible({ timeout: 2000 }).catch(() => false);
    }
    
    if (!barVisible) {
      bulkActionsBar = page.locator('[class*="fixed"]').filter({ hasText: /selected|clear/i }).first();
      barVisible = await bulkActionsBar.isVisible({ timeout: 2000 }).catch(() => false);
    }
    
    if (barVisible) {
      console.log('✓ Bulk actions bar found and visible');
      const barText = await bulkActionsBar.textContent();
      console.log('  Bar content: ' + (barText || '').replace(/\s+/g, ' ').trim());
      
      // Look for buttons
      const moveBtn = page.locator('button:has-text("Move to Stage"), button:has-text("Move")').first();
      const blockBtn = page.locator('button:has-text("Block")').first();
      const unblockBtn = page.locator('button:has-text("Unblock")').first();
      const clearBtn = page.locator('button:has-text("Clear")').first();
      
      const moveBtnVisible = await moveBtn.isVisible().catch(() => false);
      const blockBtnVisible = await blockBtn.isVisible().catch(() => false);
      const unblockBtnVisible = await unblockBtn.isVisible().catch(() => false);
      const clearBtnVisible = await clearBtn.isVisible().catch(() => false);
      
      console.log('\nButton visibility:');
      console.log('  Move to Stage: ' + moveBtnVisible);
      console.log('  Block: ' + blockBtnVisible);
      console.log('  Unblock: ' + unblockBtnVisible);
      console.log('  Clear: ' + clearBtnVisible);
      
      if (clearBtnVisible) {
        console.log('\nClicking Clear button...');
        await clearBtn.click();
        await page.waitForTimeout(1500);
        
        const barHidden = await bulkActionsBar.isHidden().catch(() => true);
        console.log('✓ Bar hidden after clear: ' + barHidden);
        
        await page.screenshot({ 
          path: '/private/tmp/kanban-after-clear.png',
          fullPage: true 
        });
        console.log('Saved: /private/tmp/kanban-after-clear.png');
      }
    } else {
      console.log('⚠ Bulk actions bar NOT found');
      console.log('  Checking page for any fixed elements...');
      
      const allFixed = await page.locator('[class*="fixed"]').all();
      console.log('  Found ' + allFixed.length + ' fixed elements');
      
      for (let i = 0; i < Math.min(allFixed.length, 5); i++) {
        const text = await allFixed[i].textContent();
        const classes = await allFixed[i].getAttribute('class');
        console.log('  Fixed element ' + i + ': ' + (text || '').substring(0, 50));
        console.log('    Classes: ' + (classes || '').substring(0, 80));
      }
    }
    
    console.log('\n=== TEST COMPLETE ===');
    console.log('✓ Navigation successful');
    console.log('✓ Found and clicked 2 cards with Cmd+Click');
    console.log('✓ Screenshots saved to /private/tmp/');
    console.log('  Bulk actions bar visible: ' + barVisible);
    
  } catch (error) {
    console.error('\n❌ Test failed:', error.message);
    await page.screenshot({ 
      path: '/private/tmp/kanban-error.png',
      fullPage: true 
    });
    console.log('Error screenshot: /private/tmp/kanban-error.png');
  } finally {
    console.log('\nBrowser will close in 10 seconds...');
    await page.waitForTimeout(10000);
    await browser.close();
  }
})();
