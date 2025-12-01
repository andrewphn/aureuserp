import { chromium } from 'playwright';

(async () => {
  let browser;
  try {
    browser = await chromium.launch({ 
      headless: false,
      executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
    });
    
    const context = await browser.newContext({
      viewport: { width: 1440, height: 900 }
    });
    
    const page = await context.newPage();

    console.log('\n=== MANUAL LOGIN REQUIRED ===');
    console.log('Opening browser...');
    console.log('Please login manually with:');
    console.log('  Email: info@tcswoodwork.com');
    console.log('  Password: Lola2024!');
    console.log('\nNavigating to PDF review page...');
    
    await page.goto('http://localhost:8000/admin/project/projects/9/pdf-review?pdf=1', {
      waitUntil: 'domcontentloaded',
      timeout: 60000
    });

    console.log('\nWaiting 30 seconds for you to login and page to load...');
    console.log('(The browser window should be open - please complete login if needed)\n');
    
    await page.waitForTimeout(30000);

    console.log('Taking screenshot...');
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-final.png',
      fullPage: true
    });
    console.log('\n✓ Screenshot saved!');

    const title = await page.title();
    console.log('\nPage Title:', title);

    const classifyPages = await page.locator('text=/Classify Pages/i').count();
    const roomsRuns = await page.locator('text=/Rooms.*Runs/i').count();
    const reviewQuote = await page.locator('text=/Review.*Quote/i').count();

    console.log('\n=== WIZARD STEPS ===');
    console.log('1. Classify Pages:', classifyPages > 0 ? '✓ FOUND' : '✗ NOT FOUND');
    console.log('2. Rooms & Runs:', roomsRuns > 0 ? '✓ FOUND' : '✗ NOT FOUND');
    console.log('3. Review & Quote:', reviewQuote > 0 ? '✓ FOUND' : '✗ NOT FOUND');

    const pdfDoc = await page.locator('text=/PDF Document/i').count();
    const entitySum = await page.locator('text=/Entity Summary/i').count();
    const customer = await page.locator('text=/Customer/i').count();
    const actions = await page.locator('text=/Quick Actions/i').count();

    console.log('\n=== SIDEBAR CARDS ===');
    console.log('- PDF Document:', pdfDoc > 0 ? '✓ FOUND' : '✗ NOT FOUND');
    console.log('- Entity Summary:', entitySum > 0 ? '✓ FOUND' : '✗ NOT FOUND');
    console.log('- Customer:', customer > 0 ? '✓ FOUND' : '✗ NOT FOUND');
    console.log('- Quick Actions:', actions > 0 ? '✓ FOUND' : '✗ NOT FOUND');

    const errors = await page.locator('.fi-notification-error, [role="alert"]').count();
    if (errors > 0) {
      console.log('\n⚠️  ERRORS DETECTED:', errors);
    } else {
      console.log('\n✓ No errors detected');
    }

    console.log('\n=== TEST COMPLETE ===');
    console.log('\nBrowser will close in 5 seconds...');
    await page.waitForTimeout(5000);

  } catch (error) {
    console.error('\n❌ ERROR:', error.message);
  } finally {
    if (browser) {
      await browser.close();
    }
  }
})();
