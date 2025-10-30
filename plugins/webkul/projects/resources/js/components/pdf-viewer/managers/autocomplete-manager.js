/**
 * Autocomplete Manager
 * Handles room and location search and selection with create-new functionality
 */

import { getCsrfToken } from '../utilities.js';

/**
 * Search rooms
 * @param {String} query - Search query
 * @param {Object} state - Component state
 */
export function searchRooms(query, state) {
    console.log('🔍 Searching rooms with query:', query);

    // Get existing rooms from tree
    const existingRooms = state.tree ? state.tree.map(room => ({
        id: room.id,
        name: room.name,
        isNew: false
    })) : [];

    if (!query || query.trim() === '') {
        // Empty query: show all existing rooms
        state.roomSuggestions = existingRooms;
        console.log(`✓ Showing ${existingRooms.length} existing rooms`);
    } else {
        // Filter existing rooms by query
        const lowerQuery = query.toLowerCase();
        const matchingRooms = existingRooms.filter(room =>
            room.name.toLowerCase().includes(lowerQuery)
        );

        // Add "Create new" option at the top
        state.roomSuggestions = [
            { id: 'new_' + Date.now(), name: query, isNew: true },
            ...matchingRooms
        ];

        console.log(`✓ Found ${matchingRooms.length} matching rooms, showing "Create ${query}" option`);
    }
}

/**
 * Select room (create new if needed)
 * @param {Object} room - Selected room
 * @param {Object} state - Component state
 * @param {Function} refreshTreeCallback - Callback to refresh tree
 * @returns {Promise<void>}
 */
export async function selectRoom(room, state, refreshTreeCallback) {
    console.log('🏠 Selecting room:', room);

    // If this is a new room, create it via API first
    if (room.isNew) {
        console.log('📝 Creating new room:', room.name);

        try {
            const response = await fetch(`/api/project/${state.projectId}/rooms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    name: room.name,
                    room_type: null
                })
            });

            const data = await response.json();

            if (data.success && data.room) {
                console.log('✓ Room created successfully:', data.room);

                // Set active context with the real room ID from database
                state.activeRoomId = data.room.id;
                state.activeRoomName = data.room.name;
                state.roomSearchQuery = data.room.name;
                state.showRoomDropdown = false;

                // Refresh tree to show the new room
                if (refreshTreeCallback) {
                    await refreshTreeCallback();
                }

                console.log('✓ Room added to project tree');
            } else {
                throw new Error(data.error || 'Failed to create room');
            }
        } catch (error) {
            console.error('❌ Failed to create room:', error);
            alert(`Error creating room: ${error.message}`);
            state.showRoomDropdown = false;
        }
    } else {
        // Existing room, just set the context
        state.activeRoomId = room.id;
        state.activeRoomName = room.name;
        state.roomSearchQuery = room.name;
        state.showRoomDropdown = false;
        console.log('✓ Selected existing room:', room.name);
    }
}

/**
 * Search locations
 * @param {String} query - Search query
 * @param {Object} state - Component state
 */
export function searchLocations(query, state) {
    if (!state.activeRoomId) return;

    console.log('🔍 Searching locations with query:', query);

    // Get existing locations from tree
    const room = state.tree?.find(r => r.id === state.activeRoomId);
    const existingLocations = room?.children ? room.children.map(loc => ({
        id: loc.id,
        name: loc.name,
        isNew: false
    })) : [];

    if (!query || query.trim() === '') {
        // Empty query: show all existing locations
        state.locationSuggestions = existingLocations;
        console.log(`✓ Showing ${existingLocations.length} existing locations`);
    } else {
        // Filter existing locations by query
        const lowerQuery = query.toLowerCase();
        const matchingLocations = existingLocations.filter(loc =>
            loc.name.toLowerCase().includes(lowerQuery)
        );

        // Add "Create new" option at the top
        state.locationSuggestions = [
            { id: 'new_' + Date.now(), name: query, isNew: true },
            ...matchingLocations
        ];

        console.log(`✓ Found ${matchingLocations.length} matching locations, showing "Create ${query}" option`);
    }
}

/**
 * Select location (create new if needed)
 * @param {Object} location - Selected location
 * @param {Object} state - Component state
 * @param {Function} refreshTreeCallback - Callback to refresh tree
 * @returns {Promise<void>}
 */
export async function selectLocation(location, state, refreshTreeCallback) {
    console.log('📍 Selecting location:', location);

    // If this is a new location, create it via API first
    if (location.isNew) {
        console.log('📝 Creating new location:', location.name);

        try {
            const response = await fetch(`/api/project/${state.projectId}/rooms/${state.activeRoomId}/locations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    name: location.name
                })
            });

            const data = await response.json();

            if (data.success && data.location) {
                console.log('✓ Location created successfully:', data.location);

                // Set active context with the real location ID from database
                state.activeLocationId = data.location.id;
                state.activeLocationName = data.location.name;
                state.locationSearchQuery = data.location.name;
                state.showLocationDropdown = false;

                // Refresh tree to show the new location
                if (refreshTreeCallback) {
                    await refreshTreeCallback();
                }

                console.log('✓ Location added to project tree');
            } else {
                throw new Error(data.error || 'Failed to create location');
            }
        } catch (error) {
            console.error('❌ Failed to create location:', error);
            alert(`Error creating location: ${error.message}`);
            state.showLocationDropdown = false;
        }
    } else {
        // Existing location, just set the context
        state.activeLocationId = location.id;
        state.activeLocationName = location.name;
        state.locationSearchQuery = location.name;
        state.showLocationDropdown = false;
        console.log('✓ Selected existing location:', location.name);
    }
}

/**
 * Clear room search
 * @param {Object} state - Component state
 */
export function clearRoomSearch(state) {
    state.roomSearchQuery = '';
    state.roomSuggestions = [];
    state.showRoomDropdown = false;
}

/**
 * Clear location search
 * @param {Object} state - Component state
 */
export function clearLocationSearch(state) {
    state.locationSearchQuery = '';
    state.locationSuggestions = [];
    state.showLocationDropdown = false;
}
