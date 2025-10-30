/**
 * Zoom Manager
 * Handles zoom controls and viewport management
 */

import { syncOverlayToCanvas, updateAnnotationPositions, invalidateZoomCache } from './coordinate-transform.js';

/**
 * Zoom in
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function zoomIn(state, refs, callbacks) {
    const newZoom = Math.min(state.zoomLevel + 0.25, state.zoomMax);
    await setZoom(newZoom, state, refs, callbacks);
}

/**
 * Zoom out
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function zoomOut(state, refs, callbacks) {
    const newZoom = Math.max(state.zoomLevel - 0.25, state.zoomMin);
    await setZoom(newZoom, state, refs, callbacks);
}

/**
 * Reset zoom to 100%
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function resetZoom(state, refs, callbacks) {
    await setZoom(1.0, state, refs, callbacks);
}

/**
 * Set zoom to specific level
 * @param {Number} level - Zoom level (1.0 = 100%)
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function setZoom(level, state, refs, callbacks) {
    state.zoomLevel = level;

    // Invalidate overlay rect cache
    state._overlayRect = null;

    // Invalidate CSS zoom cache
    invalidateZoomCache(state);

    // Re-render PDF at new zoom level
    if (callbacks.displayPdf) {
        await callbacks.displayPdf();
    }

    // Wait for DOM to update with new canvas size
    if (callbacks.$nextTick) {
        await callbacks.$nextTick();
    }
    await new Promise(resolve => setTimeout(resolve, 100));

    // Sync overlay dimensions to canvas after zoom
    syncOverlayToCanvas(refs, state);

    // Invalidate cache again to force fresh rect
    state._overlayRect = null;

    // Re-render annotations at new zoom level
    updateAnnotationPositions(state, refs);

    // Update isolation mask if in isolation mode
    if (state.isolationMode && callbacks.updateIsolationMask) {
        callbacks.updateIsolationMask();
    }

    console.log(`🔍 Zoom set to ${Math.round(level * 100)}%`);
}

/**
 * Get zoom percentage for display
 * @param {Object} state - Component state
 * @returns {Number} Zoom percentage (e.g., 100 for 100%)
 */
export function getZoomPercentage(state) {
    return Math.round(state.zoomLevel * 100);
}

/**
 * Zoom to fit annotation on screen
 * @param {Object} annotation - Annotation to zoom to
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function zoomToFitAnnotation(annotation, state, refs, callbacks) {
    console.log('🔍 Zooming to fit annotation:', annotation.label);

    // Find annotation with screen coordinates
    let annotationWithCoords = null;

    // Strategy 1: Anno already has screen coordinates
    if (annotation.screenWidth && annotation.screenHeight) {
        annotationWithCoords = annotation;
        console.log('   ✓ Using annotation directly (has screen coordinates)');
    }
    // Strategy 2: Find by annotation ID
    else {
        annotationWithCoords = state.annotations.find(a =>
            a.id === annotation.id &&
            a.screenWidth &&
            a.screenHeight
        );

        if (annotationWithCoords) {
            console.log('   ✓ Found by annotation ID');
        }
    }

    // Strategy 3: Find by type and entity ID
    if (!annotationWithCoords) {
        annotationWithCoords = state.annotations.find(a => {
            if (a.type !== annotation.type || !a.screenWidth || !a.screenHeight) {
                return false;
            }

            return a.roomId === annotation.id ||
                   a.roomLocationId === annotation.id ||
                   a.cabinetRunId === annotation.id ||
                   a.cabinetId === annotation.id;
        });

        if (annotationWithCoords) {
            console.log(`   ✓ Found by entity ID match for type: ${annotation.type}`);
        }
    }

    if (!annotationWithCoords) {
        console.warn('⚠️ Annotation does not have valid screen coordinates yet, skipping zoom');
        return;
    }

    // Get container dimensions
    const container = refs.annotationOverlay;
    if (!container) {
        console.warn('⚠️ Container not found for zoom calculation');
        return;
    }

    const containerRect = container.getBoundingClientRect();
    const containerWidth = containerRect.width;
    const containerHeight = containerRect.height;

    // Add padding around annotation (20% margin)
    const paddingFactor = 0.8;

    // Calculate required zoom to fit annotation
    const zoomX = (containerWidth * paddingFactor) / annotationWithCoords.screenWidth;
    const zoomY = (containerHeight * paddingFactor) / annotationWithCoords.screenHeight;

    // Use the smaller zoom to ensure both dimensions fit
    let targetZoom = Math.min(zoomX, zoomY);

    // Clamp to zoom limits
    targetZoom = Math.max(state.zoomMin, Math.min(targetZoom, state.zoomMax));

    // Apply the zoom
    await setZoom(targetZoom, state, refs, callbacks);

    // After zoom, scroll annotation to center of viewport
    if (callbacks.$nextTick) {
        await callbacks.$nextTick();
    }

    // Center the annotation in viewport
    await centerAnnotationInViewport(annotationWithCoords, state, refs);

    console.log(`✓ Zoomed to ${Math.round(targetZoom * 100)}% and centered annotation`);
}

/**
 * Center annotation in viewport
 * @param {Object} annotation - Annotation to center
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @returns {Promise<void>}
 */
