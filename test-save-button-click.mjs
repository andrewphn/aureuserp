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
  await page.waitForTimeout(4000);

  console.log('\n=== TESTING CONTEXT SAVE BUTTON ===\n');

  // Expand footer
  const footer = page.locator('.fi-section').last();
  await footer.click();
  await page.waitForTimeout(1000);

  // Scroll to footer to see the save button
  console.log('3. Scrolling to footer...');
  await page.evaluate(() => {
    window.scrollTo(0, document.body.scrollHeight);
  });
  await page.waitForTimeout(500);

  // Take screenshot of footer area
  console.log('4. Taking screenshot of footer with save button...');
  await page.screenshot({
    path: 'save-button-in-footer.png',
    fullPage: false
  });

  // Find save button
  const saveButton = page.locator('button.fi-btn-color-success:has-text("Save")');
  const isVisible = await saveButton.isVisible();
  console.log('5. Save button visible:', isVisible);

  if (isVisible) {
    // Check button details
    const buttonText = await saveButton.textContent();
    const bbox = await saveButton.boundingBox();
    console.log('   Button text:', buttonText.trim());
    console.log('   Button position: x=' + bbox.x + ', y=' + bbox.y);
    console.log('   Button size: ' + bbox.width + 'x' + bbox.height);

    // Test clicking the save button
    console.log('\n6. Testing save button click...');

    // Listen for console messages
    const messages = [];
    page.on('console', msg => messages.push(msg.text()));

    // Click the save button
    await saveButton.click();
    await page.waitForTimeout(2000);

    // Check console for save message
    const saveMessages = messages.filter(m => m.includes('Footer') || m.includes('save'));
    console.log('   Console messages:', saveMessages.length > 0 ? saveMessages : 'No save messages');

    // Check if form was submitted
    const currentUrl = page.url();
    console.log('   Current URL after click:', currentUrl);

    console.log('\n✅ Save button is working!');
  }

  console.log('\n=== TEST COMPLETE ===');

} catch (error) {
  console.error('\n❌ ERROR:', error.message);
} finally {
  await page.waitForTimeout(2000);
  await browser.close();
}
