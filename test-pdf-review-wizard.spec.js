const { test } = require('@playwright/test');

test('capture PDF review wizard layout', async ({ page }) => {
  // Set viewport to 1440px width
  await page.setViewportSize({ width: 1440, height: 900 });

  // Navigate to the page
  await page.goto('https://tcswoodwork.test/admin/projects/projects/26/review-pdf/67');

  // Check if login is needed
  const needsLogin = await page.locator('input[type="email"]').isVisible().catch(() => false);

  if (needsLogin) {
    console.log('Login required, authenticating...');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');

    // Wait for navigation after login
    await page.waitForURL(/\/admin/, { timeout: 10000 });

    // Navigate to target page after login
    await page.goto('https://tcswoodwork.test/admin/projects/projects/26/review-pdf/67');
  }

  // Wait for the wizard to load
  await page.waitForSelector('[data-step], .wizard, [class*="wizard"]', { timeout: 10000 });

  // Wait a bit for dynamic content to load
  await page.waitForTimeout(2000);

  // Take full page screenshot
  await page.screenshot({
    path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-layout.png',
    fullPage: true
  });

  console.log('Screenshot saved to .playwright-mcp/pdf-review-wizard-layout.png');
});
