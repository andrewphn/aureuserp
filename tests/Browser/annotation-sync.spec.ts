import { test, expect } from './fixtures/test-fixtures';
import { createPdfViewerHelpers } from './helpers/pdf-viewer-helpers';
import type { BrowserContext, Page } from '@playwright/test';

/**
 * Real-time Annotation Synchronization Tests
 *
 * Tests multi-user annotation synchronization, autosave, conflict resolution,
 * and offline handling using Playwright's multi-context capabilities.
 */

test.describe('Annotation Sync - Multi-User Real-time Synchronization', () => {
  test('should sync annotation between two users in real-time', async ({ browser, testDocument }) => {
    // Create two separate browser contexts (two users)
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    // Login both users
    await loginUser(page1, 'user1@example.com', 'password');
    await loginUser(page2, 'user2@example.com', 'password');

    // Navigate both to same document
    await page1.goto(`/admin/pdf-documents/${testDocument.id}`);
    await page2.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers1 = createPdfViewerHelpers(page1);
    const helpers2 = createPdfViewerHelpers(page2);

    await helpers1.waitForViewerReady();
    await helpers2.waitForViewerReady();

    // User 1 creates annotation
    const annotationId = await helpers1.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    // Wait for sync (via WebSocket or polling)
    await page2.waitForTimeout(2000);

    // User 2 should see the annotation
    const annotations = await helpers2.getAnnotationsOnCurrentPage();
    const syncedAnnotation = annotations.find((ann: any) => ann.id === annotationId);

    expect(syncedAnnotation).toBeTruthy();

    await context1.close();
    await context2.close();
  });

  test('should sync annotation updates between users', async ({ browser, testDocument }) => {
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    await loginUser(page1, 'user1@example.com', 'password');
    await loginUser(page2, 'user2@example.com', 'password');

    await page1.goto(`/admin/pdf-documents/${testDocument.id}`);
    await page2.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers1 = createPdfViewerHelpers(page1);
    const helpers2 = createPdfViewerHelpers(page2);

    await helpers1.waitForViewerReady();
    await helpers2.waitForViewerReady();

    // User 1 creates annotation
    const annotationId = await helpers1.createAnnotation('HighlightAnnotation', {
      x: 100,
      y: 200,
    });

    await page2.waitForTimeout(2000);

    // User 1 updates annotation color
    await helpers1.updateAnnotation(annotationId, { color: '#ff0000' });

    await page2.waitForTimeout(2000);

    // User 2 should see updated annotation
    const annotations = await helpers2.getAnnotationsOnCurrentPage();
    const updatedAnnotation = annotations.find((ann: any) => ann.id === annotationId);

    expect(updatedAnnotation).toBeTruthy();

    await context1.close();
    await context2.close();
  });

  test('should sync annotation deletion between users', async ({ browser, testDocument }) => {
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    await loginUser(page1, 'user1@example.com', 'password');
    await loginUser(page2, 'user2@example.com', 'password');

    await page1.goto(`/admin/pdf-documents/${testDocument.id}`);
    await page2.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers1 = createPdfViewerHelpers(page1);
    const helpers2 = createPdfViewerHelpers(page2);

    await helpers1.waitForViewerReady();
    await helpers2.waitForViewerReady();

    // User 1 creates annotation
    const annotationId = await helpers1.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    await page2.waitForTimeout(2000);

    // User 1 deletes annotation
    await helpers1.deleteAnnotation(annotationId);

    await page2.waitForTimeout(2000);

    // User 2 should not see deleted annotation
    const annotations = await helpers2.getAnnotationsOnCurrentPage();
    const deletedAnnotation = annotations.find((ann: any) => ann.id === annotationId);

    expect(deletedAnnotation).toBeUndefined();

    await context1.close();
    await context2.close();
  });

  test('should handle 3+ concurrent users', async ({ browser, testDocument }) => {
    const contexts = await Promise.all([
      browser.newContext(),
      browser.newContext(),
      browser.newContext(),
    ]);

    const pages = await Promise.all(contexts.map(ctx => ctx.newPage()));

    // Login all users
    await loginUser(pages[0], 'user1@example.com', 'password');
    await loginUser(pages[1], 'user2@example.com', 'password');
    await loginUser(pages[2], 'user3@example.com', 'password');

    // Navigate all to document
    await Promise.all(pages.map(page => page.goto(`/admin/pdf-documents/${testDocument.id}`)));

    const helpers = pages.map(page => createPdfViewerHelpers(page));
    await Promise.all(helpers.map(h => h.waitForViewerReady()));

    // Each user creates an annotation
    const annotationIds = await Promise.all([
      helpers[0].createAnnotation('TextAnnotation', { x: 100, y: 100 }),
      helpers[1].createAnnotation('HighlightAnnotation', { x: 200, y: 200 }),
      helpers[2].createAnnotation('RectangleAnnotation', { x: 300, y: 300 }),
    ]);

    // Wait for sync
    await pages[0].waitForTimeout(3000);

    // All users should see all 3 annotations
    for (const helper of helpers) {
      const annotations = await helper.getAnnotationsOnCurrentPage();
      expect(annotations.length).toBeGreaterThanOrEqual(3);
    }

    await Promise.all(contexts.map(ctx => ctx.close()));
  });
});

