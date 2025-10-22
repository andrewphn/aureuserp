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
  await page.waitForTimeout(5000);

  console.log('\n3. Checking Filament Save button status:');

  const saveButtonInfo = await page.evaluate(() => {
    // Find all buttons with "Save" text
    const allButtons = Array.from(document.querySelectorAll('button'));
    const saveButtons = allButtons.filter(btn =>
      btn.textContent.includes('Save') || btn.textContent.includes('save')
    );

    return saveButtons.map(btn => {
      const styles = window.getComputedStyle(btn);
      return {
        text: btn.textContent.trim(),
        type: btn.type,
        disabled: btn.disabled,
        ariaDisabled: btn.getAttribute('aria-disabled'),
        wireLoading: btn.getAttribute('wire:loading.attr'),
        classes: btn.className.substring(0, 100),
        display: styles.display,
        opacity: styles.opacity,
        pointerEvents: styles.pointerEvents,
        inFooter: !!btn.closest('[x-data*="contextFooterGlobal"]')
      };
    });
  });

  console.log('\n   Found', saveButtonInfo.length, 'save buttons:');
  saveButtonInfo.forEach((btn, i) => {
    console.log(`\n   Button ${i + 1}:`);
    console.log(`     Text: "${btn.text}"`);
    console.log(`     Type: ${btn.type}`);
    console.log(`     Disabled: ${btn.disabled}`);
    console.log(`     aria-disabled: ${btn.ariaDisabled}`);
    console.log(`     In global footer: ${btn.inFooter}`);
    console.log(`     Display: ${btn.display}`);
    console.log(`     Opacity: ${btn.opacity}`);
    console.log(`     Pointer events: ${btn.pointerEvents}`);
  });

  console.log('\n4. Checking form validation state:');

  const formInfo = await page.evaluate(() => {
    const form = document.querySelector('form');
    if (!form) return { error: 'No form found' };

    // Check for required fields
    const requiredFields = form.querySelectorAll('[required], [aria-required="true"]');
    const invalidFields = [];

    requiredFields.forEach(field => {
      const value = field.value || '';
      const hasError = field.classList.contains('error') ||
                      field.getAttribute('aria-invalid') === 'true';

      if (!value.trim() || hasError) {
        invalidFields.push({
          name: field.name || field.id,
          type: field.type,
          value: value,
          hasError: hasError
        });
      }
    });

    // Check for error messages
    const errorMessages = Array.from(document.querySelectorAll('.fi-fo-field-wrp-error-message, [role="alert"]'))
      .map(el => el.textContent.trim())
      .filter(text => text.length > 0);

    return {
      requiredFieldsCount: requiredFields.length,
      invalidFields: invalidFields,
      errorMessages: errorMessages
    };
  });

  console.log('   Required fields:', formInfo.requiredFieldsCount);
  console.log('   Invalid fields:', formInfo.invalidFields?.length || 0);

  if (formInfo.invalidFields && formInfo.invalidFields.length > 0) {
    console.log('\n   Invalid fields details:');
    formInfo.invalidFields.forEach(field => {
      console.log(`     - ${field.name}: value="${field.value}", hasError=${field.hasError}`);
    });
  }

  if (formInfo.errorMessages && formInfo.errorMessages.length > 0) {
    console.log('\n   Error messages:');
    formInfo.errorMessages.forEach(msg => {
      console.log(`     - ${msg}`);
    });
  }

  console.log('\n5. Taking screenshot...');
  await page.screenshot({ path: 'save-disabled-debug.png', fullPage: true });

  console.log('\n✅ COMPLETE');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
