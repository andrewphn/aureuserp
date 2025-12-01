const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  try {
    console.log('Navigating to login page...');
    await page.goto('https://tcswoodwork.test/admin/login', { waitUntil: 'networkidle' });

    const currentUrl = page.url();
    if (!currentUrl.includes('/login')) {
      console.log('Already logged in');
    } else {
      console.log('Logging in...');
      await page.fill('input[type="email"]', 'info@tcswoodwork.com');
      await page.fill('input[type="password"]', 'Lola2024!');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin/**', { timeout: 10000 });
      console.log('Login successful');
    }

    console.log('Navigating to PDF review wizard...');
    await page.goto('https://tcswoodwork.test/admin/project/projects/9/pdf-review?pdf=1', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    await page.waitForTimeout(2000);

    const errors = await page.locator('.fi-notification-error, [role="alert"]').count();
    if (errors > 0) {
      console.log('WARNING: Found error notifications');
    }

    console.log('Taking screenshot...');
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-final.png',
      fullPage: true
    });

    const title = await page.title();
    console.log('Page title:', title);

    const classifyPages = await page.locator('text=/Classify Pages/i').count();
    const roomsRuns = await page.locator('text=/Rooms.*Runs/i').count();
    const reviewQuote = await page.locator('text=/Review.*Quote/i').count();

    console.log('\nWizard Steps:');
    console.log('Classify Pages:', classifyPages > 0);
    console.log('Rooms & Runs:', roomsRuns > 0);
    console.log('Review & Quote:', reviewQuote > 0);

    const pdfDoc = await page.locator('text=/PDF Document/i').count();
    const entitySum = await page.locator('text=/Entity Summary/i').count();
    const customer = await page.locator('text=/Customer/i').count();
    const actions = await page.locator('text=/Quick Actions/i').count();

    console.log('\nSidebar Cards:');
    console.log('PDF Document:', pdfDoc > 0);
    console.log('Entity Summary:', entitySum > 0);
    console.log('Customer:', customer > 0);
    console.log('Quick Actions:', actions > 0);

  } catch (error) {
    console.error('Error:', error.message);
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-error.png',
      fullPage: true
    });
  } finally {
    await browser.close();
  }
})();
