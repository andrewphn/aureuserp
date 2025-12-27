{{-- Stacked Accordion Tree Navigation with Context Menu --}}
<div class="space-y-1.5">
    {{-- Loop through rooms --}}
    <template x-for="(room, roomIdx) in specData" :key="room.id || roomIdx">
        <div class="border rounded-lg overflow-hidden shadow-sm border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            {{-- Room Header --}}
            <div
                @click="selectRoom(roomIdx)"
                @contextmenu.prevent="openContextMenu($event, 'room', roomIdx, null, null)"
                :class="[
                    selectedRoomIndex === roomIdx
                        ? 'bg-blue-50 dark:bg-blue-900/30 border-l-3 border-l-blue-500'
                        : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                ]"
                class="flex items-center gap-2 px-3 py-2.5 cursor-pointer transition-colors"
            >
                {{-- Expand/Collapse Toggle --}}
                <button
                    @click.stop="toggleAccordion(room.id)"
                    class="p-0.5 rounded text-gray-400 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                    <x-heroicon-m-chevron-down
                        x-show="isExpanded(room.id)"
                        class="w-4 h-4 transition-transform"
                    />
                    <x-heroicon-m-chevron-right
                        x-show="!isExpanded(room.id)"
                        class="w-4 h-4"
                    />
                </button>

                {{-- Room Icon --}}
                <div class="p-1 rounded bg-blue-100 dark:bg-blue-900/50">
                    <x-heroicon-s-home class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                </div>

                {{-- Room Name & Info --}}
                <div class="flex-1 min-w-0">
                    <div class="font-medium truncate text-sm text-gray-900 dark:text-gray-100" x-text="room.name || 'Untitled Room'"></div>
                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                        <span class="capitalize" x-text="room.room_type || 'other'"></span>
                        <span x-show="(room.linear_feet || 0) > 0" class="font-medium text-blue-600 dark:text-blue-400" x-text="(room.linear_feet || 0).toFixed(1) + ' LF'"></span>
                    </div>
                </div>

                {{-- Selection indicator --}}
                <div x-show="selectedRoomIndex === roomIdx" class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
            </div>

            {{-- Locations (nested inside room) --}}
            <div
                x-show="isExpanded(room.id)"
                x-collapse
                class="border-t border-gray-100 dark:border-gray-700"
            >
                <div class="pl-4 pr-2 py-1.5 space-y-1">
                    <template x-for="(location, locIdx) in (room.children || [])" :key="location.id || locIdx">
                        <div class="border-l-2 border-gray-200 dark:border-gray-600">
                            {{-- Location Header --}}
                            <div
                                @click.stop="selectLocation(roomIdx, locIdx)"
                                @contextmenu.prevent="openContextMenu($event, 'location', roomIdx, locIdx, null)"
                                :class="[
                                    selectedRoomIndex === roomIdx && selectedLocationIndex === locIdx
                                        ? 'bg-green-50 dark:bg-green-900/30 border-l-2 border-l-green-500 -ml-0.5'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-700/30',
                                ]"
                                class="flex items-center gap-2 px-2.5 py-2 cursor-pointer transition-colors rounded-r-lg ml-1"
                            >
                                {{-- Expand/Collapse Toggle --}}
                                <button
                                    @click.stop="toggleAccordion(location.id)"
                                    class="p-0.5 rounded text-gray-400 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600"
                                    x-show="(location.children || []).length > 0"
                                >
                                    <x-heroicon-m-chevron-down
                                        x-show="isExpanded(location.id)"
                                        class="w-3.5 h-3.5"
                                    />
                                    <x-heroicon-m-chevron-right
                                        x-show="!isExpanded(location.id)"
                                        class="w-3.5 h-3.5"
                                    />
                                </button>
                                <div x-show="(location.children || []).length === 0" class="w-4"></div>

                                {{-- Location Icon --}}
                                <div class="p-0.5 rounded bg-green-100 dark:bg-green-900/50">
                                    <x-heroicon-s-map-pin class="w-3 h-3 text-green-600 dark:text-green-400" />
                                </div>

                                {{-- Location Name & Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate text-sm text-gray-800 dark:text-gray-200" x-text="location.name || 'Untitled'"></div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                                        <span>L<span x-text="location.cabinet_level || '2'"></span></span>
                                        <span x-show="(location.linear_feet || 0) > 0" class="text-blue-600 dark:text-blue-400" x-text="(location.linear_feet || 0).toFixed(1) + ' LF'"></span>
                                    </div>
                                </div>

                                {{-- Selection indicator --}}
                                <div x-show="selectedRoomIndex === roomIdx && selectedLocationIndex === locIdx" class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                            </div>

                            {{-- Runs (nested inside location) --}}
                            <div
                                x-show="isExpanded(location.id)"
                                x-collapse
                                class="pl-4 py-1 space-y-0.5"
                            >
                                <template x-for="(run, runIdx) in (location.children || [])" :key="run.id || runIdx">
                                    <div
                                        @click.stop="selectRun(roomIdx, locIdx, runIdx)"
                                        @contextmenu.prevent="openContextMenu($event, 'run', roomIdx, locIdx, runIdx)"
                                        :class="[
                                            selectedRoomIndex === roomIdx && selectedLocationIndex === locIdx && selectedRunIndex === runIdx
                                                ? 'bg-purple-50 dark:bg-purple-900/30 border-l-2 border-l-purple-500'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/30 border-l-2 border-transparent',
                                        ]"
                                        class="flex items-center gap-2 px-2.5 py-1.5 cursor-pointer transition-colors rounded-r-lg"
                                    >
                                        {{-- Run Icon --}}
                                        <div class="p-0.5 rounded bg-purple-100 dark:bg-purple-900/50">
                                            <x-heroicon-s-squares-2x2 class="w-2.5 h-2.5 text-purple-600 dark:text-purple-400" />
                                        </div>

                                        {{-- Run Name & Info --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="truncate text-sm text-gray-700 dark:text-gray-300" x-text="run.name || 'Untitled'"></div>
                                        </div>

                                        {{-- Stats --}}
                                        <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <span x-show="(run.children || []).length > 0" x-text="(run.children || []).length + ' cab'"></span>
                                            <span x-show="(run.linear_feet || 0) > 0" class="text-blue-600 dark:text-blue-400" x-text="(run.linear_feet || 0).toFixed(1) + ' LF'"></span>
                                        </div>

                                        {{-- Selection indicator --}}
                                        <div x-show="selectedRoomIndex === roomIdx && selectedLocationIndex === locIdx && selectedRunIndex === runIdx" class="w-1.5 h-1.5 rounded-full bg-purple-500"></div>
                                    </div>
                                </template>

                                {{-- Add Run Button (Filament Action) --}}
                                <button
                                    @click.stop="$wire.mountAction('createRun', { locationPath: roomIdx + '.children.' + locIdx })"
                                    class="w-full flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg transition-colors text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20"
                                >
                                    <x-heroicon-m-plus class="w-3.5 h-3.5" />
                                    Add Run
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Add Location Button (Filament Action) --}}
                    <button
                        @click.stop="$wire.mountAction('createLocation', { roomPath: roomIdx.toString() })"
                        class="w-full flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg transition-colors ml-1 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20"
                    >
                        <x-heroicon-m-plus class="w-3.5 h-3.5" />
                        Add Location
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- Add Room Button (Filament Action) --}}
    <button
        @click="$wire.mountAction('createRoom')"
        type="button"
        class="w-full flex items-center justify-center gap-1.5 px-3 py-2.5 text-sm font-medium border border-dashed rounded-lg transition-colors text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800 border-blue-300 dark:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20"
    >
        <x-heroicon-m-plus class="w-4 h-4" />
        Add Room
    </button>
</div>
