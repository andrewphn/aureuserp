/**
 * Resize and Move System
 * Handles annotation resizing and repositioning with throttling
 */

import { screenToPdf, pdfToScreen } from './coordinate-transform.js';
import { throttleRAF, getCsrfToken } from '../utilities.js';

/**
 * Start resizing annotation
 * @param {MouseEvent} event - Mouse down event
 * @param {Object} annotation - Annotation being resized
 * @param {String} handle - Resize handle direction (n, ne, e, se, s, sw, w, nw)
 * @param {Object} state - Component state
 */
export function startResize(event, annotation, handle, state) {
    event.stopPropagation();

    // Activate resize lockout to prevent interruptions
    activateResizeLockout(state);

    state.isResizing = true;
    state.activeAnnotationId = annotation.id;
    state.resizeHandle = handle;
    state.resizeStart = {
        mouseX: event.clientX,
        mouseY: event.clientY,
        annoX: annotation.screenX,
        annoY: annotation.screenY,
        annoWidth: annotation.screenWidth,
        annoHeight: annotation.screenHeight
    };

    console.log('🔄 Resize started:', { annotation: annotation.label, handle });

    // NOTE: Document event listeners are handled by inline handlers in pdf-viewer-core.js
    // which have proper access to Alpine.js context via 'this'
    // Do NOT add document listeners here as they lose context
}

/**
 * Update resize preview (throttled with RAF)
 * @param {MouseEvent} event - Mouse move event
 * @param {Object} state - Component state
 */
export const handleResizeMove = throttleRAF(function(event, state) {
    if (!state.isResizing || !state.resizeStart) return;

    updateResize(event, state);
}, {}, 'resizeTicking');

/**
 * Update resize dimensions
 * @param {MouseEvent} event - Mouse move event
 * @param {Object} state - Component state
 */
function updateResize(event, state) {
    console.log('🔄 [RESIZE] updateResize called', {
        isResizing: state.isResizing,
        hasResizeStart: !!state.resizeStart,
        lockoutActive: state._resizeLockout,
        activeAnnotationId: state.activeAnnotationId
    });

    const deltaX = event.clientX - state.resizeStart.mouseX;
    const deltaY = event.clientY - state.resizeStart.mouseY;

    const annotation = state.annotations.find(a => a.id === state.activeAnnotationId);
    if (!annotation) {
        console.warn('⚠️ [RESIZE] No annotation found for ID:', state.activeAnnotationId);
        return;
    }

    // Calculate new dimensions based on handle direction
    let newX = state.resizeStart.annoX;
    let newY = state.resizeStart.annoY;
    let newWidth = state.resizeStart.annoWidth;
    let newHeight = state.resizeStart.annoHeight;

    const handle = state.resizeHandle;

    // Horizontal adjustments
    if (handle.includes('w')) {
        newX = state.resizeStart.annoX + deltaX;
        newWidth = state.resizeStart.annoWidth - deltaX;
    } else if (handle.includes('e')) {
        newWidth = state.resizeStart.annoWidth + deltaX;
    }

    // Vertical adjustments
    if (handle.includes('n')) {
        newY = state.resizeStart.annoY + deltaY;
        newHeight = state.resizeStart.annoHeight - deltaY;
    } else if (handle.includes('s')) {
        newHeight = state.resizeStart.annoHeight + deltaY;
    }

    // Enforce minimum size
    const minSize = 20;
    if (newWidth < minSize || newHeight < minSize) {
        console.log('⚠️ [RESIZE] Size too small, skipping update');
        return;
    }

    console.log('📏 [RESIZE] Updating annotation dimensions', {
        annotation: annotation.label,
        newWidth: newWidth.toFixed(1),
        newHeight: newHeight.toFixed(1)
    });

    // Update annotation screen coordinates
    annotation.screenX = newX;
    annotation.screenY = newY;
    annotation.screenWidth = newWidth;
    annotation.screenHeight = newHeight;
}

