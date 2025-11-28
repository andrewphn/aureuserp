/**
 * View Type Manager Tests
 * Tests for view type and orientation management
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    setViewType,
    setOrientation,
    isAnnotationVisibleInView,
    updateAnnotationVisibility,
    getCurrentViewLabel,
} from '@pdf-viewer/managers/view-type-manager.js';
import { createMockState, createMockCallbacks, createMockAnnotation } from '../../mocks/pdf-viewer-mocks.js';

describe('View Type Manager', () => {
    let state, callbacks;

    beforeEach(() => {
        state = createMockState({
            activeViewType: 'plan',
            activeOrientation: null,
            annotations: [],
        });

        callbacks = createMockCallbacks({
            updateAnnotationVisibility: vi.fn(),
        });
    });

    describe('setViewType', () => {
        it('should set plan view', () => {
            setViewType('plan', null, state, callbacks);

            expect(state.activeViewType).toBe('plan');
            expect(state.activeOrientation).toBeNull();
            expect(callbacks.updateAnnotationVisibility).toHaveBeenCalled();
        });

        it('should set elevation view with orientation', () => {
            setViewType('elevation', 'front', state, callbacks);

            expect(state.activeViewType).toBe('elevation');
            expect(state.activeOrientation).toBe('front');
            expect(callbacks.updateAnnotationVisibility).toHaveBeenCalled();
        });

        it('should set section view with orientation', () => {
            setViewType('section', 'A-A', state, callbacks);

            expect(state.activeViewType).toBe('section');
            expect(state.activeOrientation).toBe('A-A');
        });

        it('should set detail view', () => {
            setViewType('detail', null, state, callbacks);

            expect(state.activeViewType).toBe('detail');
        });

        it('should reset orientation when switching to plan view', () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = 'front';

            setViewType('plan', null, state, callbacks);

            expect(state.activeViewType).toBe('plan');
            expect(state.activeOrientation).toBeNull();
        });

        it('should handle missing callback gracefully', () => {
            delete callbacks.updateAnnotationVisibility;

            expect(() => setViewType('plan', null, state, callbacks)).not.toThrow();
        });
    });

    describe('setOrientation', () => {
        it('should set orientation for elevation view', () => {
            state.activeViewType = 'elevation';
            setOrientation('front', state, callbacks);

            expect(state.activeOrientation).toBe('front');
            expect(callbacks.updateAnnotationVisibility).toHaveBeenCalled();
        });

        it('should set orientation for section view', () => {
            state.activeViewType = 'section';
            setOrientation('B-B', state, callbacks);

            expect(state.activeOrientation).toBe('B-B');
        });

        it('should update orientation', () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = 'front';

            setOrientation('back', state, callbacks);

            expect(state.activeOrientation).toBe('back');
        });
    });

    describe('isAnnotationVisibleInView', () => {
        it('should show plan annotations in plan view', () => {
            const anno = createMockAnnotation({ viewType: 'plan' });
            state.activeViewType = 'plan';

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });

        it('should hide elevation annotations in plan view', () => {
            const anno = createMockAnnotation({ viewType: 'elevation', viewOrientation: 'front' });
            state.activeViewType = 'plan';

            expect(isAnnotationVisibleInView(anno, state)).toBe(false);
        });

        it('should show matching elevation annotations', () => {
            const anno = createMockAnnotation({ viewType: 'elevation', viewOrientation: 'front' });
            state.activeViewType = 'elevation';
            state.activeOrientation = 'front';

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });

        it('should hide non-matching elevation annotations', () => {
            const anno = createMockAnnotation({ viewType: 'elevation', viewOrientation: 'front' });
            state.activeViewType = 'elevation';
            state.activeOrientation = 'back';

            expect(isAnnotationVisibleInView(anno, state)).toBe(false);
        });

        it('should show all elevation annotations when no orientation filter', () => {
            const anno = createMockAnnotation({ viewType: 'elevation', viewOrientation: 'front' });
            state.activeViewType = 'elevation';
            state.activeOrientation = null;

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });

        it('should default to plan view for annotations without viewType', () => {
            const anno = createMockAnnotation({ viewType: null });
            state.activeViewType = 'plan';

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });

        it('should work with section views', () => {
            const anno = createMockAnnotation({ viewType: 'section', viewOrientation: 'A-A' });
            state.activeViewType = 'section';
            state.activeOrientation = 'A-A';

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });

        it('should work with detail views', () => {
            const anno = createMockAnnotation({ viewType: 'detail' });
            state.activeViewType = 'detail';

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });
    });

    describe('getCurrentViewLabel', () => {
        it('should return "Plan View" for plan', () => {
            state.activeViewType = 'plan';
            expect(getCurrentViewLabel(state)).toBe('Plan View');
        });

        it('should return "Elevation View" for elevation without orientation', () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = null;

            expect(getCurrentViewLabel(state)).toBe('Elevation View');
        });

        it('should return "Elevation View - Front" for elevation with front orientation', () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = 'front';

            expect(getCurrentViewLabel(state)).toBe('Elevation View - Front');
        });

        it('should capitalize orientation for elevation views', () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = 'back';

            expect(getCurrentViewLabel(state)).toBe('Elevation View - Back');
        });

        it('should return "Section View - A-A" for section with orientation', () => {
            state.activeViewType = 'section';
            state.activeOrientation = 'A-A';

            expect(getCurrentViewLabel(state)).toBe('Section View - A-A');
        });

        it('should return "Detail View" for detail without orientation', () => {
            state.activeViewType = 'detail';
            state.activeOrientation = null;

            expect(getCurrentViewLabel(state)).toBe('Detail View');
        });

        it('should return "Detail View - 1" for detail with orientation', () => {
            state.activeViewType = 'detail';
            state.activeOrientation = '1';

            expect(getCurrentViewLabel(state)).toBe('Detail View - 1');
        });

        it('should return "Unknown View" for invalid view type', () => {
            state.activeViewType = 'invalid';

            expect(getCurrentViewLabel(state)).toBe('Unknown View');
        });
    });

    describe('updateAnnotationVisibility', () => {
        it('should not throw error', () => {
            expect(() => updateAnnotationVisibility(state)).not.toThrow();
        });

        it('should work with any view type', () => {
            state.activeViewType = 'elevation';
            expect(() => updateAnnotationVisibility(state)).not.toThrow();

            state.activeViewType = 'section';
            expect(() => updateAnnotationVisibility(state)).not.toThrow();
        });
    });

    describe('Edge Cases', () => {
        it('should handle undefined viewType in annotation', () => {
            const anno = createMockAnnotation({ viewType: undefined });
            state.activeViewType = 'plan';

            expect(isAnnotationVisibleInView(anno, state)).toBe(true);
        });

        it('should handle multiple view type switches', () => {
            setViewType('plan', null, state, callbacks);
            setViewType('elevation', 'front', state, callbacks);
            setViewType('section', 'A-A', state, callbacks);
            setViewType('plan', null, state, callbacks);

            expect(state.activeViewType).toBe('plan');
            expect(state.activeOrientation).toBeNull();
        });

        it('should handle empty orientation', () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = '';

            const label = getCurrentViewLabel(state);
            expect(label).toBe('Elevation View');
        });
    });
});
