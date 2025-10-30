/**
 * State Manager
 * Centralizes all reactive state for the PDF annotation viewer
 */

/**
 * Create initial state object for Alpine.js component
 * @param {Object} config - Configuration from Blade component
 * @returns {Object} Initial state object
 */
export function createInitialState(config) {
    return {
        // Configuration (from props)
        pdfUrl: config.pdfUrl,
        pageNumber: config.pageNumber,  // DEPRECATED: Use currentPage
        pdfPageId: config.pdfPageId,
        projectId: config.projectId,
        totalPages: config.totalPages || 1,
        pageMap: config.pageMap || {},

        // Pagination State
        currentPage: config.pageNumber || 1,
        pageType: config.pageType || null,

        // PDF State
        pdfReady: false,
        treeReady: false,
        annotationsReady: false,
        systemReady: false,
        pageDimensions: null,
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
        editorModalOpen: false,

        // Isolation Mode State
        isolationMode: false,
        isolationLevel: null,
        isolatedRoomId: null,
        isolatedRoomName: '',
        isolatedLocationId: null,
        isolatedLocationName: '',
        isolatedCabinetRunId: null,
        isolatedCabinetRunName: '',
        isolationViewType: null,
        isolationOrientation: null,
        overlayWidth: '100%',
        overlayHeight: '100%',
        hiddenAnnotations: [],
        visibleAnnotationsList: [],

        // Tree State
        tree: [],
        expandedNodes: [],
        selectedNodeId: null,
        selectedPath: [],
        selectedAnnotation: null,
        loading: false,
        navigating: false,
        error: null,
        treeViewMode: 'room',
        treeSidebarState: 'full',

        // Filter State
        showFilters: false,
        filterScope: 'page',
        filters: {
            types: [],
            rooms: [],
            locations: [],
            viewTypes: [],
            verticalZones: [],
            myAnnotations: false,
            recent: false,
            unlinked: false,
            pageRange: {
                from: null,
                to: null
            },
            dateRange: {
                from: null,
                to: null
            }
        },

        // Context Menu State
        contextMenu: {
            show: false,
            x: 0,
            y: 0,
            nodeId: null,
            nodeType: null,
            nodeName: '',
            parentRoomId: null
        },

        // Autocomplete State
        roomSearchQuery: '',
        locationSearchQuery: '',
        roomSuggestions: [],
        locationSuggestions: [],
        showRoomDropdown: false,
        showLocationDropdown: false,

        // Annotation State
        annotations: [],
        isDrawing: false,
        drawStart: null,
        drawPreview: null,

        // Resize and Move State
        isResizing: false,
        isMoving: false,
        resizeTicking: false,
        moveTicking: false,
        resizeHandle: null,
        moveStart: null,
        resizeStart: null,
        activeAnnotationId: null,
        resizeSaveTimeout: null,
        pendingResizeChanges: null,
        autoSaveTimeout: null,

        // View Type State
        activeViewType: 'plan',
        activeOrientation: null,
        availableOrientations: {
            elevation: ['front', 'back', 'left', 'right'],
            section: ['A-A', 'B-B', 'C-C'],
            detail: []
        },
        viewScale: 1.0,

        // Multi-Parent Entity References
        annotationReferences: {},

        // Page Observer State
        pageObserver: null,
        visiblePages: [],

        // Performance Optimization
        _overlayRect: null,
        _lastRectUpdate: 0,
        _rectCacheMs: 100,
        _cachedZoom: undefined,

        // PDF iframe scroll tracking
        pdfIframe: null,
        scrollX: 0,
        scrollY: 0,

        // Undo/Redo History Stack
        historyStack: [],
        historyIndex: -1,
        maxHistorySize: 50,
        isUndoRedoAction: false,

        // Initialization guard
        _initialized: false
    };
}

/**
 * Get color for annotation type
 * @param {String} type - Annotation type
 * @returns {String} Color hex code
 */
export function getColorForType(type) {
    const colors = {
        room: '#f59e0b',        // Amber/Orange
        location: '#9333ea',    // Purple
        cabinet_run: '#3b82f6', // Blue
        cabinet: '#10b981'      // Green
    };
    return colors[type] || colors.cabinet;
}

/**
 * Get human-readable view type label
 * @param {String} viewType - View type
 * @param {String} orientation - Optional orientation
 * @returns {String} Formatted label
 */
export function getViewTypeLabel(viewType, orientation = null) {
    const labels = {
        plan: 'Plan View',
        elevation: 'Elevation View',
        section: 'Section View',
        detail: 'Detail View'
    };

    let label = labels[viewType] || 'Unknown View';

    if (orientation && (viewType === 'elevation' || viewType === 'section')) {
        const orientationLabel = viewType === 'elevation'
            ? orientation.charAt(0).toUpperCase() + orientation.slice(1)
            : orientation;
        label += ` - ${orientationLabel}`;
    }

    return label;
}

