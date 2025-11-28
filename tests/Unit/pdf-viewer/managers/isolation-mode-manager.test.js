/**
 * Isolation Mode Manager Tests
 * Tests for isolation mode visibility and state management
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    isAnnotationVisibleInIsolation,
    getIsolationBreadcrumbs,
    updateIsolationMask,
    enterIsolationMode,
    exitIsolationMode,
} from '@pdf-viewer/managers/isolation-mode-manager.js';
import { createMockState, createMockAnnotation, createMockCallbacks } from '../../mocks/pdf-viewer-mocks.js';

// Mock utilities
vi.mock('@pdf-viewer/utilities.js', () => ({
    createSVGElement: vi.fn((tagName, attrs) => {
        const element = { tagName, ...attrs };
        return element;
    }),
}));

describe('Isolation Mode Manager', () => {
    let state;

    beforeEach(() => {
        state = createMockState({
            isolationMode: false,
            isolationLevel: null,
            isolatedRoomId: null,
            isolatedRoomName: '',
            isolatedRoomEntityId: null,
            isolatedLocationId: null,
            isolatedLocationName: '',
            isolatedCabinetRunId: null,
            isolatedCabinetRunName: '',
            isolationViewType: null,
            isolationOrientation: null,
            activeViewType: 'plan',
            activeOrientation: null,
            activeLocationId: null,
            annotations: [],
            hiddenAnnotations: [],
            tree: [],
        });

        // Mock console methods
        vi.spyOn(console, 'log').mockImplementation(() => {});
    });

    describe('isAnnotationVisibleInIsolation', () => {
        describe('Normal Mode (No Isolation)', () => {
            it('should show room annotations', () => {
                const annotation = createMockAnnotation({ id: 1, type: 'room' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should show location annotations', () => {
                const annotation = createMockAnnotation({ id: 2, type: 'location' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should hide cabinet_run annotations', () => {
                const annotation = createMockAnnotation({ id: 3, type: 'cabinet_run' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide cabinet annotations', () => {
                const annotation = createMockAnnotation({ id: 4, type: 'cabinet' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });
        });

        describe('Room Isolation Mode', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolationLevel = 'room';
                state.isolatedRoomId = 10;
                state.isolatedRoomEntityId = 100;
            });

            it('should show the isolated room itself', () => {
                const annotation = createMockAnnotation({ id: 10, type: 'room', roomId: 100 });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should show locations in the isolated room', () => {
                const annotation = createMockAnnotation({ id: 20, type: 'location', roomId: 100 });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should show cabinet runs in the isolated room', () => {
                const annotation = createMockAnnotation({ id: 30, type: 'cabinet_run', roomId: 100 });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should hide locations not in the isolated room', () => {
                const annotation = createMockAnnotation({ id: 21, type: 'location', roomId: 200 });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide cabinets (too deep)', () => {
                const annotation = createMockAnnotation({ id: 40, type: 'cabinet', roomId: 100 });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });
        });

        describe('Location Isolation Mode', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolationLevel = 'location';
                state.isolatedLocationId = 20;
                state.activeLocationId = 200;
            });

            it('should show cabinet runs in the isolated location', () => {
                const annotation = createMockAnnotation({
                    id: 30,
                    type: 'cabinet_run',
                    locationId: 200,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should show cabinets in the isolated location', () => {
                const annotation = createMockAnnotation({
                    id: 40,
                    type: 'cabinet',
                    locationId: 200,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should hide cabinet runs not in the isolated location', () => {
                const annotation = createMockAnnotation({
                    id: 31,
                    type: 'cabinet_run',
                    locationId: 300,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide rooms', () => {
                const annotation = createMockAnnotation({ id: 10, type: 'room' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide other locations', () => {
                const annotation = createMockAnnotation({
                    id: 21,
                    type: 'location',
                    locationId: 300,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });
        });

        describe('Cabinet Run Isolation Mode', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolationLevel = 'cabinet_run';
                state.isolatedCabinetRunId = 30;
            });

            it('should show cabinets in the isolated cabinet run', () => {
                const annotation = createMockAnnotation({
                    id: 40,
                    type: 'cabinet',
                    cabinetRunId: 30,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should hide cabinets not in the isolated cabinet run', () => {
                const annotation = createMockAnnotation({
                    id: 41,
                    type: 'cabinet',
                    cabinetRunId: 31,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide rooms', () => {
                const annotation = createMockAnnotation({ id: 10, type: 'room' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide locations', () => {
                const annotation = createMockAnnotation({ id: 20, type: 'location' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide other cabinet runs', () => {
                const annotation = createMockAnnotation({ id: 31, type: 'cabinet_run' });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });
        });

        describe('View Type Filtering', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolationLevel = 'room';
                state.isolatedRoomId = 10;
                state.isolatedRoomEntityId = 100;
                state.isolationViewType = 'elevation';
                state.isolationOrientation = 'north';
            });

            it('should show annotation matching view type', () => {
                const annotation = createMockAnnotation({
                    id: 20,
                    type: 'location',
                    roomId: 100,
                    viewType: 'elevation',
                    orientation: 'north',
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });

            it('should hide annotation not matching view type', () => {
                const annotation = createMockAnnotation({
                    id: 20,
                    type: 'location',
                    roomId: 100,
                    viewType: 'plan',
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should hide annotation not matching orientation', () => {
                const annotation = createMockAnnotation({
                    id: 20,
                    type: 'location',
                    roomId: 100,
                    viewType: 'elevation',
                    orientation: 'south',
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(false);
            });

            it('should show annotation without view type (default behavior)', () => {
                const annotation = createMockAnnotation({
                    id: 20,
                    type: 'location',
                    roomId: 100,
                    viewType: null,
                });

                expect(isAnnotationVisibleInIsolation(annotation, state)).toBe(true);
            });
        });
    });

    describe('getIsolationBreadcrumbs', () => {
        describe('Normal Mode', () => {
            it('should return empty array when not in isolation mode', () => {
                const breadcrumbs = getIsolationBreadcrumbs(state);

                expect(breadcrumbs).toEqual([]);
            });
        });

        describe('Room Isolation', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolatedRoomName = 'Kitchen';
            });

            it('should return room breadcrumb only', () => {
                const breadcrumbs = getIsolationBreadcrumbs(state);

                expect(breadcrumbs).toHaveLength(1);
                expect(breadcrumbs[0]).toEqual({
                    label: 'Kitchen',
                    level: 'room',
                    icon: '🏠',
                });
            });
        });

        describe('Location Isolation', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolatedRoomName = 'Kitchen';
                state.isolatedLocationName = 'Island';
            });

            it('should return room and location breadcrumbs', () => {
                const breadcrumbs = getIsolationBreadcrumbs(state);

                expect(breadcrumbs).toHaveLength(2);
                expect(breadcrumbs[0].label).toBe('Kitchen');
                expect(breadcrumbs[0].icon).toBe('🏠');
                expect(breadcrumbs[1].label).toBe('Island');
                expect(breadcrumbs[1].icon).toBe('📍');
            });
        });

        describe('Cabinet Run Isolation', () => {
            beforeEach(() => {
                state.isolationMode = true;
                state.isolatedRoomName = 'Kitchen';
                state.isolatedLocationName = 'Island';
                state.isolatedCabinetRunName = 'Upper Cabinets';
            });

            it('should return full breadcrumb trail', () => {
                const breadcrumbs = getIsolationBreadcrumbs(state);

                expect(breadcrumbs).toHaveLength(3);
                expect(breadcrumbs[0]).toEqual({
                    label: 'Kitchen',
                    level: 'room',
                    icon: '🏠',
                });
                expect(breadcrumbs[1]).toEqual({
                    label: 'Island',
                    level: 'location',
                    icon: '📍',
                });
                expect(breadcrumbs[2]).toEqual({
                    label: 'Upper Cabinets',
                    level: 'cabinet_run',
                    icon: '🗄️',
                });
            });
        });

        describe('Edge Cases', () => {
            it('should handle missing room name', () => {
                state.isolationMode = true;
                state.isolatedRoomName = '';
                state.isolatedLocationName = 'Island';

                const breadcrumbs = getIsolationBreadcrumbs(state);

                expect(breadcrumbs).toHaveLength(1);
                expect(breadcrumbs[0].level).toBe('location');
            });

            it('should only include levels that have names', () => {
                state.isolationMode = true;
                state.isolatedRoomName = 'Kitchen';
                state.isolatedLocationName = '';
                state.isolatedCabinetRunName = '';

                const breadcrumbs = getIsolationBreadcrumbs(state);

                expect(breadcrumbs).toHaveLength(1);
                expect(breadcrumbs[0].level).toBe('room');
            });
        });
    });

    describe('updateIsolationMask', () => {
        beforeEach(() => {
            // Mock DOM element
            const mockMaskRects = {
                innerHTML: '',
                appendChild: vi.fn(),
            };
            document.getElementById = vi.fn(() => mockMaskRects);

            state.overlayWidth = 800;
            state.overlayHeight = 600;
            state.annotations = [
                createMockAnnotation({
                    id: 1,
                    type: 'room',
                    roomId: 100,
                    screenX: 100,
                    screenY: 100,
                    screenWidth: 200,
                    screenHeight: 150,
                }),
            ];
        });

        it('should clear existing mask rects', () => {
            const maskRects = document.getElementById('maskRects');
            maskRects.innerHTML = 'existing content';

            updateIsolationMask(state);

            expect(maskRects.innerHTML).toBe('');
        });

        it('should handle missing maskRects element', () => {
            document.getElementById = vi.fn(() => null);

            expect(() => updateIsolationMask(state)).not.toThrow();
        });

        it('should log overlay dimensions', () => {
            updateIsolationMask(state);

            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('📐 [MASK UPDATE] Overlay dimensions: 800 × 600')
            );
        });

        it('should create mask rects for visible annotations', () => {
            const maskRects = document.getElementById('maskRects');

            updateIsolationMask(state);

            expect(maskRects.appendChild).toHaveBeenCalled();
        });

        it('should filter out hidden annotations', () => {
            state.annotations = [
                createMockAnnotation({
                    id: 1,
                    screenX: 100,
                    screenY: 100,
                    screenWidth: 200,
                    screenHeight: 150,
                }),
                createMockAnnotation({
                    id: 2,
                    screenX: 300,
                    screenY: 100,
                    screenWidth: 200,
                    screenHeight: 150,
                }),
            ];
            state.hiddenAnnotations = [2];

            const maskRects = document.getElementById('maskRects');
            updateIsolationMask(state);

            // Should only add 1 rect (annotation 1)
            expect(maskRects.appendChild).toHaveBeenCalledTimes(1);
        });
    });

    describe('Integration Tests', () => {
        it('should properly track visibility in room isolation workflow', () => {
            // Setup annotations
            state.annotations = [
                createMockAnnotation({ id: 1, type: 'room', roomId: 100 }),
                createMockAnnotation({ id: 2, type: 'location', roomId: 100 }),
                createMockAnnotation({ id: 3, type: 'location', roomId: 200 }),
            ];

            // Normal mode - show rooms and locations
            expect(isAnnotationVisibleInIsolation(state.annotations[0], state)).toBe(true);
            expect(isAnnotationVisibleInIsolation(state.annotations[1], state)).toBe(true);
            expect(isAnnotationVisibleInIsolation(state.annotations[2], state)).toBe(true);

            // Enter room isolation
            state.isolationMode = true;
            state.isolationLevel = 'room';
            state.isolatedRoomId = 1;
            state.isolatedRoomEntityId = 100;

            // Only show room 100 and its locations
            expect(isAnnotationVisibleInIsolation(state.annotations[0], state)).toBe(true);
            expect(isAnnotationVisibleInIsolation(state.annotations[1], state)).toBe(true);
            expect(isAnnotationVisibleInIsolation(state.annotations[2], state)).toBe(false);
        });

        it('should provide correct breadcrumbs throughout isolation workflow', () => {
            // Start in normal mode
            expect(getIsolationBreadcrumbs(state)).toEqual([]);

            // Enter room isolation
            state.isolationMode = true;
            state.isolatedRoomName = 'Kitchen';
            expect(getIsolationBreadcrumbs(state)).toHaveLength(1);

            // Enter location isolation
            state.isolatedLocationName = 'Island';
            expect(getIsolationBreadcrumbs(state)).toHaveLength(2);

            // Enter cabinet run isolation
            state.isolatedCabinetRunName = 'Base Cabinets';
            expect(getIsolationBreadcrumbs(state)).toHaveLength(3);

            // Exit isolation
            state.isolationMode = false;
            expect(getIsolationBreadcrumbs(state)).toEqual([]);
        });
    });

    describe('updateIsolationMask - Isolated Entity Detection', () => {
        beforeEach(() => {
            const mockMaskRects = {
                innerHTML: '',
                appendChild: vi.fn(),
            };
            document.getElementById = vi.fn(() => mockMaskRects);
            state.overlayWidth = 800;
            state.overlayHeight = 600;
        });

        it('should find and add isolated room annotation to mask', () => {
            state.isolationMode = true;
            state.isolationLevel = 'room';
            state.isolatedRoomId = 100; // Annotation ID matching entity ID

            state.annotations = [
                createMockAnnotation({
                    id: 100,
                    type: 'room',
                    roomId: 100, // roomId field matches for room type
                    label: 'Kitchen',
                    screenX: 50,
                    screenY: 50,
                    screenWidth: 300,
                    screenHeight: 200,
                }),
            ];

            updateIsolationMask(state);

            const maskRects = document.getElementById('maskRects');
            expect(maskRects.appendChild).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('✓ Added isolated entity boundary to mask: Kitchen')
            );
        });

        it('should find and add isolated location annotation to mask', () => {
            state.isolationMode = true;
            state.isolationLevel = 'location';
            state.isolatedLocationId = 200; // Annotation ID matching entity ID

            state.annotations = [
                createMockAnnotation({
                    id: 200,
                    type: 'location',
                    roomLocationId: 200, // roomLocationId field matches for location type
                    label: 'Island',
                    screenX: 100,
                    screenY: 100,
                    screenWidth: 150,
                    screenHeight: 100,
                }),
            ];

            updateIsolationMask(state);

            const maskRects = document.getElementById('maskRects');
            expect(maskRects.appendChild).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('✓ Added isolated entity boundary to mask: Island')
            );
        });

        it('should find and add isolated cabinet run annotation to mask', () => {
            state.isolationMode = true;
            state.isolationLevel = 'cabinet_run';
            state.isolatedCabinetRunId = 300; // Annotation ID matching entity ID

            state.annotations = [
                createMockAnnotation({
                    id: 300,
                    type: 'cabinet_run',
                    cabinetRunId: 300, // cabinetRunId field matches for cabinet_run type
                    label: 'Upper Cabinets',
                    screenX: 200,
                    screenY: 50,
                    screenWidth: 250,
                    screenHeight: 120,
                }),
            ];

            updateIsolationMask(state);

            const maskRects = document.getElementById('maskRects');
            expect(maskRects.appendChild).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('✓ Added isolated entity boundary to mask: Upper Cabinets')
            );
        });

        it('should not add isolated entity if screenX is undefined', () => {
            state.isolationMode = true;
            state.isolationLevel = 'room';
            state.isolatedRoomId = 10;

            state.annotations = [
                createMockAnnotation({
                    id: 10,
                    type: 'room',
                    roomId: 100,
                    label: 'Kitchen',
                    screenX: undefined, // No screen coordinates yet
                }),
            ];

            updateIsolationMask(state);

            const maskRects = document.getElementById('maskRects');
            // Should not log the "Added isolated entity" message
            expect(console.log).not.toHaveBeenCalledWith(
                expect.stringContaining('✓ Added isolated entity boundary to mask')
            );
        });
    });

    describe('enterIsolationMode', () => {
        let callbacks;

        beforeEach(() => {
            callbacks = createMockCallbacks({
                zoomToFitAnnotation: vi.fn(() => Promise.resolve()),
                $nextTick: vi.fn(() => Promise.resolve()),
                syncOverlayToCanvas: vi.fn(),
            });

            state.expandedNodes = [];
            state.activeViewType = 'plan';
            state.activeOrientation = null;
            state.tree = [
                { id: 100, name: 'Kitchen', entityId: 100 },
                {
                    id: 200,
                    name: 'Living Room',
                    entityId: 200,
                    children: [{ id: 300, name: 'TV Wall', entityId: 300 }],
                },
            ];

            // Mock document.getElementById for mask
            const mockMaskRects = {
                innerHTML: '',
                appendChild: vi.fn(),
            };
            document.getElementById = vi.fn(() => mockMaskRects);
        });

        it('should enter room isolation mode', async () => {
            const roomAnnotation = createMockAnnotation({
                id: 10,
                type: 'room',
                roomId: 100,
                label: 'Kitchen',
            });

            await enterIsolationMode(roomAnnotation, state, callbacks);

            expect(state.isolationMode).toBe(true);
            expect(state.isolationLevel).toBe('room');
            expect(state.isolatedRoomId).toBe(10);
            expect(state.isolatedRoomEntityId).toBe(100);
            expect(state.isolatedRoomName).toBe('Kitchen');
            expect(state.isolationViewType).toBe('plan');
            expect(callbacks.zoomToFitAnnotation).toHaveBeenCalledWith(roomAnnotation);
        });

        it('should enter location isolation mode', async () => {
            const locationAnnotation = createMockAnnotation({
                id: 20,
                type: 'location',
                roomId: 100,
                roomLocationId: 200,
                label: 'Island',
                roomName: 'Kitchen',
            });

            await enterIsolationMode(locationAnnotation, state, callbacks);

            expect(state.isolationMode).toBe(true);
            expect(state.isolationLevel).toBe('location');
            expect(state.isolatedRoomId).toBe(100);
            expect(state.isolatedRoomName).toBe('Kitchen');
            expect(state.isolatedLocationId).toBe(20);
            expect(state.isolatedLocationName).toBe('Island');
            expect(state.activeLocationId).toBe(200);
            expect(callbacks.zoomToFitAnnotation).toHaveBeenCalledWith(locationAnnotation);
        });

        it('should enter cabinet run isolation mode', async () => {
            const cabinetRunAnnotation = createMockAnnotation({
                id: 30,
                type: 'cabinet_run',
                roomId: 100,
                locationId: 200,
                label: 'Upper Cabinets',
                roomName: 'Kitchen',
                locationName: 'Island',
            });

            await enterIsolationMode(cabinetRunAnnotation, state, callbacks);

            expect(state.isolationMode).toBe(true);
            expect(state.isolationLevel).toBe('cabinet_run');
            expect(state.isolatedRoomId).toBe(100);
            expect(state.isolatedRoomName).toBe('Kitchen');
            expect(state.isolatedLocationId).toBe(200);
            expect(state.isolatedLocationName).toBe('Island');
            expect(state.isolatedCabinetRunId).toBe(30);
            expect(state.isolatedCabinetRunName).toBe('Upper Cabinets');
            expect(callbacks.zoomToFitAnnotation).toHaveBeenCalledWith(cabinetRunAnnotation);
        });

        it('should store isolation view context', async () => {
            state.activeViewType = 'elevation';
            state.activeOrientation = 'north';

            const roomAnnotation = createMockAnnotation({
                id: 10,
                type: 'room',
                roomId: 100,
                label: 'Kitchen',
            });

            await enterIsolationMode(roomAnnotation, state, callbacks);

            expect(state.isolationViewType).toBe('elevation');
            expect(state.isolationOrientation).toBe('north');
        });

        it('should expand isolated nodes in tree', async () => {
            const locationAnnotation = createMockAnnotation({
                id: 20,
                type: 'location',
                roomId: 100,
                roomLocationId: 200,
                label: 'Island',
                roomName: 'Kitchen',
            });

            await enterIsolationMode(locationAnnotation, state, callbacks);

            expect(state.expandedNodes).toContain(100); // Room
            expect(state.expandedNodes).toContain(20); // Location
        });

        it('should select the isolated node', async () => {
            const locationAnnotation = createMockAnnotation({
                id: 20,
                type: 'location',
                roomId: 100,
                roomLocationId: 200,
                label: 'Island',
                roomName: 'Kitchen',
            });

            await enterIsolationMode(locationAnnotation, state, callbacks);

            expect(state.selectedNodeId).toBe(20); // Location annotation ID
        });

        it('should call all callback functions in sequence', async () => {
            const roomAnnotation = createMockAnnotation({
                id: 10,
                type: 'room',
                roomId: 100,
                label: 'Kitchen',
            });

            await enterIsolationMode(roomAnnotation, state, callbacks);

            expect(callbacks.zoomToFitAnnotation).toHaveBeenCalled();
            expect(callbacks.$nextTick).toHaveBeenCalled();
            expect(callbacks.syncOverlayToCanvas).toHaveBeenCalled();
        });

        it('should lookup room name from tree when not in annotation', async () => {
            const locationAnnotation = createMockAnnotation({
                id: 20,
                type: 'location',
                roomId: 100,
                roomLocationId: 200,
                label: 'Island',
                // No roomName provided
            });

            await enterIsolationMode(locationAnnotation, state, callbacks);

            expect(state.isolatedRoomName).toBe('Kitchen'); // Found from tree
        });

        it('should lookup location name from tree when not in annotation', async () => {
            const cabinetRunAnnotation = createMockAnnotation({
                id: 30,
                type: 'cabinet_run',
                roomId: 200,
                locationId: 300, // Match tree location ID
                label: 'Upper Cabinets',
                roomName: 'Living Room',
                // No locationName provided
            });

            await enterIsolationMode(cabinetRunAnnotation, state, callbacks);

            expect(state.isolatedLocationName).toBe('TV Wall'); // Found from tree
        });
    });

    describe('exitIsolationMode', () => {
        let callbacks;

        beforeEach(() => {
            callbacks = createMockCallbacks({
                clearContext: vi.fn(),
                resetZoom: vi.fn(() => Promise.resolve()),
            });

            // Set up isolation state
            state.isolationMode = true;
            state.isolationLevel = 'location';
            state.isolatedRoomId = 10;
            state.isolatedRoomName = 'Kitchen';
            state.isolatedLocationId = 20;
            state.isolatedLocationName = 'Island';
            state.isolationViewType = 'plan';
            state.isolationOrientation = null;
            state.selectedNodeId = 20;
            state.hiddenAnnotations = [1, 2, 3];

            // Mock document.getElementById for mask
            const mockMaskRects = {
                innerHTML: '',
                appendChild: vi.fn(),
            };
            document.getElementById = vi.fn(() => mockMaskRects);
        });

        it('should clear all isolation state', async () => {
            await exitIsolationMode(state, callbacks);

            expect(state.isolationMode).toBe(false);
            expect(state.isolationLevel).toBe(null);
            expect(state.isolatedRoomId).toBe(null);
            expect(state.isolatedRoomName).toBe('');
            expect(state.isolatedLocationId).toBe(null);
            expect(state.isolatedLocationName).toBe('');
            expect(state.isolatedCabinetRunId).toBe(null);
            expect(state.isolatedCabinetRunName).toBe('');
            expect(state.isolationViewType).toBe(null);
            expect(state.isolationOrientation).toBe(null);
        });

        it('should call clearContext callback', async () => {
            await exitIsolationMode(state, callbacks);

            expect(callbacks.clearContext).toHaveBeenCalled();
        });

        it('should deselect node', async () => {
            await exitIsolationMode(state, callbacks);

            expect(state.selectedNodeId).toBe(null);
        });

        it('should clear hidden annotations', async () => {
            await exitIsolationMode(state, callbacks);

            expect(state.hiddenAnnotations).toEqual([]);
        });

        it('should call resetZoom callback', async () => {
            await exitIsolationMode(state, callbacks);

            expect(callbacks.resetZoom).toHaveBeenCalled();
        });

        it('should log exit message', async () => {
            await exitIsolationMode(state, callbacks);

            expect(console.log).toHaveBeenCalledWith('🔓 Exiting isolation mode');
            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('✓ Returned to normal view with reset zoom')
            );
        });

        it('should handle missing callbacks gracefully', async () => {
            const emptyCallbacks = {};

            await expect(exitIsolationMode(state, emptyCallbacks)).resolves.not.toThrow();
        });
    });
});
