import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  const consoleErrors = [];
  const pageErrors = [];

  // Capture console errors
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  // Capture page errors
  page.on('pageerror', error => {
    pageErrors.push(error.message);
  });

  try {
    console.log('Step 1: Navigate to login page...');
    await page.goto('http://aureuserp.test/admin/login', { waitUntil: 'networkidle' });
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-step1-login.png', fullPage: true });

    console.log('Step 2: Fill in login credentials...');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');

    console.log('Step 3: Submit login form...');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]')
    ]);

    // Wait for successful login - should redirect away from login page
    await page.waitForTimeout(2000);
    console.log('After login URL:', page.url());
    await page.screenshot({ path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-step2-after-login.png', fullPage: true });

    console.log('Step 4: Navigate to PDF review page...');
    await page.goto('http://aureuserp.test/admin/project/projects/18/pdf-review?pdf=19', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Wait a bit for any dynamic content to load
    await page.waitForTimeout(2000);

    console.log('Step 5: Capture full page screenshot...');
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-page-broken.png',
      fullPage: true
    });

    // Check for visible error messages
    const errorElements = await page.locator('.error, .alert-error, [role="alert"]').all();
    const visibleErrors = [];
    for (const el of errorElements) {
      if (await el.isVisible()) {
        visibleErrors.push(await el.textContent());
      }
    }

    // Check for two-column layout
    const hasLeftColumn = await page.locator('.pdf-review-left, .left-column, [class*="left"]').count();
    const hasRightColumn = await page.locator('.pdf-review-right, .right-column, [class*="right"]').count();
    const gridLayout = await page.locator('[class*="grid-cols-2"], [class*="lg:grid-cols-2"]').count();

    // Get page title
    const title = await page.title();

    // Get current URL
    const currentUrl = page.url();

    // Report findings
    console.log('\n=== PDF REVIEW PAGE ANALYSIS ===\n');
    console.log('Current URL:', currentUrl);
    console.log('Page Title:', title);
    console.log('\n--- Layout Analysis ---');
    console.log('Left column elements found:', hasLeftColumn);
    console.log('Right column elements found:', hasRightColumn);
    console.log('Grid layout elements found:', gridLayout);
    console.log('\n--- Visible Errors ---');
    if (visibleErrors.length > 0) {
      visibleErrors.forEach((err, i) => console.log(`Error ${i + 1}: ${err}`));
    } else {
      console.log('No visible error messages found');
    }
    console.log('\n--- Console Errors ---');
    if (consoleErrors.length > 0) {
      consoleErrors.forEach((err, i) => console.log(`Console Error ${i + 1}: ${err}`));
    } else {
      console.log('No console errors detected');
    }
    console.log('\n--- Page Errors ---');
    if (pageErrors.length > 0) {
      pageErrors.forEach((err, i) => console.log(`Page Error ${i + 1}: ${err}`));
    } else {
      console.log('No page errors detected');
    }

  } catch (error) {
    console.error('Error during test:', error.message);
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-error-state.png',
      fullPage: true
    });
  } finally {
    await browser.close();
  }
})();
