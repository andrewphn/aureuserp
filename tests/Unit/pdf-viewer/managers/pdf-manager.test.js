/**
 * PDF Manager Tests
 * Tests for PDF loading, rendering, and dimension extraction
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    preloadPdf,
    extractPdfDimensions,
    displayPdf,
    setupPageObserver,
    initializePdfSystem,
    reloadPdf,
    goToPage,
} from '@pdf-viewer/managers/pdf-manager.js';
import { createMockState, createMockRefs } from '../../mocks/pdf-viewer-mocks.js';

// Mock coordinate-transform module
vi.mock('@pdf-viewer/managers/coordinate-transform.js', () => ({
    syncOverlayToCanvas: vi.fn(),
}));

describe('PDF Manager', () => {
    let state;
    let refs;
    let mockPdfDocument;
    let mockPage;
    let mockViewport;

    beforeEach(() => {
        state = createMockState({
            pdfUrl: 'test.pdf',
            currentPage: 1,
            totalPages: 5,
            zoomLevel: 1.0,
            pageDimensions: null,
            canvasScale: 1.0,
            pdfReady: false,
            error: null,
            navigating: false,
            pdfPageId: null,
            pageMap: { 1: 123, 2: 124, 3: 125 },
            annotations: [],
            pageObserver: null,
            visiblePages: [],
        });

        // Mock viewport
        mockViewport = {
            width: 612,
            height: 792,
        };

        // Mock page
        mockPage = {
            getViewport: vi.fn(({ scale }) => ({
                width: mockViewport.width * scale,
                height: mockViewport.height * scale,
            })),
            render: vi.fn(() => ({
                promise: Promise.resolve(),
            })),
        };

        // Mock PDF document
        mockPdfDocument = {
            numPages: 5,
            fingerprint: 'test-fingerprint',
            getPage: vi.fn(() => Promise.resolve(mockPage)),
        };

        // Mock PDF.js library
        global.window.pdfjsLib = {
            getDocument: vi.fn((url) => ({
                promise: Promise.resolve(mockPdfDocument),
            })),
        };

        // Mock DOM refs
        const mockCanvas = document.createElement('canvas');
        mockCanvas.width = 612;
        mockCanvas.height = 792;
        mockCanvas.style.width = '100%';
        mockCanvas.style.height = 'auto';
        mockCanvas.getContext = vi.fn(() => ({
            drawImage: vi.fn(),
        }));

        refs = createMockRefs({
            pdfEmbed: {
                clientWidth: 800,
                clientHeight: 1000,
                innerHTML: '',
                appendChild: vi.fn(),
                querySelector: vi.fn((selector) => {
                    if (selector === 'canvas') return mockCanvas;
                    if (selector === 'iframe') return null;
                    return null;
                }),
                querySelectorAll: vi.fn(() => []),
            },
        });

        // Mock console methods
        vi.spyOn(console, 'log').mockImplementation(() => {});
        vi.spyOn(console, 'error').mockImplementation(() => {});
        vi.spyOn(console, 'warn').mockImplementation(() => {});

        // Mock document.createElement for canvas
        const mockCreateCanvas = () => {
            return {
                width: 612,
                height: 792,
                clientWidth: 800,
                clientHeight: 1000,
                style: {},
                getContext: vi.fn(() => ({
                    drawImage: vi.fn(),
                })),
            };
        };

        vi.spyOn(document, 'createElement').mockImplementation((tagName) => {
            if (tagName === 'canvas') {
                return mockCreateCanvas();
            }
            // Return a simple object for other elements
            return {
                tagName,
                style: {},
                appendChild: vi.fn(),
                innerHTML: '',
            };
        });

        // Mock IntersectionObserver
        global.IntersectionObserver = vi.fn(function (callback, options) {
            this.observe = vi.fn();
            this.disconnect = vi.fn();
            this.callback = callback;
            this.options = options;
        });
    });

    describe('preloadPdf', () => {
        it('should preload PDF document successfully', async () => {
            await preloadPdf(state);

            expect(window.pdfjsLib.getDocument).toHaveBeenCalledWith('test.pdf');
            expect(console.log).toHaveBeenCalledWith('📄 Preloading PDF document...');
            expect(console.log).toHaveBeenCalledWith(
                '✓ PDF document preloaded',
                expect.objectContaining({
                    numPages: 5,
                    fingerprint: 'test-fingerprint',
                })
            );
        });

        it('should throw error when PDF.js not loaded', async () => {
            delete window.pdfjsLib;

            await expect(preloadPdf(state)).rejects.toThrow('PDF.js library not loaded');
            expect(state.error).toContain('PDF.js library not loaded');
        });

        it('should handle PDF loading errors', async () => {
            window.pdfjsLib.getDocument = vi.fn(() => ({
                promise: Promise.reject(new Error('Failed to load')),
            }));

            await expect(preloadPdf(state)).rejects.toThrow('Failed to load');
            expect(state.error).toBe('Failed to load PDF: Failed to load');
            expect(console.error).toHaveBeenCalledWith('❌ Failed to preload PDF:', expect.any(Error));
        });

        it('should use WeakMap to store PDF document', async () => {
            await preloadPdf(state);

            // Document should be retrievable (tested in extractPdfDimensions)
            expect(window.pdfjsLib.getDocument).toHaveBeenCalled();
        });
    });

    describe('extractPdfDimensions', () => {
        beforeEach(async () => {
            // Preload PDF first
            await preloadPdf(state);
        });

        it('should extract PDF page dimensions', async () => {
            await extractPdfDimensions(state);

            expect(mockPdfDocument.getPage).toHaveBeenCalledWith(1);
            expect(mockPage.getViewport).toHaveBeenCalledWith({ scale: 1.0 });
            expect(state.pageDimensions).toEqual({
                width: 612,
                height: 792,
            });
            expect(console.log).toHaveBeenCalledWith('✓ Page dimensions: 612 × 792 pts');
        });

        it('should handle PDF document not preloaded', async () => {
            // Create new state without preloading
            const newState = createMockState({ currentPage: 1 });

            await extractPdfDimensions(newState);

            expect(console.error).toHaveBeenCalledWith('❌ PDF document not preloaded');
        });

        it('should use fallback dimensions on error', async () => {
            mockPdfDocument.getPage = vi.fn(() => Promise.reject(new Error('Page error')));

            await extractPdfDimensions(state);

            expect(state.pageDimensions).toEqual({
                width: 612,
                height: 792,
            });
            expect(console.warn).toHaveBeenCalledWith(
                '⚠️ PDF.js dimension extraction failed, using fallback method:',
                'Page error'
            );
            expect(console.log).toHaveBeenCalledWith('✓ Using fallback dimensions: 612 × 792 pts (letter size)');
            expect(state.error).toBeNull();
        });

        it('should extract dimensions for different pages', async () => {
            state.currentPage = 2;

            await extractPdfDimensions(state);

            expect(mockPdfDocument.getPage).toHaveBeenCalledWith(2);
        });
    });

    describe('displayPdf', () => {
        beforeEach(async () => {
            await preloadPdf(state);
        });

        it('should render PDF to canvas at 100% zoom', async () => {
            state.zoomLevel = 1.0;

            await displayPdf(state, refs);

            expect(mockPdfDocument.getPage).toHaveBeenCalledWith(1);
            expect(mockPage.render).toHaveBeenCalled();
            expect(state.pdfReady).toBe(true);
            expect(refs.pdfEmbed.appendChild).toHaveBeenCalled();
        });

        it('should render PDF with zoom applied', async () => {
            state.zoomLevel = 1.5;

            await displayPdf(state, refs);

            expect(mockPage.getViewport).toHaveBeenCalled();
            expect(state.canvasScale).toBeGreaterThan(0);
        });

        it('should preload PDF if not in WeakMap', async () => {
            // Create new state without preloading
            const newState = createMockState({
                pdfUrl: 'test.pdf',
                currentPage: 1,
                zoomLevel: 1.0,
            });

            await displayPdf(newState, refs);

            expect(window.pdfjsLib.getDocument).toHaveBeenCalled();
            expect(newState.pdfReady).toBe(true);
        });

        it('should throw error if preload fails', async () => {
            const newState = createMockState({
                pdfUrl: 'test.pdf',
                currentPage: 1,
            });

            window.pdfjsLib.getDocument = vi.fn(() => ({
                promise: Promise.reject(new Error('Load failed')),
            }));

            await expect(displayPdf(newState, refs)).rejects.toThrow();
        });

        it('should clear container before rendering', async () => {
            refs.pdfEmbed.innerHTML = 'old content';

            await displayPdf(state, refs);

            // appendChild is called after innerHTML is set to ''
            expect(refs.pdfEmbed.appendChild).toHaveBeenCalled();
        });

        it('should store canvas scale factor', async () => {
            await displayPdf(state, refs);

            expect(state.canvasScale).toBeGreaterThan(0);
            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('✓ Canvas scale factor:')
            );
        });

        it('should handle rendering errors', async () => {
            mockPage.render = vi.fn(() => ({
                promise: Promise.reject(new Error('Render failed')),
            }));

            await expect(displayPdf(state, refs)).rejects.toThrow('Render failed');
            expect(state.error).toBe('Failed to render PDF: Render failed');
            expect(console.error).toHaveBeenCalledWith('❌ Failed to render PDF:', expect.any(Error));
        });
    });

    describe('setupPageObserver', () => {
        let loadAnnotationsCallback;

        beforeEach(() => {
            loadAnnotationsCallback = vi.fn();
        });

        it('should create IntersectionObserver', () => {
            setupPageObserver(state, refs, loadAnnotationsCallback);

            expect(global.IntersectionObserver).toHaveBeenCalled();
        });

        it('should disconnect existing observer', () => {
            const existingObserver = {
                disconnect: vi.fn(),
            };
            state.pageObserver = existingObserver;

            setupPageObserver(state, refs, loadAnnotationsCallback);

            expect(existingObserver.disconnect).toHaveBeenCalled();
        });

        it('should handle missing pdfEmbed ref', () => {
            refs.pdfEmbed = null;

            expect(() => setupPageObserver(state, refs, loadAnnotationsCallback)).not.toThrow();
        });

        it('should observe page elements', () => {
            const mockPages = [
                { dataset: { pageNumber: '1' } },
                { dataset: { pageNumber: '2' } },
            ];
            refs.pdfEmbed.querySelectorAll = vi.fn(() => mockPages);

            setupPageObserver(state, refs, loadAnnotationsCallback);

            expect(refs.pdfEmbed.querySelectorAll).toHaveBeenCalledWith('.page');
        });

        it('should track visible pages when intersecting', () => {
            const mockPages = [{ dataset: { pageNumber: '1' } }];
            refs.pdfEmbed.querySelectorAll = vi.fn(() => mockPages);

            setupPageObserver(state, refs, loadAnnotationsCallback);

            // Simulate intersection
            const observerInstance = global.IntersectionObserver.mock.results[0].value;
            const entries = [
                {
                    isIntersecting: true,
                    target: { dataset: { pageNumber: '1' } },
                },
            ];
            observerInstance.callback(entries);

            expect(state.visiblePages).toContain(1);
            expect(console.log).toHaveBeenCalledWith('📄 Page visible:', 1);
            expect(loadAnnotationsCallback).toHaveBeenCalledWith(1);
        });

        it('should remove pages from visiblePages when not intersecting', () => {
            state.visiblePages = [1, 2];

            setupPageObserver(state, refs, loadAnnotationsCallback);

            const observerInstance = global.IntersectionObserver.mock.results[0].value;
            const entries = [
                {
                    isIntersecting: false,
                    target: { dataset: { pageNumber: '1' } },
                },
            ];
            observerInstance.callback(entries);

            expect(state.visiblePages).not.toContain(1);
            expect(console.log).toHaveBeenCalledWith('📄 Page hidden:', 1);
        });

        it('should not add duplicate pages to visiblePages', () => {
            state.visiblePages = [1];

            setupPageObserver(state, refs, loadAnnotationsCallback);

            const observerInstance = global.IntersectionObserver.mock.results[0].value;
            const entries = [
                {
                    isIntersecting: true,
                    target: { dataset: { pageNumber: '1' } },
                },
            ];
            observerInstance.callback(entries);

            expect(state.visiblePages.filter(p => p === 1)).toHaveLength(1);
        });
    });

    describe('initializePdfSystem', () => {
        it('should initialize PDF system successfully', async () => {
            const callbacks = {
                $nextTick: vi.fn(() => Promise.resolve()),
            };

            await initializePdfSystem(state, refs, callbacks);

            expect(window.pdfjsLib.getDocument).toHaveBeenCalled();
            expect(state.pageDimensions).toBeDefined();
            expect(state.pdfReady).toBe(true);
            expect(callbacks.$nextTick).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith('✓ PDF system initialized successfully');
        });

        it('should handle initialization without $nextTick callback', async () => {
            const callbacks = {};

            await initializePdfSystem(state, refs, callbacks);

            expect(state.pdfReady).toBe(true);
        });

        it('should handle initialization errors', async () => {
            window.pdfjsLib.getDocument = vi.fn(() => ({
                promise: Promise.reject(new Error('Init failed')),
            }));

            const callbacks = {};

            await expect(initializePdfSystem(state, refs, callbacks)).rejects.toThrow('Init failed');
            expect(state.error).toBe('Init failed');
            expect(console.error).toHaveBeenCalledWith(
                '❌ PDF system initialization failed:',
                expect.any(Error)
            );
        });

        it('should calculate canvas scale during initialization', async () => {
            const callbacks = {};

            await initializePdfSystem(state, refs, callbacks);

            expect(state.canvasScale).toBeGreaterThan(0);
            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('📐 Canvas scale:')
            );
        });
    });

    describe('reloadPdf', () => {
        beforeEach(async () => {
            await preloadPdf(state);
        });

        it('should reload PDF successfully', async () => {
            await reloadPdf(state, refs);

            expect(mockPdfDocument.getPage).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith('✓ PDF reloaded');
        });

        it('should recalculate scale on reload', async () => {
            state.canvasScale = 0;

            await reloadPdf(state, refs);

            expect(state.canvasScale).toBeGreaterThan(0);
        });

        it('should handle reload errors', async () => {
            mockPdfDocument.getPage = vi.fn(() => Promise.reject(new Error('Reload failed')));

            await reloadPdf(state, refs);

            expect(state.error).toBe('Reload failed');
            expect(console.error).toHaveBeenCalledWith('❌ PDF reload failed:', expect.any(Error));
        });
    });

    describe('goToPage', () => {
        let loadAnnotationsCallback;

        beforeEach(async () => {
            await preloadPdf(state);
            loadAnnotationsCallback = vi.fn(() => Promise.resolve());
        });

        it('should navigate to valid page', async () => {
            await goToPage(2, state, refs, loadAnnotationsCallback);

            expect(state.currentPage).toBe(2);
            expect(state.pdfPageId).toBe(124); // From pageMap
            expect(state.annotations).toEqual([]);
            expect(loadAnnotationsCallback).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith('✓ Navigated to page 2');
        });

        it('should warn on invalid page number (too low)', async () => {
            await goToPage(0, state, refs, loadAnnotationsCallback);

            expect(state.currentPage).toBe(1); // Unchanged
            expect(console.warn).toHaveBeenCalledWith('⚠️ Invalid page number: 0');
        });

        it('should warn on invalid page number (too high)', async () => {
            await goToPage(10, state, refs, loadAnnotationsCallback);

            expect(state.currentPage).toBe(1); // Unchanged
            expect(console.warn).toHaveBeenCalledWith('⚠️ Invalid page number: 10');
        });

        it('should prevent concurrent navigation', async () => {
            state.navigating = true;

            await goToPage(2, state, refs, loadAnnotationsCallback);

            expect(console.log).toHaveBeenCalledWith('⏸️ Navigation already in progress');
            expect(loadAnnotationsCallback).not.toHaveBeenCalled();
        });

        it('should clear navigating flag after navigation', async () => {
            await goToPage(2, state, refs, loadAnnotationsCallback);

            expect(state.navigating).toBe(false);
        });

        it('should clear navigating flag even on error', async () => {
            mockPdfDocument.getPage = vi.fn(() => Promise.reject(new Error('Nav failed')));

            await goToPage(2, state, refs, loadAnnotationsCallback);

            expect(state.navigating).toBe(false);
            expect(state.error).toBe('Nav failed');
        });

        it('should update pdfPageId from pageMap', async () => {
            await goToPage(3, state, refs, loadAnnotationsCallback);

            expect(state.pdfPageId).toBe(125);
            expect(console.log).toHaveBeenCalledWith('✓ Updated pdfPageId to 125');
        });

        it('should work without loadAnnotationsCallback', async () => {
            await goToPage(2, state, refs, null);

            expect(state.currentPage).toBe(2);
        });

        it('should clear annotations before loading new page', async () => {
            state.annotations = [{ id: 1 }, { id: 2 }];

            await goToPage(2, state, refs, loadAnnotationsCallback);

            expect(state.annotations).toEqual([]);
        });
    });

    describe('Edge Cases', () => {
        it('should handle multiple consecutive operations', async () => {
            await preloadPdf(state);
            await extractPdfDimensions(state);
            await displayPdf(state, refs);
            await reloadPdf(state, refs);

            expect(state.pdfReady).toBe(true);
            expect(state.pageDimensions).toBeDefined();
        });

        it('should handle canvas element creation', async () => {
            await preloadPdf(state);
            await displayPdf(state, refs);

            expect(document.createElement).toHaveBeenCalledWith('canvas');
        });

        it('should handle different zoom levels', async () => {
            await preloadPdf(state);

            // Test 50% zoom
            state.zoomLevel = 0.5;
            await displayPdf(state, refs);
            const scale1 = state.canvasScale;

            // Test 200% zoom
            state.zoomLevel = 2.0;
            await displayPdf(state, refs);
            const scale2 = state.canvasScale;

            expect(scale2).toBeGreaterThan(scale1);
        });
    });
});
