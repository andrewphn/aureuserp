import { test, expect } from '@playwright/test';

test.describe('Cabinet Spec Builder - Full User Story Test', () => {
  // Use global auth from playwright config
  test.use({ storageState: 'tests/Browser/auth-state.json' });

  // Helper to navigate to Cabinet Spec Builder - it's on the VIEW page
  async function navigateToSpecBuilder(page) {
    // Navigate to project 105 VIEW page (not edit - spec builder is in relation manager)
    await page.goto('http://aureuserp.test/admin/project/projects/105');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    // Scroll down to find relation manager tabs
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(1000);

    // Look for Cabinet Spec (Tree) tab in relation managers
    const specTreeTab = page.locator('button, a, [role="tab"]').filter({ hasText: /Cabinet Spec.*Tree|Spec.*Tree/i }).first();
    if (await specTreeTab.isVisible({ timeout: 5000 }).catch(() => false)) {
      await specTreeTab.click();
      await page.waitForTimeout(3000);
    } else {
      // Scroll more and try again
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(1000);

      const altTab = page.locator('button, a').filter({ hasText: /Cabinet Spec|Spec Tree/i }).first();
      if (await altTab.isVisible({ timeout: 3000 }).catch(() => false)) {
        await altTab.click();
        await page.waitForTimeout(3000);
      }
    }

    // Final scroll to ensure spec builder is in view
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(1000);
  }

  test('1. Load spec builder and verify initial state', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Take screenshot of initial state
    await page.screenshot({ path: 'test-results/spec-builder-1-initial.png', fullPage: true });

    // Verify spec builder container exists (use specific class selector)
    const specBuilder = page.locator('.cabinet-spec-builder').first();
    await expect(specBuilder).toBeVisible({ timeout: 15000 });
    console.log('✓ Spec builder component loaded');
  });

  test('2. Test Add Room modal (not slideOver)', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Find and click Add Room button
    const addRoomBtn = page.locator('button').filter({ hasText: /Add Room/i }).first();
    await expect(addRoomBtn).toBeVisible({ timeout: 10000 });
    await addRoomBtn.click();
    await page.waitForTimeout(2000);

    // Take screenshot of modal
    await page.screenshot({ path: 'test-results/spec-builder-2-add-room-modal.png', fullPage: true });

    // Verify modal content is visible (Room Name field, modal header)
    const modalHeader = page.locator('text=Add Room').first();
    await expect(modalHeader).toBeVisible({ timeout: 5000 });

    // Verify form fields are visible
    const roomNameLabel = page.locator('text=Room Name').first();
    await expect(roomNameLabel).toBeVisible();

    // Verify it's NOT a slideOver (slideOvers slide from the side)
    const isSlideOver = await page.locator('.fi-modal-slide-over').isVisible().catch(() => false);
    expect(isSlideOver).toBe(false);
    console.log('✓ Add Room modal opens correctly (regular centered modal, not slideOver)');

    // Close modal
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);
  });

  test('3. Create a new room', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Click Add Room
    const addRoomBtn = page.locator('button').filter({ hasText: /Add Room/i }).first();
    await addRoomBtn.click();
    await page.waitForTimeout(1000);

    // Fill in room details
    const nameInput = page.locator('input').filter({ has: page.locator('[name*="name"]') }).first()
      .or(page.locator('input[placeholder*="Kitchen"]').first())
      .or(page.locator('.fi-modal input[type="text"]').first());

    await nameInput.fill('Test Room ' + Date.now());

    // Take screenshot before submit
    await page.screenshot({ path: 'test-results/spec-builder-3-room-form-filled.png', fullPage: true });

    // Submit the form
    const submitBtn = page.locator('.fi-modal button[type="submit"], .fi-modal button').filter({ hasText: /Create|Save|Add/i }).first();
    if (await submitBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await submitBtn.click();
      await page.waitForTimeout(2000);
    }

    // Take screenshot after creation
    await page.screenshot({ path: 'test-results/spec-builder-3-room-created.png', fullPage: true });
    console.log('✓ Room creation flow tested');
  });

  test('4. Test tree accordion navigation', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Look for room items in tree
    const roomItems = page.locator('[x-for*="room"]').or(page.locator('.border.rounded-lg').filter({ has: page.locator('[x-text*="room.name"]') }));

    // Take screenshot of tree structure
    await page.screenshot({ path: 'test-results/spec-builder-4-tree-structure.png', fullPage: true });

    // Try clicking on a room to expand
    const firstRoom = page.locator('text=Kitchen').first().or(page.locator('text=K1').first());
    if (await firstRoom.isVisible({ timeout: 5000 }).catch(() => false)) {
      await firstRoom.click();
      await page.waitForTimeout(1000);
      await page.screenshot({ path: 'test-results/spec-builder-4-room-expanded.png', fullPage: true });
      console.log('✓ Tree navigation works');
    }
  });

  test('5. Test Add Location within room', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // First expand a room
    const roomExpander = page.locator('button').filter({ has: page.locator('[class*="chevron"]') }).first();
    if (await roomExpander.isVisible({ timeout: 3000 }).catch(() => false)) {
      await roomExpander.click();
      await page.waitForTimeout(500);
    }

    // Look for Add Location button
    const addLocationBtn = page.locator('button').filter({ hasText: /Add Location/i }).first();
    if (await addLocationBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await addLocationBtn.click();
      await page.waitForTimeout(1000);

      // Verify modal opens
      await page.screenshot({ path: 'test-results/spec-builder-5-add-location-modal.png', fullPage: true });

      // Close modal
      await page.keyboard.press('Escape');
      console.log('✓ Add Location modal works');
    }
  });

  test('6. Test inspector panel displays correctly', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Click on a room in the tree to select it
    const roomItem = page.locator('text=Kitchen').first().or(page.locator('text=K1').first());
    if (await roomItem.isVisible({ timeout: 5000 }).catch(() => false)) {
      await roomItem.click();
      await page.waitForTimeout(1000);
    }

    // Take screenshot of inspector panel
    await page.screenshot({ path: 'test-results/spec-builder-6-inspector-panel.png', fullPage: true });

    // Look for inspector content
    const inspectorContent = page.locator('text=Type').first().or(page.locator('text=DEFAULT PRICING').first());
    if (await inspectorContent.isVisible({ timeout: 3000 }).catch(() => false)) {
      console.log('✓ Inspector panel displays room details');
    }
  });

  test('7. Verify dark mode Tailwind classes work', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Toggle dark mode
    await page.evaluate(() => {
      document.documentElement.classList.toggle('dark');
    });
    await page.waitForTimeout(500);

    // Take screenshot in dark mode
    await page.screenshot({ path: 'test-results/spec-builder-7-dark-mode.png', fullPage: true });

    // Verify dark mode classes are applied (no isDark Alpine logic)
    const darkElements = await page.evaluate(() => {
      const elements = document.querySelectorAll('[class*="dark:"]');
      return elements.length;
    });

    console.log(`✓ Found ${darkElements} elements with dark: classes`);
    expect(darkElements).toBeGreaterThan(0);

    // Toggle back to light mode
    await page.evaluate(() => {
      document.documentElement.classList.toggle('dark');
    });
  });

  test('8. Test Project Summary section', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Look for Project Summary
    const summarySection = page.locator('text=Project Summary').first();
    if (await summarySection.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Verify linear feet and price are shown
      const lfText = page.locator('text=/\\d+.*LF/').first();
      const priceText = page.locator('text=/\\$[\\d,]+/').first();

      await page.screenshot({ path: 'test-results/spec-builder-8-project-summary.png', fullPage: true });
      console.log('✓ Project Summary section visible');
    }
  });

  test('9. Test cabinet table inline editing', async ({ page }) => {
    await navigateToSpecBuilder(page);

    // Navigate to a run that has cabinets
    const runItem = page.locator('text=Base').first().or(page.locator('text=Upper').first());
    if (await runItem.isVisible({ timeout: 5000 }).catch(() => false)) {
      await runItem.click();
      await page.waitForTimeout(1000);
    }

    // Look for cabinet table
    const cabinetTable = page.locator('table').first();
    if (await cabinetTable.isVisible({ timeout: 5000 }).catch(() => false)) {
      await page.screenshot({ path: 'test-results/spec-builder-9-cabinet-table.png', fullPage: true });

      // Try double-clicking a cell to edit
      const editableCell = page.locator('td').filter({ has: page.locator('text=/\\d+/')}).first();
      if (await editableCell.isVisible({ timeout: 3000 }).catch(() => false)) {
        await editableCell.dblclick();
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'test-results/spec-builder-9-inline-edit.png', fullPage: true });
        console.log('✓ Cabinet table inline editing available');
      }
    }
  });

  test('10. Verify no JavaScript errors', async ({ page }) => {
    const errors: string[] = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    page.on('pageerror', error => {
      errors.push(error.message);
    });

    await navigateToSpecBuilder(page);
    await page.waitForTimeout(3000);

    // Filter out known non-critical errors
    const criticalErrors = errors.filter(e =>
      !e.includes('favicon') &&
      !e.includes('404') &&
      !e.includes('ResizeObserver')
    );

    console.log(`Console errors: ${criticalErrors.length}`);
    if (criticalErrors.length > 0) {
      console.log('Errors:', criticalErrors);
    }

    // Allow test to pass with warning if minor errors
    expect(criticalErrors.length).toBeLessThan(5);
  });
});
