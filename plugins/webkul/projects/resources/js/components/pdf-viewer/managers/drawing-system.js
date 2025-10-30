/**
 * Drawing System
 * Handles interactive rectangle drawing for creating annotations
 */

import { screenToPdf } from './coordinate-transform.js';
import { generateTempId } from '../utilities.js';
import { getColorForType } from './state-manager.js';

/**
 * Start drawing annotation
 * @param {MouseEvent} event - Mouse down event
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function startDrawing(event, state, refs) {
    if (!state.drawMode) return;

    // Activate drawing lockout to prevent interruptions during micro-adjustments
    activateDrawingLockout(state);

    // Get canvas element for coordinate conversion
    const canvas = refs.pdfEmbed?.querySelector('canvas');
    if (!canvas) return;

    // Get visual coordinates (from mouse event)
    const rect = canvas.getBoundingClientRect();
    const visualX = event.clientX - rect.left;
    const visualY = event.clientY - rect.top;

    // Convert visual coordinates to layout coordinates
    // (layout dimensions match overlay size where annotations are positioned)
    const scaleX = canvas.offsetWidth / rect.width;
    const scaleY = canvas.offsetHeight / rect.height;
    const x = visualX * scaleX;
    const y = visualY * scaleY;

    // Store drawing start point (in layout space)
    state.isDrawing = true;
    state.drawStart = { x, y };
    state.drawPreview = { x, y, width: 0, height: 0 };

    console.log('🖱️ Drawing started at', { x, y }, '(layout space)');
}

/**
 * Update drawing preview
 * @param {MouseEvent} event - Mouse move event
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function updateDrawPreview(event, state, refs) {
    if (!state.isDrawing || !state.drawStart) return;

    // Get canvas element for coordinate conversion
    const canvas = refs.pdfEmbed?.querySelector('canvas');
    if (!canvas) return;

    // Get visual coordinates (from mouse event)
    const rect = canvas.getBoundingClientRect();
    const visualX = event.clientX - rect.left;
    const visualY = event.clientY - rect.top;

    // Convert visual coordinates to layout coordinates
    const scaleX = canvas.offsetWidth / rect.width;
    const scaleY = canvas.offsetHeight / rect.height;
    const currentX = visualX * scaleX;
    const currentY = visualY * scaleY;

    // Calculate rectangle (in layout space)
    const x = Math.min(state.drawStart.x, currentX);
    const y = Math.min(state.drawStart.y, currentY);
    const width = Math.abs(currentX - state.drawStart.x);
    const height = Math.abs(currentY - state.drawStart.y);

    // Update preview (in layout space)
    state.drawPreview = { x, y, width, height };
}

/**
 * Finish drawing and create annotation
 * @param {MouseEvent} event - Mouse up event
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function finishDrawing(event, state, refs) {
    if (!state.isDrawing || !state.drawPreview || !refs.annotationOverlay) return;

    // Clear drawing lockout immediately (drawing complete)
    clearDrawingLockout(state);

    // Minimum size check (prevent accidental clicks)
    const minSize = 20;
    if (state.drawPreview.width < minSize || state.drawPreview.height < minSize) {
        console.log('⚠️ Rectangle too small, ignoring draw');
        resetDrawing(state);
        return;
    }

    console.log('✓ Drawing finished, creating annotation...');

    // Create annotation from drawn rectangle
    createAnnotationFromDrawing(state, refs);

    // Reset drawing state
    resetDrawing(state);
}

/**
 * Reset drawing state
 * @param {Object} state - Component state
 */
function resetDrawing(state) {
    state.isDrawing = false;
    state.drawStart = null;
    state.drawPreview = null;
}

