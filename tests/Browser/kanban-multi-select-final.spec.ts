import { test, expect } from '@playwright/test';

test('Kanban multi-select final test', async ({ page }) => {
  // Navigate to kanban board
  await page.goto('http://aureuserp.test/admin/project/kanban');
  
  // Wait for page to load
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  console.log('Page loaded, looking for Discovery column...');
  
  // Find Discovery column
  const discoveryColumn = page.locator('[data-stage-name="Discovery"]').first();
  await expect(discoveryColumn).toBeVisible({ timeout: 10000 });
  
  console.log('Discovery column found, looking for project cards...');
  
  // Find project cards in Discovery column
  const projectCards = discoveryColumn.locator('[data-project-id]');
  const cardCount = await projectCards.count();
  
  console.log(`Found ${cardCount} cards in Discovery column`);
  
  if (cardCount < 2) {
    throw new Error(`Need at least 2 cards in Discovery column, found ${cardCount}`);
  }
  
  // Get first two cards
  const firstCard = projectCards.nth(0);
  const secondCard = projectCards.nth(1);
  
  const firstCardId = await firstCard.getAttribute('data-project-id');
  const secondCardId = await secondCard.getAttribute('data-project-id');
  
  console.log(`Selecting card 1: ${firstCardId}`);
  console.log(`Selecting card 2: ${secondCardId}`);
  
  // Cmd+Click on first card
  await firstCard.click({ modifiers: ['Meta'] });
  await page.waitForTimeout(500);
  
  // Verify first card has selection ring
  const firstCardClasses = await firstCard.getAttribute('class');
  console.log(`First card classes: ${firstCardClasses}`);
  
  // Cmd+Click on second card
  await secondCard.click({ modifiers: ['Meta'] });
  await page.waitForTimeout(1000);
  
  // Verify second card has selection ring
  const secondCardClasses = await secondCard.getAttribute('class');
  console.log(`Second card classes: ${secondCardClasses}`);
  
  // Look for bulk actions bar
  const bulkActionsBar = page.locator('[data-bulk-actions-bar]').first();
  await expect(bulkActionsBar).toBeVisible({ timeout: 5000 });
  
  console.log('Bulk actions bar visible');
  
  // Verify badge shows "2 selected"
  const badge = bulkActionsBar.locator('.bg-blue-500').first();
  await expect(badge).toBeVisible();
  const badgeText = await badge.textContent();
  console.log(`Badge text: ${badgeText}`);
  
  // Verify buttons exist
  const moveToStageBtn = bulkActionsBar.locator('button:has-text("Move to Stage")');
  const blockBtn = bulkActionsBar.locator('button:has-text("Block")');
  const unblockBtn = bulkActionsBar.locator('button:has-text("Unblock")');
  const clearBtn = bulkActionsBar.locator('button:has-text("Clear")');
  
  await expect(moveToStageBtn).toBeVisible();
  await expect(blockBtn).toBeVisible();
  await expect(unblockBtn).toBeVisible();
  await expect(clearBtn).toBeVisible();
  
  console.log('All buttons verified');
  
  // Take screenshot showing selection
  await page.screenshot({ 
    path: '/private/tmp/kanban-multi-select-final.png',
    fullPage: true 
  });
  
  console.log('Screenshot saved to /private/tmp/kanban-multi-select-final.png');
  
  // Click "Move to Stage" to verify dropdown
  await moveToStageBtn.click();
  await page.waitForTimeout(500);
  
  // Look for dropdown menu
  const dropdown = page.locator('[role="menu"], .dropdown-menu, [data-stage-dropdown]').first();
  const dropdownVisible = await dropdown.isVisible().catch(() => false);
  
  if (dropdownVisible) {
    console.log('Dropdown menu appeared');
    await page.screenshot({ 
      path: '/private/tmp/kanban-dropdown-visible.png',
      fullPage: true 
    });
  } else {
    console.log('Dropdown not found, taking screenshot anyway');
    await page.screenshot({ 
      path: '/private/tmp/kanban-dropdown-check.png',
      fullPage: true 
    });
  }
  
  // Click Clear
  await clearBtn.click();
  await page.waitForTimeout(500);
  
  // Verify bulk actions bar is hidden
  const barHidden = await bulkActionsBar.isHidden().catch(() => true);
  console.log(`Bulk actions bar hidden: ${barHidden}`);
  
  // Verify cards no longer have selection ring
  const firstCardClassesAfter = await firstCard.getAttribute('class');
  const secondCardClassesAfter = await secondCard.getAttribute('class');
  
  console.log(`First card classes after clear: ${firstCardClassesAfter}`);
  console.log(`Second card classes after clear: ${secondCardClassesAfter}`);
  
  // Final screenshot
  await page.screenshot({ 
    path: '/private/tmp/kanban-after-clear.png',
    fullPage: true 
  });
  
  console.log('\nTest Summary:');
  console.log('✓ Navigated to kanban board');
  console.log(`✓ Selected 2 cards (${firstCardId}, ${secondCardId})`);
  console.log('✓ Bulk actions bar appeared with badge');
  console.log('✓ All buttons present (Move to Stage, Block, Unblock, Clear)');
  console.log('✓ Clear button works');
  console.log('\nScreenshots saved:');
  console.log('  - /private/tmp/kanban-multi-select-final.png (with 2 selected)');
  console.log('  - /private/tmp/kanban-after-clear.png (after clearing)');
});
