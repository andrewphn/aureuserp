/**
 * Filter System
 * Manages annotation filtering with multiple criteria
 */

/**
 * Get filtered annotations based on all active filters
 * @param {Object} state - Component state
 * @returns {Array<Object>} Filtered annotations
 */
export function getFilteredAnnotations(state) {
    let filtered = [...state.annotations];

    // Apply scope filter (page vs all)
    if (state.filterScope === 'page') {
        filtered = filtered.filter(a => a.pageNumber === state.currentPage);
    }

    // Apply type filters
    if (state.filters.types.length > 0) {
        filtered = filtered.filter(a => state.filters.types.includes(a.type));
    }

    // Apply room filters
    if (state.filters.rooms.length > 0) {
        filtered = filtered.filter(a => state.filters.rooms.includes(a.roomId));
    }

    // Apply location filters
    if (state.filters.locations.length > 0) {
        filtered = filtered.filter(a => state.filters.locations.includes(a.locationId));
    }

    // Apply view type filters
    if (state.filters.viewTypes.length > 0) {
        filtered = filtered.filter(a => state.filters.viewTypes.includes(a.viewType));
    }

    // Apply vertical zone filters
    if (state.filters.verticalZones.length > 0) {
        filtered = filtered.filter(a => state.filters.verticalZones.includes(a.verticalZone));
    }

    // Apply my annotations filter
    if (state.filters.myAnnotations) {
        const currentUserId = window.currentUserId; // Assumes user ID is available
        filtered = filtered.filter(a => a.createdBy === currentUserId);
    }

    // Apply recent filter (last 24 hours)
    if (state.filters.recent) {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        filtered = filtered.filter(a => new Date(a.createdAt) > yesterday);
    }

    // Apply unlinked filter (no entity connections)
    if (state.filters.unlinked) {
        filtered = filtered.filter(a => !a.roomId && !a.locationId && !a.cabinetRunId);
    }

    // Apply page range filter
    if (state.filters.pageRange.from || state.filters.pageRange.to) {
        const from = state.filters.pageRange.from || 1;
        const to = state.filters.pageRange.to || state.totalPages;
        filtered = filtered.filter(a => a.pageNumber >= from && a.pageNumber <= to);
    }

    // Apply date range filter
    if (state.filters.dateRange.from || state.filters.dateRange.to) {
        const from = state.filters.dateRange.from ? new Date(state.filters.dateRange.from) : new Date(0);
        const to = state.filters.dateRange.to ? new Date(state.filters.dateRange.to) : new Date();
        filtered = filtered.filter(a => {
            const date = new Date(a.createdAt);
            return date >= from && date <= to;
        });
    }

    return filtered;
}

/**
 * Count active filters
 * @param {Object} state - Component state
 * @returns {Number} Number of active filters
 */
export function countActiveFilters(state) {
    let count = 0;

    if (state.filterScope === 'all') count++;
    count += state.filters.types.length;
    count += state.filters.rooms.length;
    count += state.filters.locations.length;
    count += state.filters.viewTypes.length;
    count += state.filters.verticalZones.length;
    if (state.filters.myAnnotations) count++;
    if (state.filters.recent) count++;
    if (state.filters.unlinked) count++;
    if (state.filters.pageRange.from || state.filters.pageRange.to) count++;
    if (state.filters.dateRange.from || state.filters.dateRange.to) count++;

    return count;
}

/**
 * Get active filter chips for display
 * @param {Object} state - Component state
 * @returns {Array<Object>} Filter chips
 */