/**
 * Finish resizing and save changes
 * @param {MouseEvent} event - Mouse up event
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function handleResizeEnd(event, state, refs) {
    if (!state.isResizing) return;

    // Check if annotation actually resized (minimum 2 pixels change in width or height)
    const annotation = state.annotations.find(a => a.id === state.activeAnnotationId);
    if (annotation && state.resizeStart) {
        const deltaWidth = Math.abs(annotation.screenWidth - state.resizeStart.annoWidth);
        const deltaHeight = Math.abs(annotation.screenHeight - state.resizeStart.annoHeight);
        const resized = deltaWidth > 2 || deltaHeight > 2;

        if (!resized) {
            console.log('⏸️ Resize cancelled - no significant size change detected');

            // Reset annotation to original dimensions
            annotation.screenX = state.resizeStart.annoX;
            annotation.screenY = state.resizeStart.annoY;
            annotation.screenWidth = state.resizeStart.annoWidth;
            annotation.screenHeight = state.resizeStart.annoHeight;

            // Clean up immediately without saving
            state.isResizing = false;
            state.resizeHandle = null;
            state.resizeStart = null;
            state.activeAnnotationId = null;

            // Clear lockout since we're not saving
            clearResizeLockout(state);

            return;
        }

        console.log('✓ Resize finished, saving...');

        // DON'T clear lockout yet - keep it active until save completes
        // This prevents interruptions during micro-adjustments
        // Lockout will be cleared by debouncedSaveAnnotation after save completes

        // Calculate PDF coordinates from final screen position
        finishResize(annotation, state, refs);
    }

    // Clean up
    state.isResizing = false;
    state.resizeHandle = null;
    state.resizeStart = null;
    state.activeAnnotationId = null;

    // Remove global listeners
    document.removeEventListener('mousemove', handleResizeMove);
    document.removeEventListener('mouseup', handleResizeEnd);
}

/**
 * Finish resize and update PDF coordinates
 * @param {Object} annotation - Resized annotation
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
function finishResize(annotation, state, refs) {
    // Convert screen coordinates back to PDF coordinates
    const pdfTopLeft = screenToPdf(annotation.screenX, annotation.screenY, refs, state);
    const pdfBottomRight = screenToPdf(
        annotation.screenX + annotation.screenWidth,
        annotation.screenY + annotation.screenHeight,
        refs,
        state
    );

    // Update PDF coordinates
    annotation.pdfX = pdfTopLeft.x;
    annotation.pdfY = pdfTopLeft.y;
    annotation.pdfWidth = Math.abs(pdfBottomRight.x - pdfTopLeft.x);
    annotation.pdfHeight = Math.abs(pdfTopLeft.y - pdfBottomRight.y);
    annotation.normalizedX = pdfTopLeft.normalized.x;
    annotation.normalizedY = pdfTopLeft.normalized.y;

    console.log('✓ Resize completed:', annotation.label);

    // Debounced save to server
    debouncedSaveAnnotation(annotation, state);
}

/**
 * Start moving annotation
 * @param {MouseEvent} event - Mouse down event
 * @param {Object} annotation - Annotation being moved
 * @param {Object} state - Component state
 */
export function startMove(event, annotation, state) {
    // Don't start move if clicking on resize handle
    if (event.target.classList.contains('resize-handle')) {
        return;
    }

    // Activate resize lockout to prevent interruptions
    activateResizeLockout(state);

    state.isMoving = true;
    state.activeAnnotationId = annotation.id;
    state.moveStart = {
        mouseX: event.clientX,
        mouseY: event.clientY,
        annoX: annotation.screenX,
        annoY: annotation.screenY
    };

    console.log('🖱️ Move started:', annotation.label);

    // NOTE: Document event listeners are handled by inline handlers in pdf-viewer-core.js
    // which have proper access to Alpine.js context via 'this'
    // Do NOT add document listeners here as they lose context
}

/**
 * Update move position (throttled with RAF)
 * @param {MouseEvent} event - Mouse move event
 * @param {Object} state - Component state
 */
export const handleMoveUpdate = throttleRAF(function(event, state) {
    if (!state.isMoving || !state.moveStart) return;

    updateMove(event, state);
}, {}, 'moveTicking');

/**
 * Update annotation position
 * @param {MouseEvent} event - Mouse move event
 * @param {Object} state - Component state
 */
function updateMove(event, state) {
    console.log('🖱️ [MOVE] updateMove called', {
        isMoving: state.isMoving,
        hasMoveStart: !!state.moveStart,
        lockoutActive: state._resizeLockout,
        activeAnnotationId: state.activeAnnotationId
    });

    const deltaX = event.clientX - state.moveStart.mouseX;
    const deltaY = event.clientY - state.moveStart.mouseY;

    const annotation = state.annotations.find(a => a.id === state.activeAnnotationId);
    if (!annotation) {
        console.warn('⚠️ [MOVE] No annotation found for ID:', state.activeAnnotationId);
        return;
    }

    console.log('📍 [MOVE] Updating annotation position', {
        annotation: annotation.label,
        deltaX: deltaX.toFixed(1),
        deltaY: deltaY.toFixed(1)
    });

    // Update screen position
    annotation.screenX = state.moveStart.annoX + deltaX;
    annotation.screenY = state.moveStart.annoY + deltaY;
}

