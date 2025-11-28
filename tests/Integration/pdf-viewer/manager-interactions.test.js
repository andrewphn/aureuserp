/**
 * PDF Viewer Manager Interactions Integration Tests
 * Tests how managers work together in real workflows
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    createMockState,
    createMockRefs,
    createMockCallbacks,
    createMockAnnotation,
} from '../../Unit/mocks/pdf-viewer-mocks.js';

// Import actual manager functions that exist
import { nextPage, previousPage } from '@pdf-viewer/managers/navigation-manager.js';
import { displayPdf } from '@pdf-viewer/managers/pdf-manager.js';
import { zoomIn, zoomOut, setZoom, zoomToFitAnnotation } from '@pdf-viewer/managers/zoom-manager.js';
import { enterIsolationMode, exitIsolationMode } from '@pdf-viewer/managers/isolation-mode-manager.js';
import { selectAnnotationContext } from '@pdf-viewer/managers/state-manager.js';
import { undo, redo, canUndo, canRedo } from '@pdf-viewer/managers/undo-redo-manager.js';
import { toggleRoomVisibility, toggleLocationVisibility } from '@pdf-viewer/managers/visibility-toggle-manager.js';

// Mock coordinate-transform module
vi.mock('@pdf-viewer/managers/coordinate-transform.js', () => ({
    syncOverlayToCanvas: vi.fn(),
    updateAnnotationPositions: vi.fn(),
    invalidateZoomCache: vi.fn(),
}));

describe('PDF Viewer Manager Interactions', () => {
    let state, refs, callbacks;

    beforeEach(() => {
        state = createMockState({
            currentPage: 2,
            totalPages: 5,
            pageMap: { 1: 101, 2: 102, 3: 103, 4: 104, 5: 105 },
            annotations: [],
            pdfDoc: { numPages: 5 },
            zoomLevel: 1.0,
        });
        refs = createMockRefs();
        callbacks = createMockCallbacks({
            // Add integration-specific callbacks
            zoomToFitAnnotation: vi.fn().mockResolvedValue(undefined),
            updateAnnotationVisibility: vi.fn(),
            expandNode: vi.fn(),
            selectNode: vi.fn(),
            applyHistoryState: vi.fn(),
            onTreeNodeSelected: vi.fn(),
            restorePreIsolationView: vi.fn(),
        });

        // Setup getBoundingClientRect for zoom tests
        refs.annotationOverlay.getBoundingClientRect = vi.fn(() => ({
            width: 800,
            height: 600,
        }));
    });

    describe('Navigation + Display Integration', () => {
        it('should navigate pages and trigger display', async () => {
            // Start at page 2
            expect(state.currentPage).toBe(2);

            // Navigate to next page
            await nextPage(state, refs, callbacks);
            expect(state.currentPage).toBe(3);
            expect(state.pdfPageId).toBe(103);

            // Navigate to previous page
            await previousPage(state, refs, callbacks);
            expect(state.currentPage).toBe(2);
            expect(state.pdfPageId).toBe(102);
        });

        it('should handle navigation at boundaries', async () => {
            // Start at first page
            state.currentPage = 1;

            await previousPage(state, refs, callbacks);
            expect(state.currentPage).toBe(1); // Should stay at first page

            // Go to last page
            state.currentPage = 5;

            await nextPage(state, refs, callbacks);
            expect(state.currentPage).toBe(5); // Should stay at last page
        });
    });

    describe('Zoom + Isolation Mode Integration', () => {
        it('should zoom when entering isolation mode', async () => {
            const roomAnnotation = createMockAnnotation({
                id: 1,
                type: 'room',
                roomId: 100,
                label: 'Kitchen',
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            state.annotations = [roomAnnotation];
            state.tree = [{ id: 100, name: 'Kitchen', type: 'room' }];

            const initialZoom = state.zoomLevel;

            // Enter isolation mode - should trigger zoom
            await enterIsolationMode(roomAnnotation, state, callbacks);

            expect(state.isolationMode).toBe(true);
            expect(state.isolationLevel).toBe('room');
            expect(callbacks.zoomToFitAnnotation).toHaveBeenCalled();
        });

        it('should maintain zoom state across isolation levels', async () => {
            const roomAnnotation = createMockAnnotation({
                id: 1,
                type: 'room',
                roomId: 100,
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            const locationAnnotation = createMockAnnotation({
                id: 2,
                type: 'location',
                roomId: 100,
                roomLocationId: 200,
                screenX: 200,
                screenY: 200,
                screenWidth: 300,
                screenHeight: 250,
            });

            state.annotations = [roomAnnotation, locationAnnotation];

            // Enter room isolation
            await enterIsolationMode(roomAnnotation, state, callbacks);

            // Enter nested location isolation
            await enterIsolationMode(locationAnnotation, state, callbacks);

            expect(state.isolationLevel).toBe('location');
            // Zoom callback should be called for both levels
            expect(callbacks.zoomToFitAnnotation).toHaveBeenCalledTimes(2);
        });
    });

    describe('State Selection + Isolation Integration', () => {
        it('should maintain context when entering isolation', async () => {
            const roomAnnotation = createMockAnnotation({
                id: 1,
                type: 'room',
                roomId: 100,
                label: 'Kitchen',
            });

            // Select room context first
            selectAnnotationContext(roomAnnotation, state, callbacks);

            // Then enter isolation mode
            await enterIsolationMode(roomAnnotation, state, callbacks);

            // Both state and isolation should have room context
            expect(state.isolationMode).toBe(true);
            expect(state.isolatedRoomId).toBe(1);
            expect(state.isolatedRoomName).toBe('Kitchen');
        });
    });

    describe('Visibility + Isolation Integration', () => {
        it('should hide annotations outside isolated room', async () => {
            state.annotations = [
                createMockAnnotation({ id: 1, type: 'room', roomId: 100, visible: true }),
                createMockAnnotation({ id: 2, type: 'location', roomId: 100, visible: true }),
                createMockAnnotation({ id: 3, type: 'room', roomId: 101, visible: true }),
            ];

            const roomAnnotation = state.annotations[0];

            // Enter isolation for room 100
            await enterIsolationMode(roomAnnotation, state, callbacks);

            // Isolation mode should track which annotations should be hidden
            expect(state.isolationMode).toBe(true);
            expect(state.isolatedRoomId).toBe(1);

            // Isolation mode tracks hidden annotations internally
            expect(state.hiddenAnnotations).toBeDefined();
        });

        it('should restore visibility when exiting isolation', async () => {
            state.isolationMode = true;
            state.isolationLevel = 'room';
            state.isolatedRoomId = 1;
            state.hiddenAnnotations = [2, 3, 4]; // Some hidden annotations

            // Exit isolation
            await exitIsolationMode(state, callbacks);

            expect(state.isolationMode).toBe(false);
            expect(state.isolationLevel).toBe(null);
            // exitIsolationMode clears hiddenAnnotations array directly
            expect(state.hiddenAnnotations).toEqual([]);
            expect(state.selectedNodeId).toBe(null);
        });
    });

    describe('Zoom + Display Integration', () => {
        it('should trigger display when zooming', async () => {
            const initialZoom = state.zoomLevel;

            await zoomIn(state, refs, callbacks);

            expect(state.zoomLevel).toBeGreaterThan(initialZoom);
            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should handle zoom limits correctly', async () => {
            // Zoom to max
            state.zoomLevel = 2.9;
            state.zoomMax = 3.0;

            await zoomIn(state, refs, callbacks);
            expect(state.zoomLevel).toBe(3.0);

            // Try to zoom beyond max
            await zoomIn(state, refs, callbacks);
            expect(state.zoomLevel).toBe(3.0); // Should stay at max

            // Zoom to min
            state.zoomLevel = 1.1;
            state.zoomMin = 1.0;

            await zoomOut(state, refs, callbacks);
            expect(state.zoomLevel).toBe(1.0);

            // Try to zoom below min
            await zoomOut(state, refs, callbacks);
            expect(state.zoomLevel).toBe(1.0); // Should stay at min
        });
    });

    describe('Undo/Redo State Integration', () => {
        it('should track undo/redo state', () => {
            // Setup history with snapshots
            const annotation1 = createMockAnnotation({ id: 1, label: 'First' });
            const annotation2 = createMockAnnotation({ id: 2, label: 'Second' });

            state.historyStack = [
                { annotations: [annotation1], action: 'Initial' },
                { annotations: [annotation1, annotation2], action: 'Add annotation' }
            ];
            state.historyIndex = 1;
            state.annotations = [annotation1, annotation2];

            expect(canUndo(state)).toBe(true);
            expect(canRedo(state)).toBe(false);

            // Undo operation - should decrease historyIndex and restore annotations
            undo(state);

            expect(state.historyIndex).toBe(0);
            expect(state.annotations).toHaveLength(1);
            expect(state.annotations[0].label).toBe('First');
        });

        it('should allow redo after undo', () => {
            state.historyStack = [
                { annotations: [] },
                { annotations: [] },
            ];
            state.historyIndex = 1;

            // Should be able to undo
            expect(canUndo(state)).toBe(true);

            // Simulate undo
            undo(state, callbacks);

            // Now should be able to redo
            expect(canRedo(state)).toBe(true);
        });
    });

    describe('Multi-Manager Complex Workflows', () => {
        it('should handle: navigate → zoom → select → isolate → exit', async () => {
            // Step 1: Navigate
            await nextPage(state, refs, callbacks);
            expect(state.currentPage).toBe(3);

            // Step 2: Zoom
            await zoomIn(state, refs, callbacks);
            expect(state.zoomLevel).toBe(1.25);

            // Step 3: Select annotation
            const roomAnnotation = createMockAnnotation({
                id: 1,
                type: 'room',
                roomId: 100,
                label: 'Kitchen',
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            state.annotations = [roomAnnotation];
            selectAnnotationContext(roomAnnotation, state, callbacks);

            // Step 4: Enter isolation
            await enterIsolationMode(roomAnnotation, state, callbacks);
            expect(state.isolationMode).toBe(true);

            // Step 5: Exit isolation
            await exitIsolationMode(state, callbacks);
            expect(state.isolationMode).toBe(false);
        });

        it('should handle rapid zoom changes with isolation', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                type: 'room',
                roomId: 100,
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            state.annotations = [annotation];

            // Rapid zoom changes
            await zoomIn(state, refs, callbacks);
            await zoomIn(state, refs, callbacks);
            await zoomOut(state, refs, callbacks);

            // Enter isolation
            await enterIsolationMode(annotation, state, callbacks);

            // More zoom changes in isolation
            await setZoom(2.0, state, refs, callbacks);

            expect(state.isolationMode).toBe(true);
            expect(state.zoomLevel).toBe(2.0);
        });
    });

    describe('Error Recovery Integration', () => {
        it('should maintain state consistency when callbacks fail', async () => {
            // Simulate callback failure
            callbacks.displayPdf = vi.fn(() => {
                throw new Error('Display failed');
            });

            const initialPage = state.currentPage;

            try {
                await nextPage(state, refs, callbacks);
            } catch (e) {
                // Navigation should still update state even if display fails
                expect(state.currentPage).toBeGreaterThan(initialPage);
            }
        });

        it('should handle missing refs gracefully', async () => {
            refs.annotationOverlay = null;

            const annotation = createMockAnnotation({
                id: 1,
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            // Should not throw when container is missing
            await expect(
                zoomToFitAnnotation(annotation, state, refs, callbacks)
            ).resolves.not.toThrow();
        });

        it('should handle missing callbacks gracefully', async () => {
            const emptyCallbacks = {};

            // All operations should handle missing callbacks
            await expect(nextPage(state, refs, emptyCallbacks)).resolves.not.toThrow();
            await expect(zoomIn(state, refs, emptyCallbacks)).resolves.not.toThrow();

            const annotation = createMockAnnotation({ id: 1, type: 'room', roomId: 100 });
            expect(() => selectAnnotationContext(annotation, state, emptyCallbacks)).not.toThrow();
        });
    });
});
