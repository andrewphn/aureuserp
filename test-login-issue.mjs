import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const page = await context.newPage();

try {
  console.log('1. Navigating to login page...');
  await page.goto('http://aureuserp.test/admin/login');
  await page.waitForTimeout(2000);

  console.log('2. Checking login page elements...');
  const emailField = page.locator('input[type="email"]');
  const passwordField = page.locator('input[type="password"]');
  const submitButton = page.locator('button[type="submit"]');

  const emailExists = await emailField.count() > 0;
  const passwordExists = await passwordField.count() > 0;
  const submitExists = await submitButton.count() > 0;

  console.log('   Email field exists:', emailExists);
  console.log('   Password field exists:', passwordExists);
  console.log('   Submit button exists:', submitExists);

  if (!emailExists || !passwordExists || !submitExists) {
    console.log('\n❌ Login form elements missing!');
    await page.screenshot({ path: 'login-page-issue.png' });
    await browser.close();
    return;
  }

  console.log('\n3. Entering credentials...');
  await emailField.fill('info@tcswoodwork.com');
  await passwordField.fill('Lola2024!');

  console.log('4. Clicking submit...');

  // Listen for navigation
  const navigationPromise = page.waitForNavigation({ timeout: 10000 }).catch(() => null);

  await submitButton.click();

  console.log('5. Waiting for navigation...');
  await navigationPromise;

  await page.waitForTimeout(3000);

  const currentUrl = page.url();
  console.log('\n6. Current URL after login:', currentUrl);

  if (currentUrl.includes('/login')) {
    console.log('❌ Still on login page - login failed!');

    // Check for error messages
    const errorMessages = await page.locator('.text-danger, .text-red-600, [role="alert"]').allTextContents();
    if (errorMessages.length > 0) {
      console.log('   Error messages:', errorMessages);
    }

    // Check console errors
    const logs = [];
    page.on('console', msg => logs.push(msg.text()));
    await page.waitForTimeout(1000);

    const errors = logs.filter(log => log.toLowerCase().includes('error'));
    if (errors.length > 0) {
      console.log('   Console errors:', errors);
    }

    await page.screenshot({ path: 'login-failed.png', fullPage: true });
  } else {
    console.log('✅ Login successful!');
    console.log('   Redirected to:', currentUrl);
  }

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
  console.error(error.stack);
  await page.screenshot({ path: 'login-error.png', fullPage: true });
} finally {
  await page.waitForTimeout(3000);
  await browser.close();
}
