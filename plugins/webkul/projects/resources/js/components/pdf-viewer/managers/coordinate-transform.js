/**
 * Coordinate Transformation Manager
 * Handles conversion between PDF coordinates and screen coordinates
 */

/**
 * Initialize coordinate transformation system
 * @param {Object} state - Component state
 */
export function initializeCoordinateSystem(state) {
    // Cache overlay rect for performance
    state._overlayRect = null;
    state._lastRectUpdate = 0;
    state._rectCacheMs = 100;

    // Cache CSS zoom for performance
    state._cachedZoom = undefined;
}

/**
 * Get effective CSS zoom factor (for debugging only)
 * NOTE: This detects CSS zoom property, NOT browser zoom (Ctrl/Cmd +/-)
 * Browser zoom is automatically handled by getBoundingClientRect()
 * Keeping for debugging purposes only - no longer used in coordinate calculations
 * @param {Object} state - Component state
 * @returns {Number} CSS zoom factor (usually 1.0)
 */
export function getEffectiveZoom(state) {
    // Return cached zoom if available
    if (state._cachedZoom !== undefined) {
        return state._cachedZoom;
    }

    // Detect zoom from computed style (CSS zoom property only, not browser zoom)
    const computedStyle = window.getComputedStyle(document.documentElement);
    const zoom = parseFloat(computedStyle.zoom || '1');

    state._cachedZoom = zoom;
    console.log('🔍 CSS zoom factor (not browser zoom):', zoom);

    return zoom;
}

/**
 * Invalidate CSS zoom cache (call after zoom changes)
 * @param {Object} state - Component state
 */
export function invalidateZoomCache(state) {
    state._cachedZoom = undefined;
}

/**
 * Get canvas bounding rectangle (cached for performance)
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {DOMRect|null} Canvas bounding rectangle
 */
export function getCanvasRect(refs, state) {
    if (!state) {
        // Fallback for direct calls without state
        const canvas = refs.pdfEmbed?.querySelector('canvas');
        return canvas ? canvas.getBoundingClientRect() : null;
    }

    const now = Date.now();

    // Return cached rect if still valid
    if (state._overlayRect && (now - state._lastRectUpdate) < state._rectCacheMs) {
        return state._overlayRect;
    }

    // Get the canvas element (not the overlay)
    const canvas = refs.pdfEmbed?.querySelector('canvas');
    if (!canvas) return null;

    state._overlayRect = canvas.getBoundingClientRect();
    state._lastRectUpdate = now;

    return state._overlayRect;
}

/**
 * Get cached overlay bounding rectangle (uses canvas rect)
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {DOMRect|null} Bounding rectangle
 */
export function getOverlayRect(refs, state) {
    return getCanvasRect(refs, state);
}

/**
 * Calculate canvas scale based on actual vs natural dimensions
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {Number} Scale factor
 */
export function getCanvasScale(refs, state) {
    if (!refs.pdfEmbed || !state.pageDimensions) {
        return 1.0;
    }

    const canvas = refs.pdfEmbed.querySelector('canvas');
    if (!canvas) return 1.0;

    // Canvas scale = visual display width / natural PDF width
    const rect = canvas.getBoundingClientRect();
    const actualWidth = rect.width;
    const naturalWidth = state.pageDimensions.width;

    return actualWidth / naturalWidth;
}

/**
 * Convert PDF coordinates to screen coordinates
 * @param {Number} pdfX - PDF X coordinate
 * @param {Number} pdfY - PDF Y coordinate (bottom-left origin)
 * @param {Number} pdfWidth - PDF width
 * @param {Number} pdfHeight - PDF height
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {Object} Screen position {x, y, width, height}
 */
