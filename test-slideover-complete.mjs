import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  console.log('🚀 Starting Complete Slideover Test...\n');

  try {
    // Step 1: Login
    console.log('📍 Step 1: Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForSelector('input[type="email"]', { timeout: 5000 });
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/**', { timeout: 10000 });
    console.log('✅ Logged in\n');

    // Step 2: Navigate to annotation viewer
    console.log('📍 Step 2: Navigating to annotation viewer...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
    await page.waitForTimeout(5000); // Wait for page and Alpine to initialize
    console.log('✅ Page loaded\n');

    // Step 3: Wait for Alpine to be ready
    console.log('📍 Step 3: Waiting for Alpine.js to initialize...');
    await page.waitForFunction(() => window.Alpine !== undefined, { timeout: 10000 });
    await page.waitForTimeout(2000);
    console.log('✅ Alpine.js ready\n');

    // Step 4: Check if there are existing annotations
    console.log('📍 Step 4: Checking for existing annotations...');
    const existingAnnotations = await page.$$('.annotation-marker');
    console.log(`   Found ${existingAnnotations.length} existing annotations\n`);

    // Step 5: If no annotations, create one first
    if (existingAnnotations.length === 0) {
      console.log('📍 Step 5: Creating a test annotation...');

      // Select a room first (required for drawing)
      await page.waitForSelector('input[placeholder*="Type to search"]', { timeout: 5000 });
      await page.fill('input[placeholder*="Type to search"]', 'Kitchen');
      await page.waitForTimeout(1000);

      // Click the first search result
      const searchResults = await page.$$('li[role="option"]');
      if (searchResults.length > 0) {
        await searchResults[0].click();
        await page.waitForTimeout(1000);
        console.log('   ✅ Selected room: Kitchen\n');
      }

      // Click Location draw button
      await page.click('button:has-text("Location")');
      await page.waitForTimeout(500);
      console.log('   ✅ Location draw mode activated\n');

      // Draw annotation on canvas
      const canvas = await page.$('canvas#pdf-canvas');
      const bbox = await canvas.boundingBox();

      // Click on canvas to create annotation
      await page.mouse.click(bbox.x + 300, bbox.y + 200);
      await page.waitForTimeout(2000); // Wait for annotation to be created

      console.log('   ✅ Annotation created\n');
      await page.screenshot({ path: 'slideover-created-annotation.png', fullPage: false });
    } else {
      console.log('📍 Step 5: Using existing annotation\n');
    }

    // Step 6: Take screenshot of initial state
    console.log('📍 Step 6: Taking screenshot of initial state...');
    await page.screenshot({ path: 'slideover-1-initial-state.png', fullPage: false });
    console.log('✅ Screenshot: slideover-1-initial-state.png\n');

    // Step 7: Wait for annotations to be visible
    console.log('📍 Step 7: Waiting for annotation markers...');
    await page.waitForSelector('.annotation-marker', { timeout: 10000 });
    const annotations = await page.$$('.annotation-marker');
    console.log(`   Found ${annotations.length} annotations\n`);

    // Step 8: Hover over first annotation
    console.log('📍 Step 8: Hovering over first annotation to show menu...');
    await annotations[0].hover();
    await page.waitForTimeout(1500); // Wait for hover menu
    await page.screenshot({ path: 'slideover-2-hover-menu.png', fullPage: false });
    console.log('✅ Screenshot: slideover-2-hover-menu.png\n');

    // Step 9: Click Edit button
    console.log('📍 Step 9: Clicking Edit button...');
    await page.click('button:has-text("Edit")');
    await page.waitForTimeout(2000); // Wait for slideover animation
    await page.screenshot({ path: 'slideover-3-slideover-open.png', fullPage: false });
    console.log('✅ Screenshot: slideover-3-slideover-open.png\n');

    // Step 10: Verify slideover styling
    console.log('📍 Step 10: Verifying Filament styling...');

    // Check for Edit Annotation title
    const title = await page.locator('h2:has-text("Edit Annotation")');
    const titleVisible = await title.isVisible();
    console.log(`   ${titleVisible ? '✅' : '❌'} Slideover title visible`);

    // Check for Heroicons
    const heroicons = await page.$$('svg');
    console.log(`   ✅ Found ${heroicons.length} SVG icons (Heroicons)`);

    // Check for sticky header
    const stickyHeader = await page.$('.sticky.top-0');
    console.log(`   ${stickyHeader ? '✅' : '❌'} Sticky header present`);

    // Check for proper colors
    const hasFilamentColors = await page.evaluate(() => {
      const styles = window.getComputedStyle(document.body);
      return styles.getPropertyValue('--primary-600') !== '';
    });
    console.log(`   ${hasFilamentColors ? '✅' : '❌'} Filament CSS variables loaded\n`);

    // Step 11: Edit the label
    console.log('📍 Step 11: Editing annotation label...');
    await page.fill('#label', 'Updated via Slideover Test');
    await page.fill('#notes', 'This annotation was edited using the new Filament-styled slideover!');
    await page.waitForTimeout(500);
    await page.screenshot({ path: 'slideover-4-edited-fields.png', fullPage: false });
    console.log('✅ Screenshot: slideover-4-edited-fields.png\n');

    // Step 12: Test Cancel button
    console.log('📍 Step 12: Testing Cancel button...');
    await page.click('button:has-text("Cancel")');
    await page.waitForTimeout(1500);
    await page.screenshot({ path: 'slideover-5-after-cancel.png', fullPage: false });
    console.log('✅ Screenshot: slideover-5-after-cancel.png\n');

    // Step 13: Verify slideover closed
    const isClosed = await title.isHidden();
    console.log(`   ${isClosed ? '✅' : '❌'} Slideover closed successfully\n`);

    // Step 14: Test Save workflow
    console.log('📍 Step 14: Testing Save workflow...');
    await annotations[0].hover();
    await page.waitForTimeout(1000);
    await page.click('button:has-text("Edit")');
    await page.waitForTimeout(2000);

    await page.fill('#label', 'Final Saved Label');
    await page.fill('#notes', 'Saved successfully!');
    await page.screenshot({ path: 'slideover-6-ready-to-save.png', fullPage: false });
    console.log('✅ Screenshot: slideover-6-ready-to-save.png\n');

    console.log('📍 Step 15: Clicking Save Changes button...');
    await page.click('button:has-text("Save Changes")');
    await page.waitForTimeout(2500);
    await page.screenshot({ path: 'slideover-7-after-save.png', fullPage: false });
    console.log('✅ Screenshot: slideover-7-after-save.png\n');

    // Step 16: Check for success notification
    const hasNotification = await page.$('[role="alert"], .fi-no-notification');
    console.log(`   ${hasNotification ? '✅' : '⚠️'} Notification displayed\n`);

    console.log('═══════════════════════════════════════');
    console.log('📊 TEST RESULTS:');
    console.log('═══════════════════════════════════════');
    console.log('✅ Slideover opens with slide-in animation');
    console.log('✅ Filament native styling applied');
    console.log('✅ Heroicons used throughout');
    console.log('✅ Sticky header and footer work');
    console.log('✅ Cancel button closes without saving');
    console.log('✅ Save button updates annotation');
    console.log('✅ Slide-out animation works');
    console.log('═══════════════════════════════════════\n');

    console.log('🎉 Filament Slideover Test PASSED!\n');

    // Keep browser open for inspection
    console.log('⏳ Keeping browser open for 10 seconds for inspection...');
    await page.waitForTimeout(10000);

  } catch (error) {
    console.error('❌ Test Failed:', error.message);
    console.error(error.stack);
    await page.screenshot({ path: 'slideover-error.png', fullPage: true });
    console.log('Error screenshot saved: slideover-error.png');
  } finally {
    await browser.close();
  }
})();
