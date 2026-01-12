/**
 * Cabinet Spec Builder Alpine Component
 * 
 * A Notion-like hierarchical tree builder for cabinet specifications.
 * Hierarchy: Room → RoomLocation → CabinetRun → Cabinet
 * 
 * This component is registered globally so it's available before Alpine
 * initializes, preventing timing issues with Livewire's @script directive.
 */

// Define the Alpine component factory function
function createSpecAccordionBuilder(specData, expanded, pricingTiers, materialOptions, finishOptions, measurementSettings) {
    return {
        specData: specData,
        expanded: expanded,

        // Pricing data from TcsPricingService
        pricingTiers: pricingTiers || {},
        materialOptions: materialOptions || {},
        finishOptions: finishOptions || {},

        // UI state
        sidebarCollapsed: false,

        // Selection state (objects, not indexes for accordion)
        selectedRoomIndex: null,
        selectedRoom: null,
        selectedLocationIndex: null,
        selectedLocation: null,
        selectedRunIndex: null,
        selectedRun: null,
        selectedCabinetIndex: null,

        // Inline editing state
        editingRow: null,
        editingField: null,

        // Field order for tab navigation
        fieldOrder: ['name', 'length_inches', 'height_inches', 'depth_inches', 'quantity'],

        init() {
            // Initialize MeasurementFormatter with settings from PHP
            if (window.MeasurementFormatter && measurementSettings) {
                window.MeasurementFormatter.init(measurementSettings);
            }

            // Watch for specData changes from Livewire
            this.$watch('specData', (value) => {
                // Re-select if current selections still valid
                if (this.selectedRoomIndex !== null && value[this.selectedRoomIndex]) {
                    this.selectedRoom = value[this.selectedRoomIndex];

                    if (this.selectedLocationIndex !== null && this.selectedRoom.children?.[this.selectedLocationIndex]) {
                        this.selectedLocation = this.selectedRoom.children[this.selectedLocationIndex];

                        if (this.selectedRunIndex !== null && this.selectedLocation.children?.[this.selectedRunIndex]) {
                            this.selectedRun = this.selectedLocation.children[this.selectedRunIndex];
                        }
                    }
                }
            });

            // Auto-select first room if available
            if (this.specData && this.specData.length > 0) {
                this.selectRoom(0);
            }
        },

        // =========================================================================
        // SELECTION METHODS
        // =========================================================================

        selectRoom(index) {
            const room = this.specData[index];
            if (!room) return;

            this.selectedRoomIndex = index;
            this.selectedRoom = room;

            // Clear downstream selections
            this.selectedLocationIndex = null;
            this.selectedLocation = null;
            this.selectedRunIndex = null;
            this.selectedRun = null;

            // Expand this room in accordion
            if (room.id && !this.expanded.includes(room.id)) {
                this.expanded.push(room.id);
            }
        },

        selectLocation(roomIndex, locationIndex) {
            // Make sure room is selected first
            if (this.selectedRoomIndex !== roomIndex) {
                this.selectRoom(roomIndex);
            }

            const location = this.specData[roomIndex]?.children?.[locationIndex];
            if (!location) return;

            this.selectedLocationIndex = locationIndex;
            this.selectedLocation = location;

            // Clear run selection
            this.selectedRunIndex = null;
            this.selectedRun = null;

            // Expand this location in accordion
            if (location.id && !this.expanded.includes(location.id)) {
                this.expanded.push(location.id);
            }
        },

        selectRun(roomIndex, locationIndex, runIndex) {
            // Make sure location is selected first
            if (this.selectedRoomIndex !== roomIndex || this.selectedLocationIndex !== locationIndex) {
                this.selectLocation(roomIndex, locationIndex);
            }

            const run = this.specData[roomIndex]?.children?.[locationIndex]?.children?.[runIndex];
            if (!run) return;

            this.selectedRunIndex = runIndex;
            this.selectedRun = run;
            this.selectedCabinetIndex = null; // Clear cabinet selection when changing runs
        },

        // Toggle accordion expansion
        toggleAccordion(nodeId) {
            const idx = this.expanded.indexOf(nodeId);
            if (idx > -1) {
                this.expanded.splice(idx, 1);
            } else {
                this.expanded.push(nodeId);
            }
        },

        isExpanded(nodeId) {
            return this.expanded.includes(nodeId);
        },

        // Breadcrumb navigation
        clearToRoom() {
            this.selectedLocationIndex = null;
            this.selectedLocation = null;
            this.selectedRunIndex = null;
            this.selectedRun = null;
        },

        clearToLocation() {
            this.selectedRunIndex = null;
            this.selectedRun = null;
        },

        // =========================================================================
        // PRICING HELPERS
        // =========================================================================

        /**
         * Calculate price per linear foot from level, material, and finish
         * Prices are extracted from the option labels which contain the price info
         */
        getPricePerLF(level = '3', material = 'stain_grade', finish = 'unfinished') {
            // Extract base price from level label (e.g., "Level 3 - Enhanced ($192/LF)" -> 192)
            let basePrice = 192; // Default Level 3
            const levelLabel = this.pricingTiers[level] || '';
            const levelMatch = levelLabel.match(/\$(\d+(?:\.\d+)?)/);
            if (levelMatch) {
                basePrice = parseFloat(levelMatch[1]);
            } else {
                // Fallback prices if label doesn't contain price
                const fallbackPrices = {'1': 138, '2': 168, '3': 192, '4': 242, '5': 345};
                basePrice = fallbackPrices[level] || 192;
            }

            // Extract material price from label (e.g., "Stain Grade +$156/LF" -> 156)
            let materialPrice = 0;
            const materialLabel = this.materialOptions[material] || '';
            const materialMatch = materialLabel.match(/\+\$(\d+(?:\.\d+)?)/);
            if (materialMatch) {
                materialPrice = parseFloat(materialMatch[1]);
            } else if (material === 'stain_grade') {
                materialPrice = 156; // Default for stain grade
            }

            // Extract finish price from label (e.g., "Stain + Clear +$85/LF" -> 85)
            let finishPrice = 0;
            const finishLabel = this.finishOptions[finish] || '';
            const finishMatch = finishLabel.match(/\+\$(\d+(?:\.\d+)?)/);
            if (finishMatch) {
                finishPrice = parseFloat(finishMatch[1]);
            }

            return basePrice + materialPrice + finishPrice;
        },

        /**
         * Update location pricing field and sync to Livewire
         */
        updateLocationPricing(field, value) {
            if (this.selectedLocationIndex === null) return;

            // Update local state
            this.selectedLocation[field] = value || null; // null for "inherit"

            // Build path and update via Livewire
            const path = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}`;
            this.$wire.updateNodeField(path, { [field]: value || null });
        },

        /**
         * Update room pricing field and sync to Livewire
         */
        updateRoomPricing(field, value) {
            if (this.selectedRoomIndex === null) return;

            // Update local state
            this.selectedRoom[field] = value;

            // Update via Livewire
            const path = `${this.selectedRoomIndex}`;
            this.$wire.updateNodeField(path, { [field]: value });
        },

        /**
         * Update run pricing field and sync to Livewire
         */
        updateRunPricing(field, value) {
            if (this.selectedRunIndex === null) return;

            // Update local state
            this.selectedRun[field] = value || null;

            // Build path and update via Livewire
            const path = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}`;
            this.$wire.updateNodeField(path, { [field]: value || null });
        },

        // =========================================================================
        // CABINET OPERATIONS
        // =========================================================================

        addCabinetFromCode(code) {
            if (!code || !this.selectedRun) return;

            code = code.trim().toUpperCase();

            // Parse cabinet code (B24, W30, SB36, etc.)
            const parsed = this.parseCabinetCode(code);

            if (!parsed.width) {
                return;
            }

            // Build the run path
            const runPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}`;

            // Dispatch to Livewire to add cabinet
            this.$wire.handleAiAddCabinet({
                run_path: runPath,
                name: code,
                cabinet_type: parsed.type,
                length_inches: parsed.width,
                depth_inches: parsed.depth,
                height_inches: parsed.height,
                quantity: 1,
                source: 'user'
            });
        },

        parseCabinetCode(code) {
            const defaults = {
                base: { depth: 24, height: 34.5 },
                wall: { depth: 12, height: 30 },
                tall: { depth: 24, height: 84 },
                vanity: { depth: 21, height: 34.5 },
                sink_base: { depth: 24, height: 34.5 }
            };

            let type = 'base';
            let width = null;

            // Patterns for different cabinet types
            if (/^(SB|BBC)(\d+)$/.test(code)) {
                type = 'sink_base';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^(DB|B)(\d+)$/.test(code)) {
                type = 'base';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^W(\d+)/.test(code)) {
                type = 'wall';
                width = parseInt(code.match(/^W(\d+)/)[1]);
            } else if (/^(T|TP|P)(\d+)$/.test(code)) {
                type = 'tall';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^V(\d+)$/.test(code)) {
                type = 'vanity';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^\d+$/.test(code)) {
                type = 'base';
                width = parseInt(code);
            }

            return {
                type: type,
                width: width,
                depth: defaults[type]?.depth || 24,
                height: defaults[type]?.height || 34.5
            };
        },

        // =========================================================================
        // INLINE EDITING
        // =========================================================================

        startEdit(rowIndex, field) {
            this.editingRow = rowIndex;
            this.editingField = field;
        },

        cancelEdit() {
            this.editingRow = null;
            this.editingField = null;
        },

        saveCabinetField(cabIndex, field, value) {
            if (!this.selectedRun || !this.selectedRun.children?.[cabIndex]) return;

            const cabinet = this.selectedRun.children[cabIndex];
            const oldValue = cabinet[field];

            // Parse numeric fields
            if (['length_inches', 'height_inches', 'depth_inches', 'quantity'].includes(field)) {
                value = parseFloat(value) || oldValue;
            }

            if (value === oldValue) {
                this.cancelEdit();
                return;
            }

            cabinet[field] = value;

            const cabinetPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}.children.${cabIndex}`;
            this.$wire.updateCabinetField(cabinetPath, field, value);

            this.cancelEdit();
        },

        moveToNextCell(rowIndex, currentField, event = null) {
            if (event) event.preventDefault();

            const currentIdx = this.fieldOrder.indexOf(currentField);
            const nextIdx = currentIdx + 1;

            if (nextIdx < this.fieldOrder.length) {
                this.$nextTick(() => {
                    this.startEdit(rowIndex, this.fieldOrder[nextIdx]);
                });
            } else {
                this.moveToNextRow(rowIndex, event);
            }
        },

        moveToNextRow(rowIndex, event = null) {
            if (event) event.preventDefault();

            const cabinets = this.selectedRun?.children || [];
            const nextRow = rowIndex + 1;

            if (nextRow < cabinets.length) {
                this.$nextTick(() => {
                    this.startEdit(nextRow, this.fieldOrder[0]);
                });
            } else {
                this.cancelEdit();
                this.$nextTick(() => {
                    this.$refs.quickAddInput?.focus();
                });
            }
        },

        deleteCabinet(cabIndex) {
            if (!this.selectedRun) return;

            const cabinetPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}.children.${cabIndex}`;
            this.$wire.deleteCabinetByPath(cabinetPath);
        },

        // =========================================================================
        // CONTEXT MENU
        // =========================================================================

        contextMenu: {
            show: false,
            x: 0,
            y: 0,
            type: null, // 'room', 'location', 'run'
            roomIdx: null,
            locIdx: null,
            runIdx: null
        },

        openContextMenu(event, type, roomIdx, locIdx, runIdx) {
            // Get the node and siblings to determine position
            let node, siblings, currentIdx;

            if (type === 'room') {
                node = this.specData[roomIdx];
                siblings = this.specData;
                currentIdx = roomIdx;
            } else if (type === 'location') {
                node = this.specData[roomIdx]?.children?.[locIdx];
                siblings = this.specData[roomIdx]?.children || [];
                currentIdx = locIdx;
            } else if (type === 'run') {
                node = this.specData[roomIdx]?.children?.[locIdx]?.children?.[runIdx];
                siblings = this.specData[roomIdx]?.children?.[locIdx]?.children || [];
                currentIdx = runIdx;
            }

            this.contextMenu = {
                show: true,
                x: event.clientX,
                y: event.clientY,
                type: type,
                roomIdx: roomIdx,
                locIdx: locIdx,
                runIdx: runIdx,
                nodeName: node?.name || 'Item',
                isFirst: currentIdx === 0,
                isLast: currentIdx === siblings.length - 1,
                childType: type === 'room' ? 'Location' : (type === 'location' ? 'Run' : 'Cabinet')
            };

            // Close on click outside
            setTimeout(() => {
                document.addEventListener('click', this.closeContextMenu.bind(this), { once: true });
            }, 0);
        },

        closeContextMenu() {
            this.contextMenu.show = false;
        },

        contextMenuAction(action) {
            const { type, roomIdx, locIdx, runIdx } = this.contextMenu;
            let path;

            if (type === 'room') {
                path = roomIdx.toString();
            } else if (type === 'location') {
                path = `${roomIdx}.children.${locIdx}`;
            } else if (type === 'run') {
                path = `${roomIdx}.children.${locIdx}.children.${runIdx}`;
            }

            switch (action) {
                case 'edit':
                    this.$wire.mountAction('editNode', { nodePath: path });
                    break;
                case 'duplicate':
                    this.$wire.mountAction('duplicateNode', { nodePath: path, nodeType: type === 'location' ? 'room_location' : (type === 'run' ? 'cabinet_run' : type) });
                    break;
                case 'moveUp':
                    this.$wire.moveNode(path, 'up');
                    break;
                case 'moveDown':
                    this.$wire.moveNode(path, 'down');
                    break;
                case 'addChild':
                    if (type === 'room') {
                        this.$wire.mountAction('createLocation', { roomPath: path });
                    } else if (type === 'location') {
                        this.$wire.mountAction('createRun', { locationPath: path });
                    } else if (type === 'run') {
                        this.$wire.mountAction('createCabinet', { runPath: path });
                    }
                    break;
                case 'delete':
                    this.$wire.mountAction('deleteNode', { nodePath: path, nodeType: type === 'location' ? 'room_location' : (type === 'run' ? 'cabinet_run' : type) });
                    break;
            }

            this.closeContextMenu();
        }
    };
}

// Register Alpine component when Alpine is available
// Prevent duplicate registrations by checking if already registered
let specAccordionBuilderRegistered = false;

function registerSpecAccordionBuilder() {
    if (specAccordionBuilderRegistered) return;
    
    if (typeof Alpine !== 'undefined' && !Alpine.data('specAccordionBuilder')) {
        Alpine.data('specAccordionBuilder', createSpecAccordionBuilder);
        specAccordionBuilderRegistered = true;
        // Only log in development
        if (import.meta.env?.DEV || window.location.hostname.includes('localhost')) {
            console.log('✅ Cabinet Spec Builder Alpine component registered');
        }
    }
}

if (typeof Alpine !== 'undefined') {
    registerSpecAccordionBuilder();
} else {
    // Wait for Alpine to be ready (only register once)
    document.addEventListener('alpine:init', registerSpecAccordionBuilder, { once: true });
}

// Export for ES module imports
export default createSpecAccordionBuilder;