export function pdfToScreen(pdfX, pdfY, pdfWidth = 0, pdfHeight = 0, refs, state) {
    if (!state.pageDimensions) {
        return { x: 0, y: 0, width: 0, height: 0 };
    }

    // Get canvas element for layout dimensions (offsetWidth/Height)
    const canvas = refs.pdfEmbed?.querySelector('canvas');
    if (!canvas) {
        return { x: 0, y: 0, width: 0, height: 0 };
    }

    // Use layout dimensions (offsetWidth/Height) for calculations
    // These match the overlay's layout dimensions (not affected by browser zoom)
    // Annotations are positioned in layout space via transform: translate()
    const layoutWidth = canvas.offsetWidth;
    const layoutHeight = canvas.offsetHeight;

    // Normalize PDF coordinates to 0-1 range
    const normalizedX = pdfX / state.pageDimensions.width;
    const normalizedY = (state.pageDimensions.height - pdfY) / state.pageDimensions.height;

    // Calculate screen coordinates using layout dimensions
    // This ensures annotations align with canvas in the overlay's coordinate space
    const screenX = normalizedX * layoutWidth;
    const screenY = normalizedY * layoutHeight;
    const screenWidth = (pdfWidth / state.pageDimensions.width) * layoutWidth;
    const screenHeight = (pdfHeight / state.pageDimensions.height) * layoutHeight;

    return {
        x: screenX,
        y: screenY,
        width: screenWidth,
        height: screenHeight
    };
}

/**
 * Convert screen coordinates to PDF coordinates
 * @param {Number} screenX - Screen X position
 * @param {Number} screenY - Screen Y position
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {Object} PDF position {x, y, normalized: {x, y}}
 */
export function screenToPdf(screenX, screenY, refs, state) {
    if (!state.pageDimensions) {
        return { x: 0, y: 0, normalized: { x: 0, y: 0 } };
    }

    // Get canvas element for layout dimensions
    const canvas = refs.pdfEmbed?.querySelector('canvas');
    if (!canvas) {
        return { x: 0, y: 0, normalized: { x: 0, y: 0 } };
    }

    // Use layout dimensions (offsetWidth/Height) for calculations
    // screenX/screenY come from mouse events subtracted by canvas.getBoundingClientRect()
    // which gives visual-space coordinates, but we need to normalize using layout dimensions
    // to match the overlay coordinate space where annotations are positioned
    const layoutWidth = canvas.offsetWidth;
    const layoutHeight = canvas.offsetHeight;

    // Normalize to 0-1 range using layout dimensions
    const normalizedX = screenX / layoutWidth;
    const normalizedY = screenY / layoutHeight;

    // Convert to PDF coordinates (PDF y-axis is from bottom)
    const pdfX = normalizedX * state.pageDimensions.width;
    const pdfY = state.pageDimensions.height - (normalizedY * state.pageDimensions.height);

    return {
        x: pdfX,
        y: pdfY,
        normalized: {
            x: normalizedX,
            y: normalizedY
        }
    };
}

/**
 * Get annotation screen position from stored PDF coordinates
 * @param {Object} annotation - Annotation object
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 * @returns {Object} Screen position {x, y, width, height}
 */
export function getAnnotationScreenPosition(annotation, refs, state) {
    return pdfToScreen(
        annotation.pdfX,
        annotation.pdfY,
        annotation.pdfWidth,
        annotation.pdfHeight,
        refs,
        state
    );
}

/**
 * Sync overlay dimensions to match canvas
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} state - Component state
 */
export function syncOverlayToCanvas(refs, state) {
    if (!refs.pdfEmbed || !refs.annotationOverlay) return;

    const canvas = refs.pdfEmbed.querySelector('canvas');
    if (!canvas) return;

    // Use offsetWidth/Height for setting CSS dimensions (layout space)
    // Browser will apply zoom during rendering to match visual canvas size
    // This ensures overlay covers canvas exactly, accounting for browser zoom
    const width = canvas.offsetWidth;
    const height = canvas.offsetHeight;

    // Set overlay to match canvas layout dimensions
    // (browser zoom will be applied during rendering)
    state.overlayWidth = `${width}px`;
    state.overlayHeight = `${height}px`;

    // Invalidate overlay rect cache
    state._overlayRect = null;

    console.log(`📐 Overlay synced to canvas: ${width} × ${height} (layout space, zoom applied by browser)`);
}

