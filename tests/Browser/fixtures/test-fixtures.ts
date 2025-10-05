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
    // Navigate to login page
    await page.goto('/login');

    // Fill in login credentials
    await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL || 'test@example.com');
    await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD || 'password');

    // Submit login form
    await page.click('button[type="submit"]');

    // Wait for navigation to dashboard
    await page.waitForURL('/admin/dashboard', { timeout: 10000 });

    // Use the authenticated page
    await use(page);

    // Cleanup: Logout after test
    await page.goto('/logout');
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
   * Creates a test PDF document in the database and provides its details.
   * Automatically cleans up after the test.
   */
  testDocument: async ({ authenticatedPage }, use) => {
    // Create test document via API
    const response = await authenticatedPage.request.post('/api/pdf-documents', {
      data: {
        title: 'Test PDF Document',
        description: 'Automated test document',
        file_path: 'test-pdfs/sample.pdf',
        tags: ['test', 'automated'],
        is_public: true,
      },
    });

    const documentData = await response.json();
    const document = documentData.data;

    // Use the test document
    await use({
      id: document.id,
      title: document.attributes.title,
      filePath: document.attributes.file_path,
    });

    // Cleanup: Delete test document
    await authenticatedPage.request.delete(`/api/pdf-documents/${document.id}`);
  },
});

export { expect };