async function centerAnnotationInViewport(annotation, state, refs) {
    // Get canvas element
    const canvas = refs.pdfEmbed?.querySelector('canvas');
    if (!canvas) {
        console.warn('⚠️ Canvas not found for centering');
        return;
    }

    const container = refs.annotationOverlay;
    if (!container) return;

    const containerRect = container.getBoundingClientRect();
    const containerWidth = containerRect.width;
    const containerHeight = containerRect.height;

    // Find scroll containers
    let scrollContainerX = findScrollContainer(container, 'X');
    let scrollContainerY = findScrollContainer(container, 'Y');

    // Calculate annotation center in screen coordinates
    const annoScreenPos = {
        x: annotation.screenX + annotation.screenWidth / 2,
        y: annotation.screenY + annotation.screenHeight / 2
    };

    console.log('📍 Centering calculation:', {
        annoScreenPos,
        containerWidth,
        containerHeight,
        targetScrollLeft: annoScreenPos.x - containerWidth / 2,
        targetScrollTop: annoScreenPos.y - containerHeight / 2
    });

    // Center the annotation in viewport
    scrollContainerX.scrollLeft = annoScreenPos.x - containerWidth / 2;
    scrollContainerY.scrollTop = annoScreenPos.y - containerHeight / 2;

    console.log('📍 After scroll:', {
        actualScrollLeft: scrollContainerX.scrollLeft,
        actualScrollTop: scrollContainerY.scrollTop
    });
}

/**
 * Find scroll container for specific axis
 * @param {HTMLElement} element - Starting element
 * @param {String} axis - Axis ('X' or 'Y')
 * @returns {HTMLElement} Scroll container
 */
function findScrollContainer(element, axis) {
    let current = element.parentElement;

    while (current && current !== document.body) {
        const style = window.getComputedStyle(current);
        const overflow = axis === 'X' ? style.overflowX : style.overflowY;
        const hasScroll = axis === 'X'
            ? current.scrollWidth > current.clientWidth
            : current.scrollHeight > current.clientHeight;

        if ((overflow === 'auto' || overflow === 'scroll') && hasScroll) {
            return current;
        }

        current = current.parentElement;
    }

    // Default to documentElement
    return document.documentElement || document.body;
}

/**
 * Check if at minimum zoom
 * @param {Object} state - Component state
 * @returns {Boolean} True if at minimum zoom
 */
export function isAtMinZoom(state) {
    return state.zoomLevel <= state.zoomMin;
}

/**
 * Check if at maximum zoom
 * @param {Object} state - Component state
 * @returns {Boolean} True if at maximum zoom
 */
export function isAtMaxZoom(state) {
    return state.zoomLevel >= state.zoomMax;
}
