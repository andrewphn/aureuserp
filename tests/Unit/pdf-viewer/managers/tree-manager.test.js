/**
 * Tree Manager Tests
 * Tests for project tree loading and navigation
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import {
    loadTree,
    refreshTree,
    toggleNode,
    isExpanded,
    selectNode,
    navigateToNodePage,
    showContextMenu,
    deleteTreeNode,
    navigateOnDoubleClick,
    buildAnnotationTree,
    getPageGroupedAnnotations,
    getRoomNameById,
    getLocationNameById,
} from '@pdf-viewer/managers/tree-manager.js';
import { createMockState } from '../../mocks/pdf-viewer-mocks.js';

// Mock utilities
vi.mock('@pdf-viewer/utilities.js', () => ({
    getCsrfToken: vi.fn(() => 'test-csrf-token'),
}));

describe('Tree Manager', () => {
    let state;
    let mockFetch;

    beforeEach(() => {
        state = createMockState({
            projectId: 123,
            loading: false,
            tree: [],
            treeReady: false,
            error: null,
            expandedNodes: [],
            selectedNodeId: null,
            selectedPath: [],
            activeRoomId: null,
            activeRoomName: '',
            activeLocationId: null,
            activeLocationName: '',
            activeCabinetRunId: null,
            activeCabinetRunName: '',
            activeCabinetId: null,
            activeCabinetName: '',
            roomSearchQuery: '',
            locationSearchQuery: '',
            currentPage: 1,
            activeViewType: 'plan',
            drawMode: null,
            contextMenu: {
                show: false,
                x: 0,
                y: 0,
                nodeId: null,
                nodeType: null,
                nodeName: null,
                parentRoomId: null,
                parentLocationId: null,
            },
            _resizeLockout: false,
            isResizing: false,
            isMoving: false,
            pageMap: { 1: 123, 2: 124 },
            annotations: [],
            filteredAnnotations: null,
        });

        // Mock fetch
        mockFetch = vi.fn(() =>
            Promise.resolve({
                json: () =>
                    Promise.resolve([
                        {
                            id: 1,
                            name: 'Kitchen',
                            type: 'room',
                            pages: [{ page: 1, viewType: 'plan' }],
                            children: [
                                {
                                    id: 10,
                                    name: 'Island',
                                    type: 'room_location',
                                    pages: [{ page: 1, viewType: 'plan' }],
                                    children: [
                                        {
                                            id: 100,
                                            name: 'Base Cabinets',
                                            type: 'cabinet_run',
                                            pages: [{ page: 1, viewType: 'plan' }],
                                        },
                                    ],
                                },
                            ],
                        },
                        {
                            id: 2,
                            name: 'Bathroom',
                            type: 'room',
                            pages: [{ page: 2, viewType: 'plan' }],
                        },
                    ]),
            })
        );
        global.fetch = mockFetch;

        // Mock console methods
        vi.spyOn(console, 'log').mockImplementation(() => {});
        vi.spyOn(console, 'error').mockImplementation(() => {});

        // Mock confirm
        global.confirm = vi.fn(() => true);

        // Mock alert
        global.alert = vi.fn();

        // Use fake timers for setTimeout
        vi.useFakeTimers();
    });

    describe('loadTree', () => {
        it('should load tree from server', async () => {
            await loadTree(state);

            expect(mockFetch).toHaveBeenCalledWith('/api/projects/123/tree');
            expect(state.tree).toHaveLength(2);
            expect(state.tree[0].name).toBe('Kitchen');
            expect(state.tree[1].name).toBe('Bathroom');
            expect(state.treeReady).toBe(true);
            expect(console.log).toHaveBeenCalledWith('✓ Tree loaded:', state.tree);
        });

        it('should set loading state during load', async () => {
            const promise = loadTree(state);
            expect(state.loading).toBe(true);
            await promise;
            expect(state.loading).toBe(false);
        });

        it('should handle load errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));

            await loadTree(state);

            expect(state.error).toBe('Failed to load project tree');
            expect(console.error).toHaveBeenCalled();
            expect(state.loading).toBe(false);
        });

        it('should ensure all nodes have children property', async () => {
            mockFetch.mockResolvedValueOnce({
                json: () => Promise.resolve([{ id: 1, name: 'Room', type: 'room' }]),
            });

            await loadTree(state);

            expect(state.tree[0].children).toEqual([]);
        });

        it('should handle null tree response', async () => {
            mockFetch.mockResolvedValueOnce({
                json: () => Promise.resolve(null),
            });

            await loadTree(state);

            expect(state.tree).toEqual([]);
        });
    });

    describe('refreshTree', () => {
        it('should defer refresh when resize lockout active', async () => {
            state._resizeLockout = true;

            const promise = refreshTree(state);

            expect(console.log).toHaveBeenCalledWith('⏳ Tree refresh deferred - resize/move in progress');

            // Clear lockout and advance time
            state._resizeLockout = false;
            vi.advanceTimersByTime(100);

            await promise;
            expect(mockFetch).toHaveBeenCalled();
        });

        it('should defer refresh when resizing', async () => {
            state.isResizing = true;

            const promise = refreshTree(state);

            expect(console.log).toHaveBeenCalledWith('⏳ Tree refresh deferred - resize/move in progress');

            state.isResizing = false;
            vi.advanceTimersByTime(100);

            await promise;
        });

        it('should defer refresh when moving', async () => {
            state.isMoving = true;

            refreshTree(state);

            expect(console.log).toHaveBeenCalledWith('⏳ Tree refresh deferred - resize/move in progress');
        });

        it('should load tree when not locked', async () => {
            await refreshTree(state);

            expect(console.log).toHaveBeenCalledWith('🌳 Refreshing project tree');
            expect(mockFetch).toHaveBeenCalled();
        });

        it('should call syncOverlayToCanvas callback if provided', async () => {
            const callbacks = {
                $nextTick: vi.fn(() => Promise.resolve()),
                syncOverlayToCanvas: vi.fn(),
            };
            const refs = {};

            await refreshTree(state, refs, callbacks);

            expect(callbacks.$nextTick).toHaveBeenCalled();
            expect(callbacks.syncOverlayToCanvas).toHaveBeenCalled();
            expect(console.log).toHaveBeenCalledWith('✓ Annotation positions recalculated after tree refresh');
        });

        it('should work without callbacks', async () => {
            await refreshTree(state, null, null);

            expect(state.treeReady).toBe(true);
        });
    });

    describe('toggleNode', () => {
        it('should expand collapsed node', () => {
            toggleNode(1, state);

            expect(state.expandedNodes).toContain(1);
        });

        it('should collapse expanded node', () => {
            state.expandedNodes = [1, 2];

            toggleNode(1, state);

            expect(state.expandedNodes).not.toContain(1);
            expect(state.expandedNodes).toContain(2);
        });

        it('should handle multiple toggles', () => {
            toggleNode(1, state);
            expect(state.expandedNodes).toContain(1);

            toggleNode(1, state);
            expect(state.expandedNodes).not.toContain(1);

            toggleNode(1, state);
            expect(state.expandedNodes).toContain(1);
        });
    });

    describe('isExpanded', () => {
        it('should return true for expanded node', () => {
            state.expandedNodes = [1, 2];

            expect(isExpanded(1, state)).toBe(true);
            expect(isExpanded(2, state)).toBe(true);
        });

        it('should return false for collapsed node', () => {
            expect(isExpanded(1, state)).toBe(false);
        });
    });

    describe('selectNode', () => {
        beforeEach(async () => {
            await loadTree(state);
        });

        it('should select room node', async () => {
            const callbacks = {
                navigateToNodePage: vi.fn(),
            };

            await selectNode({ nodeId: 1, type: 'room', name: 'Kitchen' }, state, callbacks);

            expect(state.selectedNodeId).toBe(1);
            expect(state.activeRoomId).toBe(1);
            expect(state.activeRoomName).toBe('Kitchen');
            expect(state.roomSearchQuery).toBe('Kitchen');
            expect(state.activeLocationId).toBeNull();
            expect(state.selectedPath).toEqual([1]);
            expect(callbacks.navigateToNodePage).toHaveBeenCalled();
        });

        it('should select location node', async () => {
            const callbacks = { navigateToNodePage: vi.fn() };

            await selectNode(
                {
                    nodeId: 10,
                    type: 'room_location',
                    name: 'Island',
                    parentRoomId: 1,
                },
                state,
                callbacks
            );

            expect(state.activeRoomId).toBe(1);
            expect(state.activeLocationId).toBe(10);
            expect(state.activeLocationName).toBe('Island');
            expect(state.locationSearchQuery).toBe('Island');
            expect(state.selectedPath).toEqual([1, 10]);
        });

        it('should select cabinet run node', async () => {
            const callbacks = { navigateToNodePage: vi.fn() };

            await selectNode(
                {
                    nodeId: 100,
                    type: 'cabinet_run',
                    name: 'Base Cabinets',
                    parentRoomId: 1,
                    parentLocationId: 10,
                },
                state,
                callbacks
            );

            expect(state.activeRoomId).toBe(1);
            expect(state.activeLocationId).toBe(10);
            expect(state.activeCabinetRunId).toBe(100);
            expect(state.activeCabinetRunName).toBe('Base Cabinets');
            expect(state.selectedPath).toEqual([1, 10, 100]);
        });

        it('should select cabinet node', async () => {
            const callbacks = { navigateToNodePage: vi.fn() };

            await selectNode(
                {
                    nodeId: 1000,
                    type: 'cabinet',
                    name: 'Cabinet 1',
                    parentRoomId: 1,
                    parentLocationId: 10,
                    parentCabinetRunId: 100,
                },
                state,
                callbacks
            );

            expect(state.activeRoomId).toBe(1);
            expect(state.activeLocationId).toBe(10);
            expect(state.activeCabinetRunId).toBe(100);
            expect(state.activeCabinetId).toBe(1000);
            expect(state.activeCabinetName).toBe('Cabinet 1');
            expect(state.selectedPath).toEqual([1, 10, 100, 1000]);
        });

        it('should log selection', async () => {
            const callbacks = { navigateToNodePage: vi.fn() };

            await selectNode({ nodeId: 1, type: 'room', name: 'Kitchen' }, state, callbacks);

            expect(console.log).toHaveBeenCalledWith('🌳 Selected node:', {
                nodeId: 1,
                type: 'room',
                name: 'Kitchen',
                path: [1],
            });
        });
    });

    describe('navigateToNodePage', () => {
        let callbacks;

        beforeEach(() => {
            callbacks = {
                goToPage: vi.fn(() => Promise.resolve()),
            };
        });

        it('should navigate to page with matching view type on node', async () => {
            const node = {
                name: 'Kitchen',
                pages: [
                    { page: 1, viewType: 'plan' },
                    { page: 2, viewType: 'elevation' },
                ],
            };
            state.activeViewType = 'elevation';

            await navigateToNodePage(node, null, null, state, callbacks);

            expect(callbacks.goToPage).toHaveBeenCalledWith(2);
            expect(console.log).toHaveBeenCalledWith('📍 Found elevation view on Kitchen at page 2');
        });

        it('should try parent location when node has no matching view', async () => {
            const node = {
                name: 'Cabinet Run',
                pages: [{ page: 1, viewType: 'plan' }],
            };
            const parentLocation = {
                name: 'Island',
                pages: [{ page: 2, viewType: 'elevation' }],
            };
            state.activeViewType = 'elevation';

            await navigateToNodePage(node, parentLocation, null, state, callbacks);

            expect(callbacks.goToPage).toHaveBeenCalledWith(2);
            expect(console.log).toHaveBeenCalledWith(
                '📍 Found elevation view on parent location Island at page 2'
            );
        });

        it('should try parent room when location has no matching view', async () => {
            const node = {
                name: 'Cabinet Run',
                pages: [{ page: 1, viewType: 'plan' }],
            };
            const parentRoom = {
                name: 'Kitchen',
                pages: [{ page: 3, viewType: 'elevation' }],
            };
            state.activeViewType = 'elevation';

            await navigateToNodePage(node, null, parentRoom, state, callbacks);

            expect(callbacks.goToPage).toHaveBeenCalledWith(3);
        });

        it('should fall back to first page when no matching view found', async () => {
            const node = {
                name: 'Kitchen',
                pages: [
                    { page: 1, viewType: 'plan' },
                    { page: 2, viewType: 'section' },
                ],
            };
            state.activeViewType = 'elevation';
            state.currentPage = 5; // Different from fallback page

            await navigateToNodePage(node, null, null, state, callbacks);

            expect(callbacks.goToPage).toHaveBeenCalledWith(1);
            expect(console.log).toHaveBeenCalledWith(
                '📍 No elevation view found, using first available page 1'
            );
        });

        it('should not navigate if already on target page', async () => {
            const node = {
                name: 'Kitchen',
                pages: [{ page: 1, viewType: 'plan' }],
            };
            state.currentPage = 1;
            state.activeViewType = 'plan';

            await navigateToNodePage(node, null, null, state, callbacks);

            expect(callbacks.goToPage).not.toHaveBeenCalled();
        });

        it('should handle nodes with no pages', async () => {
            const node = { name: 'Kitchen', pages: [] };

            await navigateToNodePage(node, null, null, state, callbacks);

            expect(callbacks.goToPage).not.toHaveBeenCalled();
        });
    });

    describe('showContextMenu', () => {
        it('should show context menu with correct state', () => {
            const event = { clientX: 100, clientY: 200 };
            const params = {
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
                parentRoomId: null,
                parentLocationId: null,
            };

            showContextMenu(event, params, state);

            expect(state.contextMenu).toEqual({
                show: true,
                x: 100,
                y: 200,
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
                parentRoomId: null,
                parentLocationId: null,
            });
            expect(console.log).toHaveBeenCalledWith('🖱️ Right-click detected!', {
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
            });
        });

        it('should include parent IDs when provided', () => {
            const event = { clientX: 150, clientY: 250 };
            const params = {
                nodeId: 10,
                nodeType: 'room_location',
                nodeName: 'Island',
                parentRoomId: 1,
                parentLocationId: null,
            };

            showContextMenu(event, params, state);

            expect(state.contextMenu.parentRoomId).toBe(1);
        });
    });

    describe('deleteTreeNode', () => {
        let refreshCallback;

        beforeEach(() => {
            refreshCallback = vi.fn(() => Promise.resolve());
            mockFetch.mockResolvedValue({
                json: () => Promise.resolve({ success: true }),
            });
        });

        it('should delete room node', async () => {
            state.contextMenu = {
                show: true,
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(confirm).toHaveBeenCalledWith(
                'Are you sure you want to delete "Kitchen"? This will also delete all associated annotations and data.'
            );
            expect(mockFetch).toHaveBeenCalledWith('/api/project/room/1', expect.any(Object));
            expect(refreshCallback).toHaveBeenCalled();
            expect(state.contextMenu.show).toBe(false);
        });

        it('should delete location node', async () => {
            state.contextMenu = {
                show: true,
                nodeId: 10,
                nodeType: 'room_location',
                nodeName: 'Island',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(mockFetch).toHaveBeenCalledWith('/api/project/location/10', expect.any(Object));
        });

        it('should delete cabinet run node', async () => {
            state.contextMenu = {
                show: true,
                nodeId: 100,
                nodeType: 'cabinet_run',
                nodeName: 'Base Cabinets',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(mockFetch).toHaveBeenCalledWith('/api/project/cabinet-run/100', expect.any(Object));
        });

        it('should cancel delete if user cancels confirm', async () => {
            global.confirm = vi.fn(() => false);
            state.contextMenu = {
                show: true,
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(mockFetch).not.toHaveBeenCalled();
            expect(state.contextMenu.show).toBe(false);
        });

        it('should clear active context if deleted node was selected', async () => {
            state.selectedNodeId = 1;
            state.activeRoomId = 1;
            state.activeRoomName = 'Kitchen';
            state.contextMenu = {
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(state.activeRoomId).toBeNull();
            expect(state.activeRoomName).toBe('');
            expect(state.drawMode).toBeNull();
            expect(state.selectedNodeId).toBeNull();
        });

        it('should handle delete errors', async () => {
            mockFetch.mockResolvedValueOnce({
                json: () => Promise.resolve({ success: false, error: 'Delete failed' }),
            });
            state.contextMenu = {
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(console.error).toHaveBeenCalled();
            expect(alert).toHaveBeenCalledWith('Error deleting room: Delete failed');
            expect(state.contextMenu.show).toBe(false);
        });

        it('should handle network errors', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));
            state.contextMenu = {
                nodeId: 1,
                nodeType: 'room',
                nodeName: 'Kitchen',
            };

            await deleteTreeNode(state, refreshCallback);

            expect(alert).toHaveBeenCalled();
        });
    });

    describe('navigateOnDoubleClick', () => {
        beforeEach(async () => {
            await loadTree(state);
            state.currentPage = 5; // Set to different page so navigation actually happens
        });

        it('should navigate to room page on double-click', async () => {
            const callbacks = { goToPage: vi.fn(() => Promise.resolve()) };

            await navigateOnDoubleClick({ nodeId: 1, nodeType: 'room' }, state, callbacks);

            expect(callbacks.goToPage).toHaveBeenCalledWith(1);
        });

        it('should navigate to location page on double-click', async () => {
            const callbacks = { goToPage: vi.fn(() => Promise.resolve()) };

            await navigateOnDoubleClick(
                { nodeId: 10, nodeType: 'room_location', parentRoomId: 1 },
                state,
                callbacks
            );

            expect(callbacks.goToPage).toHaveBeenCalledWith(1);
        });

        it('should navigate to cabinet run page on double-click', async () => {
            const callbacks = { goToPage: vi.fn(() => Promise.resolve()) };

            await navigateOnDoubleClick(
                {
                    nodeId: 100,
                    nodeType: 'cabinet_run',
                    parentRoomId: 1,
                    parentLocationId: 10,
                },
                state,
                callbacks
            );

            expect(callbacks.goToPage).toHaveBeenCalledWith(1);
        });
    });

    describe('buildAnnotationTree', () => {
        it('should build hierarchical tree from flat annotations', () => {
            const annotations = [
                { id: 1, type: 'room', parentId: null },
                { id: 2, type: 'location', parentId: 1 },
                { id: 3, type: 'location', parentId: 1 },
                { id: 4, type: 'cabinet_run', parentId: 2 },
            ];

            const tree = buildAnnotationTree(annotations);

            expect(tree).toHaveLength(1);
            expect(tree[0].id).toBe(1);
            expect(tree[0].children).toHaveLength(2);
            expect(tree[0].children[0].id).toBe(2);
            expect(tree[0].children[0].children).toHaveLength(1);
            expect(tree[0].children[0].children[0].id).toBe(4);
        });

        it('should handle orphan nodes with missing parents', () => {
            const annotations = [
                { id: 1, type: 'room', parentId: null },
                { id: 2, type: 'location', parentId: 999 }, // Parent doesn't exist
            ];

            const tree = buildAnnotationTree(annotations);

            expect(tree).toHaveLength(2);
        });

        it('should handle empty annotation array', () => {
            const tree = buildAnnotationTree([]);

            expect(tree).toEqual([]);
        });
    });

    describe('getPageGroupedAnnotations', () => {
        beforeEach(() => {
            state.pageMap = {
                1: 123,
                2: 124,
            };
        });

        it('should group annotations by page', () => {
            state.annotations = [
                { id: 1, pageNumber: 1, type: 'room', parentId: null },
                { id: 2, pageNumber: 1, type: 'location', parentId: 1 },
                { id: 3, pageNumber: 2, type: 'room', parentId: null },
            ];

            const grouped = getPageGroupedAnnotations(state);

            expect(grouped).toHaveLength(2);
            expect(grouped[0].pageNumber).toBe(1);
            expect(grouped[0].annotations).toHaveLength(1); // Root nodes
            expect(grouped[1].pageNumber).toBe(2);
            expect(grouped[1].annotations).toHaveLength(1);
        });

        it('should use filteredAnnotations when available', () => {
            state.annotations = [
                { id: 1, pageNumber: 1 },
                { id: 2, pageNumber: 2 },
            ];
            state.filteredAnnotations = [{ id: 1, pageNumber: 1 }];

            const grouped = getPageGroupedAnnotations(state);

            expect(grouped.find(p => p.pageNumber === 1).annotations).toHaveLength(1);
            expect(grouped.find(p => p.pageNumber === 2).annotations).toHaveLength(0);
        });

        it('should handle annotations without pageNumber', () => {
            state.currentPage = 1;
            state.annotations = [{ id: 1, type: 'room' }]; // No pageNumber

            const grouped = getPageGroupedAnnotations(state);

            expect(grouped.find(p => p.pageNumber === 1).annotations).toHaveLength(1);
        });
    });

    describe('getRoomNameById', () => {
        beforeEach(async () => {
            await loadTree(state);
        });

        it('should return room name for valid ID', () => {
            expect(getRoomNameById(1, state)).toBe('Kitchen');
            expect(getRoomNameById(2, state)).toBe('Bathroom');
        });

        it('should return empty string for invalid ID', () => {
            expect(getRoomNameById(999, state)).toBe('');
        });

        it('should return empty string when no tree loaded', () => {
            state.tree = null;
            expect(getRoomNameById(1, state)).toBe('');
        });

        it('should return empty string for null ID', () => {
            expect(getRoomNameById(null, state)).toBe('');
        });
    });

    describe('getLocationNameById', () => {
        beforeEach(async () => {
            await loadTree(state);
        });

        it('should return location name for valid ID', () => {
            expect(getLocationNameById(10, state)).toBe('Island');
        });

        it('should return empty string for invalid ID', () => {
            expect(getLocationNameById(999, state)).toBe('');
        });

        it('should return empty string when no tree loaded', () => {
            state.tree = null;
            expect(getLocationNameById(10, state)).toBe('');
        });

        it('should return empty string for null ID', () => {
            expect(getLocationNameById(null, state)).toBe('');
        });
    });

    describe('Edge Cases', () => {
        it('should handle multiple tree refreshes', async () => {
            await refreshTree(state);
            await refreshTree(state);

            expect(mockFetch).toHaveBeenCalledTimes(2);
        });

        it('should handle node selection without tree loaded', async () => {
            state.tree = [];
            const callbacks = { navigateToNodePage: vi.fn() };

            await selectNode({ nodeId: 1, type: 'room', name: 'Kitchen' }, state, callbacks);

            expect(state.selectedNodeId).toBe(1);
        });

        it('should handle context menu for different node types', () => {
            const event = { clientX: 100, clientY: 200 };

            showContextMenu(event, { nodeId: 1, nodeType: 'room', nodeName: 'Room' }, state);
            expect(state.contextMenu.nodeType).toBe('room');

            showContextMenu(event, { nodeId: 10, nodeType: 'room_location', nodeName: 'Location' }, state);
            expect(state.contextMenu.nodeType).toBe('room_location');

            showContextMenu(event, { nodeId: 100, nodeType: 'cabinet_run', nodeName: 'Run' }, state);
            expect(state.contextMenu.nodeType).toBe('cabinet_run');
        });
    });
});
