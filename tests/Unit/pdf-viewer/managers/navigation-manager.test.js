/**
 * Navigation Manager Tests
 * Tests for page navigation and pagination
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    nextPage,
    previousPage,
    goToPage,
    canGoNext,
    canGoPrevious,
} from '@pdf-viewer/managers/navigation-manager.js';
import { createMockState, createMockCallbacks } from '../../mocks/pdf-viewer-mocks.js';

describe('Navigation Manager', () => {
    let state, callbacks;

    beforeEach(() => {
        state = createMockState({
            currentPage: 2,
            totalPages: 5,
            pageMap: {
                1: 101,
                2: 102,
                3: 103,
                4: 104,
                5: 105,
            },
            navigating: false,
            annotations: [],
        });

        callbacks = createMockCallbacks({
            displayPdf: vi.fn().mockResolvedValue(undefined),
            loadAnnotations: vi.fn().mockResolvedValue(undefined),
            getFilteredPageNumbers: null, // No filtering by default
        });
    });

    describe('nextPage', () => {
        it('should navigate to next page', async () => {
            await nextPage(state, callbacks);

            expect(state.currentPage).toBe(3);
            expect(state.pdfPageId).toBe(103);
            expect(state.annotations).toEqual([]);
            expect(callbacks.displayPdf).toHaveBeenCalled();
            expect(callbacks.loadAnnotations).toHaveBeenCalled();
        });

        it('should not navigate if already on last page', async () => {
            state.currentPage = 5;
            await nextPage(state, callbacks);

            expect(state.currentPage).toBe(5);
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should set navigating flag during navigation', async () => {
            const displayPdfSpy = vi.fn(async () => {
                expect(state.navigating).toBe(true);
            });
            callbacks.displayPdf = displayPdfSpy;

            expect(state.navigating).toBe(false);
            await nextPage(state, callbacks);
            expect(state.navigating).toBe(false);
        });

        it('should prevent concurrent navigation', async () => {
            state.navigating = true;
            await nextPage(state, callbacks);

            expect(state.currentPage).toBe(2); // Should not change
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should work with filtered pages', async () => {
            callbacks.getFilteredPageNumbers = vi.fn(() => [1, 3, 5]); // Skip even pages
            state.currentPage = 3;

            await nextPage(state, callbacks);

            expect(state.currentPage).toBe(5);
            expect(callbacks.getFilteredPageNumbers).toHaveBeenCalled();
        });

        it('should handle empty filtered pages', async () => {
            callbacks.getFilteredPageNumbers = vi.fn(() => []);

            await nextPage(state, callbacks);

            expect(state.currentPage).toBe(2); // No change
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });
    });

    describe('previousPage', () => {
        it('should navigate to previous page', async () => {
            await previousPage(state, callbacks);

            expect(state.currentPage).toBe(1);
            expect(state.pdfPageId).toBe(101);
            expect(callbacks.displayPdf).toHaveBeenCalled();
            expect(callbacks.loadAnnotations).toHaveBeenCalled();
        });

        it('should not navigate if already on first page', async () => {
            state.currentPage = 1;
            await previousPage(state, callbacks);

            expect(state.currentPage).toBe(1);
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should set navigating flag during navigation', async () => {
            const displayPdfSpy = vi.fn(async () => {
                expect(state.navigating).toBe(true);
            });
            callbacks.displayPdf = displayPdfSpy;

            expect(state.navigating).toBe(false);
            await previousPage(state, callbacks);
            expect(state.navigating).toBe(false);
        });

        it('should prevent concurrent navigation', async () => {
            state.navigating = true;
            await previousPage(state, callbacks);

            expect(state.currentPage).toBe(2); // Should not change
        });

        it('should work with filtered pages', async () => {
            callbacks.getFilteredPageNumbers = vi.fn(() => [1, 3, 5]);
            state.currentPage = 5;

            await previousPage(state, callbacks);

            expect(state.currentPage).toBe(3);
        });
    });

    describe('goToPage', () => {
        it('should navigate to specific page', async () => {
            await goToPage(4, state, callbacks);

            expect(state.currentPage).toBe(4);
            expect(state.pdfPageId).toBe(104);
            expect(callbacks.displayPdf).toHaveBeenCalled();
            expect(callbacks.loadAnnotations).toHaveBeenCalled();
        });

        it('should not navigate to invalid page (too low)', async () => {
            await goToPage(0, state, callbacks);

            expect(state.currentPage).toBe(2); // No change
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should not navigate to invalid page (too high)', async () => {
            await goToPage(10, state, callbacks);

            expect(state.currentPage).toBe(2); // No change
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should prevent concurrent navigation', async () => {
            state.navigating = true;
            await goToPage(4, state, callbacks);

            expect(state.currentPage).toBe(2); // Should not change
        });

        it('should clear annotations on navigation', async () => {
            state.annotations = [{ id: 1 }, { id: 2 }];
            await goToPage(3, state, callbacks);

            expect(state.annotations).toEqual([]);
        });

        it('should handle missing pdfPageId in pageMap', async () => {
            delete state.pageMap[4];
            await goToPage(4, state, callbacks);

            expect(state.currentPage).toBe(4);
            // pdfPageId should not be updated
        });
    });

    describe('canGoNext', () => {
        it('should return true when not on last page', () => {
            state.currentPage = 2;
            expect(canGoNext(state, null)).toBe(true);
        });

        it('should return false when on last page', () => {
            state.currentPage = 5;
            expect(canGoNext(state, null)).toBe(false);
        });

        it('should work with filtered pages', () => {
            const getFilteredPages = () => [1, 3, 5];
            state.currentPage = 3;

            expect(canGoNext(state, getFilteredPages)).toBe(true);

            state.currentPage = 5;
            expect(canGoNext(state, getFilteredPages)).toBe(false);
        });

        it('should handle single page document', () => {
            state.totalPages = 1;
            state.currentPage = 1;

            expect(canGoNext(state, null)).toBe(false);
        });
    });

    describe('canGoPrevious', () => {
        it('should return true when not on first page', () => {
            state.currentPage = 2;
            expect(canGoPrevious(state, null)).toBe(true);
        });

        it('should return false when on first page', () => {
            state.currentPage = 1;
            expect(canGoPrevious(state, null)).toBe(false);
        });

        it('should work with filtered pages', () => {
            const getFilteredPages = () => [1, 3, 5];
            state.currentPage = 3;

            expect(canGoPrevious(state, getFilteredPages)).toBe(true);

            state.currentPage = 1;
            expect(canGoPrevious(state, getFilteredPages)).toBe(false);
        });

        it('should handle single page document', () => {
            state.totalPages = 1;
            state.currentPage = 1;

            expect(canGoPrevious(state, null)).toBe(false);
        });
    });

    describe('Edge Cases', () => {
        it('should handle navigation with missing callbacks', async () => {
            delete callbacks.displayPdf;
            delete callbacks.loadAnnotations;

            await expect(goToPage(3, state, callbacks)).resolves.not.toThrow();
            expect(state.currentPage).toBe(3);
        });

        it('should handle rapid navigation requests', async () => {
            const promises = [
                nextPage(state, callbacks),
                nextPage(state, callbacks),
                nextPage(state, callbacks),
            ];

            await Promise.all(promises);

            // Only one navigation should succeed
            expect(state.currentPage).toBe(3);
        });

        it('should reset navigating flag even if callbacks throw', async () => {
            callbacks.displayPdf = vi.fn().mockRejectedValue(new Error('Test error'));

            await expect(goToPage(3, state, callbacks)).rejects.toThrow('Test error');

            expect(state.navigating).toBe(false);
        });
    });
});