export function getActiveFilterChips(state) {
    const chips = [];

    // Scope filter
    if (state.filterScope === 'all') {
        chips.push({
            type: 'scope',
            label: 'All Pages',
            key: 'filterScope'
        });
    }

    // Type filters
    state.filters.types.forEach(type => {
        chips.push({
            type: 'array',
            arrayKey: 'types',
            value: type,
            label: `Type: ${formatType(type)}`
        });
    });

    // Room filters
    state.filters.rooms.forEach(roomId => {
        const roomName = getRoomName(roomId, state);
        chips.push({
            type: 'array',
            arrayKey: 'rooms',
            value: roomId,
            label: `Room: ${roomName}`
        });
    });

    // Location filters
    state.filters.locations.forEach(locationId => {
        const locationName = getLocationName(locationId, state);
        chips.push({
            type: 'array',
            arrayKey: 'locations',
            value: locationId,
            label: `Location: ${locationName}`
        });
    });

    // View type filters
    state.filters.viewTypes.forEach(viewType => {
        chips.push({
            type: 'array',
            arrayKey: 'viewTypes',
            value: viewType,
            label: `View: ${formatViewType(viewType)}`
        });
    });

    // Vertical zone filters
    state.filters.verticalZones.forEach(zone => {
        chips.push({
            type: 'array',
            arrayKey: 'verticalZones',
            value: zone,
            label: `Zone: ${zone}`
        });
    });

    // Boolean filters
    if (state.filters.myAnnotations) {
        chips.push({
            type: 'boolean',
            key: 'myAnnotations',
            label: 'My Work'
        });
    }

    if (state.filters.recent) {
        chips.push({
            type: 'boolean',
            key: 'recent',
            label: 'Recent (24h)'
        });
    }

    if (state.filters.unlinked) {
        chips.push({
            type: 'boolean',
            key: 'unlinked',
            label: 'Unlinked'
        });
    }

    // Page range filter
    if (state.filters.pageRange.from || state.filters.pageRange.to) {
        const from = state.filters.pageRange.from || 1;
        const to = state.filters.pageRange.to || state.totalPages;
        chips.push({
            type: 'range',
            key: 'pageRange',
            label: `Pages ${from}-${to}`
        });
    }

    // Date range filter
    if (state.filters.dateRange.from || state.filters.dateRange.to) {
        const from = state.filters.dateRange.from || 'start';
        const to = state.filters.dateRange.to || 'end';
        chips.push({
            type: 'range',
            key: 'dateRange',
            label: `Date: ${from} to ${to}`
        });
    }

    return chips;
}

/**
 * Get available filter options based on current annotations
 * @param {Object} state - Component state
 * @returns {Object} Available options
 */
export function getAvailableFilterOptions(state) {
    const types = new Set();
    const rooms = new Map();
    const locations = new Map();
    const viewTypes = new Set();
    const verticalZones = new Set();

    state.annotations.forEach(anno => {
        // Types
        if (anno.type) types.add(anno.type);

        // Rooms
        if (anno.roomId && anno.roomName) {
            rooms.set(anno.roomId, anno.roomName);
        }

        // Locations
        if (anno.locationId && anno.locationName) {
            locations.set(anno.locationId, anno.locationName);
        }

        // View types
        if (anno.viewType) viewTypes.add(anno.viewType);

        // Vertical zones
        if (anno.verticalZone) verticalZones.add(anno.verticalZone);
    });

    return {
        types: Array.from(types).sort(),
        rooms: Array.from(rooms.entries()).map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name)),
        locations: Array.from(locations.entries()).map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name)),
        viewTypes: Array.from(viewTypes).sort(),
        verticalZones: Array.from(verticalZones).sort()
    };
}

/**
 * Clear all filters
 * @param {Object} state - Component state
 */
export function clearAllFilters(state) {
    state.filterScope = 'page';
    state.filters.types = [];
    state.filters.rooms = [];
    state.filters.locations = [];
    state.filters.viewTypes = [];
    state.filters.verticalZones = [];
    state.filters.myAnnotations = false;
    state.filters.recent = false;
    state.filters.unlinked = false;
    state.filters.pageRange.from = null;
    state.filters.pageRange.to = null;
    state.filters.dateRange.from = null;
    state.filters.dateRange.to = null;

    console.log('✓ All filters cleared');
}