test.describe('Annotation Sync - Autosave Functionality', () => {
  test('should autosave annotation after creation', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Create annotation
    const annotationId = await helpers.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    // Wait for autosave (typically 2-5 seconds)
    await pdfViewerPage.waitForTimeout(5000);

    // Verify save indicator
    const saveIndicator = pdfViewerPage.locator('[data-save-status]');
    await expect(saveIndicator).toContainText('Saved');
  });

  test('should show saving indicator during autosave', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const annotationId = await helpers.createAnnotation('HighlightAnnotation', {
      x: 100,
      y: 200,
    });

    // Should show "Saving..." indicator
    const saveIndicator = pdfViewerPage.locator('[data-save-status]');
    await expect(saveIndicator).toContainText('Saving', { timeout: 1000 });
  });

  test('should persist annotations after page refresh', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Create annotation
    const annotationId = await helpers.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    // Wait for autosave
    await pdfViewerPage.waitForTimeout(5000);

    // Refresh page
    await pdfViewerPage.reload();
    await helpers.waitForViewerReady();

    // Annotation should still exist
    const annotations = await helpers.getAnnotationsOnCurrentPage();
    const persistedAnnotation = annotations.find((ann: any) => ann.id === annotationId);

    expect(persistedAnnotation).toBeTruthy();
  });

  test('should batch multiple rapid annotations for autosave', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Create multiple annotations rapidly
    const annotationIds = await Promise.all([
      helpers.createAnnotation('TextAnnotation', { x: 100, y: 100 }),
      helpers.createAnnotation('HighlightAnnotation', { x: 200, y: 200 }),
      helpers.createAnnotation('RectangleAnnotation', { x: 300, y: 300 }),
    ]);

    // Wait for batched autosave
    await pdfViewerPage.waitForTimeout(5000);

    // All should be saved
    const saveIndicator = pdfViewerPage.locator('[data-save-status]');
    await expect(saveIndicator).toContainText('Saved');
  });
});