/**
 * Get color for view type badge
 * @param {String} viewType - View type
 * @returns {String} CSS color variable
 */
export function getViewTypeColor(viewType) {
    const colors = {
        plan: 'var(--primary-600)',
        elevation: 'var(--warning-600)',
        section: 'var(--info-600)',
        detail: 'var(--success-600)'
    };
    return colors[viewType] || 'var(--gray-600)';
}

/**
 * Select annotation context for hierarchical tool enabling
 * Sets active room/location context based on clicked annotation
 * @param {Object} anno - Annotation object
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions { getRoomNameById, getLocationNameById }
 */
export function selectAnnotationContext(anno, state, callbacks) {
    // CRITICAL: Don't change context during active resize/move operations
    if (state._resizeLockout || state.isResizing || state.isMoving) {
        console.log('⚠️ Context change blocked - resize/move in progress');
        return;
    }

    console.log('🎯 Selecting annotation context:', anno.type, anno.label);

    // Set as active annotation for z-index priority
    state.activeAnnotationId = anno.id;
    state.selectedAnnotation = anno;

    // Hierarchical context enabling based on annotation type
    if (anno.type === 'room') {
        // Clicking a room annotation:
        // - Sets room context
        // - Clears location context
        // - Enables: Draw Location
        state.activeRoomId = anno.roomId || anno.id;
        state.activeRoomName = anno.label;
        state.activeLocationId = null;
        state.activeLocationName = '';

        // Update search fields
        state.roomSearchQuery = anno.label;
        state.locationSearchQuery = '';

        console.log(`✓ Room context set: Room "${anno.label}"`);
        console.log('✓ Enabled tools: Draw Location');
    }
    else if (anno.type === 'location') {
        // Clicking a location annotation:
        // - Sets room context (from the location's parent)
        // - Sets location context
        // - Enables: Draw Cabinet Run, Draw Cabinet
        state.activeRoomId = anno.roomId;
        state.activeRoomName = anno.roomName || (callbacks.getRoomNameById ? callbacks.getRoomNameById(anno.roomId) : '');
        state.activeLocationId = anno.id;
        state.activeLocationName = anno.label;

        // Update search fields
        state.roomSearchQuery = state.activeRoomName;
        state.locationSearchQuery = anno.label;

        console.log(`✓ Location context set: Room "${state.activeRoomName}" → Location "${anno.label}"`);
        console.log('✓ Enabled tools: Draw Cabinet Run, Draw Cabinet');
    }
    else if (anno.type === 'cabinet_run') {
        // Clicking a cabinet run annotation:
        // - Sets room context (from the cabinet run's parent hierarchy)
        // - Sets location context (parent location)
        // - Enables: Draw Cabinet (inside this run)
        state.activeRoomId = anno.roomId;
        state.activeRoomName = anno.roomName || (callbacks.getRoomNameById ? callbacks.getRoomNameById(anno.roomId) : '');
        state.activeLocationId = anno.locationId;
        state.activeLocationName = anno.locationName || (callbacks.getLocationNameById ? callbacks.getLocationNameById(anno.locationId) : '');

        // Update search fields
        state.roomSearchQuery = state.activeRoomName;
        state.locationSearchQuery = state.activeLocationName;

        console.log(`✓ Cabinet Run context set: Room "${state.activeRoomName}" → Location "${state.activeLocationName}" → Run "${anno.label}"`);
        console.log('✓ Enabled tools: Draw Cabinet');
    }
    else if (anno.type === 'cabinet') {
        // Clicking a cabinet annotation:
        // - Sets full hierarchy context
        // - Enables: Draw Cabinet (sibling cabinets)
        state.activeRoomId = anno.roomId;
        state.activeRoomName = anno.roomName || (callbacks.getRoomNameById ? callbacks.getRoomNameById(anno.roomId) : '');
        state.activeLocationId = anno.locationId;
        state.activeLocationName = anno.locationName || (callbacks.getLocationNameById ? callbacks.getLocationNameById(anno.locationId) : '');

        // Update search fields
        state.roomSearchQuery = state.activeRoomName;
        state.locationSearchQuery = state.activeLocationName;

        console.log(`✓ Cabinet context set: Room "${state.activeRoomName}" → Location "${state.activeLocationName}" → Cabinet "${anno.label}"`);
        console.log('✓ Enabled tools: Draw Cabinet (sibling)');
    }

    // Visual feedback: Select the corresponding tree node
    state.selectedNodeId = anno.id;
}
