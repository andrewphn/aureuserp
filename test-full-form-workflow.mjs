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

  console.log('2. Navigating to create new project...');
  await page.goto('http://aureuserp.test/admin/project/projects/create');
  await page.waitForTimeout(3000);

  console.log('\n=== FULL FORM TEST ===\n');

  // Check for infinite loop
  console.log('3. Checking for console errors/loops...');
  const messages = [];
  page.on('console', msg => messages.push(msg.text()));
  await page.waitForTimeout(2000);

  const entityStoreMessages = messages.filter(m => m.includes('EntityStore'));
  console.log(`   EntityStore messages: ${entityStoreMessages.length}`);
  if (entityStoreMessages.length > 20) {
    console.log('   ❌ INFINITE LOOP DETECTED');
  } else {
    console.log('   ✅ No infinite loop');
  }

  console.log('\n4. Selecting Company...');
  const companySelect = page.locator('button:has-text("Company")').first();
  await companySelect.click();
  await page.waitForTimeout(1000);
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(2000);
  console.log('   ✅ Company selected');

  console.log('\n5. Selecting Branch...');
  const branchSelect = page.locator('button:has-text("Branch")').first();
  await branchSelect.click();
  await page.waitForTimeout(1000);
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(2000);
  console.log('   ✅ Branch selected');

  console.log('\n6. Selecting Customer...');
  const customerSelect = page.locator('button:has-text("Customer")').first();
  await customerSelect.click();
  await page.waitForTimeout(1000);
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');
  await page.waitForTimeout(3000);
  console.log('   ✅ Customer selected');

  console.log('\n7. Checking if address auto-populated...');
  const street1 = page.locator('input[id*="street1"]').first();
  const street1Value = await street1.inputValue();
  const isDisabled = await street1.isDisabled();
  console.log(`   Street1: "${street1Value || '(empty)'}"`);
  console.log(`   Disabled: ${isDisabled}`);

  if (street1Value) {
    console.log('   ✅ Address auto-populated from customer');
  } else {
    console.log('   ⚠️ Address did not auto-populate');
  }

  console.log('\n8. Testing toggle OFF for manual entry...');
  const toggle = page.locator('button[role="switch"]').first();
  await toggle.click();
  await page.waitForTimeout(1500);

  const isDisabledAfterToggle = await street1.isDisabled();
  console.log(`   Fields disabled: ${isDisabledAfterToggle}`);

  if (!isDisabledAfterToggle) {
    console.log('\n9. Entering manual address...');
    await street1.clear();
    await street1.fill('25 Friendship Lane');
    const manualValue = await street1.inputValue();
    console.log(`   ✅ Manual address: "${manualValue}"`);
  } else {
    console.log('   ❌ Fields still disabled after toggle');
  }

  console.log('\n10. Checking for any errors in final 2 seconds...');
  await page.waitForTimeout(2000);
  const finalMessages = [];
  page.on('console', msg => finalMessages.push(msg.text()));
  await page.waitForTimeout(2000);

  const errors = finalMessages.filter(m =>
    m.includes('error') || m.includes('Error') || m.includes('EntityStore')
  );

  if (errors.length > 0) {
    console.log(`   ⚠️ Found ${errors.length} errors/warnings`);
    errors.slice(0, 5).forEach(e => console.log(`      ${e}`));
  } else {
    console.log('   ✅ No errors detected');
  }

  console.log('\n=== TEST COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await browser.close();
}
