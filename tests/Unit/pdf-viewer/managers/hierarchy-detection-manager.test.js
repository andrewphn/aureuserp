/**
 * Hierarchy Detection Manager Tests
 * Tests for hierarchy detection and entity defaults
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    detectMissingHierarchy,
    getEntityDefaults,
    getEntityDisplayName,
    canSaveDirectly,
} from '@pdf-viewer/managers/hierarchy-detection-manager.js';
import { createMockState, createMockAnnotation } from '../../mocks/pdf-viewer-mocks.js';

describe('Hierarchy Detection Manager', () => {
    let state;

    beforeEach(() => {
        state = createMockState({
            projectId: 123,
            activeRoomId: null,
            activeLocationId: null,
            activeCabinetRunId: null,
            activeViewType: 'plan',
        });

        // Mock console methods
        vi.spyOn(console, 'log').mockImplementation(() => {});
    });

    describe('detectMissingHierarchy', () => {
        describe('Drawing Room (Level 0)', () => {
            it('should have no missing levels when drawing room', () => {
                const missing = detectMissingHierarchy('room', state);

                expect(missing).toEqual([]);
            });

            it('should not require any context for room', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;
                state.activeCabinetRunId = 30;

                const missing = detectMissingHierarchy('room', state);

                expect(missing).toEqual([]);
            });
        });

        describe('Drawing Room Location (Level 1)', () => {
            it('should require room when drawing location', () => {
                const missing = detectMissingHierarchy('room_location', state);

                expect(missing).toHaveLength(1);
                expect(missing[0]).toEqual({
                    type: 'room',
                    level: 0,
                    required: true,
                });
            });

            it('should have no missing levels when room exists', () => {
                state.activeRoomId = 10;

                const missing = detectMissingHierarchy('room_location', state);

                expect(missing).toEqual([]);
            });

            it('should not require location or cabinet run', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;
                state.activeCabinetRunId = 30;

                const missing = detectMissingHierarchy('room_location', state);

                expect(missing).toEqual([]);
            });
        });

        describe('Drawing Cabinet Run (Level 2)', () => {
            it('should require room and location when both missing', () => {
                const missing = detectMissingHierarchy('cabinet_run', state);

                expect(missing).toHaveLength(2);
                expect(missing[0].type).toBe('room');
                expect(missing[1].type).toBe('room_location');
            });

            it('should require only location when room exists', () => {
                state.activeRoomId = 10;

                const missing = detectMissingHierarchy('cabinet_run', state);

                expect(missing).toHaveLength(1);
                expect(missing[0].type).toBe('room_location');
            });

            it('should require only room when location exists', () => {
                state.activeLocationId = 20;

                const missing = detectMissingHierarchy('cabinet_run', state);

                expect(missing).toHaveLength(1);
                expect(missing[0].type).toBe('room');
            });

            it('should have no missing levels when room and location exist', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;

                const missing = detectMissingHierarchy('cabinet_run', state);

                expect(missing).toEqual([]);
            });
        });

        describe('Drawing Cabinet (Level 3)', () => {
            it('should require all levels when all missing', () => {
                const missing = detectMissingHierarchy('cabinet', state);

                expect(missing).toHaveLength(3);
                expect(missing[0].type).toBe('room');
                expect(missing[1].type).toBe('room_location');
                expect(missing[2].type).toBe('cabinet_run');
            });

            it('should require location and cabinet run when room exists', () => {
                state.activeRoomId = 10;

                const missing = detectMissingHierarchy('cabinet', state);

                expect(missing).toHaveLength(2);
                expect(missing[0].type).toBe('room_location');
                expect(missing[1].type).toBe('cabinet_run');
            });

            it('should require cabinet run when room and location exist', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;

                const missing = detectMissingHierarchy('cabinet', state);

                expect(missing).toHaveLength(1);
                expect(missing[0].type).toBe('cabinet_run');
            });

            it('should have no missing levels when all exist', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;
                state.activeCabinetRunId = 30;

                const missing = detectMissingHierarchy('cabinet', state);

                expect(missing).toEqual([]);
            });
        });

        describe('Logging', () => {
            it('should log detection results', () => {
                detectMissingHierarchy('cabinet', state);

                expect(console.log).toHaveBeenCalledWith(
                    expect.stringContaining('🔍 [Hierarchy Detection] Drawing cabinet'),
                    expect.any(Array)
                );
            });
        });
    });

    describe('getEntityDefaults', () => {
        let annotation;

        beforeEach(() => {
            annotation = createMockAnnotation({
                label: 'Test Annotation',
            });
        });

        describe('Room Defaults', () => {
            it('should return defaults for room', () => {
                const defaults = getEntityDefaults('room', annotation, state);

                expect(defaults).toMatchObject({
                    name: 'Test Annotation',
                    project_id: 123,
                    room_type: 'general',
                    floor_number: 1,
                });
            });

            it('should use "Untitled" when no label provided', () => {
                annotation.label = null;

                const defaults = getEntityDefaults('room', annotation, state);

                expect(defaults.name).toBe('Untitled');
            });
        });

        describe('Room Location Defaults', () => {
            beforeEach(() => {
                state.activeRoomId = 10;
            });

            it('should return defaults for room location', () => {
                const defaults = getEntityDefaults('room_location', annotation, state);

                expect(defaults).toMatchObject({
                    name: 'Test Annotation',
                    project_id: 123,
                    location_type: 'wall',
                    room_id: 10,
                });
            });

            it('should use "Location" when no label provided', () => {
                annotation.label = null;

                const defaults = getEntityDefaults('room_location', annotation, state);

                expect(defaults.name).toBe('Location');
            });

            it('should include room_id from active context', () => {
                state.activeRoomId = 42;

                const defaults = getEntityDefaults('room_location', annotation, state);

                expect(defaults.room_id).toBe(42);
            });
        });

        describe('Cabinet Run Defaults', () => {
            beforeEach(() => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;
            });

            it('should return defaults for base cabinet run (plan view)', () => {
                state.activeViewType = 'plan';

                const defaults = getEntityDefaults('cabinet_run', annotation, state);

                expect(defaults).toMatchObject({
                    name: 'Base Cabinet',
                    project_id: 123,
                    room_id: 10,
                    room_location_id: 20,
                    run_type: 'base',
                    position_in_location: 0,
                });
            });

            it('should return defaults for wall cabinet run (elevation view)', () => {
                state.activeViewType = 'elevation';

                const defaults = getEntityDefaults('cabinet_run', annotation, state);

                expect(defaults).toMatchObject({
                    name: 'Wall Cabinet',
                    run_type: 'wall',
                });
            });

            it('should include context IDs', () => {
                state.activeRoomId = 42;
                state.activeLocationId = 84;

                const defaults = getEntityDefaults('cabinet_run', annotation, state);

                expect(defaults.room_id).toBe(42);
                expect(defaults.room_location_id).toBe(84);
            });
        });

        describe('Cabinet Defaults', () => {
            beforeEach(() => {
                state.activeRoomId = 10;
                state.activeCabinetRunId = 30;
            });

            it('should return defaults for cabinet', () => {
                const defaults = getEntityDefaults('cabinet', annotation, state);

                expect(defaults).toMatchObject({
                    name: 'Test Annotation',
                    project_id: 123,
                    room_id: 10,
                    cabinet_run_id: 30,
                    product_variant_id: 1,
                    position_in_run: 0,
                    length_inches: 24,
                    depth_inches: 24,
                    height_inches: 30,
                    quantity: 1,
                });
            });

            it('should include dimensional defaults', () => {
                const defaults = getEntityDefaults('cabinet', annotation, state);

                expect(defaults.length_inches).toBe(24);
                expect(defaults.depth_inches).toBe(24);
                expect(defaults.height_inches).toBe(30);
            });

            it('should include quantity default', () => {
                const defaults = getEntityDefaults('cabinet', annotation, state);

                expect(defaults.quantity).toBe(1);
            });
        });

        describe('Edge Cases', () => {
            it('should handle unknown entity type', () => {
                const defaults = getEntityDefaults('unknown_type', annotation, state);

                expect(defaults).toMatchObject({
                    name: 'Test Annotation',
                    project_id: 123,
                });
            });

            it('should handle null annotation label', () => {
                annotation.label = null;

                const defaults = getEntityDefaults('room', annotation, state);

                expect(defaults.name).toBe('Untitled');
            });

            it('should handle undefined annotation label', () => {
                annotation.label = undefined;

                const defaults = getEntityDefaults('room', annotation, state);

                expect(defaults.name).toBe('Untitled');
            });
        });
    });

    describe('getEntityDisplayName', () => {
        it('should return display name for room', () => {
            expect(getEntityDisplayName('room')).toBe('Room');
        });

        it('should return display name for room_location', () => {
            expect(getEntityDisplayName('room_location')).toBe('Room Location');
        });

        it('should return display name for cabinet_run', () => {
            expect(getEntityDisplayName('cabinet_run')).toBe('Cabinet Run');
        });

        it('should return display name for cabinet', () => {
            expect(getEntityDisplayName('cabinet')).toBe('Cabinet');
        });

        it('should return entity type when no display name defined', () => {
            expect(getEntityDisplayName('unknown_type')).toBe('unknown_type');
        });

        it('should handle null input', () => {
            expect(getEntityDisplayName(null)).toBe(null);
        });
    });

    describe('canSaveDirectly', () => {
        describe('Room Level', () => {
            it('should allow direct save for room', () => {
                expect(canSaveDirectly('room', state)).toBe(true);
            });
        });

        describe('Room Location Level', () => {
            it('should not allow direct save when room missing', () => {
                expect(canSaveDirectly('room_location', state)).toBe(false);
            });

            it('should allow direct save when room exists', () => {
                state.activeRoomId = 10;

                expect(canSaveDirectly('room_location', state)).toBe(true);
            });
        });

        describe('Cabinet Run Level', () => {
            it('should not allow direct save when hierarchy incomplete', () => {
                expect(canSaveDirectly('cabinet_run', state)).toBe(false);
            });

            it('should not allow direct save when only room exists', () => {
                state.activeRoomId = 10;

                expect(canSaveDirectly('cabinet_run', state)).toBe(false);
            });

            it('should not allow direct save when only location exists', () => {
                state.activeLocationId = 20;

                expect(canSaveDirectly('cabinet_run', state)).toBe(false);
            });

            it('should allow direct save when room and location exist', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;

                expect(canSaveDirectly('cabinet_run', state)).toBe(true);
            });
        });

        describe('Cabinet Level', () => {
            it('should not allow direct save when hierarchy incomplete', () => {
                expect(canSaveDirectly('cabinet', state)).toBe(false);
            });

            it('should not allow direct save when only room exists', () => {
                state.activeRoomId = 10;

                expect(canSaveDirectly('cabinet', state)).toBe(false);
            });

            it('should not allow direct save when only room and location exist', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;

                expect(canSaveDirectly('cabinet', state)).toBe(false);
            });

            it('should allow direct save when all hierarchy levels exist', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;
                state.activeCabinetRunId = 30;

                expect(canSaveDirectly('cabinet', state)).toBe(true);
            });
        });

        describe('Integration with detectMissingHierarchy', () => {
            it('should return true when detectMissingHierarchy returns empty array', () => {
                state.activeRoomId = 10;
                state.activeLocationId = 20;

                expect(canSaveDirectly('cabinet_run', state)).toBe(true);
            });

            it('should return false when detectMissingHierarchy returns items', () => {
                expect(canSaveDirectly('cabinet', state)).toBe(false);
            });
        });
    });

    describe('Integration Tests', () => {
        it('should provide complete workflow for missing hierarchy', () => {
            // Detect missing levels for cabinet
            const missing = detectMissingHierarchy('cabinet', state);
            expect(missing).toHaveLength(3);

            // Get display names for each missing level
            const displayNames = missing.map(m => getEntityDisplayName(m.type));
            expect(displayNames).toEqual(['Room', 'Room Location', 'Cabinet Run']);

            // Get defaults for creating first missing level
            const annotation = createMockAnnotation({ label: 'Kitchen' });
            const roomDefaults = getEntityDefaults('room', annotation, state);
            expect(roomDefaults.name).toBe('Kitchen');
            expect(roomDefaults.room_type).toBe('general');

            // Verify can't save directly
            expect(canSaveDirectly('cabinet', state)).toBe(false);
        });

        it('should show progressive hierarchy building', () => {
            // Start with no context
            expect(canSaveDirectly('cabinet', state)).toBe(false);
            expect(detectMissingHierarchy('cabinet', state)).toHaveLength(3);

            // Add room
            state.activeRoomId = 10;
            expect(canSaveDirectly('cabinet', state)).toBe(false);
            expect(detectMissingHierarchy('cabinet', state)).toHaveLength(2);

            // Add location
            state.activeLocationId = 20;
            expect(canSaveDirectly('cabinet', state)).toBe(false);
            expect(detectMissingHierarchy('cabinet', state)).toHaveLength(1);

            // Add cabinet run
            state.activeCabinetRunId = 30;
            expect(canSaveDirectly('cabinet', state)).toBe(true);
            expect(detectMissingHierarchy('cabinet', state)).toHaveLength(0);
        });
    });
});
