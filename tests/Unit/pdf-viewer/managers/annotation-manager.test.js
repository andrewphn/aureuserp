/**
 * Annotation Manager Tests
 * Tests for annotation loading, saving, and CRUD operations
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import {
    loadAnnotations,
    saveAnnotations,
    deleteAnnotation,
    editAnnotation,
    findAnnotationByEntity,
    checkForDuplicateEntity,
    highlightAnnotation,
    getAnnotationZIndex,
    toggleLockAnnotation,
} from '@pdf-viewer/managers/annotation-manager.js';
import { createMockState, createMockRefs, createMockAnnotation } from '../../mocks/pdf-viewer-mocks.js';

// Mock dependencies
vi.mock('@pdf-viewer/managers/state-manager.js', () => ({
    getColorForType: vi.fn((type) => {
        const colors = {
            room: '#3b82f6',
            location: '#10b981',
            cabinet_run: '#f59e0b',
            cabinet: '#8b5cf6',
        };
        return colors[type] || '#6b7280';
    }),
}));

vi.mock('@pdf-viewer/managers/coordinate-transform.js', () => ({
    pdfToScreen: vi.fn((x, y, width, height) => ({ x, y, width, height })),
}));

vi.mock('@pdf-viewer/utilities.js', () => ({
    getCsrfToken: vi.fn(() => 'mock-csrf-token'),
}));

// Mock window.Livewire
global.window = global.window || {};
global.window.Livewire = {
    dispatch: vi.fn(),
};

describe('Annotation Manager', () => {
    let state, refs;
    let mockFetch, mockAlert, mockConfirm;

    beforeEach(() => {
        state = createMockState({
            projectId: 123,
            pdfPageId: 456,
            currentPage: 1,
            pageDimensions: { width: 800, height: 600 },
            annotations: [],
            hiddenAnnotations: [],
            isolationMode: false,
            activeRoomId: null,
            activeLocationId: null,
            activeCabinetRunId: null,
            activeCabinetId: null,
        });

        refs = createMockRefs();

        // Mock console methods
        vi.spyOn(console, 'log').mockImplementation(() => {});
        vi.spyOn(console, 'error').mockImplementation(() => {});

        // Mock global fetch
        mockFetch = vi.fn();
        global.fetch = mockFetch;

        // Mock global alert
        mockAlert = vi.fn();
        global.alert = mockAlert;

        // Mock global confirm
        mockConfirm = vi.fn(() => true);
        global.confirm = mockConfirm;
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('loadAnnotations', () => {
        it('should load annotations from API', async () => {
            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    annotations: [
                        {
                            id: 1,
                            annotation_type: 'room',
                            x: 0.1,
                            y: 0.2,
                            width: 0.3,
                            height: 0.4,
                            text: 'Kitchen',
                            color: '#3b82f6',
                            room_id: 1,
                        },
                    ],
                }),
            });

            await loadAnnotations(state, refs);

            expect(mockFetch).toHaveBeenCalledWith('/api/pdf/page/456/annotations');
            expect(state.annotations).toHaveLength(1);
            expect(state.annotations[0].type).toBe('room');
            expect(state.annotations[0].label).toBe('Kitchen');
        });

        it('should clear existing annotations before loading', async () => {
            state.annotations = [createMockAnnotation({ id: 999 })];

            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    annotations: [],
                }),
            });

            await loadAnnotations(state, refs);

            expect(state.annotations).toHaveLength(0);
        });

        it('should handle empty annotation list', async () => {
            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    annotations: [],
                }),
            });

            await loadAnnotations(state, refs);

            expect(state.annotations).toHaveLength(0);
        });

        it('should handle API error', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await loadAnnotations(state, refs);

            expect(console.error).toHaveBeenCalledWith('Failed to load annotations:', expect.any(Error));
            expect(state.error).toBe('Network error');
        });

        it('should transform annotation coordinates', async () => {
            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    annotations: [
                        {
                            id: 1,
                            annotation_type: 'room',
                            x: 0.5,
                            y: 0.5,
                            width: 0.2,
                            height: 0.3,
                            text: 'Test',
                        },
                    ],
                }),
            });

            await loadAnnotations(state, refs);

            const anno = state.annotations[0];
            expect(anno.pdfX).toBe(400); // 0.5 * 800
            expect(anno.pdfY).toBe(300); // (1 - 0.5) * 600
            expect(anno.pdfWidth).toBe(160); // 0.2 * 800
            expect(anno.pdfHeight).toBe(180); // 0.3 * 600
        });

        it('should log loading activity', async () => {
            mockFetch.mockResolvedValueOnce({
                json: async () => ({
                    success: true,
                    annotations: [],
                }),
            });

            await loadAnnotations(state, refs);

            expect(console.log).toHaveBeenCalledWith(
                expect.stringContaining('📥 Loading annotations for page 1 (pdfPageId: 456)')
            );
        });
    });

    describe('saveAnnotations', () => {
        it('should save annotations to API', async () => {
            state.annotations = [
                createMockAnnotation({
                    id: 'temp_1',
                    type: 'room',
                    label: 'Kitchen',
                    normalizedX: 0.1,
                    normalizedY: 0.2,
                    pdfWidth: 160,
                    pdfHeight: 180,
                }),
            ];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 1,
                }),
            });

            await saveAnnotations(state, null, true);

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/pdf/page/456/annotations',
                expect.objectContaining({
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': 'mock-csrf-token',
                    },
                })
            );
        });

        it('should show alert on successful save when not silent', async () => {
            state.annotations = [createMockAnnotation({ id: 'temp_1' })];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 1,
                }),
            });

            await saveAnnotations(state, null, false);

            expect(mockAlert).toHaveBeenCalledWith('Successfully saved 1 annotations!');
        });

        it('should not show alert when silent', async () => {
            state.annotations = [createMockAnnotation({ id: 'temp_1' })];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 1,
                }),
            });

            await saveAnnotations(state, null, true);

            expect(mockAlert).not.toHaveBeenCalled();
        });

        it('should call reload callback after save', async () => {
            state.annotations = [createMockAnnotation({ id: 'temp_1' })];
            const reloadCallback = vi.fn();

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 1,
                }),
            });

            await saveAnnotations(state, reloadCallback, true);

            expect(reloadCallback).toHaveBeenCalled();
        });

        it('should handle API error', async () => {
            state.annotations = [createMockAnnotation({ id: 'temp_1' })];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: false,
                    error: 'Database error',
                }),
            });

            await expect(saveAnnotations(state, null, true)).rejects.toThrow('Database error');
            expect(mockAlert).toHaveBeenCalledWith('Error saving annotations: Database error');
        });

        it('should handle network error', async () => {
            state.annotations = [createMockAnnotation({ id: 'temp_1' })];

            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await expect(saveAnnotations(state, null, true)).rejects.toThrow('Network error');
        });

        it('should link new cabinet annotation to active cabinet', async () => {
            state.activeCabinetId = 99;
            state.activeCabinetRunId = 88;
            state.activeLocationId = 77;
            state.activeRoomId = 66;

            state.annotations = [
                createMockAnnotation({
                    id: 'temp_1',
                    type: 'cabinet',
                    label: 'Base Cabinet B1',
                }),
            ];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 1,
                }),
            });

            await saveAnnotations(state, null, true);

            const requestBody = JSON.parse(mockFetch.mock.calls[0][1].body);
            expect(requestBody.annotations[0].cabinet_specification_id).toBe(99);
            expect(requestBody.annotations[0].cabinet_run_id).toBe(88);
        });
    });

    describe('deleteAnnotation', () => {
        it('should delete temporary annotation without API call', async () => {
            const annotation = createMockAnnotation({ id: 'temp_1', label: 'Test' });
            state.annotations = [annotation];

            await deleteAnnotation(annotation, state);

            expect(state.annotations).toHaveLength(0);
            expect(mockFetch).not.toHaveBeenCalled();
        });

        it('should confirm before deleting', async () => {
            const annotation = createMockAnnotation({ id: 'temp_1', label: 'Test' });
            state.annotations = [annotation];

            await deleteAnnotation(annotation, state);

            expect(mockConfirm).toHaveBeenCalledWith('Delete "Test"?');
        });

        it('should not delete if user cancels', async () => {
            mockConfirm.mockReturnValueOnce(false);
            const annotation = createMockAnnotation({ id: 'temp_1', label: 'Test' });
            state.annotations = [annotation];

            await deleteAnnotation(annotation, state);

            expect(state.annotations).toHaveLength(1);
        });

        it('should delete persisted annotation via API', async () => {
            const annotation = createMockAnnotation({ id: 1, label: 'Test' });
            state.annotations = [annotation];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                }),
            });

            const refreshTree = vi.fn();
            await deleteAnnotation(annotation, state, refreshTree);

            expect(mockFetch).toHaveBeenCalledWith(
                '/api/pdf/page/annotations/1',
                expect.objectContaining({
                    method: 'DELETE',
                })
            );
            expect(state.annotations).toHaveLength(0);
            expect(refreshTree).toHaveBeenCalled();
        });
    });

    describe('editAnnotation', () => {
        it('should dispatch Livewire event with annotation', () => {
            const annotation = createMockAnnotation({ id: 1, label: 'Kitchen' });

            editAnnotation(annotation, state);

            expect(window.Livewire.dispatch).toHaveBeenCalledWith(
                'edit-annotation',
                {
                    annotation: expect.objectContaining({
                        id: 1,
                        label: 'Kitchen',
                        pdfPageId: 456,
                        projectId: 123,
                    }),
                }
            );
        });

        it('should add context to annotation', () => {
            const annotation = createMockAnnotation({ id: 1 });

            editAnnotation(annotation, state);

            const dispatchCall = window.Livewire.dispatch.mock.calls[0];
            const passedAnnotation = dispatchCall[1].annotation;

            expect(passedAnnotation.pdfPageId).toBe(456);
            expect(passedAnnotation.projectId).toBe(123);
        });
    });

    describe('findAnnotationByEntity', () => {
        beforeEach(() => {
            state.annotations = [
                createMockAnnotation({ id: 1, type: 'room', roomId: 10 }),
                createMockAnnotation({ id: 2, type: 'location', roomLocationId: 20 }),
                createMockAnnotation({ id: 3, type: 'cabinet_run', cabinetRunId: 30 }),
                createMockAnnotation({ id: 4, type: 'cabinet', cabinetRunId: 30, cabinetSpecId: 40 }),
            ];
        });

        it('should find room annotation by roomId and type', () => {
            const anno = findAnnotationByEntity('room', 10, state);
            expect(anno).toBeDefined();
            expect(anno.id).toBe(1);
            expect(anno.type).toBe('room');
        });

        it('should find location annotation by roomLocationId and type', () => {
            const anno = findAnnotationByEntity('room_location', 20, state);
            expect(anno).toBeDefined();
            expect(anno.id).toBe(2);
            expect(anno.type).toBe('location');
        });

        it('should find cabinet run annotation by cabinetRunId and type', () => {
            const anno = findAnnotationByEntity('cabinet_run', 30, state);
            expect(anno).toBeDefined();
            expect(anno.id).toBe(3);
            expect(anno.type).toBe('cabinet_run');
        });

        it('should return null if entity ID not found', () => {
            const anno = findAnnotationByEntity('room', 999, state);
            expect(anno).toBeNull();
        });

        it('should return null if entityId is null or undefined', () => {
            expect(findAnnotationByEntity('room', null, state)).toBeNull();
            expect(findAnnotationByEntity('room', undefined, state)).toBeNull();
        });

        it('should return null if annotations array is null', () => {
            state.annotations = null;
            expect(findAnnotationByEntity('room', 10, state)).toBeNull();
        });
    });

    describe('checkForDuplicateEntity', () => {
        beforeEach(() => {
            state.annotations = [
                createMockAnnotation({ id: 1, type: 'room', roomId: 10 }),
                createMockAnnotation({ id: 2, type: 'location', roomLocationId: 20 }),
                createMockAnnotation({ id: 3, type: 'cabinet_run', cabinetRunId: 30 }),
            ];
        });

        it('should return existing annotation for duplicate room', () => {
            state.activeRoomId = 10;
            const result = checkForDuplicateEntity('room', state);
            expect(result).toBeDefined();
            expect(result.id).toBe(1);
            expect(result.type).toBe('room');
        });

        it('should return null when no duplicate exists', () => {
            state.activeRoomId = 99;
            const result = checkForDuplicateEntity('room', state);
            expect(result).toBeNull();
        });

        it('should return existing annotation for duplicate location', () => {
            state.activeLocationId = 20;
            const result = checkForDuplicateEntity('location', state);
            expect(result).toBeDefined();
            expect(result.id).toBe(2);
        });

        it('should return null for cabinet draw mode (allows multiple)', () => {
            state.activeCabinetId = 40;
            const result = checkForDuplicateEntity('cabinet', state);
            expect(result).toBeNull();
        });

        it('should return null when no entity selected (creating new)', () => {
            state.activeRoomId = null;
            const result = checkForDuplicateEntity('room', state);
            expect(result).toBeNull();
        });

        it('should return null if annotations array is null', () => {
            state.annotations = null;
            state.activeRoomId = 10;
            const result = checkForDuplicateEntity('room', state);
            expect(result).toBeNull();
        });
    });

    describe('highlightAnnotation', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('should change annotation color to red', () => {
            const annotation = createMockAnnotation({ id: 1, color: '#3b82f6', label: 'Test' });

            highlightAnnotation(annotation, state);

            expect(annotation.color).toBe('#ff0000');
        });

        it('should restore original color after 2 seconds', () => {
            const annotation = createMockAnnotation({ id: 1, color: '#3b82f6', label: 'Test' });

            highlightAnnotation(annotation, state);

            expect(annotation.color).toBe('#ff0000');

            vi.advanceTimersByTime(2000);

            expect(annotation.color).toBe('#3b82f6');
        });

        it('should log highlight activity', () => {
            const annotation = createMockAnnotation({ id: 1, label: 'Kitchen' });

            highlightAnnotation(annotation, state);

            expect(console.log).toHaveBeenCalledWith('🎯 Highlighted annotation: Kitchen');
        });
    });

    describe('getAnnotationZIndex', () => {
        it('should return 100 for selected annotation', () => {
            const annotation = createMockAnnotation({ id: 1 });
            state.selectedAnnotation = annotation;

            const zIndex = getAnnotationZIndex(annotation, state);
            expect(zIndex).toBe(100);
        });

        it('should return 100 for active annotation', () => {
            const annotation = createMockAnnotation({ id: 1 });
            state.activeAnnotationId = 1;

            const zIndex = getAnnotationZIndex(annotation, state);
            expect(zIndex).toBe(100);
        });

        it('should return 10 for non-selected/non-active annotation', () => {
            const annotation = createMockAnnotation({ id: 1 });
            state.selectedAnnotation = null;
            state.activeAnnotationId = null;

            const zIndex = getAnnotationZIndex(annotation, state);
            expect(zIndex).toBe(10);
        });

        it('should return 10 when different annotation is selected', () => {
            const annotation = createMockAnnotation({ id: 1 });
            const otherAnnotation = createMockAnnotation({ id: 2 });
            state.selectedAnnotation = otherAnnotation;

            const zIndex = getAnnotationZIndex(annotation, state);
            expect(zIndex).toBe(10);
        });
    });

    describe('toggleLockAnnotation', () => {
        it('should lock unlocked annotation', () => {
            const annotation = createMockAnnotation({ id: 1, locked: false });

            toggleLockAnnotation(annotation, state);

            expect(annotation.locked).toBe(true);
        });

        it('should unlock locked annotation', () => {
            const annotation = createMockAnnotation({ id: 1, locked: true });

            toggleLockAnnotation(annotation, state);

            expect(annotation.locked).toBe(false);
        });

        it('should log lock state change when locking', () => {
            const annotation = createMockAnnotation({ id: 1, label: 'Test', locked: false });

            toggleLockAnnotation(annotation, state);

            expect(console.log).toHaveBeenCalledWith('🔒 Locked annotation: Test');
        });

        it('should log unlock state change when unlocking', () => {
            const annotation = createMockAnnotation({ id: 1, label: 'Test', locked: true });

            toggleLockAnnotation(annotation, state);

            expect(console.log).toHaveBeenCalledWith('🔓 Unlocked annotation: Test');
        });

        it('should clear active state when locking active annotation', () => {
            const annotation = createMockAnnotation({ id: 1, locked: false });
            state.activeAnnotationId = 1;
            state.selectedAnnotation = annotation;

            toggleLockAnnotation(annotation, state);

            expect(annotation.locked).toBe(true);
            expect(state.activeAnnotationId).toBeNull();
            expect(state.selectedAnnotation).toBeNull();
        });

        it('should not clear active state when unlocking', () => {
            const annotation = createMockAnnotation({ id: 1, locked: true });
            state.activeAnnotationId = 2;
            state.selectedAnnotation = createMockAnnotation({ id: 2 });

            toggleLockAnnotation(annotation, state);

            expect(annotation.locked).toBe(false);
            expect(state.activeAnnotationId).toBe(2);
            expect(state.selectedAnnotation).toBeDefined();
        });
    });

    describe('Edge Cases', () => {
        it('should handle empty annotations array in saveAnnotations', async () => {
            state.annotations = [];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 0,
                }),
            });

            await expect(saveAnnotations(state, null, true)).resolves.not.toThrow();
        });

        it('should handle HTML error response from server', async () => {
            state.annotations = [createMockAnnotation({ id: 'temp_1' })];

            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                statusText: 'Internal Server Error',
                headers: {
                    get: (key) => (key === 'content-type' ? 'text/html' : null),
                },
                text: async () => '<html><body>Error</body></html>',
            });

            await expect(saveAnnotations(state, null, true)).rejects.toThrow('Server error (500)');
        });

        it('should handle mixed temporary and persisted annotations', async () => {
            state.annotations = [
                createMockAnnotation({ id: 'temp_1' }),
                createMockAnnotation({ id: 1 }),
            ];

            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    count: 2,
                }),
            });

            await expect(saveAnnotations(state, null, true)).resolves.not.toThrow();
        });
    });
});
