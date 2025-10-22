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

  console.log('\n=== FULL WORKFLOW TEST ===\n');

  // Check initial state
  console.log('3. Checking initial state...');
  const street1 = page.locator('input[id*="street1"]').first();
  const initialValue = await street1.inputValue();
  const initialDisabled = await street1.isDisabled();
  console.log(`   Street1: "${initialValue || '(empty)'}"`);
  console.log(`   Disabled: ${initialDisabled}`);

  // Check for errors
  console.log('\n4. Checking for console errors...');
  const messages = [];
  page.on('console', msg => messages.push(msg.text()));
  await page.waitForTimeout(2000);

  const errors = messages.filter(m =>
    m.includes('error') || m.includes('Error') || m.includes('EntityStore')
  );
  console.log(`   Errors/EntityStore: ${errors.length}`);
  if (errors.length > 20) {
    console.log('   ❌ INFINITE LOOP DETECTED');
  }

  // Try to change customer (if there's a customer select visible)
  console.log('\n5. Looking for Customer select...');
  const customerSelects = await page.locator('text="Customer"').count();
  console.log(`   Found ${customerSelects} "Customer" labels`);

  if (customerSelects > 0) {
    console.log('\n6. Attempting to change customer selection...');
    try {
      // Find the button that shows current customer or "Select an option"
      const customerButton = page.locator('[id*="partner_id"]').locator('button').first();
      await customerButton.click({ timeout: 5000 });
      await page.waitForTimeout(1000);

      console.log('   Customer dropdown opened');

      // Select different customer
      await page.keyboard.press('ArrowDown');
      await page.keyboard.press('ArrowDown');
      await page.keyboard.press('Enter');
      await page.waitForTimeout(3000);

      console.log('   Customer changed');

      // Check if address auto-populated
      const newValue = await street1.inputValue();
      console.log(`   Street1 after customer change: "${newValue || '(empty)'}"`);

      if (newValue && newValue !== initialValue) {
        console.log('   ✅ Address auto-populated!');
      } else {
        console.log('   ⚠️ Address did not auto-populate');
      }

    } catch (error) {
      console.log(`   ❌ Error changing customer: ${error.message}`);
    }
  }

  // Test manual entry
  console.log('\n7. Testing toggle OFF for manual entry...');
  const toggle = page.locator('button[role="switch"]').first();
  await toggle.click();
  await page.waitForTimeout(1000);

  // Click another field to trigger update
  const nameField = page.locator('input[placeholder*="Project"]').first();
  await nameField.click();
  await page.waitForTimeout(1000);

  const isDisabledAfterToggle = await street1.isDisabled();
  console.log(`   Fields disabled after toggle: ${isDisabledAfterToggle}`);

  if (!isDisabledAfterToggle) {
    console.log('\n8. Entering manual address...');
    await street1.clear();
    await street1.fill('25 Friendship Lane');
    const manualValue = await street1.inputValue();
    console.log(`   ✅ Manual address entered: "${manualValue}"`);
  } else {
    console.log('   ❌ Fields still disabled');
  }

  console.log('\n=== TEST COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await browser.close();
}
