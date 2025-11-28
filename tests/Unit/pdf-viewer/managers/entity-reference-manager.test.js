/**
 * Entity Reference Manager Tests
 * Tests for entity reference tracking and management
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    addEntityReference,
    removeEntityReference,
    getEntityReferences,
    getReferencesByType,
    hasEntityReference,
    clearAnnotationReferences,
} from '@pdf-viewer/managers/entity-reference-manager.js';
import { createMockState } from '../../mocks/pdf-viewer-mocks.js';

describe('Entity Reference Manager', () => {
    let state;

    beforeEach(() => {
        state = createMockState({
            annotationReferences: {},
        });

        // Mock console methods
        vi.spyOn(console, 'log').mockImplementation(() => {});
    });

    describe('addEntityReference', () => {
        it('should add entity reference to annotation', () => {
            addEntityReference(1, 'room', 10, 'primary', state);

            expect(state.annotationReferences[1]).toHaveLength(1);
            expect(state.annotationReferences[1][0]).toEqual({
                entity_type: 'room',
                entity_id: 10,
                reference_type: 'primary',
            });
        });

        it('should initialize references array for new annotation', () => {
            expect(state.annotationReferences[1]).toBeUndefined();

            addEntityReference(1, 'room', 10, 'primary', state);

            expect(state.annotationReferences[1]).toBeDefined();
            expect(Array.isArray(state.annotationReferences[1])).toBe(true);
        });

        it('should not add duplicate reference', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(1, 'room', 10, 'primary', state);

            expect(state.annotationReferences[1]).toHaveLength(1);
        });

        it('should allow multiple references with different entity types', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(1, 'location', 20, 'primary', state);
            addEntityReference(1, 'cabinet_run', 30, 'primary', state);

            expect(state.annotationReferences[1]).toHaveLength(3);
        });

        it('should allow multiple references with different entity IDs', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(1, 'room', 20, 'primary', state);

            expect(state.annotationReferences[1]).toHaveLength(2);
        });

        it('should use default reference type "primary"', () => {
            addEntityReference(1, 'room', 10, undefined, state);

            expect(state.annotationReferences[1][0].reference_type).toBe('primary');
        });

        it('should support different reference types', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(1, 'location', 20, 'secondary', state);
            addEntityReference(1, 'cabinet_run', 30, 'context', state);

            expect(state.annotationReferences[1][0].reference_type).toBe('primary');
            expect(state.annotationReferences[1][1].reference_type).toBe('secondary');
            expect(state.annotationReferences[1][2].reference_type).toBe('context');
        });

        it('should log when adding reference', () => {
            addEntityReference(1, 'room', 10, 'primary', state);

            expect(console.log).toHaveBeenCalledWith(
                '✓ Added primary reference: room #10 to annotation #1'
            );
        });

        it('should not log when adding duplicate', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            console.log.mockClear();

            addEntityReference(1, 'room', 10, 'primary', state);

            expect(console.log).not.toHaveBeenCalled();
        });
    });

    describe('removeEntityReference', () => {
        beforeEach(() => {
            state.annotationReferences[1] = [
                { entity_type: 'room', entity_id: 10, reference_type: 'primary' },
                { entity_type: 'location', entity_id: 20, reference_type: 'primary' },
                { entity_type: 'cabinet_run', entity_id: 30, reference_type: 'primary' },
            ];
        });

        it('should remove entity reference from annotation', () => {
            removeEntityReference(1, 'room', 10, state);

            expect(state.annotationReferences[1]).toHaveLength(2);
            expect(state.annotationReferences[1].some(ref => ref.entity_id === 10)).toBe(false);
        });

        it('should only remove matching entity type and ID', () => {
            removeEntityReference(1, 'room', 10, state);

            expect(state.annotationReferences[1]).toHaveLength(2);
            expect(state.annotationReferences[1][0].entity_type).toBe('location');
            expect(state.annotationReferences[1][1].entity_type).toBe('cabinet_run');
        });

        it('should handle removing non-existent reference', () => {
            removeEntityReference(1, 'room', 999, state);

            expect(state.annotationReferences[1]).toHaveLength(3);
        });

        it('should handle removing from non-existent annotation', () => {
            expect(() => removeEntityReference(999, 'room', 10, state)).not.toThrow();
        });

        it('should log when removing reference', () => {
            removeEntityReference(1, 'room', 10, state);

            expect(console.log).toHaveBeenCalledWith(
                '✓ Removed reference: room #10 from annotation #1'
            );
        });

        it('should log even when removing non-existent reference', () => {
            removeEntityReference(1, 'room', 999, state);

            expect(console.log).toHaveBeenCalledWith(
                '✓ Removed reference: room #999 from annotation #1'
            );
        });

        it('should allow removing all references one by one', () => {
            removeEntityReference(1, 'room', 10, state);
            removeEntityReference(1, 'location', 20, state);
            removeEntityReference(1, 'cabinet_run', 30, state);

            expect(state.annotationReferences[1]).toHaveLength(0);
        });
    });

    describe('getEntityReferences', () => {
        it('should return all references for annotation', () => {
            state.annotationReferences[1] = [
                { entity_type: 'room', entity_id: 10, reference_type: 'primary' },
                { entity_type: 'location', entity_id: 20, reference_type: 'primary' },
            ];

            const references = getEntityReferences(1, state);

            expect(references).toHaveLength(2);
            expect(references[0].entity_type).toBe('room');
            expect(references[1].entity_type).toBe('location');
        });

        it('should return empty array for annotation with no references', () => {
            const references = getEntityReferences(1, state);

            expect(references).toEqual([]);
        });

        it('should return empty array for non-existent annotation', () => {
            const references = getEntityReferences(999, state);

            expect(references).toEqual([]);
        });

        it('should return array reference, not copy', () => {
            state.annotationReferences[1] = [
                { entity_type: 'room', entity_id: 10, reference_type: 'primary' },
            ];

            const references = getEntityReferences(1, state);
            references.push({ entity_type: 'location', entity_id: 20, reference_type: 'primary' });

            expect(state.annotationReferences[1]).toHaveLength(2);
        });
    });

    describe('getReferencesByType', () => {
        beforeEach(() => {
            state.annotationReferences[1] = [
                { entity_type: 'room', entity_id: 10, reference_type: 'primary' },
                { entity_type: 'room', entity_id: 20, reference_type: 'secondary' },
                { entity_type: 'location', entity_id: 30, reference_type: 'primary' },
                { entity_type: 'cabinet_run', entity_id: 40, reference_type: 'primary' },
            ];
        });

        it('should return all references matching entity type', () => {
            const roomRefs = getReferencesByType(1, 'room', state);

            expect(roomRefs).toHaveLength(2);
            expect(roomRefs[0].entity_id).toBe(10);
            expect(roomRefs[1].entity_id).toBe(20);
        });

        it('should return empty array when no references match type', () => {
            const cabinetRefs = getReferencesByType(1, 'cabinet', state);

            expect(cabinetRefs).toEqual([]);
        });

        it('should return single reference when only one matches', () => {
            const locationRefs = getReferencesByType(1, 'location', state);

            expect(locationRefs).toHaveLength(1);
            expect(locationRefs[0].entity_id).toBe(30);
        });

        it('should return empty array for annotation with no references', () => {
            const roomRefs = getReferencesByType(999, 'room', state);

            expect(roomRefs).toEqual([]);
        });

        it('should filter correctly across all entity types', () => {
            expect(getReferencesByType(1, 'room', state)).toHaveLength(2);
            expect(getReferencesByType(1, 'location', state)).toHaveLength(1);
            expect(getReferencesByType(1, 'cabinet_run', state)).toHaveLength(1);
            expect(getReferencesByType(1, 'cabinet', state)).toHaveLength(0);
        });
    });

    describe('hasEntityReference', () => {
        beforeEach(() => {
            state.annotationReferences[1] = [
                { entity_type: 'room', entity_id: 10, reference_type: 'primary' },
                { entity_type: 'location', entity_id: 20, reference_type: 'primary' },
            ];
        });

        it('should return true when reference exists', () => {
            expect(hasEntityReference(1, 'room', 10, state)).toBe(true);
            expect(hasEntityReference(1, 'location', 20, state)).toBe(true);
        });

        it('should return false when reference does not exist', () => {
            expect(hasEntityReference(1, 'room', 999, state)).toBe(false);
        });

        it('should return false for wrong entity type', () => {
            expect(hasEntityReference(1, 'cabinet_run', 10, state)).toBe(false);
        });

        it('should return false for annotation with no references', () => {
            expect(hasEntityReference(999, 'room', 10, state)).toBe(false);
        });

        it('should match both entity type and ID', () => {
            expect(hasEntityReference(1, 'room', 10, state)).toBe(true);
            expect(hasEntityReference(1, 'room', 20, state)).toBe(false);
            expect(hasEntityReference(1, 'location', 10, state)).toBe(false);
        });
    });

    describe('clearAnnotationReferences', () => {
        beforeEach(() => {
            state.annotationReferences[1] = [
                { entity_type: 'room', entity_id: 10, reference_type: 'primary' },
                { entity_type: 'location', entity_id: 20, reference_type: 'primary' },
                { entity_type: 'cabinet_run', entity_id: 30, reference_type: 'primary' },
            ];
        });

        it('should clear all references for annotation', () => {
            clearAnnotationReferences(1, state);

            expect(state.annotationReferences[1]).toBeUndefined();
        });

        it('should log count of cleared references', () => {
            clearAnnotationReferences(1, state);

            expect(console.log).toHaveBeenCalledWith(
                '✓ Cleared 3 references from annotation #1'
            );
        });

        it('should handle clearing non-existent annotation', () => {
            expect(() => clearAnnotationReferences(999, state)).not.toThrow();
        });

        it('should not log when clearing non-existent annotation', () => {
            clearAnnotationReferences(999, state);

            expect(console.log).not.toHaveBeenCalled();
        });

        it('should handle clearing already cleared annotation', () => {
            clearAnnotationReferences(1, state);
            console.log.mockClear();

            clearAnnotationReferences(1, state);

            expect(console.log).not.toHaveBeenCalled();
        });

        it('should only clear specified annotation', () => {
            state.annotationReferences[2] = [
                { entity_type: 'room', entity_id: 40, reference_type: 'primary' },
            ];

            clearAnnotationReferences(1, state);

            expect(state.annotationReferences[1]).toBeUndefined();
            expect(state.annotationReferences[2]).toHaveLength(1);
        });
    });

    describe('Edge Cases', () => {
        it('should handle multiple annotations independently', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(2, 'room', 20, 'primary', state);

            expect(state.annotationReferences[1]).toHaveLength(1);
            expect(state.annotationReferences[2]).toHaveLength(1);
            expect(state.annotationReferences[1][0].entity_id).toBe(10);
            expect(state.annotationReferences[2][0].entity_id).toBe(20);
        });

        it('should handle complex reference scenarios', () => {
            // Add various references
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(1, 'location', 20, 'secondary', state);
            addEntityReference(1, 'cabinet_run', 30, 'context', state);

            // Verify all exist
            expect(hasEntityReference(1, 'room', 10, state)).toBe(true);
            expect(hasEntityReference(1, 'location', 20, state)).toBe(true);
            expect(hasEntityReference(1, 'cabinet_run', 30, state)).toBe(true);

            // Remove one
            removeEntityReference(1, 'location', 20, state);
            expect(hasEntityReference(1, 'location', 20, state)).toBe(false);

            // Others still exist
            expect(hasEntityReference(1, 'room', 10, state)).toBe(true);
            expect(hasEntityReference(1, 'cabinet_run', 30, state)).toBe(true);
        });

        it('should handle rapid add/remove operations', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            removeEntityReference(1, 'room', 10, state);
            addEntityReference(1, 'room', 10, 'primary', state);

            expect(state.annotationReferences[1]).toHaveLength(1);
            expect(hasEntityReference(1, 'room', 10, state)).toBe(true);
        });

        it('should maintain reference integrity across operations', () => {
            addEntityReference(1, 'room', 10, 'primary', state);
            addEntityReference(1, 'location', 20, 'primary', state);

            const refs1 = getEntityReferences(1, state);
            expect(refs1).toHaveLength(2);

            addEntityReference(1, 'cabinet_run', 30, 'primary', state);

            const refs2 = getEntityReferences(1, state);
            expect(refs2).toHaveLength(3);
        });
    });
});