/**
 * Finish moving and save changes
 * @param {MouseEvent} event - Mouse up event
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function handleMoveEnd(event, state, refs) {
    if (!state.isMoving) return;

    // Check if annotation actually moved (minimum 2 pixels)
    const annotation = state.annotations.find(a => a.id === state.activeAnnotationId);
    if (annotation && state.moveStart) {
        const deltaX = Math.abs(annotation.screenX - state.moveStart.annoX);
        const deltaY = Math.abs(annotation.screenY - state.moveStart.annoY);
        const moved = deltaX > 2 || deltaY > 2;

        if (!moved) {
            console.log('⏸️ Move cancelled - no significant movement detected');

            // Reset annotation to original position
            annotation.screenX = state.moveStart.annoX;
            annotation.screenY = state.moveStart.annoY;

            // Clean up immediately without saving
            state.isMoving = false;
            state.moveStart = null;
            state.activeAnnotationId = null;

            // Clear lockout since we're not saving
            clearResizeLockout(state);

            return;
        }

        console.log('✓ Move finished, saving...');

        // DON'T clear lockout yet - keep it active until save completes
        // This prevents interruptions during micro-adjustments
        // Lockout will be cleared by debouncedSaveAnnotation after save completes

        // Calculate PDF coordinates from final screen position
        finishMove(annotation, state, refs);
    }

    // Clean up
    state.isMoving = false;
    state.moveStart = null;
    state.activeAnnotationId = null;

    // Remove global listeners
    document.removeEventListener('mousemove', handleMoveUpdate);
    document.removeEventListener('mouseup', handleMoveEnd);
}

/**
 * Finish move and update PDF coordinates
 * @param {Object} annotation - Moved annotation
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
function finishMove(annotation, state, refs) {
    // Convert screen coordinates back to PDF coordinates
    const pdfPos = screenToPdf(annotation.screenX, annotation.screenY, refs, state);

    // Update PDF coordinates (keep width/height the same)
    annotation.pdfX = pdfPos.x;
    annotation.pdfY = pdfPos.y;
    annotation.normalizedX = pdfPos.normalized.x;
    annotation.normalizedY = pdfPos.normalized.y;

    console.log('✓ Move completed:', annotation.label);

    // Debounced save to server
    debouncedSaveAnnotation(annotation, state);
}

/**
 * Debounced save to prevent excessive API calls
 * @param {Object} annotation - Annotation to save
 * @param {Object} state - Component state
 */
function debouncedSaveAnnotation(annotation, state) {
    console.log('💾 [SAVE] debouncedSaveAnnotation called', {
        annotation: annotation.label,
        lockoutActive: state._resizeLockout,
        hasPendingTimeout: !!state.resizeSaveTimeout,
        isResizing: state.isResizing,
        isMoving: state.isMoving
    });

    // Check if resize lockout is active
    if (state._resizeLockout) {
        console.warn('⚠️ [SAVE] Resize lockout active - save requested during active resize/move!');
    }

    // Clear existing timeout
    if (state.resizeSaveTimeout) {
        console.log('🔄 [SAVE] Clearing existing save timeout');
        clearTimeout(state.resizeSaveTimeout);
    }

    // Store pending changes
    state.pendingResizeChanges = annotation;

    // Save after 1 second of inactivity
    state.resizeSaveTimeout = setTimeout(async () => {
        console.log('⏰ [SAVE] Save timeout fired', {
            annotation: state.pendingResizeChanges?.label,
            lockoutActive: state._resizeLockout,
            isResizing: state.isResizing,
            isMoving: state.isMoving
        });

        if (state.pendingResizeChanges) {
            await saveAnnotationToServer(state.pendingResizeChanges, state);
            state.pendingResizeChanges = null;

            // Clear lockout after save completes (end of micro-adjustment session)
            clearResizeLockout(state);
        }
    }, 1000);

    console.log('✓ [SAVE] Save scheduled for 1s from now');
}

