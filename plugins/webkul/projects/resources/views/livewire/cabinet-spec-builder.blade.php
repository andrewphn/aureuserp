<div class="cabinet-spec-builder"
     wire:key="cabinet-spec-builder"
     x-data="specAccordionBuilder(
         @entangle('specData'),
         @entangle('expanded'),
         @js($pricingTiers),
         @js($materialOptions),
         @js($finishOptions)
     )"
     x-init="init()">

    {{-- Header with Totals --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 p-4 rounded-xl border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-lg shadow-sm bg-white dark:bg-gray-700">
                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </div>
            <div>
                <span class="font-semibold text-gray-900 dark:text-white">Cabinet Specifications</span>
                @if(count($specData) > 0)
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ count($specData) }} room{{ count($specData) !== 1 ? 's' : '' }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4">
            @if($totalLinearFeet > 0)
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-300">Total:</span>
                        <strong class="tabular-nums text-blue-600 dark:text-blue-400">{{ format_linear_feet($totalLinearFeet) }}</strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-300">Est:</span>
                        <strong class="tabular-nums text-green-600 dark:text-green-400">${{ number_format($totalPrice, 0) }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Breadcrumb Navigation --}}
    @include('webkul-project::livewire.partials.spec-breadcrumb')

    {{-- Empty State --}}
    @if(empty($specData))
        <div class="text-center py-16 px-6 border-2 border-dashed rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                <x-heroicon-o-home class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-base font-medium mb-1 text-gray-900 dark:text-white">No rooms added yet</h3>
            <p class="text-sm mb-5 max-w-sm mx-auto text-gray-500 dark:text-gray-400">
                Start building your cabinet specification by adding your first room
            </p>
            {{-- Using Filament Action for Add Room --}}
            {{ $this->createRoomAction }}
        </div>
    @else
        {{-- Main Layout: Sidebar (40%) + Inspector (60%) --}}
        <div class="flex min-h-[550px] border rounded-xl overflow-hidden border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">

            {{-- Navigation Sidebar - 200px min, 280px max when expanded --}}
            <div
                :style="sidebarCollapsed ? 'width: 56px; flex: 0 0 56px;' : 'flex: 0 0 280px; min-width: 200px; max-width: 280px;'"
                class="border-r transition-all duration-200 flex flex-col overflow-hidden border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
            >
                {{-- Sidebar Header with Collapse Toggle --}}
                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <span x-show="!sidebarCollapsed" class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Rooms</span>
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="p-1.5 rounded-lg text-gray-500 transition-colors hover:bg-gray-200 dark:hover:bg-gray-700"
                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    >
                        <x-heroicon-o-chevron-left x-show="!sidebarCollapsed" class="w-4 h-4" />
                        <x-heroicon-o-chevron-right x-show="sidebarCollapsed" class="w-4 h-4" />
                    </button>
                </div>

                {{-- Accordion Tree (shown when sidebar expanded) --}}
                <div x-show="!sidebarCollapsed" x-cloak class="flex-1 overflow-y-auto p-2">
                    @include('webkul-project::livewire.partials.spec-tree-accordion')
                </div>

                {{-- Icon Strip (shown when sidebar collapsed) --}}
                <div x-show="sidebarCollapsed" x-cloak class="flex-1 flex flex-col items-center py-3 gap-1.5 overflow-y-auto">
                    <template x-for="(room, roomIdx) in specData" :key="room.id || roomIdx">
                        <button
                            @click="selectRoom(roomIdx); sidebarCollapsed = false"
                            :class="selectedRoomIndex === roomIdx
                                ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                                : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="w-10 h-10 rounded-lg flex items-center justify-center text-xs font-bold shadow-sm transition-colors"
                            :title="room.name"
                        >
                            <span x-text="(room.name || 'R').charAt(0).toUpperCase()"></span>
                        </button>
                    </template>
                    {{-- Filament Action: Add Room --}}
                    <button
                        wire:click="mountAction('createRoom')"
                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-600 dark:hover:text-primary-400"
                        title="Add Room"
                    >
                        <x-heroicon-m-plus class="w-5 h-5" />
                    </button>
                </div>
            </div>

            {{-- Inspector Panel - fills remaining space --}}
            <div
                style="flex: 1 1 auto; min-width: 0;"
                class="flex flex-col overflow-hidden"
            >
                {{-- Inspector Content --}}
                <div class="flex-1 overflow-y-auto p-4">
                    @include('webkul-project::livewire.partials.spec-inspector')
                </div>
            </div>
        </div>

        {{-- Summary Footer --}}
        @if($totalLinearFeet > 0)
            <div class="mt-4 p-4 bg-gradient-to-r rounded-xl border shadow-sm from-blue-50 dark:from-blue-900/20 to-green-50 dark:to-green-900/20 border-blue-200/50 dark:border-blue-800/50">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Project Summary</span>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider font-medium text-gray-500 dark:text-gray-300">Total Linear Feet</div>
                            <div class="text-xl font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ format_linear_feet($totalLinearFeet) }}</div>
                        </div>
                        <div class="w-px h-10 hidden sm:block bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider font-medium text-gray-500 dark:text-gray-300">Estimated Price</div>
                            <div class="text-xl font-bold tabular-nums text-green-600 dark:text-green-400">${{ number_format($totalPrice, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Context Menu --}}
    <div
        x-show="contextMenu.show"
        x-cloak
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`"
        class="fixed z-50 min-w-[180px] py-1 rounded-lg shadow-lg border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700"
        @click.away="closeContextMenu()"
    >
        {{-- Edit --}}
        <button
            @click="contextMenuAction('edit')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-pencil-square class="w-4 h-4 text-blue-500" />
            Edit
        </button>

        {{-- Duplicate --}}
        <button
            @click="contextMenuAction('duplicate')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-document-duplicate class="w-4 h-4 text-purple-500" />
            Duplicate
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Move Up --}}
        <button
            @click="contextMenuAction('moveUp')"
            :disabled="contextMenu.isFirst"
            :class="contextMenu.isFirst ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-arrow-up class="w-4 h-4 text-gray-500" />
            Move Up
        </button>

        {{-- Move Down --}}
        <button
            @click="contextMenuAction('moveDown')"
            :disabled="contextMenu.isLast"
            :class="contextMenu.isLast ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-arrow-down class="w-4 h-4 text-gray-500" />
            Move Down
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Add Child --}}
        <button
            @click="contextMenuAction('addChild')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            <x-heroicon-m-plus class="w-4 h-4 text-green-500" />
            <span>Add <span x-text="contextMenu.childType"></span></span>
        </button>

        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>

        {{-- Delete --}}
        <button
            @click="contextMenuAction('delete')"
            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
        >
            <x-heroicon-m-trash class="w-4 h-4" />
            Delete
        </button>
    </div>

    {{-- Filament Action Modals --}}
    <x-filament-actions::modals />
</div>

@script
<script>
// Initialize MeasurementFormatter with settings from PHP
if (window.MeasurementFormatter) {
    window.MeasurementFormatter.init(@js(measurement_settings()));
}

Alpine.data('specAccordionBuilder', (specData, expanded, pricingTiers, materialOptions, finishOptions) => ({
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
}));
</script>
@endscript
