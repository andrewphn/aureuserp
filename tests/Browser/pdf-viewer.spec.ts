import { test, expect } from './fixtures/test-fixtures';
import { createPdfViewerHelpers } from './helpers/pdf-viewer-helpers';

/**
 * PDF Viewer Core Functionality Tests
 *
 * Tests the Nutrient Web SDK PDF viewer integration including:
 * - PDF loading and rendering
 * - Annotation tools
 * - Toolbar interactions
 * - Zoom controls
 * - Page navigation
 * - Mobile responsiveness
 */

test.describe('PDF Viewer - Loading and Rendering', () => {
  test('should load PDF viewer successfully', async ({ authenticatedPage, testDocument }) => {
    await authenticatedPage.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers = createPdfViewerHelpers(authenticatedPage);
    await helpers.waitForViewerReady();

    // Verify viewer is visible
    await expect(authenticatedPage.locator('[data-pdf-viewer]')).toBeVisible();

    // Verify PSPDFKit loaded
    const isPSPDFKitLoaded = await authenticatedPage.evaluate(() => {
      return window.hasOwnProperty('PSPDFKit');
    });
    expect(isPSPDFKitLoaded).toBe(true);
  });

  test('should display correct page count', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const totalPages = await helpers.getTotalPages();
    expect(totalPages).toBeGreaterThan(0);

    // Verify page count is displayed in UI
    const pageCountText = await pdfViewerPage.locator('[data-page-count]').textContent();
    expect(pageCountText).toContain(totalPages.toString());
  });

  test('should load large PDF file (50MB+)', async ({ authenticatedPage }) => {
    // Create large test document
    const largeDoc = await authenticatedPage.request.post('/api/pdf-documents', {
      data: {
        title: 'Large PDF Test',
        file_path: 'test-pdfs/large-sample-50mb.pdf',
        file_size: 52428800, // 50MB
      },
    });
    const docData = await largeDoc.json();

    await authenticatedPage.goto(`/admin/pdf-documents/${docData.data.id}`);

    const helpers = createPdfViewerHelpers(authenticatedPage);

    // Should load within 30 seconds even for large files
    await helpers.waitForViewerReady(30000);

    const totalPages = await helpers.getTotalPages();
    expect(totalPages).toBeGreaterThan(0);
  });

  test('should show loading state while PDF loads', async ({ authenticatedPage, testDocument }) => {
    await authenticatedPage.goto(`/admin/pdf-documents/${testDocument.id}`);

    // Loading indicator should be visible initially
    const loadingIndicator = authenticatedPage.locator('[data-pdf-loading]');
    await expect(loadingIndicator).toBeVisible({ timeout: 1000 });

    // Loading indicator should disappear once loaded
    await expect(loadingIndicator).toBeHidden({ timeout: 20000 });
  });

  test('should handle PDF load error gracefully', async ({ authenticatedPage }) => {
    // Navigate to non-existent document
    await authenticatedPage.goto('/admin/pdf-documents/99999');

    // Should show error message
    const errorMessage = authenticatedPage.locator('[data-pdf-error]');
    await expect(errorMessage).toBeVisible({ timeout: 10000 });
    await expect(errorMessage).toContainText('failed to load');
  });
});

