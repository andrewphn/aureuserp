import type { Page, Locator } from '@playwright/test';

/**
 * PDF Viewer Test Helpers
 *
 * Utility functions for interacting with the Nutrient Web SDK PDF viewer
 * in browser tests.
 */

export class PdfViewerHelpers {
  constructor(private page: Page) {}

  /**
   * Wait for PDF viewer to be fully loaded and ready
   */
  async waitForViewerReady(timeout: number = 20000): Promise<void> {
    // Wait for PSPDFKit global object
    await this.page.waitForFunction(
      () => window.hasOwnProperty('PSPDFKit'),
      { timeout }
    );

    // Wait for viewer container
    await this.page.waitForSelector('[data-pdf-viewer]', { timeout });

    // Wait for document to be loaded
    await this.page.waitForFunction(
      () => {
        const instance = (window as any).pdfViewerInstance;
        return instance && instance.totalPageCount > 0;
      },
      { timeout }
    );
  }

  /**
   * Get the current page number in the viewer
   */
  async getCurrentPage(): Promise<number> {
    return await this.page.evaluate(() => {
      const instance = (window as any).pdfViewerInstance;
      return instance ? instance.viewState.currentPageIndex : 0;
    });
  }

  /**
   * Navigate to a specific page in the PDF
   */
  async goToPage(pageNumber: number): Promise<void> {
    await this.page.evaluate((page) => {
      const instance = (window as any).pdfViewerInstance;
      if (instance) {
        instance.setViewState((state: any) => state.set('currentPageIndex', page - 1));
      }
    }, pageNumber);

    // Wait for page to render
    await this.page.waitForTimeout(500);
  }

  /**
   * Get total number of pages in the document
   */
  async getTotalPages(): Promise<number> {
    return await this.page.evaluate(() => {
      const instance = (window as any).pdfViewerInstance;
      return instance ? instance.totalPageCount : 0;
    });
  }

  /**
   * Create an annotation on the current page
   */
  async createAnnotation(annotationType: string, position: { x: number; y: number }): Promise<string> {
    return await this.page.evaluate(
      ({ type, pos }) => {
        const instance = (window as any).pdfViewerInstance;
        if (!instance) throw new Error('PDF viewer instance not found');

        const annotation = new (instance.Annotations[type])({
          pageIndex: instance.viewState.currentPageIndex,
          boundingBox: new instance.Geometry.Rect({
            left: pos.x,
            top: pos.y,
            width: 200,
            height: 100,
          }),
        });

        return instance.create(annotation).then((ann: any) => ann.id);
      },
      { type: annotationType, pos: position }
    );
  }

  /**
   * Get all annotations on the current page
   */
  async getAnnotationsOnCurrentPage(): Promise<any[]> {
    return await this.page.evaluate(() => {
      const instance = (window as any).pdfViewerInstance;
      if (!instance) return [];

      const currentPage = instance.viewState.currentPageIndex;
      return instance.getAnnotations(currentPage).then((annotations: any) =>
        annotations.toJS()
      );
    });
  }

  /**
   * Delete an annotation by ID
   */
  async deleteAnnotation(annotationId: string): Promise<void> {
    await this.page.evaluate((id) => {
      const instance = (window as any).pdfViewerInstance;
      if (instance) {
        instance.delete(id);
      }
    }, annotationId);
  }

  /**
   * Update an annotation
   */
  async updateAnnotation(annotationId: string, properties: Record<string, any>): Promise<void> {
    await this.page.evaluate(
      ({ id, props }) => {
        const instance = (window as any).pdfViewerInstance;
        if (!instance) return;

        instance.getAnnotations().then((annotations: any) => {
          const annotation = annotations.find((ann: any) => ann.id === id);
          if (annotation) {
            instance.update(annotation.set(props));
          }
        });
      },
      { id: annotationId, props: properties }
    );
  }

  /**
   * Get viewer toolbar locator
   */
  getToolbar(): Locator {
    return this.page.locator('[data-pdf-toolbar]');
  }

  /**
   * Click a toolbar button
   */
  async clickToolbarButton(buttonName: string): Promise<void> {
    await this.page.click(`[data-pdf-toolbar] [data-tool="${buttonName}"]`);
  }

  /**
   * Zoom in on the document
   */
  async zoomIn(): Promise<void> {
    await this.page.evaluate(() => {
      const instance = (window as any).pdfViewerInstance;
      if (instance) {
        instance.setViewState((state: any) =>
          state.set('zoom', state.zoom * 1.25)
        );
      }
    });
  }

  /**
   * Zoom out on the document
   */
  async zoomOut(): Promise<void> {
    await this.page.evaluate(() => {
      const instance = (window as any).pdfViewerInstance;
      if (instance) {
        instance.setViewState((state: any) =>
          state.set('zoom', state.zoom * 0.8)
        );
      }
    });
  }

  /**
   * Get current zoom level
   */
  async getZoomLevel(): Promise<number> {
    return await this.page.evaluate(() => {
      const instance = (window as any).pdfViewerInstance;
      return instance ? instance.viewState.zoom : 1;
    });
  }

  /**
   * Take a screenshot of the PDF viewer
   */
  async takeViewerScreenshot(path: string): Promise<void> {
    const viewerElement = await this.page.locator('[data-pdf-viewer]');
    await viewerElement.screenshot({ path });
  }

  /**
   * Check if annotation toolbar is visible
   */
  async isAnnotationToolbarVisible(): Promise<boolean> {
    return await this.page.isVisible('[data-annotation-toolbar]');
  }

  /**
   * Select annotation tool
   */
  async selectAnnotationTool(toolType: string): Promise<void> {
    await this.page.click(`[data-annotation-toolbar] [data-tool="${toolType}"]`);
  }

  /**
   * Wait for annotation to be created and synced
   */
  async waitForAnnotationSync(annotationId: string, timeout: number = 5000): Promise<void> {
    await this.page.waitForFunction(
      (id) => {
        const instance = (window as any).pdfViewerInstance;
        if (!instance) return false;

        return instance.getAnnotations().then((annotations: any) =>
          annotations.some((ann: any) => ann.id === id)
        );
      },
      annotationId,
      { timeout }
    );
  }
}

/**
 * Helper function to create PdfViewerHelpers instance
 */
export function createPdfViewerHelpers(page: Page): PdfViewerHelpers {
  return new PdfViewerHelpers(page);
}
