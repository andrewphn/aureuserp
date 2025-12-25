{{-- Dynamic Inspector Panel --}}
{{-- Content changes based on selection: Room → Location → Run → Cabinets --}}

<div class="space-y-4">

    {{-- ================================================================== --}}
    {{-- WHEN A RUN IS SELECTED: Show run details + cabinets table --}}
    {{-- ================================================================== --}}
    <template x-if="selectedRun">
        <div class="space-y-4">
            {{-- Run Edit Form --}}
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="p-1.5 rounded bg-purple-100 dark:bg-purple-900/50">
                            <x-heroicon-s-squares-2x2 class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <span x-text="selectedRun.name || 'Cabinet Run'"></span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            @click="$wire.openEdit('cabinet_run', selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex)"
                            class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 hover:text-gray-700 transition-colors"
                            title="Edit Run"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="if(confirm('Delete this run?')) $wire.deleteByPath('cabinet_run', selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex)"
                            class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-600 transition-colors"
                            title="Delete Run"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Run Stats Row --}}
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400">Cabinets:</span>
                        <strong class="text-gray-900 dark:text-white" x-text="(selectedRun.children || []).length"></strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400">Linear Feet:</span>
                        <strong class="text-blue-600 dark:text-blue-400 tabular-nums" x-text="(selectedRun.linear_feet || 0).toFixed(2) + ' LF'"></strong>
                    </div>
                </div>
            </div>

            {{-- Pricing Info (inherited from location) --}}
            <div class="flex items-center gap-3 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800/50 text-sm">
                <x-heroicon-m-currency-dollar class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                <span class="text-amber-800 dark:text-amber-300">
                    <span class="font-medium">Pricing:</span>
                    Level <span x-text="selectedLocation?.cabinet_level || '2'"></span>
                    @ $<span x-text="getPricePerLF(selectedLocation?.cabinet_level || '2')"></span>/LF
                </span>
            </div>

            {{-- Cabinets Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Cabinets</h4>
                    <button
                        @click="$wire.openCreate('cabinet', selectedRoomIndex + '.children.' + selectedLocationIndex + '.children.' + selectedRunIndex)"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 font-medium flex items-center gap-1"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Cabinet
                    </button>
                </div>

                <div class="overflow-x-auto">
                    @include('webkul-project::livewire.partials.spec-cabinet-table')
                </div>

                {{-- Quick Add Input --}}
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            x-ref="quickAddInput"
                            placeholder="Quick add: B24, W30, SB36..."
                            @keydown.enter.prevent.stop="addCabinetFromCode($event.target.value); $event.target.value = ''"
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                        <button
                            @click="addCabinetFromCode($refs.quickAddInput.value); $refs.quickAddInput.value = ''"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors"
                        >
                            Add
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Codes: B=Base, W=Wall, SB=Sink Base, T=Tall, V=Vanity + width (e.g., B24, W3012)
                    </p>
                </div>
            </div>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A LOCATION IS SELECTED (but no run): Show location + runs list --}}
    {{-- ================================================================== --}}
    <template x-if="selectedLocation && !selectedRun">
        <div class="space-y-4">
            {{-- Location Edit Form --}}
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="p-1.5 rounded bg-green-100 dark:bg-green-900/50">
                            <x-heroicon-s-map-pin class="w-4 h-4 text-green-600 dark:text-green-400" />
                        </div>
                        <span x-text="selectedLocation.name || 'Location'"></span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            @click="$wire.openEdit('room_location', selectedRoomIndex + '.children.' + selectedLocationIndex)"
                            class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 hover:text-gray-700 transition-colors"
                            title="Edit Location"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="if(confirm('Delete this location and all its runs?')) $wire.deleteByPath('room_location', selectedRoomIndex + '.children.' + selectedLocationIndex)"
                            class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-600 transition-colors"
                            title="Delete Location"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Location Details --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Level</span>
                        <strong class="text-gray-900 dark:text-white">L<span x-text="selectedLocation.cabinet_level || '2'"></span></strong>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Price/LF</span>
                        <strong class="text-green-600 dark:text-green-400">$<span x-text="getPricePerLF(selectedLocation.cabinet_level || '2')"></span></strong>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Runs</span>
                        <strong class="text-gray-900 dark:text-white" x-text="(selectedLocation.children || []).length"></strong>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Linear Feet</span>
                        <strong class="text-blue-600 dark:text-blue-400 tabular-nums" x-text="(selectedLocation.linear_feet || 0).toFixed(2) + ' LF'"></strong>
                    </div>
                </div>
            </div>

            {{-- Runs List --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Cabinet Runs</h4>
                    <button
                        @click="$wire.openCreate('cabinet_run', selectedRoomIndex + '.children.' + selectedLocationIndex)"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 font-medium flex items-center gap-1"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Run
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="!(selectedLocation.children || []).length">
                        <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="w-10 h-10 mx-auto mb-2 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-400" />
                            </div>
                            No cabinet runs yet.
                            <button
                                @click="$wire.openCreate('cabinet_run', selectedRoomIndex + '.children.' + selectedLocationIndex)"
                                class="text-primary-600 hover:text-primary-700 font-medium ml-1"
                            >Add your first run</button>
                        </div>
                    </template>

                    <template x-for="(run, runIdx) in (selectedLocation.children || [])" :key="run.id || runIdx">
                        <div
                            @click="selectRun(selectedRoomIndex, selectedLocationIndex, runIdx)"
                            class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 rounded bg-purple-100 dark:bg-purple-900/50">
                                    <x-heroicon-s-squares-2x2 class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm" x-text="run.name || 'Untitled Run'"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="(run.children || []).length"></span> cabinets
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400 tabular-nums" x-text="(run.linear_feet || 0).toFixed(2) + ' LF'"></span>
                                <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- WHEN A ROOM IS SELECTED (but no location): Show room + locations list --}}
    {{-- ================================================================== --}}
    <template x-if="selectedRoom && !selectedLocation">
        <div class="space-y-4">
            {{-- Room Edit Form --}}
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="p-1.5 rounded bg-blue-100 dark:bg-blue-900/50">
                            <x-heroicon-s-home class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <span x-text="selectedRoom.name || 'Room'"></span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            @click="$wire.openEdit('room', selectedRoomIndex.toString())"
                            class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 hover:text-gray-700 transition-colors"
                            title="Edit Room"
                        >
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            @click="if(confirm('Delete this room and all its contents?')) $wire.deleteByPath('room', selectedRoomIndex.toString())"
                            class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-600 transition-colors"
                            title="Delete Room"
                        >
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Room Details --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Type</span>
                        <strong class="text-gray-900 dark:text-white capitalize" x-text="selectedRoom.room_type || 'Other'"></strong>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Code</span>
                        <strong class="text-gray-900 dark:text-white" x-text="selectedRoom.room_code || '-'"></strong>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Locations</span>
                        <strong class="text-gray-900 dark:text-white" x-text="(selectedRoom.children || []).length"></strong>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">Linear Feet</span>
                        <strong class="text-blue-600 dark:text-blue-400 tabular-nums" x-text="(selectedRoom.linear_feet || 0).toFixed(2) + ' LF'"></strong>
                    </div>
                </div>
            </div>

            {{-- Locations List --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Wall Locations</h4>
                    <button
                        @click="$wire.openCreate('room_location', selectedRoomIndex.toString())"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 font-medium flex items-center gap-1"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Location
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="!(selectedRoom.children || []).length">
                        <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="w-10 h-10 mx-auto mb-2 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-400" />
                            </div>
                            No wall locations yet.
                            <button
                                @click="$wire.openCreate('room_location', selectedRoomIndex.toString())"
                                class="text-primary-600 hover:text-primary-700 font-medium ml-1"
                            >Add your first location</button>
                        </div>
                    </template>

                    <template x-for="(location, locIdx) in (selectedRoom.children || [])" :key="location.id || locIdx">
                        <div
                            @click="selectLocation(selectedRoomIndex, locIdx)"
                            class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-1.5 rounded bg-green-100 dark:bg-green-900/50">
                                    <x-heroicon-s-map-pin class="w-4 h-4 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm" x-text="location.name || 'Untitled'"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Level <span x-text="location.cabinet_level || '2'"></span>
                                        &bull;
                                        <span x-text="(location.children || []).length"></span> runs
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400 tabular-nums" x-text="(location.linear_feet || 0).toFixed(2) + ' LF'"></span>
                                <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    {{-- ================================================================== --}}
    {{-- EMPTY STATE: No selection --}}
    {{-- ================================================================== --}}
    <template x-if="!selectedRoom">
        <div class="flex flex-col items-center justify-center h-full py-16 text-center">
            <div class="w-16 h-16 mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <x-heroicon-o-cursor-arrow-rays class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">Select a room to begin</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                Click on a room in the sidebar to view its locations, runs, and cabinets.
            </p>
        </div>
    </template>
</div>
