import { test as base, expect } from '@playwright/test';
import type { Page } from '@playwright/test';

/**
 * Test Fixtures for PDF Viewer Browser Tests
 *
 * Provides reusable fixtures for authentication, test data setup,
 * and common PDF viewer interactions.
 */

// Extended test fixtures
type TestFixtures = {
  authenticatedPage: Page;
  pdfViewerPage: Page;
  testDocument: {
    id: number;
    title: string;
    filePath: string;
  };
};

// Extend base test with custom fixtures
export const test = base.extend<TestFixtures>({
  /**
   * Fixture: Authenticated Page
   *
   * Provides a page instance that is already logged in as a test user.
   * Automatically handles login/logout flow.
   */
  authenticatedPage: async ({ page }, use) => {
    // Check if already authenticated (from global setup)
    await page.goto('/admin/dashboard');

    const currentUrl = page.url();

    // If redirected to login, authenticate
    if (currentUrl.includes('/login')) {
      // Fill in login credentials
      await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL || 'info@tcswoodwork.com');
      await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD || 'Lola2024!');

      // Submit login form
      await page.click('button[type="submit"]');

      // Wait for navigation to dashboard
      await page.waitForURL('/admin/dashboard', { timeout: 10000 });
    }

    // Use the authenticated page
    await use(page);

    // Cleanup: Browser context will be destroyed automatically by Playwright
    // No need to manually logout - this was causing GET /logout errors
  },

  /**
   * Fixture: PDF Viewer Page
   *
   * Provides a page instance navigated to a PDF document viewer.
   * Requires authentication.
   */
  pdfViewerPage: async ({ authenticatedPage }, use) => {
    // Navigate to PDF documents list
    await authenticatedPage.goto('/admin/pdf-documents');

    // Wait for table to load
    await authenticatedPage.waitForSelector('table', { timeout: 10000 });

    // Click the first document view action
    await authenticatedPage.click('table tbody tr:first-child [data-action="view"]');

    // Wait for PDF viewer to initialize
    await authenticatedPage.waitForSelector('[data-pdf-viewer]', { timeout: 15000 });

    // Wait for Nutrient SDK to load
    await authenticatedPage.waitForFunction(
      () => window.hasOwnProperty('PSPDFKit'),
      { timeout: 20000 }
    );

    // Use the PDF viewer page
    await use(authenticatedPage);
  },

  /**
   * Fixture: Test Document
   *
   * Uses existing PDF document from Project 9 for testing.
   * NOTE: Multi-user tests should navigate to the annotation viewer URL, not the document list.
   * The correct URL is: /admin/project/projects/{projectId}/annotate-v2/{pdfPageId}?pdf={pdfDocId}
   */
  testDocument: async ({ authenticatedPage }, use) => {
    // Use existing PDF document from Project 9
    // Maps to: Project 9, PDF Page 1, PDF Document 1
    const document = {
      id: '/admin/project/projects/9/annotate-v2/1?pdf=1', // Full annotation viewer URL
      title: 'Test PDF Document - Project 9 Page 1',
      filePath: '/storage/pdf-documents/01K823HKYN0NFEK8RVG8947083.pdf',
    };

    // Use the test document (no cleanup needed since we're using existing doc)
    await use(document);
  },
});

export { expect };
