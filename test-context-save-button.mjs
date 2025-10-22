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

  console.log('\n=== CHECKING CONTEXT SAVE BUTTON ===\n');

  // Check if global footer is visible
  const footer = page.locator('.fi-section').last();
  const isFooterVisible = await footer.isVisible();
  console.log('3. Global footer visible:', isFooterVisible);

  // Expand footer if minimized
  console.log('4. Expanding footer...');
  await footer.click();
  await page.waitForTimeout(1000);

  // Look for the save button in the global footer
  console.log('5. Looking for Save button in global footer...');

  // Try multiple selectors
  const saveButtonSelectors = [
    'button.fi-btn-color-success:has-text("Save")',
    'button:has(svg):has-text("Save")',
    '.fi-section button[type="button"]:has-text("Save")',
  ];

  let saveButton = null;
  let foundSelector = null;

  for (const selector of saveButtonSelectors) {
    const btn = page.locator(selector).first();
    const count = await btn.count();
    if (count > 0) {
      const visible = await btn.isVisible();
      console.log(`   Selector "${selector}": found=${count}, visible=${visible}`);
      if (visible) {
        saveButton = btn;
        foundSelector = selector;
        break;
      }
    } else {
      console.log(`   Selector "${selector}": NOT FOUND`);
    }
  }

  if (saveButton) {
    console.log('\n✅ Save button found!');
    console.log('   Using selector:', foundSelector);

    // Get button details
    const buttonText = await saveButton.textContent();
    const buttonClass = await saveButton.getAttribute('class');
    console.log('   Button text:', buttonText.trim());
    console.log('   Button classes:', buttonClass);

    // Take screenshot
    console.log('\n6. Taking screenshot of footer with save button...');
    await page.screenshot({
      path: 'context-save-button-visible.png',
      fullPage: true
    });
    console.log('   Screenshot saved: context-save-button-visible.png');

  } else {
    console.log('\n❌ Save button NOT FOUND in global footer');

    // Debug: Show all buttons in footer
    console.log('\n   All buttons in footer:');
    const allButtons = page.locator('.fi-section button');
    const buttonCount = await allButtons.count();
    console.log('   Total buttons:', buttonCount);

    for (let i = 0; i < buttonCount; i++) {
      const btn = allButtons.nth(i);
      const text = await btn.textContent();
      const type = await btn.getAttribute('type');
      const classes = await btn.getAttribute('class');
      console.log(`   [${i}] type="${type}" text="${text.trim()}" classes="${classes}"`);
    }

    // Take screenshot for debugging
    await page.screenshot({
      path: 'context-save-button-not-found.png',
      fullPage: true
    });
    console.log('\n   Debug screenshot saved: context-save-button-not-found.png');
  }

  // Also check the Alpine.js function exists
  console.log('\n7. Checking Alpine.js functions...');
  const hasIsOnEditPage = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (!footer) return 'Footer element not found';

    const alpineData = Alpine.$data(footer);
    return typeof alpineData.isOnEditPage === 'function' ? 'Function exists' : 'Function missing';
  });
  console.log('   isOnEditPage():', hasIsOnEditPage);

  const hasSaveCurrentForm = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (!footer) return 'Footer element not found';

    const alpineData = Alpine.$data(footer);
    return typeof alpineData.saveCurrentForm === 'function' ? 'Function exists' : 'Function missing';
  });
  console.log('   saveCurrentForm():', hasSaveCurrentForm);

  // Check what isOnEditPage() returns
  const isOnEditPageResult = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (!footer) return 'Footer element not found';

    const alpineData = Alpine.$data(footer);
    if (typeof alpineData.isOnEditPage !== 'function') return 'Function not found';

    return alpineData.isOnEditPage();
  });
  console.log('   isOnEditPage() returns:', isOnEditPageResult);

  console.log('\n=== TEST COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
