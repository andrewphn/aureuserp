/**
 * Isolation Mode Manager
 * Handles Illustrator-style isolation mode for hierarchical annotation editing
 *
 * Isolation mode allows users to "enter" an annotation (room, location, or cabinet run)
 * and see only its direct children, making it easier to work on complex hierarchies.
 */

import { createSVGElement } from '../utilities.js';

/**
 * Enter isolation mode for an annotation
 * @param {Object} annotation - Annotation to isolate
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function enterIsolationMode(annotation, state, callbacks) {
    console.log('🔒 Entering isolation mode for:', annotation.type, annotation.label);

    // Store current view context
    state.isolationViewType = state.activeViewType;
    state.isolationOrientation = state.activeOrientation;
    console.log(`📐 Isolation view context: ${state.isolationViewType}${state.isolationOrientation ? ` (${state.isolationOrientation})` : ''}`);

    if (annotation.type === 'cabinet_run') {
        // Isolate at cabinet run level
        state.isolationMode = true;
        state.isolationLevel = 'cabinet_run';
        state.isolatedRoomId = annotation.roomId; // Parent: entity ID
        state.isolatedRoomName = annotation.roomName || getRoomNameById(annotation.roomId, state);
        state.isolatedLocationId = annotation.locationId || annotation.roomLocationId; // Parent: entity ID
        state.isolatedLocationName = annotation.locationName || getLocationNameById(annotation.locationId || annotation.roomLocationId, state);
        state.isolatedCabinetRunId = annotation.id; // CRITICAL: Isolated level uses annotation ID for template wrapper
        state.isolatedCabinetRunName = annotation.label;

        // Set active context
        state.activeRoomId = annotation.roomId;
        state.activeRoomName = state.isolatedRoomName;
        state.activeLocationId = annotation.locationId || annotation.roomLocationId;
        state.activeLocationName = state.isolatedLocationName;

        // Update search fields
        state.roomSearchQuery = state.isolatedRoomName;
        state.locationSearchQuery = state.isolatedLocationName;

        console.log(`✓ Cabinet Run isolation: 🏠 ${state.isolatedRoomName} → 📍 ${state.isolatedLocationName} → 🗄️ ${annotation.label}`);

    } else if (annotation.type === 'location') {
        // Isolate at location level
        state.isolationMode = true;
        state.isolationLevel = 'location';
        state.isolatedRoomId = annotation.roomId; // Parent: entity ID
        state.isolatedRoomName = annotation.roomName || getRoomNameById(annotation.roomId, state);
        state.isolatedLocationId = annotation.id; // CRITICAL: Isolated level uses annotation ID for template wrapper
        state.isolatedLocationName = annotation.label;
        state.isolatedCabinetRunId = null;
        state.isolatedCabinetRunName = '';

        // Set active context
        state.activeRoomId = annotation.roomId;
        state.activeRoomName = state.isolatedRoomName;
        state.activeLocationId = annotation.roomLocationId;
        state.activeLocationName = annotation.label;

        // Update search fields
        state.roomSearchQuery = state.isolatedRoomName;
        state.locationSearchQuery = annotation.label;

        console.log(`✓ Location isolation: 🏠 ${state.isolatedRoomName} → 📍 ${annotation.label} (entity ID: ${annotation.roomLocationId})`);

    } else {
        // For any other type, treat as room isolation
        const roomAnnotationId = annotation.type === 'room' ? annotation.id : annotation.roomId;
        const roomEntityId = annotation.roomId; // Entity ID for child matching
        const roomName = annotation.type === 'room' ? annotation.label : (annotation.roomName || getRoomNameById(annotation.roomId, state));

        state.isolationMode = true;
        state.isolationLevel = 'room';
        state.isolatedRoomId = roomAnnotationId; // Annotation ID for template wrapper x-show
        state.isolatedRoomEntityId = roomEntityId; // Entity ID for hierarchy child matching
        state.isolatedRoomName = roomName;
        state.isolatedLocationId = null;
        state.isolatedLocationName = '';
        state.isolatedCabinetRunId = null;
        state.isolatedCabinetRunName = '';

        // Set active context
        state.activeRoomId = roomAnnotationId;
        state.activeRoomName = roomName;
        state.activeLocationId = null;
        state.activeLocationName = '';
        state.locationSearchQuery = '';

        // Update search field
        state.roomSearchQuery = roomName;

        console.log(`✓ Room isolation: 🏠 ${roomName} (annoId: ${roomAnnotationId}, entityId: ${roomEntityId})`);
    }

    // Expand isolated node in tree
    if (!state.expandedNodes.includes(state.isolatedRoomId)) {
        state.expandedNodes.push(state.isolatedRoomId);
    }
    if (state.isolatedLocationId && !state.expandedNodes.includes(state.isolatedLocationId)) {
        state.expandedNodes.push(state.isolatedLocationId);
    }
    if (state.isolatedCabinetRunId && !state.expandedNodes.includes(state.isolatedCabinetRunId)) {
        state.expandedNodes.push(state.isolatedCabinetRunId);
    }

    // Select the isolated node
    state.selectedNodeId = state.isolationLevel === 'cabinet_run' ? state.isolatedCabinetRunId :
                          state.isolationLevel === 'location' ? state.isolatedLocationId :
                          state.isolatedRoomId;

    // Apply visibility filter
    applyIsolationVisibilityFilter(state);

    // Zoom to fit annotation
    if (callbacks.zoomToFitAnnotation) {
        await callbacks.zoomToFitAnnotation(annotation);
    }

    // Wait for overlay dimensions to sync after zoom
    if (callbacks.$nextTick) {
        await callbacks.$nextTick();
    }
    await new Promise(resolve => setTimeout(resolve, 100));

    // Sync overlay dimensions for blur layer
    if (callbacks.syncOverlayToCanvas) {
        callbacks.syncOverlayToCanvas();
    }

    // Force coordinate recalculation after zoom and overlay sync
    if (callbacks.$nextTick) {
        await callbacks.$nextTick();
    }
    // Wait for coordinates to be recalculated based on new scale
    await new Promise(resolve => setTimeout(resolve, 150));

    // Update the isolation mask (now coordinates should be ready)
    updateIsolationMask(state);
}

/**
 * Exit isolation mode
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function exitIsolationMode(state, callbacks) {
    console.log('🔓 Exiting isolation mode');

    // Clear isolation state
    state.isolationMode = false;
    state.isolationLevel = null;
    state.isolatedRoomId = null;
    state.isolatedRoomName = '';
    state.isolatedLocationId = null;
    state.isolatedLocationName = '';
    state.isolatedCabinetRunId = null;
    state.isolatedCabinetRunName = '';
    state.isolationViewType = null;
    state.isolationOrientation = null;

    // Clear active context
    if (callbacks.clearContext) {
        callbacks.clearContext();
    }

    // Deselect node
    state.selectedNodeId = null;

    // Clear hidden annotations
    console.log(`👁️ [EXIT ISOLATION] Clearing hidden annotations (was: [${state.hiddenAnnotations.join(', ')}])`);
    state.hiddenAnnotations = [];

    // Reset zoom
    if (callbacks.resetZoom) {
        await callbacks.resetZoom();
    }

    console.log('✓ Returned to normal view with reset zoom');

    // Update isolation mask
    updateIsolationMask(state);
}

/**
 * Apply isolation visibility filter to annotations
 * @param {Object} state - Component state
 */
