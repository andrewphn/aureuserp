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
} from '@pdf-viewer/managers/zoom-manager.js';
import {
    createMockState,
    createMockRefs,
    createMockCallbacks,
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
