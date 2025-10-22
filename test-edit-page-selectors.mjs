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

  console.log('2. Navigating to project edit...');
  await page.goto('http://aureuserp.test/admin/project/projects/2/edit');
  await page.waitForTimeout(4000);

  console.log('\n=== CHECKING SELECTORS ===\n');

  // Find all buttons with "Select" text
  const selectButtons = await page.locator('button:has-text("Select")').count();
  console.log(`Buttons with "Select": ${selectButtons}`);

  // Find all selects by looking for Filament select wrappers
  const filamentSelects = await page.locator('[data-filament-select]').count();
  console.log(`Filament selects: ${filamentSelects}`);

  // Look for customer label
  const customerLabels = await page.locator('label:has-text("Customer")').count();
  console.log(`Customer labels: ${customerLabels}`);

  // Try to find the customer select by ID pattern
  const customerByIdPattern = await page.locator('[id*="partner_id"]').count();
  console.log(`Elements with partner_id in ID: ${customerByIdPattern}`);

  // List all visible select buttons
  console.log('\nAll visible buttons:');
  const allButtons = page.locator('button[type="button"]');
  const buttonCount = await allButtons.count();
  console.log(`Total buttons: ${buttonCount}`);

  // Check if there's a customer value already selected
  console.log('\n=== CHECKING CUSTOMER FIELD ===');
  try {
    const customerSection = page.locator('label:has-text("Customer")').first();
    await customerSection.waitFor({ timeout: 2000 });
    console.log('Customer label found');

    // Get the parent container
    const container = customerSection.locator('..').locator('..');
    const containerText = await container.innerText();
    console.log(`Container text: ${containerText.substring(0, 100)}`);
  } catch (e) {
    console.log(`Could not find customer field: ${e.message}`);
  }

  console.log('\n=== SCREENSHOT ===');
  await page.screenshot({ path: 'edit-page-selectors.png', fullPage: true });
  console.log('Screenshot saved as edit-page-selectors.png');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await browser.close();
}
