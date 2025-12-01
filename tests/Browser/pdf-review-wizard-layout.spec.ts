import { test } from '@playwright/test';

test('capture PDF review wizard layout', async ({ page }) => {
  // Set viewport to 1440px width
  await page.setViewportSize({ width: 1440, height: 900 });

  // Navigate directly to the PDF review page
  // Based on the route definition: slug='project/projects', route='/{record}/pdf-review?pdf={pdfId}'
  console.log('Navigating to PDF review page...');
  await page.goto('http://aureuserp.test/admin/project/projects/18/pdf-review?pdf=19', {
    waitUntil: 'domcontentloaded',
    timeout: 30000
  });

  // Wait for the page to settle and content to load
  console.log('Waiting for page to load...');
  await page.waitForTimeout(3000);

  // Take full page screenshot
  console.log('Taking screenshot...');
  await page.screenshot({
    path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-layout.png',
    fullPage: true
  });

  console.log('✅ Screenshot saved to .playwright-mcp/pdf-review-wizard-layout.png');

  // Log the page title and URL for debugging
  const title = await page.title();
  const url = page.url();
  console.log('Page title:', title);
  console.log('Current URL:', url);
});
