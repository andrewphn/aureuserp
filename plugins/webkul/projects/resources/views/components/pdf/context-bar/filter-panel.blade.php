{{-- Filter Panel Component --}}
<!-- Filter Button with Badge -->
<div class="relative z-40">
    <button
        @click="showFilters = true"
        class="px-3 py-2 rounded-lg text-white hover:scale-105 hover:shadow-md transition-all text-sm font-semibold flex items-center gap-2 relative"
        :style="activeFiltersCount > 0 ? 'background-color: var(--primary-600);' : 'background-color: var(--gray-600);'"
        @mouseover="$el.style.backgroundColor = activeFiltersCount > 0 ? 'var(--primary-700)' : 'var(--gray-700)'"
        @mouseout="$el.style.backgroundColor = activeFiltersCount > 0 ? 'var(--primary-600)' : 'var(--gray-600)'"
        title="Filter Annotations"
    >
        <x-filament::icon icon="heroicon-o-funnel" class="h-4 w-4" />
        <span>Filter</span>
        <!-- Active Filter Count Badge -->
        <span
            x-show="activeFiltersCount > 0"
            x-text="activeFiltersCount"
            class="absolute -top-2 -right-2 flex items-center justify-center w-5 h-5 text-xs font-bold text-white rounded-full shadow-md z-10"
            style="background-color: var(--danger-600);"
        ></span>
    </button>

    {{-- Filter Panel Dropdown - teleported to body to escape stacking context --}}
    <template x-teleport="body">
        <div
            x-show="showFilters"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0"
            style="display: none; z-index: 9999;"
        >
            <!-- Backdrop - clicks here close the modal -->
            <div class="absolute inset-0 bg-gray-500/75 dark:bg-gray-900/75" @click="showFilters = false"></div>

            <!-- Dropdown Panel - positioned relative to Filter button -->
            <div class="fixed top-16 right-4 w-[36rem] max-h-[calc(100vh-5rem)]" style="z-index: 10000;" @click.stop>
            <div
                x-show="showFilters"
                x-transition:enter="transform transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transform transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                class="origin-top-right"
            >
                <div class="flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden max-h-[calc(100vh-5rem)]">
                    <!-- Header -->
                    <div class="px-4 py-4 sm:px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5" />
                                Filters
                                <span x-show="activeFiltersCount > 0"
                                      class="ml-1 px-2 py-0.5 bg-primary-600 text-white text-xs rounded-full"
                                      x-text="activeFiltersCount">
                                </span>
                            </h2>
                            <div class="flex items-center gap-2">
                                <button x-show="hasActiveFilters()"
                                        @click="clearAllFilters()"
                                        class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition">
                                    Clear all
                                </button>
                                <button @click="showFilters = false"
                                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition">
                                    <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Active Filter Chips -->
                        <div x-show="activeFilterChips.length > 0" class="flex flex-wrap gap-2">
                            <template x-for="chip in activeFilterChips" :key="chip.key">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-lg text-xs font-medium border border-primary-200 dark:border-primary-700">
                                    <span x-text="chip.label"></span>
                                    <button @click="removeFilter(chip)"
                                            class="hover:bg-primary-100 dark:hover:bg-primary-800 rounded-full p-0.5 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                        <!-- Scope Toggle -->
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                                Scope
                            </label>
                            <div class="flex gap-1">
                                <button
                                    @click="filterScope = 'page'"
                                    :class="filterScope === 'page' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="p-1.5 rounded-md transition"
                                    title="Current Page Only"
                                >
                                    <x-filament::icon icon="heroicon-o-document" class="h-4 w-4" />
                                </button>
                                <button
                                    @click="filterScope = 'all'"
                                    :class="filterScope === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="p-1.5 rounded-md transition"
                                    title="All Pages"
                                >
                                    <x-filament::icon icon="heroicon-o-document-duplicate" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Quick Filter Presets -->
                        <div>
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2 block">
                                Quick Filters
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    @click="applyPreset('myWork')"
                                    :class="isPresetActive('myWork') ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="p-3 rounded-md transition flex flex-col items-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-user" class="h-5 w-5" />
                                    <span class="text-xs font-medium">My Work</span>
                                </button>
                                <button
                                    @click="applyPreset('recent')"
                                    :class="isPresetActive('recent') ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="p-3 rounded-md transition flex flex-col items-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5" />
                                    <span class="text-xs font-medium">Recent</span>
                                </button>
                                <button
                                    @click="applyPreset('unlinked')"
                                    :class="isPresetActive('unlinked') ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="p-3 rounded-md transition flex flex-col items-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-link-slash" class="h-5 w-5" />
                                    <span class="text-xs font-medium">Unlinked</span>
                                </button>
                                <button
                                    @click="applyPreset('all')"
                                    :class="isPresetActive('all') ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="p-3 rounded-md transition flex flex-col items-center gap-2"
                                >
                                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
                                    <span class="text-xs font-medium">Show All</span>
                                </button>
                            </div>
                        </div>

                        <!-- Advanced Filters (Collapsible) -->
                        <div x-data="{ advancedExpanded: true }" class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <button
                                @click="advancedExpanded = !advancedExpanded"
                                class="flex items-center justify-between w-full mb-3 text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide hover:text-gray-900 dark:hover:text-white transition"
                            >
                                <span class="flex items-center gap-1.5">
                                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-3.5 w-3.5" />
                                    Advanced
                                </span>
                                <x-filament::icon
                                    x-bind:icon="advancedExpanded ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down'"
                                    class="h-3.5 w-3.5 transition-transform"
                                />
                            </button>

                            <div x-show="advancedExpanded" x-collapse class="space-y-5">
                                <!-- Type Filter -->
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2 block">
                                        <span>Type</span>
                                        <span x-show="availableTypes.length > 0" class="text-gray-500 dark:text-gray-500 font-normal ml-1" x-text="'(' + availableTypes.length + ')'"></span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 max-h-48 overflow-y-auto">
                                        <template x-for="type in availableTypes" :key="type">
                                            <label class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    :value="type"
                                                    x-model="filters.types"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-400"
                                                >
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="type"></span>
                                            </label>
                                        </template>
                                        <div x-show="availableTypes.length === 0" class="col-span-2 text-xs text-gray-500 dark:text-gray-400 italic p-2">
                                            No types available
                                        </div>
                                    </div>
                                </div>

                                <!-- Room Filter -->
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2 block">
                                        <span>Room</span>
                                        <span x-show="availableRooms.length > 0" class="text-gray-500 dark:text-gray-500 font-normal ml-1" x-text="'(' + availableRooms.length + ')'"></span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 max-h-48 overflow-y-auto">
                                        <template x-for="room in availableRooms" :key="room.id">
                                            <label class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    :value="room.id"
                                                    x-model="filters.rooms"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-400"
                                                >
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="room.name"></span>
                                            </label>
                                        </template>
                                        <div x-show="availableRooms.length === 0" class="col-span-2 text-xs text-gray-500 dark:text-gray-400 italic p-2">
                                            No rooms available
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Filter -->
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2 block">
                                        <span>Location</span>
                                        <span x-show="availableLocations.length > 0" class="text-gray-500 dark:text-gray-500 font-normal ml-1" x-text="'(' + availableLocations.length + ')'"></span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 max-h-48 overflow-y-auto">
                                        <template x-for="location in availableLocations" :key="location.id">
                                            <label class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    :value="location.id"
                                                    x-model="filters.locations"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-400"
                                                >
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="location.name"></span>
                                            </label>
                                        </template>
                                        <div x-show="availableLocations.length === 0" class="col-span-2 text-xs text-gray-500 dark:text-gray-400 italic p-2">
                                            No locations available
                                        </div>
                                    </div>
                                </div>

                                <!-- View Type Filter -->
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2 block">
                                        <span>View Type</span>
                                        <span x-show="availableViewTypes.length > 0" class="text-gray-500 dark:text-gray-500 font-normal ml-1" x-text="'(' + availableViewTypes.length + ')'"></span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 max-h-48 overflow-y-auto">
                                        <template x-for="viewType in availableViewTypes" :key="viewType">
                                            <label class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    :value="viewType"
                                                    x-model="filters.viewTypes"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-400"
                                                >
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="viewType"></span>
                                            </label>
                                        </template>
                                        <div x-show="availableViewTypes.length === 0" class="col-span-2 text-xs text-gray-500 dark:text-gray-400 italic p-2">
                                            No view types available
                                        </div>
                                    </div>
                                </div>

                                <!-- Vertical Zone Filter -->
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2 block">
                                        <span>Vertical Zone</span>
                                        <span x-show="availableVerticalZones.length > 0" class="text-gray-500 dark:text-gray-500 font-normal ml-1" x-text="'(' + availableVerticalZones.length + ')'"></span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 max-h-48 overflow-y-auto">
                                        <template x-for="zone in availableVerticalZones" :key="zone">
                                            <label class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    :value="zone"
                                                    x-model="filters.verticalZones"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-400"
                                                >
                                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="zone"></span>
                                            </label>
                                        </template>
                                        <div x-show="availableVerticalZones.length === 0" class="col-span-2 text-xs text-gray-500 dark:text-gray-400 italic p-2">
                                            No zones available
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                            <button
                                x-show="hasActiveFilters()"
                                @click="clearAllFilters()"
                                class="flex-1 px-3 py-1.5 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-center gap-1.5"
                                title="Clear all filters"
                            >
                                <x-filament::icon icon="heroicon-o-x-circle" class="h-3.5 w-3.5" />
                                <span>Clear</span>
                            </button>
                            <button
                                @click="showFilters = false"
                                class="px-3 py-1.5 rounded-md text-xs font-medium text-white transition-colors flex items-center justify-center gap-1.5"
                                :class="hasActiveFilters() ? 'flex-1' : 'w-full'"
                                style="background-color: var(--primary-600);"
                                @mouseover="$el.style.backgroundColor = 'var(--primary-700)'"
                                @mouseout="$el.style.backgroundColor = 'var(--primary-600)'"
                            >
                                <x-filament::icon icon="heroicon-o-check" class="h-3.5 w-3.5" />
                                <span>Done</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>

    {{-- Clear All Button - appears below Filter button when active --}}
    <div
        x-show="activeFiltersCount > 0"
        x-transition
        class="absolute -bottom-8 left-0 right-0 z-50"
    >
        <button
            @click="clearAllFilters()"
            class="w-full px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
            title="Clear all filters"
        >
            Clear All
        </button>
    </div>
</div>
