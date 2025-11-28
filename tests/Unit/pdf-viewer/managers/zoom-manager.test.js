/**
 * Zoom Manager Tests
 * Tests for zoom controls and viewport management
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    zoomIn,
    zoomOut,
    resetZoom,
    setZoom,
    getZoomPercentage,
    isAtMinZoom,
    isAtMaxZoom,
    zoomToFitAnnotation,
} from '@pdf-viewer/managers/zoom-manager.js';
import {
    createMockState,
    createMockRefs,
    createMockCallbacks,
    createMockAnnotation,
    flushPromises,
} from '../../mocks/pdf-viewer-mocks.js';

// Mock coordinate-transform module
vi.mock('@pdf-viewer/managers/coordinate-transform.js', () => ({
    syncOverlayToCanvas: vi.fn(),
    updateAnnotationPositions: vi.fn(),
    invalidateZoomCache: vi.fn(),
}));

describe('Zoom Manager', () => {
    let state, refs, callbacks;

    beforeEach(() => {
        state = createMockState({
            zoomLevel: 1.0,
            zoomMin: 1.0,
            zoomMax: 3.0,
        });
        refs = createMockRefs();
        callbacks = createMockCallbacks();
    });

    describe('zoomIn', () => {
        it('should increase zoom by 0.25', async () => {
            await zoomIn(state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.25);
            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should not exceed maximum zoom', async () => {
            state.zoomLevel = 2.9;
            await zoomIn(state, refs, callbacks);

            expect(state.zoomLevel).toBe(3.0); // Max limit
        });

        it('should call displayPdf callback', async () => {
            await zoomIn(state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalledTimes(1);
        });

        it('should call $nextTick callback multiple times', async () => {
            await zoomIn(state, refs, callbacks);

            expect(callbacks.$nextTick).toHaveBeenCalled();
        });
    });

    describe('zoomOut', () => {
        it('should decrease zoom by 0.25', async () => {
            state.zoomLevel = 1.5;
            await zoomOut(state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.25);
        });

        it('should not go below minimum zoom', async () => {
            state.zoomLevel = 1.1;
            await zoomOut(state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.0); // Min limit
        });

        it('should call displayPdf callback', async () => {
            state.zoomLevel = 1.5;
            await zoomOut(state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });
    });

    describe('resetZoom', () => {
        it('should reset zoom to 1.0', async () => {
            state.zoomLevel = 2.5;
            await resetZoom(state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.0);
        });

        it('should call displayPdf callback', async () => {
            state.zoomLevel = 2.0;
            await resetZoom(state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });
    });

    describe('setZoom', () => {
        it('should set zoom to specific level', async () => {
            await setZoom(1.75, state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.75);
        });

        it('should invalidate overlay rect cache', async () => {
            state._overlayRect = { width: 800, height: 600 };
            await setZoom(1.5, state, refs, callbacks);

            expect(state._overlayRect).toBeNull();
        });

        it('should call displayPdf callback', async () => {
            await setZoom(2.0, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalledTimes(1);
        });

        it('should call $nextTick callback', async () => {
            await setZoom(1.5, state, refs, callbacks);

            expect(callbacks.$nextTick).toHaveBeenCalled();
        });

        it('should update isolation mask if in isolation mode', async () => {
            state.isolationMode = true;
            await setZoom(1.5, state, refs, callbacks);

            expect(callbacks.updateIsolationMask).toHaveBeenCalled();
        });

        it('should not update isolation mask if not in isolation mode', async () => {
            state.isolationMode = false;
            await setZoom(1.5, state, refs, callbacks);

            expect(callbacks.updateIsolationMask).not.toHaveBeenCalled();
        });

        it('should handle missing displayPdf callback gracefully', async () => {
            delete callbacks.displayPdf;

            await expect(setZoom(1.5, state, refs, callbacks)).resolves.not.toThrow();
        });
    });

    describe('getZoomPercentage', () => {
        it('should return zoom as percentage', () => {
            state.zoomLevel = 1.0;
            expect(getZoomPercentage(state)).toBe(100);
        });

        it('should round to nearest integer', () => {
            state.zoomLevel = 1.234;
            expect(getZoomPercentage(state)).toBe(123);
        });

        it('should handle decimal zoom levels', () => {
            state.zoomLevel = 1.5;
            expect(getZoomPercentage(state)).toBe(150);

            state.zoomLevel = 2.75;
            expect(getZoomPercentage(state)).toBe(275);
        });

        it('should handle minimum zoom', () => {
            state.zoomLevel = 1.0;
            expect(getZoomPercentage(state)).toBe(100);
        });

        it('should handle maximum zoom', () => {
            state.zoomLevel = 3.0;
            expect(getZoomPercentage(state)).toBe(300);
        });
    });

    describe('isAtMinZoom', () => {
        it('should return true when at minimum zoom', () => {
            state.zoomLevel = 1.0;
            state.zoomMin = 1.0;

            expect(isAtMinZoom(state)).toBe(true);
        });

        it('should return false when above minimum zoom', () => {
            state.zoomLevel = 1.5;
            state.zoomMin = 1.0;

            expect(isAtMinZoom(state)).toBe(false);
        });

        it('should return true when below minimum zoom', () => {
            state.zoomLevel = 0.8;
            state.zoomMin = 1.0;

            expect(isAtMinZoom(state)).toBe(true);
        });
    });

    describe('isAtMaxZoom', () => {
        it('should return true when at maximum zoom', () => {
            state.zoomLevel = 3.0;
            state.zoomMax = 3.0;

            expect(isAtMaxZoom(state)).toBe(true);
        });

        it('should return false when below maximum zoom', () => {
            state.zoomLevel = 2.5;
            state.zoomMax = 3.0;

            expect(isAtMaxZoom(state)).toBe(false);
        });

        it('should return true when above maximum zoom', () => {
            state.zoomLevel = 3.5;
            state.zoomMax = 3.0;

            expect(isAtMaxZoom(state)).toBe(true);
        });
    });

    describe('zoomToFitAnnotation', () => {
        beforeEach(() => {
            // Mock container for getBoundingClientRect
            refs.annotationOverlay.getBoundingClientRect = vi.fn(() => ({
                width: 800,
                height: 600,
            }));
        });

        it('should use annotation directly when it has screen coordinates', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                label: 'Test Room',
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should find annotation by ID when coordinates not in source', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                label: 'Test Room',
                screenWidth: null, // Explicitly no screen coordinates in source
                screenHeight: null,
            });

            state.annotations = [
                createMockAnnotation({
                    id: 1,
                    screenX: 100,
                    screenY: 100,
                    screenWidth: 400,
                    screenHeight: 300,
                }),
            ];

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should find annotation by entity ID match', async () => {
            const annotation = createMockAnnotation({
                id: 10,
                type: 'location',
                label: 'Island',
                screenWidth: null, // Explicitly no screen coordinates in source
                screenHeight: null,
            });

            state.annotations = [
                createMockAnnotation({
                    id: 20,
                    type: 'location', // Same type as source
                    roomLocationId: 10, // Matches annotation.id
                    screenX: 150,
                    screenY: 150,
                    screenWidth: 300,
                    screenHeight: 200,
                }),
            ];

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should skip zoom when annotation has no valid coordinates', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                label: 'Test Room',
                screenWidth: null, // Explicitly no screen coordinates
                screenHeight: null,
            });

            state.annotations = []; // No matching annotation with coordinates

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            // Should not call displayPdf when no valid coordinates found
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should skip zoom when container not found', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            refs.annotationOverlay = null; // No container

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            // Should not call displayPdf when container not found
            expect(callbacks.displayPdf).not.toHaveBeenCalled();
        });

        it('should calculate zoom to fit with padding', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                label: 'Test Room',
                screenX: 100,
                screenY: 100,
                screenWidth: 400, // Container is 800x600, so zoom should be ~1.6 with padding
                screenHeight: 300,
            });

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            // Zoom should be calculated to fit 400x300 into 640x480 (80% of 800x600)
            // zoomX = 640 / 400 = 1.6
            // zoomY = 480 / 300 = 1.6
            // Final zoom = min(1.6, 1.6) = 1.6
            expect(state.zoomLevel).toBe(1.6);
        });

        it('should clamp zoom to maximum limit', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                screenX: 100,
                screenY: 100,
                screenWidth: 50, // Very small - would require huge zoom
                screenHeight: 50,
            });

            state.zoomMax = 3.0;

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(state.zoomLevel).toBe(3.0); // Clamped to max
        });

        it('should clamp zoom to minimum limit', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                screenX: 100,
                screenY: 100,
                screenWidth: 2000, // Very large - would require tiny zoom
                screenHeight: 2000,
            });

            state.zoomMin = 1.0;

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.0); // Clamped to min
        });

        it('should call $nextTick callback', async () => {
            const annotation = createMockAnnotation({
                id: 1,
                screenX: 100,
                screenY: 100,
                screenWidth: 400,
                screenHeight: 300,
            });

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.$nextTick).toHaveBeenCalled();
        });

        it('should match by roomId for room annotations', async () => {
            const annotation = createMockAnnotation({
                id: 100,
                type: 'room',
                label: 'Kitchen',
                screenWidth: null, // No screen coordinates in source
                screenHeight: null,
            });

            state.annotations = [
                createMockAnnotation({
                    id: 101,
                    type: 'room', // Same type as source
                    roomId: 100, // Matches annotation.id
                    screenX: 200,
                    screenY: 200,
                    screenWidth: 250,
                    screenHeight: 180,
                }),
            ];

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should match by cabinetRunId for cabinet run annotations', async () => {
            const annotation = createMockAnnotation({
                id: 30,
                type: 'cabinet_run',
                label: 'Upper Cabinets',
                screenWidth: null, // No screen coordinates in source
                screenHeight: null,
            });

            state.annotations = [
                createMockAnnotation({
                    id: 40,
                    type: 'cabinet_run', // Same type as source
                    cabinetRunId: 30, // Matches annotation.id
                    screenX: 220,
                    screenY: 220,
                    screenWidth: 180,
                    screenHeight: 120,
                }),
            ];

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });

        it('should match by cabinetId for cabinet annotations', async () => {
            const annotation = createMockAnnotation({
                id: 40,
                type: 'cabinet',
                label: 'Base Cabinet B1',
                screenWidth: null, // No screen coordinates in source
                screenHeight: null,
            });

            state.annotations = [
                createMockAnnotation({
                    id: 50,
                    type: 'cabinet', // Same type as source
                    cabinetId: 40, // Matches annotation.id
                    screenX: 240,
                    screenY: 240,
                    screenWidth: 160,
                    screenHeight: 100,
                }),
            ];

            await zoomToFitAnnotation(annotation, state, refs, callbacks);

            expect(callbacks.displayPdf).toHaveBeenCalled();
        });
    });

    describe('Edge Cases', () => {
        it('should handle rapid zoom changes', async () => {
            await zoomIn(state, refs, callbacks);
            await zoomIn(state, refs, callbacks);
            await zoomOut(state, refs, callbacks);

            expect(state.zoomLevel).toBe(1.25);
        });

        it('should handle zoom limits correctly', async () => {
            // Zoom to max
            state.zoomLevel = 2.9;
            await zoomIn(state, refs, callbacks);
            await zoomIn(state, refs, callbacks); // Should not exceed max

            expect(state.zoomLevel).toBe(3.0);

            // Zoom to min
            await resetZoom(state, refs, callbacks);
            await zoomOut(state, refs, callbacks); // Should not go below min

            expect(state.zoomLevel).toBe(1.0);
        });

        it('should handle missing canvas element', async () => {
            refs.pdfEmbed = document.createElement('div'); // No canvas child

            await expect(setZoom(1.5, state, refs, callbacks)).resolves.not.toThrow();
        });

        it('should handle missing annotation overlay', async () => {
            delete refs.annotationOverlay;

            await expect(setZoom(1.5, state, refs, callbacks)).resolves.not.toThrow();
        });
    });
});
