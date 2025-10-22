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

  console.log('\n=== CHECKING BOTH FOOTERS ===\n');

  // Find all sections
  const sections = page.locator('.fi-section');
  const sectionCount = await sections.count();
  console.log('3. Total sections found:', sectionCount);

  // Check each section
  for (let i = 0; i < sectionCount; i++) {
    const section = sections.nth(i);
    const text = await section.textContent();
    const position = await section.evaluate(el => {
      const rect = el.getBoundingClientRect();
      const style = window.getComputedStyle(el);
      return {
        y: rect.y,
        bottom: rect.bottom,
        position: style.position,
        zIndex: style.zIndex
      };
    });
    console.log(`\nSection ${i}:`);
    console.log('  Position:', position);
    console.log('  Text preview:', text.substring(0, 100).replace(/\s+/g, ' '));
  }

  // Look for global footer specifically
  console.log('\n4. Looking for global footer...');
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  const globalFooterExists = await globalFooter.count() > 0;
  console.log('   Global footer exists:', globalFooterExists);

  if (globalFooterExists) {
    const isVisible = await globalFooter.isVisible();
    console.log('   Global footer visible:', isVisible);

    // Expand it
    await globalFooter.click();
    await page.waitForTimeout(1000);

    // Check for save button in global footer
    const saveInGlobal = globalFooter.locator('button:has-text("Save")');
    const saveCount = await saveInGlobal.count();
    console.log('   Save buttons in global footer:', saveCount);

    if (saveCount > 0) {
      const isVisible = await saveInGlobal.isVisible();
      console.log('   Save button visible:', isVisible);
    }
  }

  // Take full page screenshot
  console.log('\n5. Taking full page screenshot...');
  await page.screenshot({
    path: 'both-footers-view.png',
    fullPage: true
  });

  console.log('\n=== TEST COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(2000);
  await browser.close();
}