/**
 * Update all annotation screen positions for current zoom/scale
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function updateAnnotationPositions(state, refs) {
    if (!state.pageDimensions) return;

    // Mutate annotations in-place to preserve Alpine reactivity
    state.annotations.forEach(anno => {
        const screenPos = pdfToScreen(
            anno.pdfX,
            anno.pdfY,
            anno.pdfWidth,
            anno.pdfHeight,
            refs,
            state
        );

        anno.screenX = screenPos.x;
        anno.screenY = screenPos.y;
        anno.screenWidth = screenPos.width;
        anno.screenHeight = screenPos.height;
    });
}

/**
 * Render/update annotations (lockout-aware wrapper for updateAnnotationPositions)
 * Re-calculates screen positions for all annotations
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 */
export function renderAnnotations(state, refs) {
    // CRITICAL: Don't re-render during active resize/move operations
    if (state._resizeLockout || state.isResizing || state.isMoving) {
        console.log('⚠️ Render blocked - resize/move in progress');
        return;
    }

    console.log('🎨 Rendering annotations');
    updateAnnotationPositions(state, refs);
}

/**
 * Check if point is within rectangle
 * @param {Number} x - Point X
 * @param {Number} y - Point Y
 * @param {Object} rect - Rectangle {x, y, width, height}
 * @returns {Boolean} True if point is inside
 */
export function pointInRect(x, y, rect) {
    return (
        x >= rect.x &&
        x <= rect.x + rect.width &&
        y >= rect.y &&
        y <= rect.y + rect.height
    );
}

/**
 * Setup browser zoom/resize handler to keep annotations aligned
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @param {Object} callbacks - Callback functions
 * @returns {Function} Cleanup function
 */
export function setupBrowserZoomHandler(state, refs, callbacks) {
    let resizeTimeout;

    const handleBrowserZoom = () => {
        console.log('🔍 Browser zoom/resize detected - re-syncing annotations');

        // Debounce to avoid excessive updates during resize
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Invalidate all coordinate caches
            state._overlayRect = null;
            state._lastRectUpdate = 0;
            invalidateZoomCache(state);

            // Re-sync overlay dimensions to canvas
            syncOverlayToCanvas(refs, state);

            // Wait for DOM to update, then recalculate annotation positions
            setTimeout(() => {
                updateAnnotationPositions(state, refs);

                // Update isolation mask if in isolation mode
                if (state.isolationMode && callbacks.updateIsolationMask) {
                    callbacks.updateIsolationMask();
                }

                // Add final sync after PDF re-render completes (for large zoom changes)
                setTimeout(() => {
                    // Re-sync one more time to catch any canvas size changes from PDF re-render
                    syncOverlayToCanvas(refs, state);
                    updateAnnotationPositions(state, refs);

                    if (state.isolationMode && callbacks.updateIsolationMask) {
                        callbacks.updateIsolationMask();
                    }

                    console.log('✓ Final sync complete after zoom/resize');
                }, 300); // Wait for PDF re-render to complete

                console.log('✓ Annotations re-synced after browser zoom/resize');
            }, 50);
        }, 150); // 150ms debounce
    };

    // Listen to visual viewport resize (modern browsers - includes browser zoom)
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', handleBrowserZoom);
    }

    // Fallback to window resize (fires on browser zoom and window resize)
    window.addEventListener('resize', handleBrowserZoom);

    console.log('✓ Browser zoom handler initialized');

    // Return cleanup function
    return () => {
        if (window.visualViewport) {
            window.visualViewport.removeEventListener('resize', handleBrowserZoom);
        }
        window.removeEventListener('resize', handleBrowserZoom);
        clearTimeout(resizeTimeout);
        console.log('✓ Browser zoom handler cleaned up');
    };
}
