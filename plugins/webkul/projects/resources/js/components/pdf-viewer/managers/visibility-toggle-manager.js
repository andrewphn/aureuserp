/**
 * Visibility Toggle Manager
 * Handles manual visibility toggling for rooms, locations, and cabinet runs in the project tree
 */

/**
 * Toggle visibility for all annotations in a room
 * @param {Number} roomId - Room ID to toggle
 * @param {Object} state - Component state
 */
export function toggleRoomVisibility(roomId, state) {
    // Find all annotations for this room
    const roomAnnotations = state.annotations.filter(a => a.roomId === roomId);
    const roomAnnotationIds = roomAnnotations.map(a => a.id);

    // Check if any are visible
    const someVisible = roomAnnotationIds.some(id => !state.hiddenAnnotations.includes(id));

    if (someVisible) {
        // Hide all room annotations
        roomAnnotationIds.forEach(id => {
            if (!state.hiddenAnnotations.includes(id)) {
                state.hiddenAnnotations.push(id);
            }
        });
    } else {
        // Show all room annotations
        state.hiddenAnnotations = state.hiddenAnnotations.filter(id => !roomAnnotationIds.includes(id));
    }

    console.log(`👁️ Toggled room ${roomId} visibility`);
}

/**
 * Check if room has any visible annotations
 * @param {Number} roomId - Room ID to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if any annotations are visible
 */
export function isRoomVisible(roomId, state) {
    const roomAnnotations = state.annotations.filter(a => a.roomId === roomId);
    // Check if any annotations are NOT manually hidden
    return roomAnnotations.some(a => !state.hiddenAnnotations.includes(a.id));
}

/**
 * Toggle visibility for all annotations in a location
 * @param {Number} locationId - Location ID to toggle
 * @param {Object} state - Component state
 */
export function toggleLocationVisibility(locationId, state) {
    // Find all annotations for this location
    const locationAnnotations = state.annotations.filter(a =>
        a.type === 'location' && a.roomLocationId === locationId ||
        a.locationId === locationId
    );
    const locationAnnotationIds = locationAnnotations.map(a => a.id);

    // Check if any are visible
    const someVisible = locationAnnotationIds.some(id => !state.hiddenAnnotations.includes(id));

    if (someVisible) {
        // Hide all location annotations
        locationAnnotationIds.forEach(id => {
            if (!state.hiddenAnnotations.includes(id)) {
                state.hiddenAnnotations.push(id);
            }
        });
    } else {
        // Show all location annotations
        state.hiddenAnnotations = state.hiddenAnnotations.filter(id => !locationAnnotationIds.includes(id));
    }

    console.log(`👁️ Toggled location ${locationId} visibility`);
}

/**
 * Check if location has any visible annotations
 * @param {Number} locationId - Location ID to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if any annotations are visible
 */
export function isLocationVisible(locationId, state) {
    const locationAnnotations = state.annotations.filter(a =>
        a.type === 'location' && a.roomLocationId === locationId ||
        a.locationId === locationId
    );
    // Check if any annotations are NOT manually hidden
    return locationAnnotations.some(a => !state.hiddenAnnotations.includes(a.id));
}

/**
 * Toggle visibility for all annotations in a cabinet run
 * @param {Number} runId - Cabinet run ID to toggle
 * @param {Object} state - Component state
 */
export function toggleCabinetRunVisibility(runId, state) {
    // Find all annotations for this cabinet run
    const runAnnotations = state.annotations.filter(a =>
        a.type === 'cabinet_run' && a.cabinetRunId === runId ||
        a.cabinetRunId === runId
    );
    const runAnnotationIds = runAnnotations.map(a => a.id);

    // Check if any are visible
    const someVisible = runAnnotationIds.some(id => !state.hiddenAnnotations.includes(id));

    if (someVisible) {
        // Hide all cabinet run annotations
        runAnnotationIds.forEach(id => {
            if (!state.hiddenAnnotations.includes(id)) {
                state.hiddenAnnotations.push(id);
            }
        });
    } else {
        // Show all cabinet run annotations
        state.hiddenAnnotations = state.hiddenAnnotations.filter(id => !runAnnotationIds.includes(id));
    }

    console.log(`👁️ Toggled cabinet run ${runId} visibility`);
}

/**
 * Check if cabinet run has any visible annotations
 * @param {Number} runId - Cabinet run ID to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if any annotations are visible
 */
export function isCabinetRunVisible(runId, state) {
    const runAnnotations = state.annotations.filter(a =>
        a.type === 'cabinet_run' && a.cabinetRunId === runId ||
        a.cabinetRunId === runId
    );
    // Check if any annotations are NOT manually hidden
    return runAnnotations.some(a => !state.hiddenAnnotations.includes(a.id));
}

/**
 * Toggle visibility for an individual annotation (e.g., a single cabinet)
 * @param {Number} annotationId - Annotation ID to toggle
 * @param {Object} state - Component state
 */
export function toggleAnnotationVisibility(annotationId, state) {
    const index = state.hiddenAnnotations.indexOf(annotationId);

    if (index > -1) {
        // Currently hidden - show it
        state.hiddenAnnotations.splice(index, 1);
        console.log(`👁️ Showing annotation ${annotationId}`);
    } else {
        // Currently visible - hide it
        state.hiddenAnnotations.push(annotationId);
        console.log(`👁️ Hiding annotation ${annotationId}`);
    }
}

/**
 * Check if an individual annotation is visible
 * @param {Number} annotationId - Annotation ID to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if annotation is visible
 */
export function isAnnotationVisible(annotationId, state) {
    return !state.hiddenAnnotations.includes(annotationId);
}

/**
 * Check if entity has annotations on current page
 * @param {Number} entityId - Entity ID (room, location, or cabinet run)
 * @param {String} entityType - Type: 'room', 'location', or 'cabinet_run'
 * @param {Object} state - Component state
 * @returns {Boolean} True if entity has annotations on current page
 */
export function hasAnnotationsOnCurrentPage(entityId, entityType, state) {
    if (!state.annotations || state.annotations.length === 0) {
        return false;
    }

    let entityAnnotations;
    if (entityType === 'room') {
        entityAnnotations = state.annotations.filter(a => a.roomId === entityId);
    } else if (entityType === 'location') {
        entityAnnotations = state.annotations.filter(a =>
            a.type === 'location' && a.roomLocationId === entityId ||
            a.locationId === entityId
        );
    } else if (entityType === 'cabinet_run') {
        entityAnnotations = state.annotations.filter(a =>
            a.type === 'cabinet_run' && a.cabinetRunId === entityId ||
            a.cabinetRunId === entityId
        );
    }

    return entityAnnotations && entityAnnotations.length > 0;
}
