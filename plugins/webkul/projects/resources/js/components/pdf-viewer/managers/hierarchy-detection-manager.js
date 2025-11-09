/**
 * Hierarchy Detection Manager
 * Detects missing hierarchy levels when drawing annotations
 */

/**
 * Entity hierarchy levels in order
 */
const HIERARCHY_LEVELS = {
    room: 0,
    room_location: 1,
    cabinet_run: 2,
    cabinet: 3
};

/**
 * Detect missing hierarchy levels based on current context and draw mode
 * @param {String} drawMode - Type of annotation being drawn
 * @param {Object} state - Component state with active context
 * @returns {Array} Array of missing level objects: [{type: 'room_location', level: 1}, ...]
 */
export function detectMissingHierarchy(drawMode, state) {
    const missing = [];

    // Get current context levels
    const hasRoom = !!state.activeRoomId;
    const hasLocation = !!state.activeLocationId;
    const hasCabinetRun = !!state.activeCabinetRunId;

    // Determine target level (what we're drawing)
    const targetLevel = HIERARCHY_LEVELS[drawMode];

    // If drawing at top level (room), no missing hierarchy
    if (targetLevel === 0) {
        return missing;
    }

    // Check each level between top and target
    if (targetLevel >= 1 && !hasRoom) {
        missing.push({ type: 'room', level: 0, required: true });
    }

    if (targetLevel >= 2 && !hasLocation) {
        missing.push({ type: 'room_location', level: 1, required: true });
    }

    if (targetLevel >= 3 && !hasCabinetRun) {
        missing.push({ type: 'cabinet_run', level: 2, required: true });
    }

    console.log(`🔍 [Hierarchy Detection] Drawing ${drawMode}, missing levels:`, missing);

    return missing;
}

/**
 * Get smart defaults for creating a missing entity
 * @param {String} entityType - Type of entity to create
 * @param {Object} annotation - Annotation data
 * @param {Object} state - Component state
 * @returns {Object} Default values for entity creation
 */
export function getEntityDefaults(entityType, annotation, state) {
    const defaults = {
        name: annotation.label || 'Untitled',
        project_id: state.projectId,
    };

    switch (entityType) {
        case 'room':
            defaults.room_type = 'general';
            defaults.floor_number = 1;
            break;

        case 'room_location':
            defaults.location_type = 'wall';
            defaults.room_id = state.activeRoomId;
            // Use annotation label as location name
            defaults.name = annotation.label || 'Location';
            break;

        case 'cabinet_run':
            defaults.room_id = state.activeRoomId;
            defaults.room_location_id = state.activeLocationId;
            // Infer run type from view type
            if (state.activeViewType === 'elevation') {
                defaults.run_type = 'wall';
                defaults.name = 'Wall Cabinet';
            } else {
                defaults.run_type = 'base';
                defaults.name = 'Base Cabinet';
            }
            defaults.position_in_location = 0;
            break;

        case 'cabinet':
            defaults.room_id = state.activeRoomId;
            defaults.cabinet_run_id = state.activeCabinetRunId;
            defaults.product_variant_id = 1; // Default product
            defaults.position_in_run = 0;
            defaults.length_inches = 24;
            defaults.depth_inches = 24;
            defaults.height_inches = 30;
            defaults.quantity = 1;
            break;
    }

    return defaults;
}

/**
 * Get user-friendly display name for entity type
 * @param {String} entityType - Entity type
 * @returns {String} Display name
 */
export function getEntityDisplayName(entityType) {
    const names = {
        room: 'Room',
        room_location: 'Room Location',
        cabinet_run: 'Cabinet Run',
        cabinet: 'Cabinet'
    };
    return names[entityType] || entityType;
}

/**
 * Check if annotation can be saved without modal (complete hierarchy)
 * @param {String} drawMode - Type of annotation being drawn
 * @param {Object} state - Component state
 * @returns {Boolean} True if can save directly
 */
export function canSaveDirectly(drawMode, state) {
    const missing = detectMissingHierarchy(drawMode, state);
    return missing.length === 0;
}
