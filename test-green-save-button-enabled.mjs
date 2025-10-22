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

  console.log('3. Waiting for page to fully load...');
  await page.waitForTimeout(3000);

  console.log('\n4. Expanding global footer...');
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  await globalFooter.click();
  await page.waitForTimeout(1500);

  console.log('\n5. Checking green Save button state:');

  const saveInfo = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    const saveBtn = Array.from(footer.querySelectorAll('button')).find(btn =>
      btn.textContent.includes('Save') && !btn.textContent.includes('changes')
    );

    if (!saveBtn) return { error: 'Save button not found' };

    const rect = saveBtn.getBoundingClientRect();
    const styles = window.getComputedStyle(saveBtn);

    return {
      text: saveBtn.textContent.trim(),
      disabled: saveBtn.disabled,
      backgroundColor: styles.backgroundColor,
      color: styles.color,
      opacity: styles.opacity,
      cursor: styles.cursor,
      hasOpacity50: saveBtn.classList.contains('opacity-50'),
      hasCursorNotAllowed: saveBtn.classList.contains('cursor-not-allowed'),
      hasHoverBgGreen: saveBtn.classList.contains('hover:bg-green-600'),
      position: `x=${rect.left.toFixed(0)}, y=${rect.top.toFixed(0)}`,
      visible: rect.width > 0 && rect.height > 0 && styles.display !== 'none'
    };
  });

  console.log('   Text:', saveInfo.text);
  console.log('   Disabled attribute:', saveInfo.disabled);
  console.log('   Background:', saveInfo.backgroundColor);
  console.log('   Color:', saveInfo.color);
  console.log('   Opacity:', saveInfo.opacity);
  console.log('   Cursor:', saveInfo.cursor);
  console.log('   Has opacity-50 class:', saveInfo.hasOpacity50);
  console.log('   Has cursor-not-allowed class:', saveInfo.hasCursorNotAllowed);
  console.log('   Has hover:bg-green-600 class:', saveInfo.hasHoverBgGreen);
  console.log('   Position:', saveInfo.position);
  console.log('   Visible:', saveInfo.visible);

  if (!saveInfo.disabled && !saveInfo.hasOpacity50 && !saveInfo.hasCursorNotAllowed) {
    console.log('\n   ✅ GREEN SAVE BUTTON IS ENABLED!');
    console.log('   ✅ Form validation passed, button is in normal state');
  } else {
    console.log('\n   ❌ Button is still disabled/faded:');
    console.log('      - disabled attribute:', saveInfo.disabled);
    console.log('      - has opacity-50:', saveInfo.hasOpacity50);
    console.log('      - has cursor-not-allowed:', saveInfo.hasCursorNotAllowed);
  }

  console.log('\n6. Taking final screenshot...');
  await page.screenshot({
    path: 'green-save-button-enabled-state.png',
    fullPage: false
  });

  console.log('\n✅ COMPLETE');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
