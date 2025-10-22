import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
  ignoreHTTPSErrors: true,
  viewport: { width: 1440, height: 900 }
});
const page = await context.newPage();

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

  console.log('3. Expanding global footer...');
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  await globalFooter.click();
  await page.waitForTimeout(1000);

  console.log('4. Checking all buttons in global footer:');
  const allButtons = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (!footer) return [];

    const buttons = footer.querySelectorAll('button, a');
    return Array.from(buttons).map(btn => {
      const rect = btn.getBoundingClientRect();
      const styles = window.getComputedStyle(btn);
      return {
        text: btn.textContent.trim(),
        tag: btn.tagName,
        visible: styles.display !== 'none' && styles.visibility !== 'hidden',
        display: styles.display,
        position: `x=${rect.left.toFixed(0)}, y=${rect.top.toFixed(0)}`,
        width: rect.width.toFixed(0),
        height: rect.height.toFixed(0),
        classes: btn.className.substring(0, 50)
      };
    });
  });

  console.log('   Buttons found:', allButtons.length);
  allButtons.forEach((btn, i) => {
    console.log(`   ${i + 1}. "${btn.text}" [${btn.tag}]`);
    console.log(`      Visible: ${btn.visible}, Display: ${btn.display}`);
    console.log(`      Position: ${btn.position}, Size: ${btn.width}x${btn.height}`);
  });

  console.log('\n5. Taking screenshot of bottom area...');

  // Scroll to bottom to ensure footer is visible
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(500);

  // Full page screenshot
  await page.screenshot({
    path: 'save-button-full-page.png',
    fullPage: true
  });

  // Just the viewport
  await page.screenshot({
    path: 'save-button-viewport.png',
    fullPage: false
  });

  // Highlight the save button if it exists
  const saveButton = globalFooter.locator('button:has-text("Save")');
  if (await saveButton.count() > 0) {
    await saveButton.evaluate(btn => {
      btn.style.border = '3px solid red';
      btn.style.boxShadow = '0 0 10px red';
    });
    await page.waitForTimeout(500);

    await page.screenshot({
      path: 'save-button-highlighted.png',
      fullPage: false
    });

    console.log('   ✅ Save button highlighted with red border');
  } else {
    console.log('   ❌ Save button not found');
  }

  console.log('\n=== COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
