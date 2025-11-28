/**
 * Visibility Toggle Manager Tests
 * Tests for manual visibility toggling of rooms, locations, and cabinet runs
 */

import { describe, it, expect, beforeEach } from 'vitest';
import {
    toggleRoomVisibility,
    isRoomVisible,
    toggleLocationVisibility,
    isLocationVisible,
    toggleCabinetRunVisibility,
    isCabinetRunVisible,
    toggleAnnotationVisibility,
    isAnnotationVisible,
    hasAnnotationsOnCurrentPage,
} from '@pdf-viewer/managers/visibility-toggle-manager.js';
import { createMockState, createMockAnnotation } from '../../mocks/pdf-viewer-mocks.js';

describe('Visibility Toggle Manager', () => {
    let state;

    beforeEach(() => {
        state = createMockState({
            annotations: [
                createMockAnnotation({ id: 1, type: 'room', roomId: 1 }),
                createMockAnnotation({ id: 2, type: 'location', roomId: 1, locationId: 10, roomLocationId: 10 }),
                createMockAnnotation({ id: 3, type: 'cabinet_run', roomId: 1, locationId: 10, cabinetRunId: 100 }),
                createMockAnnotation({ id: 4, type: 'cabinet', roomId: 1, locationId: 10, cabinetRunId: 100 }),
                createMockAnnotation({ id: 5, type: 'room', roomId: 2 }),
                createMockAnnotation({ id: 6, type: 'location', roomId: 2, locationId: 20, roomLocationId: 20 }),
            ],
            hiddenAnnotations: [],
        });
    });

    describe('toggleRoomVisibility', () => {
        it('should hide all room annotations when some are visible', () => {
            toggleRoomVisibility(1, state);

            expect(state.hiddenAnnotations).toContain(1);
            expect(state.hiddenAnnotations).toContain(2);
            expect(state.hiddenAnnotations).toContain(3);
            expect(state.hiddenAnnotations).toContain(4);
            expect(state.hiddenAnnotations).not.toContain(5);
            expect(state.hiddenAnnotations).not.toContain(6);
        });

        it('should show all room annotations when all are hidden', () => {
            state.hiddenAnnotations = [1, 2, 3, 4];

            toggleRoomVisibility(1, state);

            expect(state.hiddenAnnotations).not.toContain(1);
            expect(state.hiddenAnnotations).not.toContain(2);
            expect(state.hiddenAnnotations).not.toContain(3);
            expect(state.hiddenAnnotations).not.toContain(4);
        });

        it('should toggle multiple times', () => {
            toggleRoomVisibility(1, state);
            expect(state.hiddenAnnotations.length).toBe(4);

            toggleRoomVisibility(1, state);
            expect(state.hiddenAnnotations.length).toBe(0);

            toggleRoomVisibility(1, state);
            expect(state.hiddenAnnotations.length).toBe(4);
        });

        it('should only affect specified room', () => {
            toggleRoomVisibility(1, state);

            expect(state.hiddenAnnotations).toContain(1);
            expect(state.hiddenAnnotations).not.toContain(5);
        });

        it('should handle room with no annotations', () => {
            expect(() => toggleRoomVisibility(999, state)).not.toThrow();
            expect(state.hiddenAnnotations.length).toBe(0);
        });
    });

    describe('isRoomVisible', () => {
        it('should return true when room has visible annotations', () => {
            expect(isRoomVisible(1, state)).toBe(true);
        });

        it('should return false when all room annotations are hidden', () => {
            state.hiddenAnnotations = [1, 2, 3, 4];

            expect(isRoomVisible(1, state)).toBe(false);
        });

        it('should return true when some room annotations are visible', () => {
            state.hiddenAnnotations = [1, 2]; // Hide some, not all

            expect(isRoomVisible(1, state)).toBe(true);
        });

        it('should return false for room with no annotations', () => {
            expect(isRoomVisible(999, state)).toBe(false);
        });
    });

    describe('toggleLocationVisibility', () => {
        it('should hide all location annotations when visible', () => {
            toggleLocationVisibility(10, state);

            expect(state.hiddenAnnotations).toContain(2); // Location
            expect(state.hiddenAnnotations).toContain(3); // Cabinet run
            expect(state.hiddenAnnotations).toContain(4); // Cabinet
            expect(state.hiddenAnnotations).not.toContain(1); // Room
        });

        it('should show all location annotations when hidden', () => {
            state.hiddenAnnotations = [2, 3, 4];

            toggleLocationVisibility(10, state);

            expect(state.hiddenAnnotations).not.toContain(2);
            expect(state.hiddenAnnotations).not.toContain(3);
            expect(state.hiddenAnnotations).not.toContain(4);
        });

        it('should only affect specified location', () => {
            toggleLocationVisibility(10, state);

            expect(state.hiddenAnnotations).toContain(2);
            expect(state.hiddenAnnotations).not.toContain(6);
        });

        it('should handle location with no annotations', () => {
            expect(() => toggleLocationVisibility(999, state)).not.toThrow();
        });
    });

    describe('isLocationVisible', () => {
        it('should return true when location has visible annotations', () => {
            expect(isLocationVisible(10, state)).toBe(true);
        });

        it('should return false when all location annotations are hidden', () => {
            state.hiddenAnnotations = [2, 3, 4];

            expect(isLocationVisible(10, state)).toBe(false);
        });

        it('should return true when some location annotations are visible', () => {
            state.hiddenAnnotations = [2]; // Hide location, but not children

            expect(isLocationVisible(10, state)).toBe(true);
        });

        it('should return false for location with no annotations', () => {
            expect(isLocationVisible(999, state)).toBe(false);
        });
    });

    describe('toggleCabinetRunVisibility', () => {
        it('should hide all cabinet run annotations when visible', () => {
            toggleCabinetRunVisibility(100, state);

            expect(state.hiddenAnnotations).toContain(3); // Cabinet run
            expect(state.hiddenAnnotations).toContain(4); // Cabinet
            expect(state.hiddenAnnotations).not.toContain(2); // Location
        });

        it('should show all cabinet run annotations when hidden', () => {
            state.hiddenAnnotations = [3, 4];

            toggleCabinetRunVisibility(100, state);

            expect(state.hiddenAnnotations).not.toContain(3);
            expect(state.hiddenAnnotations).not.toContain(4);
        });

        it('should handle cabinet run with no annotations', () => {
            expect(() => toggleCabinetRunVisibility(999, state)).not.toThrow();
        });
    });

    describe('isCabinetRunVisible', () => {
        it('should return true when cabinet run has visible annotations', () => {
            expect(isCabinetRunVisible(100, state)).toBe(true);
        });

        it('should return false when all cabinet run annotations are hidden', () => {
            state.hiddenAnnotations = [3, 4];

            expect(isCabinetRunVisible(100, state)).toBe(false);
        });

        it('should return false for cabinet run with no annotations', () => {
            expect(isCabinetRunVisible(999, state)).toBe(false);
        });
    });

    describe('toggleAnnotationVisibility', () => {
        it('should hide visible annotation', () => {
            toggleAnnotationVisibility(1, state);

            expect(state.hiddenAnnotations).toContain(1);
        });

        it('should show hidden annotation', () => {
            state.hiddenAnnotations = [1];

            toggleAnnotationVisibility(1, state);

            expect(state.hiddenAnnotations).not.toContain(1);
        });

        it('should toggle multiple times', () => {
            toggleAnnotationVisibility(1, state);
            expect(state.hiddenAnnotations).toContain(1);

            toggleAnnotationVisibility(1, state);
            expect(state.hiddenAnnotations).not.toContain(1);

            toggleAnnotationVisibility(1, state);
            expect(state.hiddenAnnotations).toContain(1);
        });

        it('should only affect specified annotation', () => {
            toggleAnnotationVisibility(1, state);

            expect(state.hiddenAnnotations).toContain(1);
            expect(state.hiddenAnnotations).not.toContain(2);
        });
    });

    describe('isAnnotationVisible', () => {
        it('should return true for visible annotation', () => {
            expect(isAnnotationVisible(1, state)).toBe(true);
        });

        it('should return false for hidden annotation', () => {
            state.hiddenAnnotations = [1];

            expect(isAnnotationVisible(1, state)).toBe(false);
        });
    });

    describe('hasAnnotationsOnCurrentPage', () => {
        it('should return true when room has annotations', () => {
            expect(hasAnnotationsOnCurrentPage(1, 'room', state)).toBe(true);
        });

        it('should return true when location has annotations', () => {
            expect(hasAnnotationsOnCurrentPage(10, 'location', state)).toBe(true);
        });

        it('should return true when cabinet run has annotations', () => {
            expect(hasAnnotationsOnCurrentPage(100, 'cabinet_run', state)).toBe(true);
        });

        it('should return false when entity has no annotations', () => {
            expect(hasAnnotationsOnCurrentPage(999, 'room', state)).toBe(false);
            expect(hasAnnotationsOnCurrentPage(999, 'location', state)).toBe(false);
            expect(hasAnnotationsOnCurrentPage(999, 'cabinet_run', state)).toBe(false);
        });

        it('should return false when annotations array is empty', () => {
            state.annotations = [];

            expect(hasAnnotationsOnCurrentPage(1, 'room', state)).toBe(false);
        });

        it('should return false when annotations array is null', () => {
            state.annotations = null;

            expect(hasAnnotationsOnCurrentPage(1, 'room', state)).toBe(false);
        });
    });

    describe('Edge Cases', () => {
        it('should handle empty annotations array', () => {
            state.annotations = [];

            expect(() => toggleRoomVisibility(1, state)).not.toThrow();
            expect(() => toggleLocationVisibility(10, state)).not.toThrow();
            expect(() => toggleCabinetRunVisibility(100, state)).not.toThrow();
        });

        it('should handle all annotations hidden', () => {
            state.hiddenAnnotations = [1, 2, 3, 4, 5, 6];

            expect(isRoomVisible(1, state)).toBe(false);
            expect(isLocationVisible(10, state)).toBe(false);
            expect(isCabinetRunVisible(100, state)).toBe(false);
        });

        it('should handle toggling non-existent annotation', () => {
            expect(() => toggleAnnotationVisibility(999, state)).not.toThrow();
        });

        it('should maintain hiddenAnnotations array integrity', () => {
            toggleAnnotationVisibility(1, state);
            toggleAnnotationVisibility(2, state);
            toggleAnnotationVisibility(1, state); // Toggle back

            expect(state.hiddenAnnotations).toEqual([2]);
            expect(state.hiddenAnnotations).not.toContain(1);
        });
    });
});
