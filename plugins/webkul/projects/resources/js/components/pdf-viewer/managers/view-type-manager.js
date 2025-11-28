/**
 * View Type Manager
 *
 * Manages view types (plan, elevation, section, detail) and orientations
 * for the PDF annotation viewer.
 *
 * @module view-type-manager
 */

/**
 * Set view type and optional orientation
 * @param {string} viewType - 'plan', 'elevation', 'section', or 'detail'
 * @param {string|null} orientation - 'front', 'back', 'left', 'right', 'A-A', etc.
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 */
export function setViewType(viewType, orientation = null, state, callbacks) {
    console.log(`📐 Switching to ${viewType} view${orientation ? ' (' + orientation + ')' : ''}`);

    state.activeViewType = viewType;
    state.activeOrientation = orientation;

    // Reset orientation if switching to plan view
    if (viewType === 'plan') {
        state.activeOrientation = null;
    }

    // Filter annotations based on new view
    if (callbacks.updateAnnotationVisibility) {
        callbacks.updateAnnotationVisibility();
    }

    // Update UI to reflect new view
    console.log(`✓ View switched to ${viewType}${orientation ? ' - ' + orientation : ''}`);
}

/**
 * Set orientation for elevation or section views
 * @param {string} orientation - 'front', 'back', 'left', 'right', 'A-A', etc.
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 */
export function setOrientation(orientation, state, callbacks) {
    console.log(`🧭 Setting orientation to ${orientation}`);
    state.activeOrientation = orientation;

    if (callbacks.updateAnnotationVisibility) {
        callbacks.updateAnnotationVisibility();
    }
}

/**
 * Check if annotation should be visible in current view
 * @param {Object} anno - Annotation object
 * @param {Object} state - Component state
 * @returns {boolean}
 */
export function isAnnotationVisibleInView(anno, state) {
    // If no view type specified on annotation, assume it's visible in plan view
    const annoViewType = anno.viewType || 'plan';

    // If we're in plan view, show all plan annotations
    if (state.activeViewType === 'plan') {
        return annoViewType === 'plan';
    }

    // If we're in elevation/section/detail view, show matching annotations
    if (state.activeViewType === annoViewType) {
        // If orientation is set, check if it matches
        if (state.activeOrientation && anno.viewOrientation) {
            return anno.viewOrientation === state.activeOrientation;
        }
        // If no orientation filter, show all annotations of this view type
        return true;
    }

    return false;
}

/**
 * Update visibility of all annotations based on current view
 * @param {Object} state - Component state
 */
export function updateAnnotationVisibility(state) {
    // This will be used in the rendering loop to filter visible annotations
    // The actual filtering happens in the x-for template
    console.log(`🔍 Updating annotation visibility for ${state.activeViewType} view`);
}

/**
 * Get human-readable label for current view
 * @param {Object} state - Component state
 * @returns {string}
 */
export function getCurrentViewLabel(state) {
    if (state.activeViewType === 'plan') {
        return 'Plan View';
    } else if (state.activeViewType === 'elevation') {
        const orientation = state.activeOrientation
            ? ` - ${state.activeOrientation.charAt(0).toUpperCase() + state.activeOrientation.slice(1)}`
            : '';
        return `Elevation View${orientation}`;
    } else if (state.activeViewType === 'section') {
        const orientation = state.activeOrientation ? ` - ${state.activeOrientation}` : '';
        return `Section View${orientation}`;
    } else if (state.activeViewType === 'detail') {
        const orientation = state.activeOrientation ? ` - ${state.activeOrientation}` : '';
        return `Detail View${orientation}`;
    }

    return 'Unknown View';
}

/**
 * Get color CSS variable for current view type
 * @param {Object} state - Component state
 * @returns {string} CSS color variable
 */
export function getCurrentViewColor(state) {
    if (state.activeViewType === 'plan') return 'var(--primary-600)';
    if (state.activeViewType === 'elevation') return 'var(--warning-600)';
    if (state.activeViewType === 'section') return 'var(--info-600)';
    if (state.activeViewType === 'detail') return 'var(--success-600)';
    return 'var(--gray-600)';
}