function applyIsolationVisibilityFilter(state) {
    state.hiddenAnnotations = [];

    state.annotations.forEach(a => {
        if (!isAnnotationVisibleInIsolation(a, state)) {
            console.log(`👁️ [ENTER ISOLATION] Hiding annotation ${a.id} (${a.label} - type: ${a.type})`);
            state.hiddenAnnotations.push(a.id);
        }
    });

    console.log(`👁️ [ENTER ISOLATION] Hidden annotations: [${state.hiddenAnnotations.join(', ')}]`);
}

/**
 * Check if annotation is visible in current isolation mode
 * @param {Object} anno - Annotation to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if visible
 */
export function isAnnotationVisibleInIsolation(anno, state) {
    if (!state.isolationMode) {
        // NORMAL MODE: Only show room and location annotations (1 level deep)
        // Hide cabinet_run and cabinet types - they're only visible in isolation mode
        if (anno.type === 'room' || anno.type === 'location') {
            return true;
        }
        return false;
    }

    // First check view type compatibility
    if (!isAnnotationVisibleInView(anno, state)) {
        return false;
    }

    // Then check hierarchy visibility
    if (state.isolationLevel === 'room') {
        // Show the isolated room itself (template wrapper will hide it via x-show)
        if (anno.id === state.isolatedRoomId) return true;

        // Show level 1: locations in this room
        // Use entity ID for matching since child annotations reference parent by entity ID
        if (anno.type === 'location' && anno.roomId === state.isolatedRoomEntityId) return true;

        // Show level 2: cabinet_runs in this room's locations
        if (anno.type === 'cabinet_run' && anno.roomId === state.isolatedRoomEntityId) return true;

        // Hide everything else
        return false;

    } else if (state.isolationLevel === 'location') {
        // Show parent room
        if (anno.id === state.isolatedRoomId) return true;

        // Show the isolated location itself (template wrapper will hide it via x-show)
        if (anno.id === state.isolatedLocationId) return true;

        // Show direct children (cabinet runs in this location) - level 1
        if (anno.type === 'cabinet_run' && anno.locationId === state.isolatedLocationId) return true;

        // Show grandchildren (cabinets within cabinet runs in this location) - level 2
        if (anno.type === 'cabinet' && anno.locationId === state.isolatedLocationId) return true;

        // Hide everything else
        return false;

    } else if (state.isolationLevel === 'cabinet_run') {
        // Show parent location
        if (anno.id === state.isolatedLocationId) return true;

        // Show parent room
        if (anno.id === state.isolatedRoomId) return true;

        // Show the isolated cabinet run itself (template wrapper will hide it via x-show)
        if (anno.id === state.isolatedCabinetRunId) return true;

        // Show direct children (cabinets in this run)
        if (anno.type === 'cabinet' && anno.cabinetRunId === state.isolatedCabinetRunId) return true;

        // Hide everything else
        return false;
    }

    return true;
}

