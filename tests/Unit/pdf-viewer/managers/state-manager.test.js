/**
 * State Manager Tests
 * Tests for state initialization and utility functions
 */

import { describe, it, expect, beforeEach } from 'vitest';
import {
    createInitialState,
    getColorForType,
    getViewTypeLabel,
    getViewTypeColor,
    selectAnnotationContext,
} from '@pdf-viewer/managers/state-manager.js';
import { createMockState, createMockAnnotation } from '../../mocks/pdf-viewer-mocks.js';

describe('State Manager', () => {
    describe('createInitialState', () => {
        it('should create initial state with default values', () => {
            const config = {
                pdfUrl: '/test.pdf',
                pageNumber: 1,
                pdfPageId: 123,
                projectId: 456,
            };

            const state = createInitialState(config);

            expect(state.pdfUrl).toBe('/test.pdf');
            expect(state.pageNumber).toBe(1);
            expect(state.currentPage).toBe(1);
            expect(state.pdfPageId).toBe(123);
            expect(state.projectId).toBe(456);
            expect(state.totalPages).toBe(1);
            expect(state.pdfReady).toBe(false);
            expect(state.systemReady).toBe(false);
            expect(state.zoomLevel).toBe(1.0);
            expect(state.isolationMode).toBe(false);
            expect(state.annotations).toEqual([]);
            expect(state.historyStack).toEqual([]);
            expect(state.historyIndex).toBe(-1);
        });

        it('should override defaults with config values', () => {
            const config = {
                pdfUrl: '/custom.pdf',
                pageNumber: 5,
                pdfPageId: 999,
                projectId: 888,
                totalPages: 10,
                pageType: 'floor_plan',
                pageMap: { 1: 'cover', 2: 'floor_plan' },
            };

            const state = createInitialState(config);

            expect(state.totalPages).toBe(10);
            expect(state.pageType).toBe('floor_plan');
            expect(state.pageMap).toEqual({ 1: 'cover', 2: 'floor_plan' });
        });

        it('should initialize with empty arrays and objects', () => {
            const state = createInitialState({ pdfUrl: '/test.pdf', projectId: 1 });

            expect(Array.isArray(state.annotations)).toBe(true);
            expect(Array.isArray(state.tree)).toBe(true);
            expect(Array.isArray(state.expandedNodes)).toBe(true);
            expect(Array.isArray(state.hiddenAnnotations)).toBe(true);
            expect(typeof state.filters).toBe('object');
            expect(typeof state.contextMenu).toBe('object');
        });
    });

    describe('getColorForType', () => {
        it('should return correct color for room', () => {
            expect(getColorForType('room')).toBe('#f59e0b');
        });

        it('should return correct color for location', () => {
            expect(getColorForType('location')).toBe('#9333ea');
        });

        it('should return correct color for cabinet_run', () => {
            expect(getColorForType('cabinet_run')).toBe('#3b82f6');
        });

        it('should return correct color for cabinet', () => {
            expect(getColorForType('cabinet')).toBe('#10b981');
        });

        it('should return default color for unknown type', () => {
            expect(getColorForType('unknown')).toBe('#10b981'); // Defaults to cabinet color
        });

        it('should handle null or undefined type', () => {
            expect(getColorForType(null)).toBe('#10b981');
            expect(getColorForType(undefined)).toBe('#10b981');
        });
    });

    describe('getViewTypeLabel', () => {
        it('should return label for plan view', () => {
            expect(getViewTypeLabel('plan')).toBe('Plan View');
        });

        it('should return label for elevation view', () => {
            expect(getViewTypeLabel('elevation')).toBe('Elevation View');
        });

        it('should return label for section view', () => {
            expect(getViewTypeLabel('section')).toBe('Section View');
        });

        it('should return label for detail view', () => {
            expect(getViewTypeLabel('detail')).toBe('Detail View');
        });

        it('should return unknown view for invalid type', () => {
            expect(getViewTypeLabel('invalid')).toBe('Unknown View');
        });

        it('should include orientation for elevation view', () => {
            expect(getViewTypeLabel('elevation', 'front')).toBe('Elevation View - Front');
            expect(getViewTypeLabel('elevation', 'back')).toBe('Elevation View - Back');
            expect(getViewTypeLabel('elevation', 'left')).toBe('Elevation View - Left');
            expect(getViewTypeLabel('elevation', 'right')).toBe('Elevation View - Right');
        });

        it('should include orientation for section view', () => {
            expect(getViewTypeLabel('section', 'A-A')).toBe('Section View - A-A');
            expect(getViewTypeLabel('section', 'B-B')).toBe('Section View - B-B');
        });

        it('should not include orientation for plan view', () => {
            expect(getViewTypeLabel('plan', 'north')).toBe('Plan View');
        });
    });

    describe('getViewTypeColor', () => {
        it('should return CSS variable for plan', () => {
            expect(getViewTypeColor('plan')).toBe('var(--primary-600)');
        });

        it('should return CSS variable for elevation', () => {
            expect(getViewTypeColor('elevation')).toBe('var(--warning-600)');
        });

        it('should return CSS variable for section', () => {
            expect(getViewTypeColor('section')).toBe('var(--info-600)');
        });

        it('should return CSS variable for detail', () => {
            expect(getViewTypeColor('detail')).toBe('var(--success-600)');
        });

        it('should return default color for unknown type', () => {
            expect(getViewTypeColor('unknown')).toBe('var(--gray-600)');
        });
    });

    describe('selectAnnotationContext', () => {
        let state, callbacks;

        beforeEach(() => {
            state = createMockState();
            callbacks = {
                getRoomNameById: (id) => `Room ${id}`,
                getLocationNameById: (id) => `Location ${id}`,
            };
        });

        it('should set room context when clicking room annotation', () => {
            const roomAnno = createMockAnnotation({
                id: 1,
                type: 'room',
                label: 'Kitchen',
                roomId: 1,
            });

            selectAnnotationContext(roomAnno, state, callbacks);

            expect(state.activeRoomId).toBe(1);
            expect(state.activeRoomName).toBe('Kitchen');
            expect(state.activeLocationId).toBeNull();
            expect(state.activeLocationName).toBe('');
            expect(state.roomSearchQuery).toBe('Kitchen');
            expect(state.locationSearchQuery).toBe('');
            expect(state.activeAnnotationId).toBe(1);
            expect(state.selectedNodeId).toBe(1);
        });

        it('should set location context when clicking location annotation', () => {
            const locationAnno = createMockAnnotation({
                id: 2,
                type: 'location',
                label: 'Island',
                roomId: 1,
                roomName: 'Kitchen',
            });

            selectAnnotationContext(locationAnno, state, callbacks);

            expect(state.activeRoomId).toBe(1);
            expect(state.activeRoomName).toBe('Kitchen');
            expect(state.activeLocationId).toBe(2);
            expect(state.activeLocationName).toBe('Island');
            expect(state.roomSearchQuery).toBe('Kitchen');
            expect(state.locationSearchQuery).toBe('Island');
        });

        it('should set cabinet run context when clicking cabinet run annotation', () => {
            const cabinetRunAnno = createMockAnnotation({
                id: 3,
                type: 'cabinet_run',
                label: 'Upper Cabinets',
                roomId: 1,
                roomName: 'Kitchen',
                locationId: 2,
                locationName: 'Island',
            });

            selectAnnotationContext(cabinetRunAnno, state, callbacks);

            expect(state.activeRoomId).toBe(1);
            expect(state.activeRoomName).toBe('Kitchen');
            expect(state.activeLocationId).toBe(2);
            expect(state.activeLocationName).toBe('Island');
            expect(state.roomSearchQuery).toBe('Kitchen');
            expect(state.locationSearchQuery).toBe('Island');
        });

        it('should use callback to get room name if not in annotation', () => {
            const locationAnno = createMockAnnotation({
                id: 2,
                type: 'location',
                label: 'Island',
                roomId: 5,
                roomName: null, // Missing room name
            });

            selectAnnotationContext(locationAnno, state, callbacks);

            expect(state.activeRoomName).toBe('Room 5');
        });

        it('should not change context during resize operation', () => {
            state.isResizing = true;
            state.activeRoomId = 99;
            state.activeRoomName = 'Original Room';

            const roomAnno = createMockAnnotation({
                id: 1,
                type: 'room',
                label: 'New Room',
            });

            selectAnnotationContext(roomAnno, state, callbacks);

            // Context should not change
            expect(state.activeRoomId).toBe(99);
            expect(state.activeRoomName).toBe('Original Room');
        });

        it('should not change context during move operation', () => {
            state.isMoving = true;
            state.activeLocationId = 88;

            const locationAnno = createMockAnnotation({
                id: 2,
                type: 'location',
                label: 'New Location',
            });

            selectAnnotationContext(locationAnno, state, callbacks);

            // Context should not change
            expect(state.activeLocationId).toBe(88);
        });

        it('should set cabinet context with full hierarchy', () => {
            const cabinetAnno = createMockAnnotation({
                id: 4,
                type: 'cabinet',
                label: 'Base Cabinet B1',
                roomId: 1,
                roomName: 'Kitchen',
                locationId: 2,
                locationName: 'Island',
            });

            selectAnnotationContext(cabinetAnno, state, callbacks);

            expect(state.activeRoomId).toBe(1);
            expect(state.activeRoomName).toBe('Kitchen');
            expect(state.activeLocationId).toBe(2);
            expect(state.activeLocationName).toBe('Island');
        });
    });
});
