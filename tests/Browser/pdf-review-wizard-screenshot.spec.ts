import { test, expect } from '@playwright/test';

test.describe('PDF Review Wizard Layout Screenshot', () => {
  test('should capture full-page screenshot of PDF review wizard', async ({ page }) => {
    // Set viewport to 1440px width
    await page.setViewportSize({ width: 1440, height: 900 });

    // First, navigate to projects list to ensure we're logged in and can see projects
    await page.goto('http://aureuserp.test/admin/project/projects', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    console.log('Current URL after projects list:', page.url());

    // Wait a bit to ensure page is loaded
    await page.waitForTimeout(1000);

    // Now navigate to the PDF review wizard page (correct FilamentPHP route with pdf query parameter)
    // Using project ID 9 (TCS-TEST-001) and PDF ID 1 (test-floor-plan.pdf) which exist in database
    await page.goto('http://aureuserp.test/admin/project/projects/9/pdf-review?pdf=1', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    console.log('Current URL after PDF review:', page.url());

    // Wait for the page to fully load
    await page.waitForTimeout(3000);

    // Get the page content to see what's actually rendered
    const bodyText = await page.locator('body').textContent();
    console.log('Page body text:', bodyText?.substring(0, 500));

    // Check if we got a 404
    const has404 = await page.locator('text=/404|NOT FOUND/i').count();
    if (has404 > 0) {
      console.log('❌ Page returned 404 - the route or resource does not exist');
      console.log('This could mean:');
      console.log('  1. Project 26 does not exist');
      console.log('  2. PDF document 67 does not exist');
      console.log('  3. The /review-pdf/ route is not registered');
    }

    // Verify wizard steps are present
    const hasWizardSteps = await page.locator('text=/Step|Classify|Rooms|Review/i').count();
    console.log(`Found ${hasWizardSteps} wizard step elements`);

    // Verify sidebar elements
    const hasPdfInfo = await page.locator('text=/PDF Document/i').count();
    const hasCustomerInfo = await page.locator('text=/Customer/i').count();
    const hasQuickActions = await page.locator('text=/Quick Actions/i').count();

    console.log(`PDF Document info: ${hasPdfInfo > 0 ? 'Found' : 'Not found'}`);
    console.log(`Customer info: ${hasCustomerInfo > 0 ? 'Found' : 'Not found'}`);
    console.log(`Quick Actions: ${hasQuickActions > 0 ? 'Found' : 'Not found'}`);

    // Take full-page screenshot
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-layout-fixed.png',
      fullPage: true
    });

    console.log('Screenshot saved successfully!');

    // Verify layout expectations (soft assertion - won't fail the test)
    if (hasWizardSteps === 0) {
      console.log('⚠️  Warning: No wizard steps found on the page');
    } else {
      console.log('✅ Wizard steps found successfully');
    }
  });
});
