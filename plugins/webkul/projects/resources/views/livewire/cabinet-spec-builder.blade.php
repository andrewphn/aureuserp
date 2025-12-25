<div class="cabinet-spec-builder"
     wire:key="cabinet-spec-builder"
     x-data="specAccordionBuilder(@entangle('specData'), @entangle('expanded'))"
     x-init="init()">

    {{-- Header with Totals --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2.5">
            <div class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
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
                        <span class="text-gray-500 dark:text-gray-400">Total:</span>
                        <strong class="text-blue-600 dark:text-blue-400 tabular-nums">{{ number_format($totalLinearFeet, 1) }} LF</strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400">Est:</span>
                        <strong class="text-green-600 dark:text-green-400 tabular-nums">${{ number_format($totalPrice, 0) }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Breadcrumb Navigation --}}
    @include('webkul-project::livewire.partials.spec-breadcrumb')

    {{-- Empty State --}}
    @if(empty($specData))
        <div class="text-center py-16 px-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50/50 dark:bg-gray-800/50">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <x-heroicon-o-home class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">No rooms added yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5 max-w-sm mx-auto">
                Start building your cabinet specification by adding your first room
            </p>
            <button
                wire:click="openCreate('room', null)"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 border border-primary-300 hover:border-primary-400 bg-white dark:bg-gray-800 rounded-lg transition-colors shadow-sm"
            >
                <x-heroicon-m-plus class="w-4 h-4" />
                Add First Room
            </button>
        </div>
    @else
        {{-- Main Layout: Sidebar + Inspector --}}
        <div class="flex min-h-[550px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 overflow-hidden">

            {{-- Collapsible Sidebar --}}
            <div
                :class="sidebarCollapsed ? 'w-14' : 'w-80'"
                class="flex-shrink-0 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 transition-all duration-200 flex flex-col"
            >
                {{-- Sidebar Header with Collapse Toggle --}}
                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <span x-show="!sidebarCollapsed" class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Rooms</span>
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 transition-colors"
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
                            :class="selectedRoomIndex === roomIdx ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="w-10 h-10 rounded-lg flex items-center justify-center text-xs font-bold shadow-sm transition-colors"
                            :title="room.name"
                        >
                            <span x-text="(room.name || 'R').charAt(0).toUpperCase()"></span>
                        </button>
                    </template>
                    <button
                        wire:click="openCreate('room', null)"
                        class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                        title="Add Room"
                    >
                        <x-heroicon-m-plus class="w-5 h-5" />
                    </button>
                </div>
            </div>

            {{-- Inspector Panel --}}
            <div class="flex-1 flex flex-col overflow-hidden">
                {{-- Inspector Content --}}
                <div class="flex-1 overflow-y-auto p-4">
                    @include('webkul-project::livewire.partials.spec-inspector')
                </div>
            </div>
        </div>

        {{-- Summary Footer --}}
        @if($totalLinearFeet > 0)
            <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 rounded-xl border border-blue-200/50 dark:border-blue-800/50 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Project Summary</span>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Total Linear Feet</div>
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ number_format($totalLinearFeet, 1) }} LF</div>
                        </div>
                        <div class="w-px h-10 bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Estimated Price</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-400 tabular-nums">${{ number_format($totalPrice, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Modal --}}
    @if($showModal)
        @include('webkul-project::livewire.partials.spec-modal')
    @endif
</div>

@script
<script>
Alpine.data('specAccordionBuilder', (specData, expanded) => ({
    specData: specData,
    expanded: expanded,

    // UI state
    sidebarCollapsed: false,

    // Selection state (objects, not indexes for accordion)
    selectedRoomIndex: null,
    selectedRoom: null,
    selectedLocationIndex: null,
    selectedLocation: null,
    selectedRunIndex: null,
    selectedRun: null,

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

    getPricePerLF(tier) {
        const prices = {
            '1': 225,
            '2': 298,
            '3': 348,
            '4': 425,
            '5': 550
        };
        return prices[tier] || 298;
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
    }
}));
</script>
@endscript
