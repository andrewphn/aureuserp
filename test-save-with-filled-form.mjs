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

  console.log('\n3. Checking initial state of required fields...');
  const initialState = await page.evaluate(() => {
    // Find the selects
    const customerSelect = document.querySelector('[name="data.partner_id"]');
    const projectTypeSelect = document.querySelector('[name="data.project_type"]');

    return {
      customerValue: customerSelect?.value || 'NOT FOUND',
      projectTypeValue: projectTypeSelect?.value || 'NOT FOUND'
    };
  });
  console.log('   Customer value:', initialState.customerValue);
  console.log('   Project Type value:', initialState.projectTypeValue);

  console.log('\n4. Filling Customer field...');
  // Click the Customer select to open dropdown
  await page.click('div:has(> select[name="data.partner_id"]) button');
  await page.waitForTimeout(1000);

  // Click on first option that appears
  const customerOption = page.locator('[role="option"]').first();
  await customerOption.click();
  await page.waitForTimeout(2000);

  console.log('5. Filling Project Type field...');
  // Click the Project Type select to open dropdown
  await page.click('div:has(> select[name="data.project_type"]) button');
  await page.waitForTimeout(1000);

  // Click on "Residential" option
  await page.click('[role="option"]:has-text("Residential")');
  await page.waitForTimeout(2000);

  console.log('\n6. Checking if fields are now filled...');
  const filledState = await page.evaluate(() => {
    const customerSelect = document.querySelector('[name="data.partner_id"]');
    const projectTypeSelect = document.querySelector('[name="data.project_type"]');

    return {
      customerValue: customerSelect?.value || 'EMPTY',
      projectTypeValue: projectTypeSelect?.value || 'EMPTY'
    };
  });
  console.log('   Customer value:', filledState.customerValue);
  console.log('   Project Type value:', filledState.projectTypeValue);

  console.log('\n7. Checking Filament native Save button state...');
  const saveButtonState = await page.evaluate(() => {
    const allButtons = Array.from(document.querySelectorAll('button'));
    const filamentSaveBtn = allButtons.find(btn => {
      const text = btn.textContent.trim();
      const isSubmit = btn.type === 'submit';
      return isSubmit && (text === 'Save' || text === 'Save changes');
    });

    if (!filamentSaveBtn) return { error: 'Filament Save button not found' };

    const styles = window.getComputedStyle(filamentSaveBtn);
    return {
      text: filamentSaveBtn.textContent.trim(),
      disabled: filamentSaveBtn.disabled,
      backgroundColor: styles.backgroundColor,
      display: styles.display,
      visible: filamentSaveBtn.offsetParent !== null
    };
  });

  console.log('   Button text:', saveButtonState.text);
  console.log('   Disabled:', saveButtonState.disabled);
  console.log('   Background:', saveButtonState.backgroundColor);
  console.log('   Visible:', saveButtonState.visible);

  if (saveButtonState.disabled) {
    console.log('\n   ❌ FILAMENT SAVE BUTTON IS STILL DISABLED AFTER FILLING FORM!');

    // Check for validation errors
    console.log('\n8. Checking for validation errors...');
    const errors = await page.evaluate(() => {
      const errorEls = document.querySelectorAll('.fi-fo-field-wrp-error-message');
      return Array.from(errorEls).map(el => el.textContent.trim());
    });

    if (errors.length > 0) {
      console.log('   Found validation errors:');
      errors.forEach(err => console.log('     -', err));
    } else {
      console.log('   No validation error messages visible');
    }

  } else {
    console.log('\n   ✅ FILAMENT SAVE BUTTON IS ENABLED! Attempting to click it...');

    // Click the Filament Save button
    await page.click('button[type="submit"]:has-text("Save")');
    await page.waitForTimeout(3000);

    console.log('\n9. Checking if save was successful...');
    const postSaveUrl = page.url();
    console.log('   Current URL:', postSaveUrl);

    // Check for success notification
    const hasSuccessNotification = await page.locator('text=/saved|success/i').count() > 0;
    console.log('   Has success notification:', hasSuccessNotification);
  }

  console.log('\n10. Taking final screenshot...');
  await page.screenshot({
    path: 'save-with-filled-form-result.png',
    fullPage: true
  });

  console.log('\n✅ COMPLETE');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(5000);
  await browser.close();
}