/**
 * Create annotation from drawn rectangle
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
function createAnnotationFromDrawing(state, refs) {
    const preview = state.drawPreview;

    // Convert screen rectangle to PDF coordinates
    const pdfTopLeft = screenToPdf(preview.x, preview.y, refs, state);
    const pdfBottomRight = screenToPdf(
        preview.x + preview.width,
        preview.y + preview.height,
        refs,
        state
    );

    // Screen rectangle for immediate display
    const screenRect = {
        x: preview.x,
        y: preview.y,
        width: preview.width,
        height: preview.height
    };

    // Determine parent annotation ID based on context
    let parentAnnotationId = null;

    if (state.isolationMode) {
        // Isolation mode: use isolated entity annotation as parent
        parentAnnotationId = state.isolationLevel === 'room' ? state.isolatedRoomId :
                           state.isolationLevel === 'location' ? state.isolatedLocationId :
                           state.isolationLevel === 'cabinet_run' ? state.isolatedCabinetRunId :
                           null;
        console.log(`🎯 [createAnnotation] Isolation mode - parentId: ${parentAnnotationId}`);
    } else {
        // Normal mode: find parent based on selected entity
        if (state.drawMode === 'location' && state.activeRoomId) {
            const roomAnno = findAnnotationByEntity('room', state.activeRoomId, state);
            parentAnnotationId = roomAnno?.id || null;
            console.log(`🎯 [createAnnotation] Normal mode - drawing location under room ${state.activeRoomId}, found parent: ${parentAnnotationId}`);
        } else if ((state.drawMode === 'cabinet_run' || state.drawMode === 'cabinet') && state.activeLocationId) {
            const locationAnno = findAnnotationByEntity('room_location', state.activeLocationId, state);
            parentAnnotationId = locationAnno?.id || null;
            console.log(`🎯 [createAnnotation] Normal mode - drawing ${state.drawMode} under location ${state.activeLocationId}, found parent: ${parentAnnotationId}`);
        }
    }

    // Create annotation object
    const annotation = {
        id: generateTempId('temp'),
        type: state.drawMode,
        pdfX: pdfTopLeft.x,
        pdfY: pdfTopLeft.y,
        pdfWidth: Math.abs(pdfBottomRight.x - pdfTopLeft.x),
        pdfHeight: Math.abs(pdfTopLeft.y - pdfBottomRight.y),
        normalizedX: pdfTopLeft.normalized.x,
        normalizedY: pdfTopLeft.normalized.y,
        screenX: screenRect.x,
        screenY: screenRect.y,
        screenWidth: screenRect.width,
        screenHeight: screenRect.height,
        roomId: state.activeRoomId,
        roomName: state.activeRoomName,
        roomLocationId: state.drawMode === 'location' ? state.activeLocationId : null,
        cabinetRunId: state.drawMode === 'cabinet_run' ? state.activeLocationId :
                      (state.drawMode === 'cabinet' && state.isolationMode && state.isolationLevel === 'cabinet_run') ? state.isolatedCabinetRunId : null,
        locationName: state.activeLocationName,
        viewType: 'plan',
        label: generateAnnotationLabel(state),
        color: getDrawColor(state),
        createdAt: new Date(),
        pdfPageId: state.pdfPageId,
        projectId: state.projectId,
        parentId: parentAnnotationId
    };

    state.annotations.push(annotation);
    console.log('✓ Annotation created:', annotation);

    // Dispatch to Livewire for editing
    window.Livewire.dispatch('edit-annotation', { annotation: annotation });
}

/**
 * Generate auto-incrementing label for annotation
 * @param {Object} state - Component state
 * @returns {String} Generated label
 */
function generateAnnotationLabel(state) {
    if (state.drawMode === 'room') {
        return state.activeRoomName || 'Room';
    } else if (state.drawMode === 'location') {
        const count = state.annotations.filter(a =>
            a.type === 'location' &&
            a.roomId === state.activeRoomId
        ).length + 1;
        return `Location ${count}`;
    } else {
        const count = state.annotations.filter(a =>
            a.type === state.drawMode &&
            a.locationId === state.activeLocationId
        ).length + 1;

        if (state.drawMode === 'cabinet_run') {
            return `Run ${count}`;
        } else {
            return `Cabinet ${count}`;
        }
    }
}

/**
 * Get color for current draw mode
 * @param {Object} state - Component state
 * @returns {String} Color hex code
 */
function getDrawColor(state) {
    return getColorForType(state.drawMode);
}

/**
 * Set draw mode (toggle on/off)
 * @param {String} mode - Draw mode to set
 * @param {Object} state - Component state
 * @param {Function} checkDuplicateCallback - Callback to check for duplicates
 * @param {Function} highlightCallback - Callback to highlight existing annotation
 */
export function setDrawMode(mode, state, checkDuplicateCallback, highlightCallback) {
    // Toggle off if clicking same mode
    if (state.drawMode === mode) {
        state.drawMode = null;
        return;
    }

    // Check for duplicate entity before entering draw mode
    if (checkDuplicateCallback) {
        const existingAnnotation = checkDuplicateCallback(mode);

        if (existingAnnotation) {
            // Show notification
            if (window.FilamentNotification) {
                new window.FilamentNotification()
                    .title('Annotation Already Exists')
                    .warning()
                    .body(`${existingAnnotation.label} already has an annotation on this page. Highlighting it now.`)
                    .send();
            }

            // Highlight existing annotation
            if (highlightCallback) {
                highlightCallback(existingAnnotation);
            }

            return;
        }
    }

    // Enter draw mode
    state.drawMode = mode;
}

