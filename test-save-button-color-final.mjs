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
  await page.waitForTimeout(5000); // Wait longer for page to fully load

  console.log('3. Expanding global footer...');
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  await globalFooter.click();
  await page.waitForTimeout(1500);

  console.log('\n4. Checking Save button details:');

  const saveButtonInfo = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    if (!footer) return { error: 'Footer not found' };

    // Find all buttons with "Save" text
    const saveButtons = Array.from(document.querySelectorAll('button')).filter(btn =>
      btn.textContent.includes('Save')
    );

    return saveButtons.map(btn => {
      const rect = btn.getBoundingClientRect();
      const styles = window.getComputedStyle(btn);
      const isInFooter = footer.contains(btn);

      return {
        text: btn.textContent.trim(),
        isInFooter: isInFooter,
        visible: styles.display !== 'none' && styles.visibility !== 'hidden' && styles.opacity !== '0',
        backgroundColor: styles.backgroundColor,
        color: styles.color,
        classes: btn.className,
        position: `x=${rect.left.toFixed(0)}, y=${rect.top.toFixed(0)}`,
        size: `${rect.width.toFixed(0)}x${rect.height.toFixed(0)}`,
        xShow: btn.getAttribute('x-show')
      };
    });
  });

  console.log('   Save buttons found:', saveButtonInfo.length);
  saveButtonInfo.forEach((btn, i) => {
    console.log(`\n   Button ${i + 1}:`);
    console.log(`     Text: "${btn.text}"`);
    console.log(`     In global footer: ${btn.isInFooter}`);
    console.log(`     Visible: ${btn.visible}`);
    console.log(`     Background: ${btn.backgroundColor}`);
    console.log(`     Color: ${btn.color}`);
    console.log(`     Position: ${btn.position}`);
    console.log(`     Size: ${btn.size}`);
    console.log(`     x-show: ${btn.xShow}`);
  });

  console.log('\n5. Taking annotated screenshot...');

  // Add colored borders to all footer buttons
  await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    const buttons = footer.querySelectorAll('button, a');

    buttons.forEach((btn, i) => {
      if (btn.textContent.includes('Save')) {
        btn.style.border = '5px solid red';
        btn.style.boxShadow = '0 0 20px red';
      } else if (btn.textContent.includes('Switch')) {
        btn.style.border = '5px solid blue';
      } else if (btn.textContent.includes('Clear')) {
        btn.style.border = '5px solid yellow';
      }
    });
  });

  await page.waitForTimeout(500);

  await page.screenshot({
    path: 'save-button-color-final.png',
    fullPage: false
  });

  console.log('\n✅ COMPLETE - Check save-button-color-final.png');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
