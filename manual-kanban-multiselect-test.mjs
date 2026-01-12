import { chromium } from 'playwright';

(async () => {
  console.log('Starting kanban multi-select and bulk drag test...\n');
  
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  try {
    // Login first
    console.log('Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    
    // Wait for navigation and full page load
    await page.waitForURL('**/admin/**', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('Logged in successfully\n');

    // Step 1: Navigate to kanban board
    console.log('Step 1: Navigating to kanban board...');
    await page.goto('http://aureuserp.test/admin/project/kanban');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    await page.screenshot({ path: '/tmp/kanban-step1-loaded.png', fullPage: true });
    console.log('Screenshot saved: /tmp/kanban-step1-loaded.png\n');

    // Step 2: Find visible project cards
    console.log('Step 2: Looking for visible project cards...');
    
    // Find only visible cards
    const cards = page.locator('[data-card-id]:visible');
    const cardCount = await cards.count();
    console.log('Found ' + cardCount + ' visible project cards');
    
    if (cardCount < 2) {
      console.log('Warning: Less than 2 visible cards found (' + cardCount + ').');
      console.log('Looking for Discovery column to find cards...');
      
      // Try to find cards in Discovery column specifically
      const discoveryCards = page.locator('[data-column-id="1"] [data-card-id]');
      const discoveryCount = await discoveryCards.count();
      console.log('Found ' + discoveryCount + ' cards in Discovery column');
      
      if (discoveryCount < 2) {
        console.log('Still not enough cards. Exiting.');
        await browser.close();
        return;
      }
    }

    const firstCard = cards.nth(0);
    const secondCard = cards.nth(1);
    
    const firstCardId = await firstCard.getAttribute('data-card-id');
    const secondCardId = await secondCard.getAttribute('data-card-id');
    console.log('First card ID: ' + firstCardId);
    console.log('Second card ID: ' + secondCardId);
    
    // Cmd+Click first card (without scrollIntoView)
    console.log('\nClicking first card with Cmd modifier...');
    await firstCard.click({ modifiers: ['Meta'], force: true });
    await page.waitForTimeout(500);
    await page.screenshot({ path: '/tmp/kanban-step2a-first-card-selected.png', fullPage: true });
    console.log('Screenshot saved: /tmp/kanban-step2a-first-card-selected.png');
    
    const firstCardClasses = await firstCard.getAttribute('class');
    console.log('First card classes: ' + (firstCardClasses || 'none'));
    const hasRing = firstCardClasses && firstCardClasses.includes('ring');
    const hasSelected = firstCardClasses && firstCardClasses.includes('selected');
    console.log('First card has "ring" class: ' + (hasRing ? 'YES' : 'NO'));
    console.log('First card has "selected" class: ' + (hasSelected ? 'YES' : 'NO'));
    
    // Cmd+Click second card
    console.log('\nClicking second card with Cmd modifier...');
    await secondCard.click({ modifiers: ['Meta'], force: true });
    await page.waitForTimeout(500);
    await page.screenshot({ path: '/tmp/kanban-step2b-two-cards-selected.png', fullPage: true });
    console.log('Screenshot saved: /tmp/kanban-step2b-two-cards-selected.png\n');

    // Step 3: Check for bulk actions bar
    console.log('Step 3: Checking for bulk actions bar...');
    
    const bulkBarSelectors = [
      'text="2 selected"',
      'text="selected"',
      '[class*="bulk"]',
      '[class*="floating"]',
      '.fixed.bottom-',
      '[data-bulk-actions]',
      '[x-show*="selectedCards"]'
    ];
    
    let bulkBarFound = false;
    let bulkBarText = '';
    for (const selector of bulkBarSelectors) {
      const element = page.locator(selector).first();
      const visible = await element.isVisible().catch(() => false);
      if (visible) {
        bulkBarText = await element.textContent();
        console.log('Found bulk actions element with selector "' + selector + '": ' + bulkBarText);
        bulkBarFound = true;
        break;
      }
    }
    
    if (!bulkBarFound) {
      console.log('Bulk actions bar NOT FOUND with any selector');
    }
    
    await page.screenshot({ path: '/tmp/kanban-step3-bulk-actions-bar.png', fullPage: true });
    console.log('Screenshot saved: /tmp/kanban-step3-bulk-actions-bar.png\n');

    // Step 4: Drag selected cards
    console.log('Step 4: Dragging selected cards to another column...');
    
    const allColumns = page.locator('[data-column-id]');
    const columnCount = await allColumns.count();
    console.log('Found ' + columnCount + ' columns on the board');
    
    if (columnCount < 2) {
      console.log('Warning: Only one column found. Cannot test drag between columns.');
    } else {
      // Try to find Design column (column 2) or use second column
      const targetColumn = allColumns.nth(1);
      const targetColumnId = await targetColumn.getAttribute('data-column-id');
      console.log('Target column ID: ' + targetColumnId);
      
      const sourceBox = await firstCard.boundingBox();
      const targetBox = await targetColumn.boundingBox();
      
      if (sourceBox && targetBox) {
        console.log('Starting drag operation...');
        console.log('Source position: ' + Math.round(sourceBox.x) + ',' + Math.round(sourceBox.y));
        console.log('Target position: ' + Math.round(targetBox.x) + ',' + Math.round(targetBox.y));
        
        await page.mouse.move(sourceBox.x + sourceBox.width / 2, sourceBox.y + sourceBox.height / 2);
        await page.mouse.down();
        await page.waitForTimeout(500);
        
        await page.screenshot({ path: '/tmp/kanban-step4a-mouse-down.png', fullPage: true });
        console.log('Screenshot saved: /tmp/kanban-step4a-mouse-down.png');
        
        await page.mouse.move(targetBox.x + targetBox.width / 2, targetBox.y + 150, { steps: 15 });
        await page.waitForTimeout(500);
        
        await page.screenshot({ path: '/tmp/kanban-step4b-dragging.png', fullPage: true });
        console.log('Screenshot saved: /tmp/kanban-step4b-dragging.png');
        
        await page.mouse.up();
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: '/tmp/kanban-step4c-dropped.png', fullPage: true });
        console.log('Screenshot saved: /tmp/kanban-step4c-dropped.png');
        console.log('Drag operation completed\n');
      } else {
        console.log('Could not get bounding boxes for drag operation\n');
      }
    }

    // Step 5: Verify cards moved
    console.log('Step 5: Verifying cards moved...');
    await page.waitForTimeout(1500);
    await page.screenshot({ path: '/tmp/kanban-step5-after-drop.png', fullPage: true });
    console.log('Screenshot saved: /tmp/kanban-step5-after-drop.png\n');

    // Step 6: Check for notification
    console.log('Step 6: Checking for success notification...');
    
    const notificationSelectors = [
      '.fi-no-notification',
      '[role="alert"]',
      '[class*="notification"]',
      '[class*="toast"]',
      '[x-data*="notification"]',
      'text="moved"',
      'text="success"'
    ];
    
    let notificationFound = false;
    let notificationText = '';
    for (const selector of notificationSelectors) {
      const element = page.locator(selector).first();
      const visible = await element.isVisible().catch(() => false);
      if (visible) {
        notificationText = await element.textContent();
        console.log('Notification found with selector "' + selector + '": ' + notificationText);
        notificationFound = true;
        break;
      }
    }
    
    if (!notificationFound) {
      console.log('No notification found\n');
    }
    
    await page.screenshot({ path: '/tmp/kanban-step6-final-state.png', fullPage: true });
    console.log('Screenshot saved: /tmp/kanban-step6-final-state.png\n');

    console.log('\n=====================================');
    console.log('    KANBAN MULTI-SELECT TEST REPORT');
    console.log('=====================================\n');
    console.log('Test Environment: http://aureuserp.test/admin/project/kanban');
    console.log('Date: ' + new Date().toISOString());
    console.log('');
    console.log('RESULTS:');
    console.log('--------');
    console.log('1. Visible cards found: ' + cardCount);
    console.log('2. First card blue ring/selected: ' + ((hasRing || hasSelected) ? 'PASS' : 'FAIL'));
    console.log('3. Bulk actions bar (2 selected): ' + (bulkBarFound ? 'PASS' : 'FAIL'));
    console.log('4. Drag operation completed: ' + (sourceBox && targetBox ? 'YES' : 'NO'));
    console.log('5. Success notification: ' + (notificationFound ? 'PASS' : 'FAIL'));
    console.log('');
    console.log('SCREENSHOTS:');
    console.log('------------');
    console.log('/tmp/kanban-step1-loaded.png - Initial kanban board');
    console.log('/tmp/kanban-step2a-first-card-selected.png - After first Cmd+Click');
    console.log('/tmp/kanban-step2b-two-cards-selected.png - After second Cmd+Click');
    console.log('/tmp/kanban-step3-bulk-actions-bar.png - Bulk actions bar visibility');
    console.log('/tmp/kanban-step4a-mouse-down.png - Drag start');
    console.log('/tmp/kanban-step4b-dragging.png - During drag');
    console.log('/tmp/kanban-step4c-dropped.png - After drop');
    console.log('/tmp/kanban-step5-after-drop.png - Final verification');
    console.log('/tmp/kanban-step6-final-state.png - Final state');
    console.log('');
    console.log('EXPECTED FUNCTIONALITY:');
    console.log('-----------------------');
    console.log('• Cmd+Click should select cards with blue ring');
    console.log('• Bulk actions bar should appear showing "2 selected"');
    console.log('• Dragging should show count badge during drag');
    console.log('• All selected cards should move together');
    console.log('• Notification should show number of projects moved');
    console.log('');
    console.log('Browser will remain open for 15 seconds for manual review...');
    console.log('=====================================\n');
    
    await page.waitForTimeout(15000);
    
  } catch (error) {
    console.error('\nTest error:', error.message);
    console.error(error.stack);
    await page.screenshot({ path: '/tmp/kanban-error.png', fullPage: true });
    console.log('Error screenshot saved to /tmp/kanban-error.png');
  } finally {
    await browser.close();
    console.log('Test completed.');
  }
})();