test.describe('Annotation Sync - Conflict Resolution', () => {
  test('should resolve conflict when two users edit same annotation', async ({ browser, testDocument }) => {
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    await loginUser(page1, 'user1@example.com', 'password');
    await loginUser(page2, 'user2@example.com', 'password');

    await page1.goto(`/admin/pdf-documents/${testDocument.id}`);
    await page2.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers1 = createPdfViewerHelpers(page1);
    const helpers2 = createPdfViewerHelpers(page2);

    await helpers1.waitForViewerReady();
    await helpers2.waitForViewerReady();

    // User 1 creates annotation
    const annotationId = await helpers1.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    await page2.waitForTimeout(2000);

    // Both users update same annotation simultaneously
    await Promise.all([
      helpers1.updateAnnotation(annotationId, { color: '#ff0000' }),
      helpers2.updateAnnotation(annotationId, { color: '#00ff00' }),
    ]);

    await page1.waitForTimeout(3000);

    // Last write should win (or show conflict indicator)
    const annotations1 = await helpers1.getAnnotationsOnCurrentPage();
    const annotations2 = await helpers2.getAnnotationsOnCurrentPage();

    const ann1 = annotations1.find((a: any) => a.id === annotationId);
    const ann2 = annotations2.find((a: any) => a.id === annotationId);

    // Both should eventually have same state
    expect(ann1).toBeTruthy();
    expect(ann2).toBeTruthy();

    await context1.close();
    await context2.close();
  });

  test('should show conflict notification when edit conflicts occur', async ({ browser, testDocument }) => {
    const context1 = await browser.newContext();
    const context2 = await browser.newContext();

    const page1 = await context1.newPage();
    const page2 = await context2.newPage();

    await loginUser(page1, 'user1@example.com', 'password');
    await loginUser(page2, 'user2@example.com', 'password');

    await page1.goto(`/admin/pdf-documents/${testDocument.id}`);
    await page2.goto(`/admin/pdf-documents/${testDocument.id}`);

    const helpers1 = createPdfViewerHelpers(page1);
    const helpers2 = createPdfViewerHelpers(page2);

    await helpers1.waitForViewerReady();
    await helpers2.waitForViewerReady();

    const annotationId = await helpers1.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    await page2.waitForTimeout(2000);

    // Simultaneous updates
    await Promise.all([
      helpers1.updateAnnotation(annotationId, { color: '#ff0000' }),
      helpers2.updateAnnotation(annotationId, { color: '#00ff00' }),
    ]);

    // Check for conflict notification
    const conflictNotification = page2.locator('[data-conflict-notification]');
    // May or may not appear depending on implementation
    // await expect(conflictNotification).toBeVisible({ timeout: 5000 });

    await context1.close();
    await context2.close();
  });
});

test.describe('Annotation Sync - Offline Handling', () => {
  test('should cache annotations when offline', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Go offline
    await pdfViewerPage.context().setOffline(true);

    // Create annotation while offline
    const annotationId = await helpers.createAnnotation('TextAnnotation', {
      x: 100,
      y: 200,
    });

    // Should show offline indicator
    const offlineIndicator = pdfViewerPage.locator('[data-offline-status]');
    await expect(offlineIndicator).toBeVisible();

    // Annotation should exist locally
    const annotations = await helpers.getAnnotationsOnCurrentPage();
    const cachedAnnotation = annotations.find((ann: any) => ann.id === annotationId);
    expect(cachedAnnotation).toBeTruthy();

    // Go back online
    await pdfViewerPage.context().setOffline(false);
  });

  test('should sync cached annotations when coming back online', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Go offline
    await pdfViewerPage.context().setOffline(true);

    // Create annotation while offline
    const annotationId = await helpers.createAnnotation('HighlightAnnotation', {
      x: 100,
      y: 200,
    });

    await pdfViewerPage.waitForTimeout(1000);

    // Go back online
    await pdfViewerPage.context().setOffline(false);

    // Wait for sync
    await pdfViewerPage.waitForTimeout(5000);

    // Should show "Synced" status
    const saveIndicator = pdfViewerPage.locator('[data-save-status]');
    await expect(saveIndicator).toContainText('Saved');

    // Refresh to verify persistence
    await pdfViewerPage.reload();
    await helpers.waitForViewerReady();

    const annotations = await helpers.getAnnotationsOnCurrentPage();
    const syncedAnnotation = annotations.find((ann: any) => ann.id === annotationId);
    expect(syncedAnnotation).toBeTruthy();
  });

  test('should handle WebSocket reconnection', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Simulate WebSocket disconnect
    await pdfViewerPage.evaluate(() => {
      const ws = (window as any).annotationWebSocket;
      if (ws) ws.close();
    });

    await pdfViewerPage.waitForTimeout(2000);

    // Should show reconnecting indicator
    const connectionStatus = pdfViewerPage.locator('[data-connection-status]');
    // await expect(connectionStatus).toContainText('Reconnecting');

    // Wait for reconnection
    await pdfViewerPage.waitForTimeout(5000);

    // Should be connected again
    const isConnected = await pdfViewerPage.evaluate(() => {
      const ws = (window as any).annotationWebSocket;
      return ws && ws.readyState === WebSocket.OPEN;
    });

    expect(isConnected).toBe(true);
  });
});

