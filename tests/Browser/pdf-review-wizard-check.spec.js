import { test, expect } from '@playwright/test';

test('PDF Review Wizard - Check Restored State', async ({ page }) => {
  // Set viewport to 1440px desktop
  await page.setViewportSize({ width: 1440, height: 900 });

  // Navigate to login page first
  await page.goto('https://aureuserp.test/admin/login', { waitUntil: 'networkidle' });

  // Check if already logged in or need to login
  const isLoginPage = await page.locator('input[type="email"]').isVisible().catch(() => false);

  if (isLoginPage) {
    console.log('Logging in...');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  }

  // Navigate to PDF review wizard
  console.log('Navigating to PDF review wizard...');
  await page.goto('https://aureuserp.test/admin/project/projects/9/pdf-review?pdf=1', {
    waitUntil: 'networkidle',
    timeout: 60000
  });

  // Wait for page to fully load
  await page.waitForTimeout(3000);

  // Take full page screenshot
  await page.screenshot({
    path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-wizard-restored.png',
    fullPage: true
  });

  console.log('Screenshot saved!');

  // Check for wizard elements
  const wizardExists = await page.locator('text=1. Classify Pages').isVisible().catch(() => false);
  const pdfThumbnailsExist = await page.locator('canvas, img[src*="pdf"], .pdf-thumbnail').count().catch(() => 0);
  const pageMetadataExists = await page.locator('text=Page Type').isVisible().catch(() => false);
  const sidebarExists = await page.locator('text=PDF Document').isVisible().catch(() => false);

  console.log('=== PDF Review Wizard State ===');
  console.log('Wizard step bar visible:', wizardExists);
  console.log('PDF thumbnails count:', pdfThumbnailsExist);
  console.log('Page metadata fields visible:', pageMetadataExists);
  console.log('Sidebar visible:', sidebarExists);

  // Get page structure
  const bodyHTML = await page.locator('body').innerHTML();
  const hasCanvas = bodyHTML.includes('<canvas');
  const hasPDFJS = bodyHTML.includes('pdf.js') || bodyHTML.includes('pdfjs');

  console.log('Has canvas elements:', hasCanvas);
  console.log('Has PDF.js references:', hasPDFJS);
});