/**
 * Check if annotation is visible in current view type
 * @param {Object} anno - Annotation to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if visible
 */
function isAnnotationVisibleInView(anno, state) {
    // Use isolation view type if in isolation mode, otherwise use active view type
    const viewType = state.isolationMode ? state.isolationViewType : state.activeViewType;
    const orientation = state.isolationMode ? state.isolationOrientation : state.activeOrientation;

    // Always show if no view type filter
    if (!viewType) return true;

    // If annotation doesn't specify a view type, show it in all views (default behavior)
    if (!anno.viewType) return true;

    // Match view type
    if (anno.viewType !== viewType) return false;

    // Match orientation for elevation/section views
    if ((viewType === 'elevation' || viewType === 'section') && orientation) {
        if (anno.orientation !== orientation) return false;
    }

    return true;
}

/**
 * Check if annotation is descendant of entity
 * @param {Object} anno - Annotation to check
 * @param {Number} parentEntityId - Parent entity ID
 * @param {Object} state - Component state
 * @returns {Boolean} True if descendant
 */
function isDescendantOf(anno, parentEntityId, state) {
    console.log(`🔍 [isDescendantOf] Checking if ${anno?.id} (${anno?.label}) is descendant of entity ${parentEntityId}`);

    if (!anno || !parentEntityId) {
        console.log(`   ❌ Missing anno or parentEntityId`);
        return false;
    }

    // Check if annotation belongs to the entity by roomId
    if (anno.roomId === parentEntityId) {
        console.log(`   ✅ Belongs to room entity! (roomId ${anno.roomId} === ${parentEntityId})`);
        return true;
    }

    // Direct child by annotation parentId
    if (anno.parentId === parentEntityId) {
        console.log(`   ✅ Direct child! parentId matches`);
        return true;
    }

    // Recursive check through parent chain
    if (anno.parentId) {
        const parent = state.annotations.find(a => a.id === anno.parentId);
        if (parent) {
            console.log(`   ⬆️ Has parent ID ${anno.parentId}, checking parent recursively...`);
            return isDescendantOf(parent, parentEntityId, state);
        }
    }

    console.log(`   ❌ Not a descendant of entity ${parentEntityId}`);
    return false;
}