/**
 * Save single annotation to server
 * @param {Object} annotation - Annotation to save
 * @param {Object} state - Component state
 * @returns {Promise<void>}
 */
async function saveAnnotationToServer(annotation, state) {
    console.log('🌐 [API] saveAnnotationToServer called', {
        annotation: annotation.label,
        annotationId: annotation.id,
        lockoutActive: state._resizeLockout,
        isResizing: state.isResizing,
        isMoving: state.isMoving
    });

    // Don't save temporary annotations
    if (annotation.id.toString().startsWith('temp_')) {
        console.log('⏭️ [API] Skipping save for temporary annotation');
        return;
    }

    // Note: Lockout remains active during debounced save to prevent interruptions
    // It will be cleared after this save completes in debouncedSaveAnnotation()

    console.log('📤 [API] Sending PATCH request to server...');

    try {
        const response = await fetch(`/api/pdf/page/annotations/${annotation.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                x: annotation.normalizedX,
                y: annotation.normalizedY,
                width: annotation.pdfWidth / state.pageDimensions.width,
                height: annotation.pdfHeight / state.pageDimensions.height
            })
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ [API] Annotation saved successfully:', annotation.label);
        } else {
            throw new Error(data.error || 'Failed to save annotation');
        }
    } catch (error) {
        console.error('❌ [API] Failed to save annotation:', error);
        // Don't show alert for auto-save failures
    }
}

/**
 * Get cursor style for resize handle
 * @param {String} handle - Handle direction (n, ne, e, se, s, sw, w, nw)
 * @returns {String} CSS cursor value
 */
export function getResizeCursor(handle) {
    const cursors = {
        n: 'n-resize',
        ne: 'ne-resize',
        e: 'e-resize',
        se: 'se-resize',
        s: 's-resize',
        sw: 'sw-resize',
        w: 'w-resize',
        nw: 'nw-resize'
    };
    return cursors[handle] || 'default';
}

/**
 * Get all resize handle directions
 * @returns {Array<String>} Array of handle directions
 */
export function getResizeHandles() {
    return ['n', 'ne', 'e', 'se', 's', 'sw', 'w', 'nw'];
}

/**
 * Get position style for resize handle
 * @param {String} handle - Handle direction
 * @param {Number} size - Handle size in pixels
 * @returns {Object} Position styles
 */
export function getHandlePosition(handle, size = 8) {
    const half = size / 2;
    const positions = {
        n:  { left: `50%`, top: `-${half}px`, transform: 'translateX(-50%)' },
        ne: { right: `-${half}px`, top: `-${half}px` },
        e:  { right: `-${half}px`, top: `50%`, transform: 'translateY(-50%)' },
        se: { right: `-${half}px`, bottom: `-${half}px` },
        s:  { left: `50%`, bottom: `-${half}px`, transform: 'translateX(-50%)' },
        sw: { left: `-${half}px`, bottom: `-${half}px` },
        w:  { left: `-${half}px`, top: `50%`, transform: 'translateY(-50%)' },
        nw: { left: `-${half}px`, top: `-${half}px` }
    };
    return positions[handle] || {};
}

/**
 * Resize/Move Lockout System
 * Prevents Alpine reactivity and auto-save interruptions during resize/move operations
 * Lockout activates on first mousedown and stays active until save completes
 * No auto-expire timer - accommodates any length of adjustment including micro-adjustments
 */

/**
 * Activate resize/move lockout to prevent interruptions during adjustments
 * Lockout stays active until save completes - no auto-expire timer
 * @param {Object} state - Component state
 */
export function activateResizeLockout(state) {
    // Initialize lockout state if not present
    if (state._resizeLockout === undefined) {
        state._resizeLockout = false;
    }

    // Set lockout flag (no timer - stays active until save completes)
    if (!state._resizeLockout) {
        state._resizeLockout = true;
        console.log('🔒 Resize lockout activated (stays active until save completes)');
    }
}

/**
 * Check if resize/move lockout is active (prevents saves/updates)
 * @param {Object} state - Component state
 * @returns {Boolean} True if lockout is active
 */
export function isResizeLocked(state) {
    return state._resizeLockout === true;
}

/**
 * Clear resize/move lockout after save completes
 * @param {Object} state - Component state
 */
export function clearResizeLockout(state) {
    if (state._resizeLockout) {
        state._resizeLockout = false;
        console.log('🔓 Resize lockout cleared (save complete)');
    }
}
