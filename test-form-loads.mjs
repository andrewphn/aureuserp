import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();

try {
  await page.goto('http://aureuserp.test/admin/login');
  await page.waitForTimeout(1000);
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  console.log('Navigating to project edit...');
  await page.goto('http://aureuserp.test/admin/project/projects/2/edit');
  await page.waitForTimeout(5000);

  console.log('\nChecking if form elements load...');
  const toggles = await page.locator('button[role="switch"]').count();
  console.log(`Toggles found: ${toggles}`);

  const selects = await page.locator('button:has-text("Select an option")').count();
  console.log(`Select buttons found: ${selects}`);

  if (toggles > 0 && selects > 0) {
    console.log('\n✅ FORM LOADED SUCCESSFULLY!');
    console.log('\nForm is no longer frozen - you can now select Branch and Customer');
  } else {
    console.log('\n❌ Form elements missing');
  }

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await browser.close();
}
