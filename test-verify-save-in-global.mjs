import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
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

  console.log('\n=== CHECKING SAVE BUTTON IN GLOBAL FOOTER ===\n');

  // Find the global footer specifically
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  console.log('3. Global footer exists:', await globalFooter.count() > 0);

  // Expand it
  await globalFooter.click();
  await page.waitForTimeout(1000);

  // Find ALL buttons with "Save" text on the entire page
  console.log('\n4. All Save buttons on page:');
  const allSaveButtons = page.locator('button:has-text("Save"), a:has-text("Save")');
  const count = await allSaveButtons.count();
  console.log(`   Total found: ${count}`);

  for (let i = 0; i < count; i++) {
    const btn = allSaveButtons.nth(i);
    const text = await btn.textContent();
    const classes = await btn.getAttribute('class');
    const bbox = await btn.boundingBox();

    // Check if it's inside the global footer
    const isInGlobalFooter = await page.evaluate(({btnText, footerSelector}) => {
      const footer = document.querySelector(footerSelector);
      if (!footer) return false;

      const buttons = footer.querySelectorAll('button, a');
      for (const button of buttons) {
        if (button.textContent.includes(btnText.trim())) {
          return true;
        }
      }
      return false;
    }, {btnText: text, footerSelector: '[x-data*="contextFooterGlobal"]'});

    console.log(`\n   Button ${i + 1}:`);
    console.log(`     Text: "${text.trim()}"`);
    console.log(`     In global footer: ${isInGlobalFooter}`);
    console.log(`     Position: y=${bbox?.y}`);
    console.log(`     Classes: ${classes?.substring(0, 100)}...`);
  }

  // Specifically check for our green save button
  console.log('\n5. Looking for green save button:');
  const greenSaveBtn = page.locator('button.bg-green-600:has-text("Save")');
  const greenCount = await greenSaveBtn.count();
  console.log(`   Green save buttons found: ${greenCount}`);

  if (greenCount > 0) {
    const isVisible = await greenSaveBtn.isVisible();
    const bbox = await greenSaveBtn.boundingBox();
    console.log(`   Visible: ${isVisible}`);
    console.log(`   Position: y=${bbox?.y}, height=${bbox?.height}`);
  }

  // Take screenshot
  await page.screenshot({
    path: 'verify-save-global.png',
    fullPage: false
  });

  console.log('\n=== COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(2000);
  await browser.close();
}