/**
 * Update SVG isolation mask
 * @param {Object} state - Component state
 */
export function updateIsolationMask(state) {
    const maskRects = document.getElementById('maskRects');
    if (!maskRects) return;

    // Clear existing rects
    maskRects.innerHTML = '';

    console.log(`📐 [MASK UPDATE] Overlay dimensions: ${state.overlayWidth} × ${state.overlayHeight}`);

    // Get visible annotations
    const visibleAnnotations = state.annotations.filter(a => !state.hiddenAnnotations.includes(a.id));

    // Find isolated entity annotation
    let isolatedEntityAnnotation = null;
    if (state.isolationMode) {
        if (state.isolationLevel === 'room') {
            isolatedEntityAnnotation = state.annotations.find(a =>
                a.type === 'room' && a.roomId === state.isolatedRoomId
            );
        } else if (state.isolationLevel === 'location') {
            isolatedEntityAnnotation = state.annotations.find(a =>
                a.type === 'location' && a.roomLocationId === state.isolatedLocationId
            );
        } else if (state.isolationLevel === 'cabinet_run') {
            isolatedEntityAnnotation = state.annotations.find(a =>
                a.type === 'cabinet_run' && a.cabinetRunId === state.isolatedCabinetRunId
            );
        }
    }

    // Create cutout for isolated entity boundary
    if (isolatedEntityAnnotation && isolatedEntityAnnotation.screenX !== undefined) {
        const rect = createMaskRect(isolatedEntityAnnotation);
        maskRects.appendChild(rect);
        console.log(`✓ Added isolated entity boundary to mask: ${isolatedEntityAnnotation.label}`);
    }

    // Create cutouts for visible child annotations
    visibleAnnotations.forEach(anno => {
        if (anno.screenX !== undefined && anno.screenWidth > 0 && anno.screenHeight > 0) {
            const rect = createMaskRect(anno);
            maskRects.appendChild(rect);
        }
    });
}

/**
 * Create SVG rect for mask cutout
 * @param {Object} annotation - Annotation
 * @returns {SVGRectElement} SVG rect element
 */
function createMaskRect(annotation) {
    const padding = 15;
    const x = (annotation.screenX || 0) - padding;
    const y = (annotation.screenY || 0) - padding;
    const width = (annotation.screenWidth || 0) + (padding * 2);
    const height = (annotation.screenHeight || 0) + (padding * 2);

    return createSVGElement('rect', {
        x: x,
        y: y,
        width: width,
        height: height,
        fill: 'black',
        rx: '8',
        filter: 'url(#feather)'
    });
}

/**
 * Get breadcrumb path for isolation mode
 * @param {Object} state - Component state
 * @returns {Array<Object>} Breadcrumb items
 */
export function getIsolationBreadcrumbs(state) {
    if (!state.isolationMode) return [];

    const breadcrumbs = [];

    // Always include room
    if (state.isolatedRoomName) {
        breadcrumbs.push({
            label: state.isolatedRoomName,
            level: 'room',
            icon: '🏠'
        });
    }

    // Add location if present
    if (state.isolatedLocationName) {
        breadcrumbs.push({
            label: state.isolatedLocationName,
            level: 'location',
            icon: '📍'
        });
    }

    // Add cabinet run if present
    if (state.isolatedCabinetRunName) {
        breadcrumbs.push({
            label: state.isolatedCabinetRunName,
            level: 'cabinet_run',
            icon: '🗄️'
        });
    }

    return breadcrumbs;
}

/**
 * Helper functions
 */

function getRoomNameById(roomId, state) {
    if (!state.tree || !roomId) return '';
    const room = state.tree.find(r => r.id === roomId);
    return room ? room.name : '';
}

function getLocationNameById(locationId, state) {
    if (!state.tree || !locationId) return '';
    for (const room of state.tree) {
        const location = room.children?.find(l => l.id === locationId);
        if (location) return location.name;
    }
    return '';
}
