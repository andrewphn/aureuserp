/**
 * Context Bar Component
 *
 * Top toolbar showing active annotation context (Room → Location)
 * with smart autocomplete for entity selection.
 *
 * Features:
 * - Persistent context across multiple annotations
 * - Smart entity detection (reuse existing or create new)
 * - Quick draw mode buttons
 * - Visual context indicator
 */

export function contextBarComponent() {
    return {
        // Active context
        projectId: null,
        activeRoomId: null,
        activeRoomName: '',
        activeLocationId: null,
        activeLocationName: '',
        drawMode: null, // 'room', 'room_location', 'cabinet_run', 'cabinet'

        // Autocomplete state
        roomSearchQuery: '',
        locationSearchQuery: '',
        roomSuggestions: [],
        locationSuggestions: [],
        showRoomDropdown: false,
        showLocationDropdown: false,

        // Available entities
        availableRooms: [],
        availableLocations: [],

        // Loading state
        loading: false,

        // Initialization
        async init(projectId) {
            this.projectId = projectId;

            // Listen for tree sidebar selections
            window.addEventListener('annotation-context-selected', (event) => {
                this.handleTreeSelection(event.detail);
            });

            // Load available rooms
            await this.loadAvailableRooms();
        },

        // Handle selection from tree sidebar
        handleTreeSelection(detail) {
            if (detail.nodeType === 'room') {
                this.selectRoom({ id: detail.nodeId, name: detail.nodeName });
            } else if (detail.nodeType === 'room_location') {
                // Get parent room from tree
                this.selectLocation({ id: detail.nodeId, name: detail.nodeName });
            }
        },

        // Load all rooms for project
        async loadAvailableRooms() {
            try {
                const response = await fetch(`/api/project/${this.projectId}/rooms`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.availableRooms = data.rooms || [];
                }
            } catch (error) {
                console.error('❌ Failed to load rooms:', error);
            }
        },

        // Load locations for selected room
        async loadLocationsForRoom(roomId) {
            try {
                const response = await fetch(`/api/project/room/${roomId}/locations`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.availableLocations = data.locations || [];
                }
            } catch (error) {
                console.error('❌ Failed to load locations:', error);
            }
        },

        // Smart room search with autocomplete
        async searchRooms(query) {
            this.roomSearchQuery = query;
            this.showRoomDropdown = true;

            if (!query || query.trim() === '') {
                this.roomSuggestions = this.availableRooms.slice(0, 10);
                return;
            }

            // Fuzzy match on room names
            const searchLower = query.toLowerCase();
            const matches = this.availableRooms.filter(room =>
                room.name.toLowerCase().includes(searchLower)
            );

            // Always show "Create New" option
            this.roomSuggestions = [
                ...matches,
                {
                    id: 'new',
                    name: query,
                    isNew: true
                }
            ];
        },

        // Smart location search with autocomplete
        async searchLocations(query) {
            this.locationSearchQuery = query;
            this.showLocationDropdown = true;

            if (!this.activeRoomId) {
                this.locationSuggestions = [];
                return;
            }

            if (!query || query.trim() === '') {
                this.locationSuggestions = this.availableLocations.slice(0, 10);
                return;
            }

            // Fuzzy match on location names
            const searchLower = query.toLowerCase();
            const matches = this.availableLocations.filter(loc =>
                loc.name.toLowerCase().includes(searchLower)
            );

            // Always show "Create New" option
            this.locationSuggestions = [
                ...matches,
                {
                    id: 'new',
                    name: query,
                    isNew: true
                }
            ];
        },

        // Select room (existing or create new)
        async selectRoom(room) {
            if (room.isNew) {
                // Create new room
                const created = await this.createRoom(room.name);
                if (created) {
                    this.activeRoomId = created.id;
                    this.activeRoomName = created.name;
                    this.availableRooms.push(created);
                }
            } else {
                // Use existing room
                this.activeRoomId = room.id;
                this.activeRoomName = room.name;
            }

            this.roomSearchQuery = this.activeRoomName;
            this.showRoomDropdown = false;

            // Load locations for this room
            await this.loadLocationsForRoom(this.activeRoomId);

            // Clear location selection when room changes
            this.activeLocationId = null;
            this.activeLocationName = '';
            this.locationSearchQuery = '';

            console.log('✅ Selected room:', this.activeRoomName);
        },

        // Select location (existing or create new)
        async selectLocation(location) {
            if (location.isNew) {
                // Create new location
                const created = await this.createLocation(location.name);
                if (created) {
                    this.activeLocationId = created.id;
                    this.activeLocationName = created.name;
                    this.availableLocations.push(created);
                }
            } else {
                // Use existing location
                this.activeLocationId = location.id;
                this.activeLocationName = location.name;
            }

            this.locationSearchQuery = this.activeLocationName;
            this.showLocationDropdown = false;

            console.log('✅ Selected location:', this.activeLocationName);
        },

        // Create new room via API
        async createRoom(name) {
            try {
                const response = await fetch(`/api/project/${this.projectId}/rooms`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ name })
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('✅ Created new room:', data.room);
                    return data.room;
                }
            } catch (error) {
                console.error('❌ Failed to create room:', error);
            }
            return null;
        },

        // Create new location via API
        async createLocation(name) {
            try {
                const response = await fetch(`/api/project/room/${this.activeRoomId}/locations`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ name })
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('✅ Created new location:', data.location);
                    return data.location;
                }
            } catch (error) {
                console.error('❌ Failed to create location:', error);
            }
            return null;
        },

        // Set draw mode
        setDrawMode(mode) {
            if (this.drawMode === mode) {
                // Toggle off if clicking same mode
                this.drawMode = null;
            } else {
                this.drawMode = mode;
            }

            // Emit event for PDF viewer
            window.dispatchEvent(new CustomEvent('draw-mode-changed', {
                detail: {
                    mode: this.drawMode,
                    context: {
                        roomId: this.activeRoomId,
                        roomName: this.activeRoomName,
                        locationId: this.activeLocationId,
                        locationName: this.activeLocationName
                    }
                }
            }));

            console.log('✅ Draw mode:', this.drawMode);
        },

        // Clear all context
        clearContext() {
            this.activeRoomId = null;
            this.activeRoomName = '';
            this.activeLocationId = null;
            this.activeLocationName = '';
            this.drawMode = null;
            this.roomSearchQuery = '';
            this.locationSearchQuery = '';

            console.log('🔄 Context cleared');
        },

        // Check if context is valid for drawing
        canDraw() {
            if (this.drawMode === 'room') return true;
            if (this.drawMode === 'room_location') return this.activeRoomId !== null;
            if (this.drawMode === 'cabinet_run') return this.activeRoomId !== null && this.activeLocationId !== null;
            if (this.drawMode === 'cabinet') return this.activeRoomId !== null && this.activeLocationId !== null;
            return false;
        },

        // Get context label for display
        getContextLabel() {
            if (this.activeRoomName && this.activeLocationName) {
                return `${this.activeRoomName} > ${this.activeLocationName}`;
            } else if (this.activeRoomName) {
                return this.activeRoomName;
            }
            return 'No context selected';
        }
    };
}
