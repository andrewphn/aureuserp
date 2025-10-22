import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
  ignoreHTTPSErrors: true,
  viewport: { width: 1440, height: 900 }
});
const page = await context.newPage();

// Collect console messages
const consoleMessages = [];
page.on('console', msg => {
  consoleMessages.push({
    type: msg.type(),
    text: msg.text()
  });
});

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

  console.log('\n3. Checking browser console for errors or warnings...');
  const relevantMessages = consoleMessages.filter(msg =>
    msg.text.includes('EntityStore') ||
    msg.text.includes('save') ||
    msg.text.includes('disabled') ||
    msg.text.includes('validation') ||
    msg.text.includes('error')
  );

  if (relevantMessages.length > 0) {
    console.log('\n   Relevant console messages:');
    relevantMessages.forEach(msg => {
      console.log(`   [${msg.type.toUpperCase()}] ${msg.text}`);
    });
  } else {
    console.log('   No relevant console messages found');
  }

  console.log('\n4. Checking Livewire component state...');
  const livewireState = await page.evaluate(() => {
    // Get the first Livewire component (the form)
    const component = window.Livewire?.all()[0];

    if (!component) return { error: 'No Livewire component found' };

    return {
      componentName: component.name,
      hasChanges: component.$wire?.__instance?.effects?.dirty || false,
      formData: {
        partner_id: component.$wire?.data?.partner_id,
        project_type: component.$wire?.data?.project_type,
        company_id: component.$wire?.data?.company_id
      }
    };
  });

  console.log('   Component:', livewireState.componentName);
  console.log('   Has unsaved changes:', livewireState.hasChanges);
  console.log('   Form data:', JSON.stringify(livewireState.formData, null, 2));

  console.log('\n5. Checking form validation state...');
  const validationState = await page.evaluate(() => {
    // Check if Filament has validation errors
    const errorMessages = Array.from(document.querySelectorAll('.fi-fo-field-wrp-error-message'))
      .map(el => el.textContent.trim());

    // Check required fields
    const requiredFields = Array.from(document.querySelectorAll('[required], [aria-required="true"]'));
    const emptyRequired = requiredFields.filter(field => {
      const value = field.value || field.getAttribute('value') || '';
      return value.trim() === '';
    }).map(field => ({
      name: field.name || field.id,
      type: field.type || field.tagName
    }));

    return {
      errorMessages,
      emptyRequiredFields: emptyRequired
    };
  });

  console.log('   Validation errors:', validationState.errorMessages.length);
  if (validationState.errorMessages.length > 0) {
    validationState.errorMessages.forEach(err => console.log('     -', err));
  }

  console.log('   Empty required fields:', validationState.emptyRequiredFields.length);
  if (validationState.emptyRequiredFields.length > 0) {
    validationState.emptyRequiredFields.forEach(field =>
      console.log(`     - ${field.name} (${field.type})`)
    );
  }

  console.log('\n6. Taking screenshot...');
  await page.screenshot({
    path: 'why-save-blocked-diagnostic.png',
    fullPage: true
  });

  console.log('\n✅ COMPLETE');

  // Summary
  console.log('\n═══════════════════════════════════════════');
  console.log('DIAGNOSIS SUMMARY');
  console.log('═══════════════════════════════════════════');

  if (validationState.errorMessages.length > 0) {
    console.log('❌ VALIDATION ERRORS FOUND:');
    validationState.errorMessages.forEach(err => console.log('   •', err));
  }

  if (validationState.emptyRequiredFields.length > 0) {
    console.log('❌ EMPTY REQUIRED FIELDS:');
    validationState.emptyRequiredFields.forEach(field =>
      console.log(`   • ${field.name} (${field.type})`)
    );
  }

  if (validationState.errorMessages.length === 0 && validationState.emptyRequiredFields.length === 0) {
    console.log('✅ No validation errors or empty required fields detected');
    console.log('   The form SHOULD allow saving');
  }

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(5000);
  await browser.close();
}