/**
 * Check if user can draw location (requires room context)
 * @param {Object} state - Component state
 * @returns {Boolean} True if can draw location
 */
export function canDrawLocation(state) {
    // In room isolation mode: always enabled
    if (state.isolationMode && state.isolationLevel === 'room') {
        return state.pdfReady;
    }
    // Normal mode: requires room selection
    return state.activeRoomId && state.pdfReady;
}

/**
 * Check if user can draw cabinet run or cabinet (requires location context)
 * @param {Object} state - Component state
 * @returns {Boolean} True if can draw
 */
export function canDraw(state) {
    // In location isolation mode: always enabled
    if (state.isolationMode && state.isolationLevel === 'location') {
        return state.pdfReady;
    }
    // In cabinet_run isolation mode: always enabled
    if (state.isolationMode && state.isolationLevel === 'cabinet_run') {
        return state.pdfReady;
    }
    // Normal mode: requires room + location selection
    return state.activeRoomId && state.activeLocationId && state.pdfReady;
}

/**
 * Clear drawing context
 * @param {Object} state - Component state
 */
export function clearContext(state) {
    state.activeRoomId = null;
    state.activeRoomName = '';
    state.activeLocationId = null;
    state.activeLocationName = '';
    state.roomSearchQuery = '';
    state.locationSearchQuery = '';
    state.drawMode = null;
}

/**
 * Get context label for display
 * @param {Object} state - Component state
 * @returns {String} Context label
 */
export function getContextLabel(state) {
    if (!state.activeRoomName) return 'No context selected';
    if (!state.activeLocationName) return `🏠 ${state.activeRoomName}`;
    return `🏠 ${state.activeRoomName} → 📍 ${state.activeLocationName}`;
}

/**
 * Helper: Find annotation by entity (placeholder - actual function in annotation-manager)
 */
function findAnnotationByEntity(entityType, entityId, state) {
    // This is imported from annotation-manager in the actual implementation
    if (!entityId || !state.annotations) return null;

    for (const anno of state.annotations) {
        if (entityType === 'room' && anno.roomId === entityId && anno.type === 'room') {
            return anno;
        } else if (entityType === 'room_location' && anno.roomLocationId === entityId && anno.type === 'location') {
            return anno;
        } else if (entityType === 'cabinet_run' && anno.cabinetRunId === entityId && anno.type === 'cabinet_run') {
            return anno;
        }
    }

    return null;
}

/**
 * Drawing Lockout System
 * Prevents Alpine reactivity interruptions during drawing by debouncing save/update operations
 */

/**
 * Activate drawing lockout to prevent interruptions during micro-adjustments
 * Resets timer if called again (for continuous drawing adjustments)
 * @param {Object} state - Component state
 */
export function activateDrawingLockout(state) {
    // Initialize lockout state if not present
    if (state._drawingLockout === undefined) {
        state._drawingLockout = false;
        state._drawingLockoutTimer = null;
    }

    // Clear existing timer if drawing continues (micro-adjustments)
    if (state._drawingLockoutTimer) {
        clearTimeout(state._drawingLockoutTimer);
        console.log('🔄 Drawing lockout timer reset (micro-adjustment detected)');
    }

    // Set lockout flag
    state._drawingLockout = true;

    // Schedule lockout release after 1 second of no drawing activity
    state._drawingLockoutTimer = setTimeout(() => {
        state._drawingLockout = false;
        state._drawingLockoutTimer = null;
        console.log('✓ Drawing lockout released (1s inactivity)');
    }, 1000);

    console.log('🔒 Drawing lockout activated');
}

/**
 * Check if drawing lockout is active (prevents saves/updates)
 * @param {Object} state - Component state
 * @returns {Boolean} True if lockout is active
 */
export function isDrawingLocked(state) {
    return state._drawingLockout === true;
}

/**
 * Clear drawing lockout immediately (call on mouseup/draw complete)
 * @param {Object} state - Component state
 */
export function clearDrawingLockout(state) {
    if (state._drawingLockoutTimer) {
        clearTimeout(state._drawingLockoutTimer);
        state._drawingLockoutTimer = null;
    }
    state._drawingLockout = false;
    console.log('🔓 Drawing lockout cleared');
}