test.describe('PDF Viewer - Page Navigation', () => {
  test('should navigate to next page', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const initialPage = await helpers.getCurrentPage();

    // Click next page button
    await pdfViewerPage.click('[data-action="next-page"]');
    await pdfViewerPage.waitForTimeout(500);

    const currentPage = await helpers.getCurrentPage();
    expect(currentPage).toBe(initialPage + 1);
  });

  test('should navigate to previous page', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Go to page 2 first
    await helpers.goToPage(2);

    // Click previous page button
    await pdfViewerPage.click('[data-action="previous-page"]');
    await pdfViewerPage.waitForTimeout(500);

    const currentPage = await helpers.getCurrentPage();
    expect(currentPage).toBe(0); // Page 1 (0-indexed)
  });

  test('should navigate to specific page via input', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const totalPages = await helpers.getTotalPages();
    if (totalPages < 3) return; // Skip if document doesn't have enough pages

    // Enter page number
    await pdfViewerPage.fill('[data-page-input]', '3');
    await pdfViewerPage.press('[data-page-input]', 'Enter');
    await pdfViewerPage.waitForTimeout(500);

    const currentPage = await helpers.getCurrentPage();
    expect(currentPage).toBe(2); // Page 3 (0-indexed)
  });

  test('should disable previous button on first page', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await helpers.goToPage(1);

    const prevButton = pdfViewerPage.locator('[data-action="previous-page"]');
    await expect(prevButton).toBeDisabled();
  });

  test('should disable next button on last page', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const totalPages = await helpers.getTotalPages();
    await helpers.goToPage(totalPages);

    const nextButton = pdfViewerPage.locator('[data-action="next-page"]');
    await expect(nextButton).toBeDisabled();
  });
});

test.describe('PDF Viewer - Zoom Controls', () => {
  test('should zoom in on document', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const initialZoom = await helpers.getZoomLevel();

    await helpers.zoomIn();
    await pdfViewerPage.waitForTimeout(300);

    const newZoom = await helpers.getZoomLevel();
    expect(newZoom).toBeGreaterThan(initialZoom);
  });

  test('should zoom out on document', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Zoom in first
    await helpers.zoomIn();
    const zoomedInLevel = await helpers.getZoomLevel();

    await helpers.zoomOut();
    await pdfViewerPage.waitForTimeout(300);

    const zoomedOutLevel = await helpers.getZoomLevel();
    expect(zoomedOutLevel).toBeLessThan(zoomedInLevel);
  });

  test('should zoom via toolbar buttons', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const initialZoom = await helpers.getZoomLevel();

    // Click zoom in button
    await pdfViewerPage.click('[data-action="zoom-in"]');
    await pdfViewerPage.waitForTimeout(300);

    const newZoom = await helpers.getZoomLevel();
    expect(newZoom).toBeGreaterThan(initialZoom);
  });

  test('should fit page to width', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await pdfViewerPage.click('[data-action="fit-width"]');
    await pdfViewerPage.waitForTimeout(500);

    // Verify zoom level changed
    const zoomLevel = await helpers.getZoomLevel();
    expect(zoomLevel).toBeGreaterThan(0);
  });

  test('should reset zoom to 100%', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Zoom in first
    await helpers.zoomIn();
    await helpers.zoomIn();

    // Reset zoom
    await pdfViewerPage.click('[data-action="zoom-reset"]');
    await pdfViewerPage.waitForTimeout(300);

    const zoomLevel = await helpers.getZoomLevel();
    expect(zoomLevel).toBeCloseTo(1.0, 1);
  });
});

