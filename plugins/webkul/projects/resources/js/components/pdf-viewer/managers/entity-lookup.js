/**
 * Entity Lookup Helpers
 * Centralized functions for looking up entity names by ID
 *
 * These functions search the hierarchical tree structure to find entity names.
 * Used throughout the application for display purposes and context management.
 */

/**
 * Get room name by entity ID
 * @param {Number} roomId - Room entity ID
 * @param {Object} state - Component state containing tree structure
 * @returns {String} Room name or empty string if not found
 */
export function getRoomNameById(roomId, state) {
    if (!state.tree || !roomId) return '';
    const room = state.tree.find(r => r.id === roomId);
    return room ? room.name : '';
}

/**
 * Get location name by entity ID
 * @param {Number} locationId - Location entity ID
 * @param {Object} state - Component state containing tree structure
 * @returns {String} Location name or empty string if not found
 */
export function getLocationNameById(locationId, state) {
    if (!state.tree || !locationId) return '';
    for (const room of state.tree) {
        const location = room.children?.find(l => l.id === locationId);
        if (location) return location.name;
    }
    return '';
}

/**
 * Get cabinet run name by entity ID
 * @param {Number} cabinetRunId - Cabinet run entity ID
 * @param {Object} state - Component state containing tree structure
 * @returns {String} Cabinet run name or empty string if not found
 */
export function getCabinetRunNameById(cabinetRunId, state) {
    if (!state.tree || !cabinetRunId) return '';
    for (const room of state.tree) {
        for (const location of (room.children || [])) {
            const run = location.children?.find(r => r.id === cabinetRunId);
            if (run) return run.name;
        }
    }
    return '';
}
