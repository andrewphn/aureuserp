import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
  ignoreHTTPSErrors: true
});
const page = await context.newPage();

try {
  console.log('1. Navigating to login page...');
  await page.goto('http://aureuserp.test/admin/login');
  await page.waitForTimeout(2000);

  console.log('2. Logging in...');
  await page.fill('input[type="email"]', 'info@tcswoodwork.com');
  await page.fill('input[type="password"]', 'Lola2024!');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  console.log('3. Navigating to create new project...');
  await page.goto('http://aureuserp.test/admin/projects/projects/create');
  await page.waitForTimeout(3000);

  console.log('4. Checking initial toggle state...');
  const snapshot1 = await page.locator('body').ariaSnapshot();
  const toggleMatch = snapshot1.match(/- switch "Use customer address for project location" \[(checked|unchecked)\]/);
  console.log(`   Toggle initial state: ${toggleMatch ? toggleMatch[1] : 'NOT FOUND'}`);

  console.log('5. Selecting a customer...');
  // Find and click the customer select
  const customerSelect = page.locator('button:has-text("Customer")').first();
  await customerSelect.click();
  await page.waitForTimeout(1000);

  // Select first customer from dropdown
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(2000);

  console.log('6. Checking if address auto-populated...');
  const street1 = await page.locator('input[id*="street1"]').first().inputValue();
  console.log(`   Street1 value: ${street1 || '(empty)'}`);

  console.log('7. Checking for console errors...');
  const messages = [];
  page.on('console', msg => messages.push(msg.text()));
  await page.waitForTimeout(2000);

  const errors = messages.filter(m => m.includes('EntityStore'));
  console.log(`   EntityStore loops: ${errors.length > 10 ? 'YES - INFINITE LOOP!' : 'NO - looks good'}`);

  console.log('8. Testing toggle OFF then ON...');
  const toggle = page.locator('button[role="switch"]').first();
  await toggle.click();
  await page.waitForTimeout(1000);
  console.log('   Toggled OFF');

  await toggle.click();
  await page.waitForTimeout(2000);
  console.log('   Toggled ON');

  const street1After = await page.locator('input[id*="street1"]').first().inputValue();
  console.log(`   Street1 after re-toggle: ${street1After || '(empty)'}`);

  console.log('\n✅ Test complete!');

} catch (error) {
  console.error('❌ Error:', error.message);
} finally {
  await browser.close();
}