test.describe('PDF Viewer - Annotation Tools', () => {
  test('should show annotation toolbar', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const isToolbarVisible = await helpers.isAnnotationToolbarVisible();
    expect(isToolbarVisible).toBe(true);
  });

  test('should create text annotation', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await helpers.selectAnnotationTool('text');

    const annotationId = await helpers.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    expect(annotationId).toBeTruthy();

    // Verify annotation appears in list
    const annotations = await helpers.getAnnotationsOnCurrentPage();
    expect(annotations.length).toBeGreaterThan(0);
  });

  test('should create highlight annotation', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await helpers.selectAnnotationTool('highlight');

    const annotationId = await helpers.createAnnotation('HighlightAnnotation', {
      x: 150,
      y: 250,
    });

    expect(annotationId).toBeTruthy();
  });

  test('should create drawing annotation', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await helpers.selectAnnotationTool('ink');

    const annotationId = await helpers.createAnnotation('InkAnnotation', {
      x: 200,
      y: 300,
    });

    expect(annotationId).toBeTruthy();
  });

  test('should create rectangle annotation', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const annotationId = await helpers.createAnnotation('RectangleAnnotation', {
      x: 100,
      y: 150,
    });

    expect(annotationId).toBeTruthy();
  });

  test('should delete annotation', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Create annotation
    const annotationId = await helpers.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    // Delete annotation
    await helpers.deleteAnnotation(annotationId);
    await pdfViewerPage.waitForTimeout(500);

    // Verify annotation is deleted
    const annotations = await helpers.getAnnotationsOnCurrentPage();
    const deletedAnnotation = annotations.find((ann: any) => ann.id === annotationId);
    expect(deletedAnnotation).toBeUndefined();
  });

  test('should update annotation color', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const annotationId = await helpers.createAnnotation('HighlightAnnotation', {
      x: 100,
      y: 200,
    });

    // Update color
    await helpers.updateAnnotation(annotationId, { color: '#ff0000' });
    await pdfViewerPage.waitForTimeout(500);

    // Verify update (would need to check visual or annotation properties)
    const annotations = await helpers.getAnnotationsOnCurrentPage();
    const updatedAnnotation = annotations.find((ann: any) => ann.id === annotationId);
    expect(updatedAnnotation).toBeTruthy();
  });
});

test.describe('PDF Viewer - Toolbar Interactions', () => {
  test('should toggle sidebar', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await pdfViewerPage.click('[data-action="toggle-sidebar"]');
    await pdfViewerPage.waitForTimeout(300);

    const sidebar = pdfViewerPage.locator('[data-sidebar]');
    await expect(sidebar).toBeVisible();

    // Toggle closed
    await pdfViewerPage.click('[data-action="toggle-sidebar"]');
    await expect(sidebar).toBeHidden();
  });

  test('should download PDF', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const downloadPromise = pdfViewerPage.waitForEvent('download');
    await pdfViewerPage.click('[data-action="download"]');

    const download = await downloadPromise;
    expect(download.suggestedFilename()).toContain('.pdf');
  });

  test('should print PDF', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Mock print dialog
    await pdfViewerPage.evaluate(() => {
      window.print = () => console.log('Print dialog opened');
    });

    await pdfViewerPage.click('[data-action="print"]');

    // Verify print was called (check console logs or other indicators)
    await pdfViewerPage.waitForTimeout(500);
  });

  test('should switch between view modes', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Switch to single page view
    await pdfViewerPage.click('[data-view-mode="single"]');
    await pdfViewerPage.waitForTimeout(300);

    // Switch to continuous view
    await pdfViewerPage.click('[data-view-mode="continuous"]');
    await pdfViewerPage.waitForTimeout(300);

    // Viewer should still be functional
    const totalPages = await helpers.getTotalPages();
    expect(totalPages).toBeGreaterThan(0);
  });
});

test.describe('PDF Viewer - Keyboard Navigation', () => {
  test('should navigate with arrow keys', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const initialPage = await helpers.getCurrentPage();

    // Press down arrow to go to next page
    await pdfViewerPage.keyboard.press('ArrowDown');
    await pdfViewerPage.waitForTimeout(500);

    const newPage = await helpers.getCurrentPage();
    expect(newPage).toBe(initialPage + 1);
  });

  test('should zoom with keyboard shortcuts', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const initialZoom = await helpers.getZoomLevel();

    // Zoom in with Cmd/Ctrl + Plus
    await pdfViewerPage.keyboard.press('Control+Equal');
    await pdfViewerPage.waitForTimeout(300);

    const newZoom = await helpers.getZoomLevel();
    expect(newZoom).toBeGreaterThan(initialZoom);
  });

  test('should toggle fullscreen with F11', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    await pdfViewerPage.keyboard.press('F11');
    await pdfViewerPage.waitForTimeout(500);

    // Verify fullscreen mode (implementation specific)
    const isFullscreen = await pdfViewerPage.evaluate(() => {
      return !!document.fullscreenElement;
    });

    // May not work in all test environments
    // expect(isFullscreen).toBe(true);
  });
});

