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
  await page.waitForTimeout(4000);

  console.log('3. Expanding global footer...');
  const globalFooter = page.locator('[x-data*="contextFooterGlobal"]');
  await globalFooter.click();
  await page.waitForTimeout(1000);

  console.log('\n4. Checking button layout:');
  const buttons = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    const buttonContainer = footer.querySelector('.flex.flex-row');

    if (!buttonContainer) {
      return { error: 'Button container not found or still using flex-col' };
    }

    const containerRect = buttonContainer.getBoundingClientRect();
    const buttons = Array.from(buttonContainer.querySelectorAll('button, a')).map(btn => {
      const rect = btn.getBoundingClientRect();
      const styles = window.getComputedStyle(btn);
      return {
        text: btn.textContent.trim(),
        x: rect.left.toFixed(0),
        y: rect.top.toFixed(0),
        width: rect.width.toFixed(0),
        visible: styles.display !== 'none'
      };
    });

    return {
      containerClass: buttonContainer.className,
      containerY: containerRect.top.toFixed(0),
      buttons: buttons
    };
  });

  console.log('   Container class:', buttons.containerClass);
  console.log('   Container Y position:', buttons.containerY);
  console.log('\n   Buttons:');
  if (buttons.buttons) {
    buttons.buttons.forEach((btn, i) => {
      console.log(`     ${i + 1}. "${btn.text}" - x=${btn.x}, y=${btn.y}, visible=${btn.visible}`);
    });
  }

  console.log('\n5. Taking screenshot...');
  await page.screenshot({
    path: 'horizontal-buttons-layout.png',
    fullPage: false
  });

  console.log('\n✅ COMPLETE');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