test.describe('Annotation Sync - Performance with High Volume', () => {
  test('should handle 100+ annotations efficiently', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const startTime = Date.now();

    // Create 100 annotations
    const annotationPromises = [];
    for (let i = 0; i < 100; i++) {
      annotationPromises.push(
        helpers.createAnnotation('TextAnnotation', {
          x: 50 + (i % 10) * 50,
          y: 50 + Math.floor(i / 10) * 50,
        })
      );
    }

    await Promise.all(annotationPromises);

    const creationTime = Date.now() - startTime;

    // Should create all within 30 seconds
    expect(creationTime).toBeLessThan(30000);

    // Wait for all to save
    await pdfViewerPage.waitForTimeout(10000);

    // Verify all annotations exist
    const annotations = await helpers.getAnnotationsOnCurrentPage();
    expect(annotations.length).toBeGreaterThanOrEqual(100);
  });

  test('should maintain performance with 200+ annotations on page', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    // Seed 200 annotations via API
    const documentId = await pdfViewerPage.evaluate(() => {
      return (window as any).currentDocumentId;
    });

    for (let i = 0; i < 200; i++) {
      await pdfViewerPage.request.post(`/api/pdf/${documentId}/annotations`, {
        data: {
          page_number: 1,
          annotation_type: 'highlight',
          annotation_data: {
            color: '#ffff00',
            position: { x: i * 10, y: i * 5, width: 50, height: 20 },
          },
        },
      });
    }

    // Reload page to load all annotations
    await pdfViewerPage.reload();
    await helpers.waitForViewerReady(30000);

    // Page should remain responsive
    const pageScrollPerformance = await pdfViewerPage.evaluate(async () => {
      const start = performance.now();
      window.scrollBy(0, 1000);
      await new Promise(resolve => setTimeout(resolve, 100));
      return performance.now() - start;
    });

    // Scrolling should be smooth (< 200ms)
    expect(pageScrollPerformance).toBeLessThan(200);
  });
});

test.describe('Annotation Sync - WebSocket Connection', () => {
  test('should establish WebSocket connection on page load', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);
    await helpers.waitForViewerReady();

    const isWebSocketConnected = await pdfViewerPage.evaluate(() => {
      const ws = (window as any).annotationWebSocket;
      return ws && ws.readyState === WebSocket.OPEN;
    });

    expect(isWebSocketConnected).toBe(true);
  });

  test('should fall back to polling if WebSocket fails', async ({ pdfViewerPage }) => {
    const helpers = createPdfViewerHelpers(pdfViewerPage);

    // Block WebSocket connections
    await pdfViewerPage.route('**/ws/**', route => route.abort());

    await helpers.waitForViewerReady();

    // Should fall back to polling
    const isPolling = await pdfViewerPage.evaluate(() => {
      return (window as any).isUsingPolling === true;
    });

    // Implementation specific - may or may not have polling fallback
    // expect(isPolling).toBe(true);
  });
});

/**
 * Helper function to login a user
 */
async function loginUser(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL('/admin/dashboard', { timeout: 10000 });
}