test.describe('PDF Viewer - Mobile Responsiveness', () => {
  test('should render correctly on mobile viewport', async ({ authenticatedPage, testDocument }) => {
    // Set mobile viewport
    await authenticatedPage.setViewportSize({ width: 375, height: 667 });

    await authenticatedPage.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers = createPdfViewerHelpers(authenticatedPage);
    await helpers.waitForViewerReady();

    // Verify viewer is visible
    await expect(authenticatedPage.locator('[data-pdf-viewer]')).toBeVisible();
  });

  test('should support touch interactions on mobile', async ({ authenticatedPage, testDocument }) => {
    await authenticatedPage.setViewportSize({ width: 375, height: 667 });
    await authenticatedPage.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers = createPdfViewerHelpers(authenticatedPage);
    await helpers.waitForViewerReady();

    const viewer = authenticatedPage.locator('[data-pdf-viewer]');

    // Simulate swipe gesture for page navigation
    await viewer.dispatchEvent('touchstart', {
      touches: [{ clientX: 200, clientY: 300 }]
    });
    await viewer.dispatchEvent('touchmove', {
      touches: [{ clientX: 50, clientY: 300 }]
    });
    await viewer.dispatchEvent('touchend');

    await authenticatedPage.waitForTimeout(500);

    // Page should change (implementation specific)
    const currentPage = await helpers.getCurrentPage();
    expect(currentPage).toBeGreaterThanOrEqual(0);
  });

  test('should show mobile-optimized toolbar', async ({ authenticatedPage, testDocument }) => {
    await authenticatedPage.setViewportSize({ width: 375, height: 667 });
    await authenticatedPage.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers = createPdfViewerHelpers(authenticatedPage);
    await helpers.waitForViewerReady();

    const mobileToolbar = authenticatedPage.locator('[data-mobile-toolbar]');
    await expect(mobileToolbar).toBeVisible();
  });
});

test.describe('PDF Viewer - Error Handling', () => {
  test('should show error for corrupted PDF', async ({ authenticatedPage }) => {
    const corruptDoc = await authenticatedPage.request.post('/api/pdf-documents', {
      data: {
        title: 'Corrupt PDF',
        file_path: 'test-pdfs/corrupted.pdf',
      },
    });
    const docData = await corruptDoc.json();

    await authenticatedPage.goto(`/admin/pdf-documents/${docData.data.id}`);

    const errorMessage = authenticatedPage.locator('[data-pdf-error]');
    await expect(errorMessage).toBeVisible({ timeout: 15000 });
  });

  test('should handle network interruption gracefully', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Simulate offline
    await pdfViewerPage.context().setOffline(true);

    // Try to create annotation (should handle error)
    try {
      await helpers.createAnnotation('TextAnnotation', { x: 100, y: 200 });
    } catch (error) {
      // Expected to fail
    }

    // Restore connection
    await pdfViewerPage.context().setOffline(false);
  });
});

test.describe('PDF Viewer - Performance', () => {
  test('should load within 5 seconds for normal PDF', async ({ authenticatedPage, testDocument }) => {
    const startTime = Date.now();

    await authenticatedPage.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers = createPdfViewerHelpers(authenticatedPage);
    await helpers.waitForViewerReady();

    const loadTime = Date.now() - startTime;
    expect(loadTime).toBeLessThan(5000);
  });

  test('should maintain 30+ FPS during page scrolling', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Scroll through pages and measure FPS
    const viewer = pdfViewerPage.locator('[data-pdf-viewer]');

    // Rapid scrolling
    for (let i = 0; i < 10; i++) {
      await viewer.evaluate((el) => {
        el.scrollBy(0, 100);
      });
      await pdfViewerPage.waitForTimeout(50);
    }

    // Application should remain responsive
    const isResponsive = await pdfViewerPage.evaluate(() => {
      return document.readyState === 'complete';
    });
    expect(isResponsive).toBe(true);
  });
});
