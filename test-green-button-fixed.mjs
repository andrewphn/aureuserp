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

  console.log('3. Expanding global footer...');
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  await globalFooter.click();
  await page.waitForTimeout(1500);

  console.log('\n4. Checking Save button color NOW:');

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
      backgroundColor: styles.backgroundColor,
      color: styles.color,
      position: `x=${rect.left.toFixed(0)}, y=${rect.top.toFixed(0)}`,
      visible: rect.width > 0 && rect.height > 0 && styles.display !== 'none'
    };
  });

  console.log('   Text:', saveInfo.text);
  console.log('   Background:', saveInfo.backgroundColor);
  console.log('   Color:', saveInfo.color);
  console.log('   Position:', saveInfo.position);
  console.log('   Visible:', saveInfo.visible);

  if (saveInfo.backgroundColor.includes('34, 197, 94') || saveInfo.backgroundColor.includes('22c55e')) {
    console.log('\n   ✅ GREEN BACKGROUND CONFIRMED!');
  } else {
    console.log('\n   ❌ Background is NOT green:', saveInfo.backgroundColor);
  }

  console.log('\n5. Taking final screenshot...');
  await page.screenshot({
    path: 'green-button-fixed.png',
    fullPage: false
  });

  console.log('\n✅ COMPLETE');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
