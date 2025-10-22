import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
  ignoreHTTPSErrors: true,
  viewport: { width: 1280, height: 800 }
});
const page = await context.newPage();

// Capture console messages
page.on('console', msg => console.log('[Browser Console]:', msg.text()));

try {
  console.log('1. Logging in...');
  await page.goto('http://aureuserp.test/admin/login');
  await page.waitForTimeout(1000);
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  console.log('2. Navigating to project edit page...');
  await page.goto('http://aureuserp.test/admin/project/projects/2/edit');
  await page.waitForTimeout(4000);

  console.log('\n=== INITIAL STATE (Before selecting customer/branch) ===\n');

  // Check if on edit page
  const currentUrl = page.url();
  console.log('3. Current URL:', currentUrl);
  console.log('   Contains /edit:', currentUrl.includes('/edit'));

  // Check isOnEditPage() function
  const isOnEditPageResult = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (footer && footer.__x) {
      const alpineData = footer.__x.$data;
      if (alpineData.isOnEditPage) {
        return alpineData.isOnEditPage();
      }
    }
    return 'Function not found or not accessible';
  });
  console.log('4. isOnEditPage() returns:', isOnEditPageResult);

  // Check global footer
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  const globalFooterExists = await globalFooter.count() > 0;
  console.log('5. Global footer exists:', globalFooterExists);

  if (globalFooterExists) {
    // Expand global footer
    await globalFooter.click();
    await page.waitForTimeout(1000);

    // Check for save button in global footer
    const saveButton = globalFooter.locator('button:has-text("Save")');
    const saveButtonCount = await saveButton.count();
    console.log('6. Save button in global footer count:', saveButtonCount);

    if (saveButtonCount > 0) {
      const saveButtonVisible = await saveButton.isVisible();
      const saveButtonInfo = await saveButton.evaluate(btn => {
        const rect = btn.getBoundingBox();
        const styles = window.getComputedStyle(btn);
        return {
          visible: saveButtonVisible,
          display: styles.display,
          opacity: styles.opacity,
          visibility: styles.visibility,
          classes: btn.className,
          xShow: btn.getAttribute('x-show'),
          boundingBox: rect ? { top: rect.top, left: rect.left, width: rect.width, height: rect.height } : null
        };
      });
      console.log('   Save button info:', saveButtonInfo);
    }
  }

  console.log('\n=== SELECTING CUSTOMER AND BRANCH ===\n');

  // Select customer
  console.log('7. Selecting customer...');
  const customerSelect = page.locator('select[id*="customer"], [wire\\:model*="customer"]').first();
  const customerExists = await customerSelect.count() > 0;
  console.log('   Customer field exists:', customerExists);

  if (customerExists) {
    await customerSelect.click();
    await page.waitForTimeout(500);
    // Select first non-empty option
    await customerSelect.selectOption({ index: 1 });
    await page.waitForTimeout(2000);
    console.log('   Customer selected');
  }

  // Select branch
  console.log('8. Selecting branch...');
  const branchSelect = page.locator('select[id*="branch"], [wire\\:model*="branch"]').first();
  const branchExists = await branchSelect.count() > 0;
  console.log('   Branch field exists:', branchExists);

  if (branchExists) {
    await branchSelect.click();
    await page.waitForTimeout(500);
    // Select first non-empty option
    await branchSelect.selectOption({ index: 1 });
    await page.waitForTimeout(3000); // Wait for form to update
    console.log('   Branch selected');
  }

  console.log('\n=== STATE AFTER SELECTING CUSTOMER/BRANCH ===\n');

  // Re-check isOnEditPage()
  const isOnEditPageAfter = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (footer && footer.__x) {
      const alpineData = footer.__x.$data;
      if (alpineData.isOnEditPage) {
        return alpineData.isOnEditPage();
      }
    }
    return 'Function not found';
  });
  console.log('9. isOnEditPage() after selection:', isOnEditPageAfter);

  // Check for Filament sticky footer
  const filamentStickyFooter = page.locator('.fi-sticky, .fi-form-actions');
  const filamentFooterCount = await filamentStickyFooter.count();
  console.log('10. Filament sticky footer count:', filamentFooterCount);

  if (filamentFooterCount > 0) {
    const filamentFooterInfo = await filamentStickyFooter.first().evaluate(el => {
      const rect = el.getBoundingBox();
      const styles = window.getComputedStyle(el);
      return {
        position: styles.position,
        zIndex: styles.zIndex,
        bottom: styles.bottom,
        top: rect.top,
        height: rect.height
      };
    });
    console.log('    Filament footer info:', filamentFooterInfo);
  }

  // Re-check global footer save button
  const saveButtonAfter = globalFooter.locator('button:has-text("Save")');
  const saveButtonCountAfter = await saveButtonAfter.count();
  console.log('11. Save button in global footer count (after):', saveButtonCountAfter);

  if (saveButtonCountAfter > 0) {
    const saveButtonVisibleAfter = await saveButtonAfter.isVisible();
    const saveButtonInfoAfter = await saveButtonAfter.evaluate(btn => {
      const rect = btn.getBoundingBox();
      const styles = window.getComputedStyle(btn);

      // Get x-show evaluation
      const xShowAttr = btn.getAttribute('x-show');
      let xShowEval = 'N/A';
      if (xShowAttr && btn.__x) {
        try {
          xShowEval = btn.__x.$data.isOnEditPage ? btn.__x.$data.isOnEditPage() : 'Function not found';
        } catch (e) {
          xShowEval = `Error: ${e.message}`;
        }
      }

      return {
        visible: saveButtonVisibleAfter,
        display: styles.display,
        opacity: styles.opacity,
        visibility: styles.visibility,
        classes: btn.className,
        xShow: xShowAttr,
        xShowEvaluation: xShowEval,
        boundingBox: rect ? { top: rect.top, left: rect.left, width: rect.width, height: rect.height } : null
      };
    });
    console.log('    Save button info (after):', saveButtonInfoAfter);
  }

  // Check all save buttons on page
  console.log('\n12. All save buttons on page:');
  const allSaveButtons = page.locator('button:has-text("Save")');
  const allSaveCount = await allSaveButtons.count();
  console.log(`    Total save buttons: ${allSaveCount}`);

  for (let i = 0; i < allSaveCount; i++) {
    const btn = allSaveButtons.nth(i);
    const btnInfo = await btn.evaluate((el, index) => {
      const rect = el.getBoundingBox();
      const styles = window.getComputedStyle(el);

      // Check if in global footer
      const globalFooter = document.querySelector('[x-data*="contextFooterGlobal"]');
      const inGlobalFooter = globalFooter && globalFooter.contains(el);

      return {
        index: index,
        text: el.textContent.trim(),
        visible: rect && rect.width > 0 && rect.height > 0,
        inGlobalFooter: inGlobalFooter,
        position: rect ? `y=${rect.top}` : 'not positioned',
        display: styles.display,
        zIndex: styles.zIndex
      };
    }, i);
    console.log(`    Button ${i + 1}:`, btnInfo);
  }

  // Take screenshots
  console.log('\n13. Taking screenshots...');
  await page.screenshot({
    path: 'save-button-debug-full.png',
    fullPage: true
  });

  await page.screenshot({
    path: 'save-button-debug-viewport.png',
    fullPage: false
  });

  console.log('\n=== DEBUG COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
  await page.screenshot({ path: 'save-button-debug-error.png', fullPage: true });
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
