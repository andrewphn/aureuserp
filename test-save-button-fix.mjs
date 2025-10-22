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
  await page.waitForTimeout(5000);

  console.log('\n3. Checking initial Save button state (should be disabled)...');
  const initialButtonState = await page.evaluate(() => {
    const allButtons = Array.from(document.querySelectorAll('button[type="submit"]'));
    const saveBtn = allButtons.find(btn => btn.textContent.includes('Save'));
    return {
      found: !!saveBtn,
      disabled: saveBtn?.disabled || false,
      text: saveBtn?.textContent.trim()
    };
  });

  console.log('   Save button found:', initialButtonState.found);
  console.log('   Initial disabled state:', initialButtonState.disabled);

  console.log('\n4. Filling Customer field...');
  // Wait for the Customer field to be ready
  await page.waitForSelector('label:has-text("Customer")');
  await page.waitForTimeout(1000);

  // Click the Customer select dropdown
  const customerLabel = await page.locator('text=Customer').first();
  const customerWrapper = await customerLabel.locator('xpath=ancestor::div[contains(@class, "fi-fo-field-wrp")]').first();
  const customerButton = await customerWrapper.locator('button').first();

  await customerButton.click();
  await page.waitForTimeout(1500);

  // Select first customer option
  const firstCustomer = page.locator('[role="option"]').first();
  await firstCustomer.click();
  await page.waitForTimeout(2000);

  console.log('5. Filling Project Type field...');
  // Click the Project Type select dropdown
  const projectTypeLabel = await page.locator('text=Project Type').first();
  const projectTypeWrapper = await projectTypeLabel.locator('xpath=ancestor::div[contains(@class, "fi-fo-field-wrp")]').first();
  const projectTypeButton = await projectTypeWrapper.locator('button').first();

  await projectTypeButton.click();
  await page.waitForTimeout(1500);

  // Select "Residential"
  await page.click('[role="option"]:has-text("Residential")');
  await page.waitForTimeout(2000);

  console.log('\n6. Clicking into Name field to trigger blur event...');
  await page.click('input[placeholder="Project Name..."]');
  await page.waitForTimeout(3000);

  console.log('\n7. Checking Save button state AFTER filling required fields...');
  const finalButtonState = await page.evaluate(() => {
    const allButtons = Array.from(document.querySelectorAll('button[type="submit"]'));
    const saveBtn = allButtons.find(btn => btn.textContent.includes('Save'));

    const styles = saveBtn ? window.getComputedStyle(saveBtn) : null;

    return {
      found: !!saveBtn,
      disabled: saveBtn?.disabled || false,
      text: saveBtn?.textContent.trim(),
      backgroundColor: styles?.backgroundColor,
      opacity: styles?.opacity
    };
  });

  console.log('   Save button found:', finalButtonState.found);
  console.log('   Disabled state:', finalButtonState.disabled);
  console.log('   Background color:', finalButtonState.backgroundColor);
  console.log('   Opacity:', finalButtonState.opacity);

  if (finalButtonState.disabled) {
    console.log('\n   ❌ SAVE BUTTON STILL DISABLED - Fix did not work!');
  } else {
    console.log('\n   ✅ SAVE BUTTON IS NOW ENABLED - Fix worked!');
  }

  console.log('\n8. Taking screenshot...');
  await page.screenshot({
    path: 'save-button-after-fix.png',
    fullPage: false
  });

  console.log('\n✅ TEST COMPLETE');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(5000);
  await browser.close();
}
