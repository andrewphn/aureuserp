/**
 * Common mocks for PDF Viewer tests
 */

import { vi } from 'vitest';

/**
 * Create mock Alpine.js state object
 * @param {Object} overrides - State properties to override
 * @returns {Object} Mock state object
 */
export function createMockState(overrides = {}) {
    return {
        // PDF State
        pdfReady: false,
        pageDimensions: { width: 800, height: 600 },
        canvasScale: 1.0,
        zoomLevel: 1.0,
        zoomMin: 1.0,
        zoomMax: 3.0,

        // Context State
        activeRoomId: null,
        activeRoomName: '',
        activeLocationId: null,
        activeLocationName: '',
        drawMode: null,

        // Isolation Mode
        isolationMode: false,
        isolationLevel: null,
        isolatedRoomId: null,
        isolatedLocationId: null,
        isolatedCabinetRunId: null,
        hiddenAnnotations: [],
        overlayWidth: '100%',
        overlayHeight: '100%',

        // Annotations
        annotations: [],
        isDrawing: false,
        drawStart: null,
        drawPreview: null,
        activeAnnotationId: null,
        selectedAnnotation: null,

        // Resize/Move
        isResizing: false,
        isMoving: false,
        resizeHandle: null,

        // View Type
        activeViewType: 'plan',
        activeOrientation: null,

        // Tree
        tree: [],
        expandedNodes: [],
        selectedNodeId: null,

        // Undo/Redo
        historyStack: [],
        historyIndex: -1,
        maxHistorySize: 50,
        isUndoRedoAction: false,

        // Cache
        _overlayRect: null,
        _lastRectUpdate: 0,
        _cachedZoom: undefined,

        // Filters
        filters: {
            types: [],
            rooms: [],
            locations: [],
            viewTypes: [],
        },
        filterScope: 'page',

        // Autocomplete
        roomSearchQuery: '',
        locationSearchQuery: '',
        roomSuggestions: [],
        locationSuggestions: [],
        showRoomDropdown: false,
        showLocationDropdown: false,

        ...overrides,
    };
}

/**
 * Create mock Alpine.js $refs object
 * @param {Object} overrides - Ref properties to override
 * @returns {Object} Mock $refs object
 */
export function createMockRefs(overrides = {}) {
    const canvas = document.createElement('canvas');
    canvas.width = 800;
    canvas.height = 600;
    canvas.style.width = '800px';
    canvas.style.height = '600px';
    Object.defineProperty(canvas, 'offsetWidth', { value: 800, writable: true });
    Object.defineProperty(canvas, 'offsetHeight', { value: 600, writable: true });

    const pdfEmbed = document.createElement('div');
    pdfEmbed.appendChild(canvas);

    const annotationOverlay = document.createElement('div');
    annotationOverlay.style.width = '800px';
    annotationOverlay.style.height = '600px';
    Object.defineProperty(annotationOverlay, 'offsetWidth', { value: 800, writable: true });
    Object.defineProperty(annotationOverlay, 'offsetHeight', { value: 600, writable: true });
    annotationOverlay.getBoundingClientRect = vi.fn(() => ({
        width: 800,
        height: 600,
        top: 0,
        left: 0,
        right: 800,
        bottom: 600,
    }));

    const isolationBlur = document.createElement('div');
    const maskRects = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    maskRects.id = 'maskRects';

    return {
        pdfEmbed,
        annotationOverlay,
        isolationBlur,
        ...overrides,
    };
}

/**
 * Create mock callbacks object
 * @param {Object} overrides - Callback functions to override
 * @returns {Object} Mock callbacks object
 */
export function createMockCallbacks(overrides = {}) {
    return {
        displayPdf: vi.fn().mockResolvedValue(undefined),
        $nextTick: vi.fn((callback) => {
            if (callback) {
                return Promise.resolve().then(callback);
            }
            return Promise.resolve();
        }),
        getRoomNameById: vi.fn((id) => `Room ${id}`),
        getLocationNameById: vi.fn((id) => `Location ${id}`),
        updateIsolationMask: vi.fn(),
        saveAnnotation: vi.fn().mockResolvedValue(undefined),
        deleteAnnotation: vi.fn().mockResolvedValue(undefined),
        loadAnnotations: vi.fn().mockResolvedValue([]),
        fetchTree: vi.fn().mockResolvedValue([]),
        ...overrides,
    };
}

/**
 * Create mock annotation object
 * @param {Object} overrides - Annotation properties to override
 * @returns {Object} Mock annotation
 */
export function createMockAnnotation(overrides = {}) {
    return {
        id: 1,
        type: 'room',
        label: 'Test Room',
        roomId: null,
        locationId: null,
        cabinetRunId: null,
        x: 100,
        y: 100,
        width: 200,
        height: 150,
        screenX: 100,
        screenY: 100,
        screenWidth: 200,
        screenHeight: 150,
        color: '#f59e0b',
        locked: false,
        visible: true,
        pageNumber: 1,
        ...overrides,
    };
}

/**
 * Create mock tree node
 * @param {Object} overrides - Node properties to override
 * @returns {Object} Mock tree node
 */
export function createMockTreeNode(overrides = {}) {
    return {
        id: 1,
        type: 'room',
        label: 'Test Room',
        parentId: null,
        children: [],
        expanded: false,
        visible: true,
        ...overrides,
    };
}

/**
 * Wait for promises to resolve
 * Useful for testing async functions
 */
export async function flushPromises() {
    return new Promise((resolve) => setImmediate(resolve));
}

/**
 * Advance timers and flush promises
 * Useful for testing debounced/throttled functions
 */
export async function advanceTimersAndFlush(ms = 0) {
    vi.advanceTimersByTime(ms);
    await flushPromises();
}