/**
 * Remove individual filter chip
 * @param {Object} chip - Filter chip to remove
 * @param {Object} state - Component state
 */
export function removeFilterChip(chip, state) {
    if (chip.type === 'boolean') {
        state.filters[chip.key] = false;
    } else if (chip.type === 'array') {
        const index = state.filters[chip.arrayKey].indexOf(chip.value);
        if (index > -1) {
            state.filters[chip.arrayKey].splice(index, 1);
        }
    } else if (chip.type === 'scope') {
        state.filterScope = 'page';
    } else if (chip.type === 'range') {
        if (chip.key === 'pageRange') {
            state.filters.pageRange.from = null;
            state.filters.pageRange.to = null;
        } else if (chip.key === 'dateRange') {
            state.filters.dateRange.from = null;
            state.filters.dateRange.to = null;
        }
    }

    console.log('✓ Filter removed:', chip.label);
}

/**
 * Apply filter preset
 * @param {String} presetName - Preset name (myWork, recent, unlinked, all)
 * @param {Object} state - Component state
 */
export function applyFilterPreset(presetName, state) {
    // Clear all filters first
    clearAllFilters(state);

    // Apply preset-specific filters
    switch(presetName) {
        case 'myWork':
            state.filters.myAnnotations = true;
            break;
        case 'recent':
            state.filters.recent = true;
            break;
        case 'unlinked':
            state.filters.unlinked = true;
            break;
        case 'all':
            // Already cleared
            break;
    }

    console.log('✓ Preset applied:', presetName);
}

/**
 * Check if preset is currently active
 * @param {String} presetName - Preset name
 * @param {Object} state - Component state
 * @returns {Boolean} True if preset is active
 */
export function isPresetActive(presetName, state) {
    const activeCount = countActiveFilters(state);

    switch(presetName) {
        case 'myWork':
            return state.filters.myAnnotations && activeCount === 1;
        case 'recent':
            return state.filters.recent && activeCount === 1;
        case 'unlinked':
            return state.filters.unlinked && activeCount === 1;
        case 'all':
            return activeCount === 0;
        default:
            return false;
    }
}

/**
 * Get filtered page numbers (for navigation)
 * @param {Object} state - Component state
 * @returns {Array<Number>} Page numbers that match filters
 */
export function getFilteredPageNumbers(state) {
    // If no view type filters, return all pages
    if (state.filters.viewTypes.length === 0) {
        return Array.from({ length: state.totalPages }, (_, i) => i + 1);
    }

    // Filter pages by view type using pageMap metadata
    const filteredPages = new Set();

    Object.entries(state.pageMap).forEach(([pageNum, pdfPageId]) => {
        // Check if this page has annotations matching the view type filters
        const hasMatchingAnnotations = state.annotations.some(anno =>
            anno.pageNumber === parseInt(pageNum) &&
            state.filters.viewTypes.includes(anno.viewType)
        );

        if (hasMatchingAnnotations) {
            filteredPages.add(parseInt(pageNum));
        }
    });

    return Array.from(filteredPages).sort((a, b) => a - b);
}

/**
 * Helper functions
 */

function formatType(type) {
    const typeNames = {
        room: 'Room',
        location: 'Location',
        cabinet_run: 'Cabinet Run',
        cabinet: 'Cabinet'
    };
    return typeNames[type] || type;
}

function formatViewType(viewType) {
    const viewNames = {
        plan: 'Plan',
        elevation: 'Elevation',
        section: 'Section',
        detail: 'Detail'
    };
    return viewNames[viewType] || viewType;
}

function getRoomName(roomId, state) {
    if (!state.tree) return `Room ${roomId}`;
    const room = state.tree.find(r => r.id === roomId);
    return room ? room.name : `Room ${roomId}`;
}

function getLocationName(locationId, state) {
    if (!state.tree) return `Location ${locationId}`;
    for (const room of state.tree) {
        const location = room.children?.find(l => l.id === locationId);
        if (location) return location.name;
    }
    return `Location ${locationId}`;
}
