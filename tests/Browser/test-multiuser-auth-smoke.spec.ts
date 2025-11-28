import { test, expect } from '@playwright/test';

/**
 * Simple smoke test to verify multi-user authentication fixes
 * Tests that both test users can login and access the PDF viewer
 */

test.describe('Multi-User Authentication Smoke Test', () => {
  test('should authenticate both test users and load PDF viewer', async ({ browser }) => {
    console.log('🧪 Starting multi-user authentication smoke test...');

    // Create two separate browser contexts (simulating two users)
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    try {
      // User 1: Login
      console.log('👤 User 1: Logging in as info@tcswoodwork.com...');
      await page1.goto('/admin/login');
      await page1.fill('input[type="email"]', 'info@tcswoodwork.com');
      await page1.fill('input[type="password"]', 'Lola2024!');
      await page1.click('button[type="submit"]');
      await page1.waitForURL('**/admin/**', { timeout: 10000 });
      console.log('✅ User 1: Login successful');

      // User 2: Login
      console.log('👤 User 2: Logging in as info@andrewphan.com...');
      await page2.goto('/admin/login');
      await page2.fill('input[type="email"]', 'info@andrewphan.com');
      await page2.fill('input[type="password"]', 'Lola2024!');
      await page2.click('button[type="submit"]');
      await page2.waitForURL('**/admin/**', { timeout: 10000 });
      console.log('✅ User 2: Login successful');

      // Both users: Navigate to PDF viewer
      const pdfViewerUrl = '/admin/project/projects/9/annotate-v2/1?pdf=1';

      console.log('📄 User 1: Navigating to PDF viewer...');
      await page1.goto(pdfViewerUrl, { waitUntil: 'domcontentloaded' });

      // Check if we got redirected (403/permission error)
      if (page1.url().includes('/login') || page1.url().includes('/403')) {
        console.log(`⚠️  User 1 redirected to: ${page1.url()}`);
      }

      console.log('📄 User 2: Navigating to PDF viewer...');
      await page2.goto(pdfViewerUrl);
      await page2.waitForLoadState('domcontentloaded');

      // Verify both pages loaded
      const url1 = page1.url();
      const url2 = page2.url();

      console.log(`✅ User 1 URL: ${url1}`);
      console.log(`✅ User 2 URL: ${url2}`);

      expect(url1).toContain('annotate-v2');
      expect(url2).toContain('annotate-v2');

      // Verify Alpine.js loaded on both pages
      const alpine1 = await page1.evaluate(() => typeof window.Alpine !== 'undefined');
      const alpine2 = await page2.evaluate(() => typeof window.Alpine !== 'undefined');

      console.log(`✅ User 1 Alpine.js loaded: ${alpine1}`);
      console.log(`✅ User 2 Alpine.js loaded: ${alpine2}`);

      expect(alpine1).toBe(true);
      expect(alpine2).toBe(true);

      console.log('🎉 Multi-user authentication smoke test PASSED!');

    } finally {
      await context1.close();
      await context2.close();
    }
  });
});
