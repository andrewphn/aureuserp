/**
 * UI Helpers
 * Utility functions for UI positioning and layout calculations
 */

import * as CoordTransform from './coordinate-transform.js';

/**
 * Get intelligent label position classes based on available space
 * @param {Object} anno - Annotation object with screen coordinates
 * @param {Object} refs - Alpine.js $refs object
 * @param {Object} state - Component state
 * @returns {string} Tailwind CSS positioning classes
 */
export function getLabelPositionClasses(anno, refs, state) {
    const overlayRect = CoordTransform.getOverlayRect(refs, state);
    if (!overlayRect) return '-top-10 left-0';

    const spaceAbove = anno.screenY;
    const spaceBelow = overlayRect.height - (anno.screenY + anno.screenHeight);
    const spaceLeft = anno.screenX;
    const spaceRight = overlayRect.width - (anno.screenX + anno.screenWidth);

    // Prefer above if there's room
    if (spaceAbove >= 40) return '-top-10 left-0';
    // Otherwise below
    if (spaceBelow >= 40) return '-bottom-10 left-0';
    // If no vertical space, try right
    if (spaceRight >= 100) return 'top-0 -right-2 translate-x-full';
    // Last resort: left
    return 'top-0 -left-2 -translate-x-full';
}

/**
 * Get intelligent button position classes based on available space
 * @param {Object} anno - Annotation object with screen coordinates
 * @param {Object} refs - Alpine.js $refs object
 * @param {Object} state - Component state
 * @returns {string} Tailwind CSS positioning classes
 */
export function getButtonPositionClasses(anno, refs, state) {
    const overlayRect = CoordTransform.getOverlayRect(refs, state);
    if (!overlayRect) return '-top-7 right-0';

    const spaceAbove = anno.screenY;
    const spaceRight = overlayRect.width - (anno.screenX + anno.screenWidth);

    // Prefer top-right corner
    if (spaceAbove >= 30) return '-top-7 right-0';
    // Otherwise bottom-right
    return '-bottom-7 right-0';
}
