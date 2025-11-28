@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
    'totalPages' => 1,
    'pageType' => null,
    'pageMap' => [],
])

@php
    // Generate unique ID for this viewer instance
    $viewerId = 'overlayViewer_' . $pdfPageId . '_' . uniqid();
@endphp

<style>
    /* Prevent body-level scroll - force viewport-constrained layout */
    html, body {
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
    }

    /* Ensure FilamentPHP containers fill height properly */
    .fi-layout, .fi-main-ctn, .fi-main, .fi-page, .fi-page-content, .fi-page-main {
        height: 100% !important;
        max-height: 100% !important;
        overflow: hidden !important;
    }

    /* Constrain PDF viewer container with viewport-based max-height */
    .pdf-viewer-container {
        overflow: auto !important;
        max-height: calc(100vh - 280px) !important;
    }

    /* PDF container should just contain content */
    [id^="pdf-container"] {
        overflow: visible !important;
        height: auto !important;
    }
</style>

<div
    wire:ignore
    x-cloak
    x-data="annotationSystemV3({
        pdfUrl: '{{ $pdfUrl }}',
        pageNumber: {{ $pageNumber }},
        pdfPageId: {{ $pdfPageId ?? 'null' }},
        projectId: {{ $projectId }},
        totalPages: {{ $totalPages }},
        pageType: {{ $pageType ? "'" . $pageType . "'" : 'null' }},
        pageMap: {{ json_encode($pageMap) }}
    })"
    x-init="init()"
    class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900 overflow-hidden"
>
    <!-- Context Bar (Top - Fixed) -->
    <div class="context-bar flex-none z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-md">
        <div class="p-3 md:p-4 flex items-center gap-4 md:gap-6 flex-wrap">

            <!-- GROUP 1: Context Selection -->
            <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3 flex-1 min-w-fit">
                <!-- Room Autocomplete -->
                <div class="relative flex-1 max-w-xs min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Room</label>
                <input
                    type="text"
                    x-model="roomSearchQuery"
                    @input="searchRooms($event.target.value)"
                    @focus="showRoomDropdown = true"
                    @click.away="showRoomDropdown = false"
                    placeholder="Type to search or create..."
                    class="w-full px-3 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 ring-primary-600 shadow-sm"
                />

                <!-- Room Suggestions Dropdown -->
                <div
                    x-show="showRoomDropdown && roomSuggestions.length > 0"
                    class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                >
                    <template x-for="room in roomSuggestions" :key="room.id">
                        <div
                            @click="selectRoom(room)"
                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex items-center gap-2"
                        >
                            <span x-show="!room.isNew" class="text-green-600">✓</span>
                            <span x-show="room.isNew" class="text-blue-600 font-bold">+</span>
                            <span x-text="room.name" class="text-sm"></span>
                            <span x-show="!room.isNew" class="text-xs text-gray-500 ml-auto">Existing</span>
                            <span x-show="room.isNew" class="text-xs text-blue-600 ml-auto">Create New</span>
                        </div>
                    </template>
                </div>
            </div>

                <!-- Location Autocomplete -->
                <div class="relative flex-1 max-w-xs min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                    <input
                        type="text"
                        x-model="locationSearchQuery"
                        @input="searchLocations($event.target.value)"
                        @focus="showLocationDropdown = true"
                        @click.away="showLocationDropdown = false"
                        :disabled="!activeRoomId"
                        placeholder="Select room first..."
                        class="w-full px-3 py-2.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 ring-primary-600 disabled:opacity-40 disabled:cursor-not-allowed shadow-sm"
                    />

                <!-- Location Suggestions Dropdown -->
                <div
                    x-show="showLocationDropdown && locationSuggestions.length > 0"
                    class="absolute z-20 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                >
                    <template x-for="location in locationSuggestions" :key="location.id">
                        <div
                            @click="selectLocation(location)"
                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex items-center gap-2"
                        >
                            <span x-show="!location.isNew" class="text-green-600">✓</span>
                            <span x-show="location.isNew" class="text-blue-600 font-bold">+</span>
                            <span x-text="location.name" class="text-sm"></span>
                            <span x-show="!location.isNew" class="text-xs text-gray-500 ml-auto">Existing</span>
                            <span x-show="location.isNew" class="text-xs text-blue-600 ml-auto">Create New</span>
                        </div>
                    </template>
                </div>
                </div>
            </div>
            <!-- END GROUP 1: Context Selection -->

            <!-- GROUP 2: Navigation + Zoom -->
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Pagination Controls -->
                <div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
                    <button
                        @click="previousPage()"
                        :disabled="currentPage <= 1"
                        class="px-3 py-2 rounded-lg text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-md"
                        style="background-color: var(--primary-600);"
                        onmouseover="this.style.backgroundColor='var(--primary-700)'"
                        onmouseout="this.style.backgroundColor='var(--primary-600)'"
                        title="Previous Page"
                    >
                        <x-filament::icon icon="heroicon-o-chevron-left" class="h-4 w-4" />
                    </button>

                    <!-- Page number and type selector stacked vertically -->
                    <div class="flex flex-col gap-1.5 min-w-[8rem]">
                        <span class="text-sm text-gray-700 dark:text-white font-semibold text-center bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-lg" x-text="`Page ${currentPage} of ${totalPages}`"></span>

                        <!-- Page Type Selector - UNDER page number -->
                        <div class="relative">
                            <select
                                x-model="pageType"
                                @change="savePageType()"
                                class="w-full h-9 pl-3 pr-8 text-xs rounded-lg border-2 font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary-600"
                                :class="{
                                    'border-blue-300 bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-100 dark:border-blue-600': pageType === 'cover',
                                    'border-green-300 bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100 dark:border-green-600': pageType === 'floor_plan',
                                    'border-purple-300 bg-purple-50 text-purple-900 dark:bg-purple-900/20 dark:text-purple-100 dark:border-purple-600': pageType === 'elevation',
                                    'border-orange-300 bg-orange-50 text-orange-900 dark:bg-orange-900/20 dark:text-orange-100 dark:border-orange-600': pageType === 'detail',
                                    'border-gray-300 bg-gray-50 text-gray-900 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600': pageType === 'other',
                                    'border-gray-300 bg-white text-gray-500 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-600': !pageType
                                }"
                                title="Set page type for current page"
                            >
                                <option value="">Type...</option>
                                <option value="cover">📋 Cover</option>
                                <option value="floor_plan">🏗️ Floor</option>
                                <option value="elevation">📐 Elev</option>
                                <option value="detail">🔍 Detail</option>
                                <option value="other">📄 Other</option>
                            </select>

                            <!-- Page Type Badge (compact version) -->
                            <div x-show="pageType" class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs font-bold rounded-full shadow-sm pointer-events-none" :class="{
                                'bg-blue-500 text-white': pageType === 'cover',
                                'bg-green-500 text-white': pageType === 'floor_plan',
                                'bg-purple-500 text-white': pageType === 'elevation',
                                'bg-orange-500 text-white': pageType === 'detail',
                                'bg-gray-500 text-white': pageType === 'other'
                            }">
                                <span x-text="pageType === 'cover' ? 'C' : pageType === 'floor_plan' ? 'F' : pageType === 'elevation' ? 'E' : pageType === 'detail' ? 'D' : 'O'"></span>
                            </div>
                        </div>
                    </div>

                    <button
                        @click="nextPage()"
                        :disabled="currentPage >= totalPages"
                        class="px-3 py-2 rounded-lg text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-md"
                        style="background-color: var(--primary-600);"
                        onmouseover="this.style.backgroundColor='var(--primary-700)'"
                        onmouseout="this.style.backgroundColor='var(--primary-600)'"
                        title="Next Page"
                    >
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                    </button>
                </div>

                <!-- Zoom Controls -->
                <div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
                    <button
                        @click="zoomOut()"
                        :disabled="zoomLevel <= zoomMin"
                        class="px-3 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                        title="Zoom Out"
                    >
                        <x-filament::icon icon="heroicon-o-minus" class="h-4 w-4" />
                    </button>

                    <span class="text-sm text-gray-700 dark:text-white font-semibold text-center bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg min-w-[4rem]" x-text="`${getZoomPercentage()}%`"></span>

                    <button
                        @click="zoomIn()"
                        :disabled="zoomLevel >= zoomMax"
                        class="px-3 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105 hover:shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                        title="Zoom In"
                    >
                        <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                    </button>

                    <button
                        @click="resetZoom()"
                        class="px-3 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-white text-xs font-semibold transition-all hover:scale-105 hover:shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600"
                        title="Reset Zoom (100%)"
                    >
                        Reset
                    </button>
                </div>
            </div>
            <!-- END GROUP 2: Navigation + Zoom -->

            <!-- GROUP 3: Drawing Tools -->
            <div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
                <!-- Draw Room Boundary (no pre-selection required) -->
                <button
                    @click="setDrawMode('room')"
                    :class="drawMode === 'room' ? 'ring-2 ring-warning-500 shadow-lg transform scale-105' : ''"
                    :style="drawMode === 'room' ? 'background-color: var(--warning-600); color: white; border-color: var(--warning-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Room Boundary - Create new room"
                >
                    <x-filament::icon icon="heroicon-o-home" class="h-5 w-5" />
                </button>

                <!-- Draw Room Location (only requires Room) -->
                <button
                    @click="setDrawMode('location')"
                    :class="drawMode === 'location' ? 'ring-2 ring-info-500 shadow-lg transform scale-105' : ''"
                    :style="drawMode === 'location' ? 'background-color: var(--info-600); color: white; border-color: var(--info-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    :disabled="!canDrawLocation()"
                    class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Room Location (Room required)"
                >
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
                </button>

                <!-- Draw Cabinet Run (requires Room + Location) -->
                <button
                    @click="setDrawMode('cabinet_run')"
                    :class="drawMode === 'cabinet_run' ? 'ring-2 ring-primary-500 shadow-lg transform scale-105' : ''"
                    :style="drawMode === 'cabinet_run' ? 'background-color: var(--primary-600); color: white; border-color: var(--primary-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    :disabled="!canDraw()"
                    class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Cabinet Run (Room + Location required)"
                >
                    <x-filament::icon icon="heroicon-o-rectangle-group" class="h-5 w-5" />
                </button>

                <!-- Draw Cabinet (requires Room + Location) -->
                <button
                    @click="setDrawMode('cabinet')"
                    :class="drawMode === 'cabinet' ? 'ring-2 ring-success-500 shadow-lg transform scale-105' : ''"
                    :style="drawMode === 'cabinet' ? 'background-color: var(--success-600); color: white; border-color: var(--success-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    :disabled="!canDraw()"
                    class="px-3 py-2 rounded-lg hover:scale-105 hover:shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Cabinet (Room + Location required)"
                >
                    <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                </button>
            </div>
            <!-- END GROUP 3: Drawing Tools -->

            <!-- GROUP 4: View Type Toggle -->
            <div class="flex items-center gap-2 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3">
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">View:</span>

                <!-- Plan View -->
                <button
                    @click="setViewType('plan')"
                    :class="activeViewType === 'plan' ? 'ring-2 ring-primary-500 shadow-md transform scale-105' : ''"
                    :style="activeViewType === 'plan' ? 'background-color: var(--primary-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
                    title="Plan View (Top-Down)"
                >
                    Plan
                </button>

                <!-- Elevation View -->
                <button
                    @click="setViewType('elevation', 'front')"
                    :class="activeViewType === 'elevation' ? 'ring-2 ring-warning-500 shadow-md transform scale-105' : ''"
                    :style="activeViewType === 'elevation' ? 'background-color: var(--warning-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
                    title="Elevation View (Side)"
                >
                    Elevation
                </button>

                <!-- Section View -->
                <button
                    @click="setViewType('section', 'A-A')"
                    :class="activeViewType === 'section' ? 'ring-2 ring-info-500 shadow-md transform scale-105' : ''"
                    :style="activeViewType === 'section' ? 'background-color: var(--info-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
                    title="Section View (Cut-Through)"
                >
                    Section
                </button>

                <!-- Detail View -->
                <button
                    @click="setViewType('detail')"
                    :class="activeViewType === 'detail' ? 'ring-2 ring-success-500 shadow-md transform scale-105' : ''"
                    :style="activeViewType === 'detail' ? 'background-color: var(--success-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-3 py-2 rounded-lg text-sm font-semibold hover:scale-105 hover:shadow-sm transition-all dark:bg-gray-700 dark:text-white"
                    title="Detail View (Zoomed)"
                >
                    Detail
                </button>

                <!-- Orientation Selector (for Elevation/Section) -->
                <template x-if="activeViewType === 'elevation' || activeViewType === 'section'">
                    <select
                        x-model="activeOrientation"
                        @change="setOrientation(activeOrientation)"
                        class="px-3 py-2 rounded-lg text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-semibold focus:outline-none focus:ring-2 focus:ring-primary-600"
                    >
                        <template x-if="activeViewType === 'elevation'">
                            <optgroup label="Elevation">
                                <option value="front">Front</option>
                                <option value="back">Back</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </optgroup>
                        </template>
                        <template x-if="activeViewType === 'section'">
                            <optgroup label="Section">
                                <option value="A-A">A-A</option>
                                <option value="B-B">B-B</option>
                                <option value="C-C">C-C</option>
                            </optgroup>
                        </template>
                    </select>
                </template>
            </div>
            <!-- END GROUP 4: View Type Toggle -->

            <!-- GROUP 5: Actions -->
            <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-gray-800/30 rounded-lg p-3 ml-auto">
                <!-- Filter Button (NEW) - wrapped in relative container -->
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

                <!-- Filter Status Indicator -->
                <div
                    x-show="activeFiltersCount > 0"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium"
                    style="background-color: var(--primary-50); color: var(--primary-700);"
                >
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
                    <span>
                        Showing <strong x-text="filteredAnnotations.length"></strong> of <strong x-text="annotations.length"></strong>
                    </span>
                </div>

                <button
                    @click="clearContext()"
                    class="px-3 py-2 rounded-lg text-white hover:scale-105 hover:shadow-md transition-all text-sm font-semibold flex items-center gap-2"
                    style="background-color: var(--danger-600);"
                    onmouseover="this.style.backgroundColor='var(--danger-700)'"
                    onmouseout="this.style.backgroundColor='var(--danger-600)'"
                    title="Clear Context"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    Clear
                </button>

                <button
                    @click="saveAnnotations()"
                    class="px-4 py-2.5 rounded-lg text-white hover:scale-105 hover:shadow-lg transition-all text-sm font-bold shadow-md flex items-center gap-2 ring-2 ring-success-500/50"
                    style="background-color: var(--success-600);"
                    onmouseover="this.style.backgroundColor='var(--success-700)'"
                    onmouseout="this.style.backgroundColor='var(--success-600)'"
                    title="Save All Annotations"
                >
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
                    Save
                </button>

                <button
                    @click="$dispatch('close-v3-modal')"
                    class="px-2.5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 hover:scale-105 transition-all text-sm font-semibold flex items-center justify-center"
                    title="Close Viewer"
                >
                    <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5" />
                </button>
            </div>
            <!-- END GROUP 5: Actions -->
        </div>

        <!-- Context Hint -->
        <div x-show="!canDrawLocation()" class="mt-3 flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-lg border" style="background-color: var(--warning-50); border-color: var(--warning-200); color: var(--warning-700);">
            <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4" />
            <span>Select a Room to draw Locations, or Room + Location to draw Cabinet Runs/Cabinets</span>
        </div>

        <!-- PDF Loading Status -->
        <div x-show="!pdfReady" class="mt-3 flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-lg border" style="background-color: var(--info-50); border-color: var(--info-200); color: var(--info-700);">
            <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 animate-spin" />
            <span>Loading PDF dimensions...</span>
        </div>
    </div>

    <!-- Isolation Mode Breadcrumb (NEW - Illustrator-style) -->
    <div x-show="isolationMode" x-transition class="isolation-breadcrumb bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 border-b-4 border-primary-500 px-6 py-4 shadow-lg" style="position: relative; z-index: 15;">
        <div class="flex items-center gap-4">
            <!-- Lock Icon + Label -->
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                <span class="text-sm font-bold text-primary-900 dark:text-primary-100 uppercase tracking-wide">
                    Isolation Mode
                </span>
            </div>

            <!-- Breadcrumb Path (using computed property) -->
            <div class="flex items-center gap-2 text-sm font-semibold text-primary-800 dark:text-primary-200">
                <template x-for="(crumb, index) in isolationBreadcrumbs" :key="crumb.level">
                    <div class="flex items-center gap-2">
                        <!-- Chevron separator (skip for first item) -->
                        <template x-if="index > 0">
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 text-primary-600" />
                        </template>

                        <!-- Breadcrumb item -->
                        <span class="flex items-center gap-1.5">
                            <span class="text-lg" x-text="crumb.icon"></span>
                            <span x-text="crumb.label"></span>
                        </span>
                    </div>
                </template>
            </div>

            <!-- Exit Button -->
            <button
                @click="exitIsolationMode()"
                class="ml-auto px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-semibold shadow-md transition-all flex items-center gap-2"
                title="Exit Isolation Mode (Esc)"
            >
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                <span>Exit Isolation</span>
            </button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content-area flex flex-1 min-h-0 overflow-hidden">
        @include('webkul-project::components.pdf.tree.sidebar')

        <!-- Context Menu Overlay (Global) -->
        <div
            x-show="contextMenu.show"
            @click.away="contextMenu.show = false"
            :style="`position: fixed; top: ${contextMenu.y}px; left: ${contextMenu.x}px; z-index: 9999;`"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[180px]"
        >
            <button
                @click="deleteTreeNode()"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 text-sm"
                style="color: var(--danger-600);"
            >
                <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                <span>Delete <span x-text="contextMenu.nodeType === 'room' ? 'Room' : contextMenu.nodeType === 'room_location' ? 'Location' : 'Cabinet Run'"></span></span>
            </button>
        </div>

        <!-- PDF Viewer (Center) with HTML Overlay -->
        <div class="pdf-viewer-container flex flex-col flex-1 min-h-0 bg-white dark:bg-gray-900 overflow-hidden relative">
            <!-- Skeleton Loading Overlay -->
            <div
                x-show="!systemReady"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-white dark:bg-gray-900 z-50 flex items-center justify-center"
                @touchmove.prevent
                @wheel.prevent
            >
                <div class="w-full h-full flex flex-col">
                    <!-- Skeleton Header Bar -->
                    <div class="h-16 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 animate-pulse"></div>

                    <!-- Skeleton Content Area -->
                    <div class="flex-1 flex items-center justify-center p-8">
                        <div class="max-w-md w-full space-y-6">
                            <!-- Loading Icon -->
                            <div class="flex justify-center">
                                <svg class="animate-spin h-16 w-16 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <!-- Loading Text -->
                            <div class="text-center space-y-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Loading PDF Viewer</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Preparing your document and annotations...</p>
                            </div>

                            <!-- Skeleton PDF Preview -->
                            <div class="space-y-3">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-4/6"></div>
                                <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mt-4"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/6"></div>
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-5/6"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            <div
                x-show="systemReady && error"
                class="absolute inset-0 bg-white dark:bg-gray-900 z-40 flex items-center justify-center p-8"
            >
                <div class="max-w-md w-full space-y-6 text-center">
                    <!-- Error Icon -->
                    <div class="flex justify-center">
                        <svg class="h-16 w-16 text-danger-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <!-- Error Message -->
                    <div class="space-y-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Failed to Load PDF Viewer</h3>
                        <p class="text-sm text-danger-600 dark:text-danger-400" x-text="error"></p>
                    </div>

                    <!-- Help Text -->
                    <div class="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                        <p>This error may occur if:</p>
                        <ul class="list-disc list-inside text-left space-y-1">
                            <li>The PDF file is missing or corrupted</li>
                            <li>Your browser blocked the PDF due to security settings</li>
                            <li>There's a network connectivity issue</li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 justify-center">
                        <button
                            @click="window.location.reload()"
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                        >
                            Reload Page
                        </button>
                        <a
                            href="{{ url()->previous() }}"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-colors"
                        >
                            Go Back
                        </a>
                    </div>
                </div>
            </div>

            <!-- PDF Container -->
            <div id="pdf-container-{{ $viewerId }}" class="relative w-full flex-1 min-h-0 overflow-auto"
                :class="{ 'overflow-hidden': !systemReady }"
            >
                <!-- PDFObject.js embed goes here -->
                <div x-ref="pdfEmbed" class="w-full h-full min-h-full"></div>

                <!-- Current View Badge - Fixed Position Top-Left -->
                <div class="absolute top-4 left-4 z-50 pointer-events-none">
                    <div
                        class="px-4 py-2 rounded-lg shadow-lg text-white font-bold text-sm flex items-center gap-2"
                        :style="{ backgroundColor: getCurrentViewColor() }"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-text="getCurrentViewLabel()"></span>
                    </div>
                </div>

                <!-- Isolation Mode Blur - Positioned exactly like annotation overlay -->
                <div
                    x-show="isolationMode"
                    x-cloak
                    x-ref="isolationBlur"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute top-0 left-0 pointer-events-none"
                    style="z-index: 1; display: none;"
                    :style="`width: ${overlayWidth}; height: ${overlayHeight}; display: ${isolationMode ? 'block' : 'none'};`"
                >
                    <!-- SVG for blur with proper masking -->
                    <!-- Only render viewBox when we have pixel dimensions (not '100%') -->
                    <svg
                        x-show="overlayWidth.includes('px')"
                        xmlns="http://www.w3.org/2000/svg"
                        preserveAspectRatio="none"
                        style="display: block; width: 100%; height: 100%;"
                        :viewBox="`0 0 ${overlayWidth.replace('px', '')} ${overlayHeight.replace('px', '')}`"
                    >
                        <defs>
                            <!-- Blur filter for background -->
                            <filter id="blur">
                                <feGaussianBlur in="SourceGraphic" stdDeviation="4"/>
                            </filter>

                            <!-- Feather filter for soft mask edges -->
                            <filter id="feather">
                                <feGaussianBlur in="SourceGraphic" stdDeviation="15"/>
                            </filter>

                            <!-- Mask: white = show blur, black = hide blur -->
                            <mask id="blurMask">
                                <!-- White everywhere = show blur everywhere -->
                                <!-- Dynamically sized to match canvas -->
                                <rect
                                    x="0"
                                    y="0"
                                    :width="overlayWidth.replace('px', '')"
                                    :height="overlayHeight.replace('px', '')"
                                    fill="white"
                                />

                                <!-- Black rectangle at selected annotation and its visible children = hide blur there -->
                                <!-- This excludes the focused area from the darkening blur in isolation mode -->
                                <g id="maskRects"></g>
                            </mask>
                        </defs>

                        <!-- Dark overlay with blur, masked to exclude annotation -->
                        <!-- Dynamically sized to match canvas -->
                        <rect
                            x="0"
                            y="0"
                            :width="overlayWidth.replace('px', '')"
                            :height="overlayHeight.replace('px', '')"
                            fill="rgba(0, 0, 0, 0.65)"
                            filter="url(#blur)"
                            mask="url(#blurMask)"
                        />
                    </svg>
                </div>

                <!-- Annotation Overlay (HTML Elements) -->
                <div
                    x-ref="annotationOverlay"
                    @click="activeAnnotationId = null; selectedAnnotation = null;"
                    @mousedown="startDrawing($event)"
                    @mousemove="if (isResizing) window.PdfViewerManagers?.ResizeMoveSystem?.handleResizeMove($event, $data); else if (isMoving) window.PdfViewerManagers?.ResizeMoveSystem?.handleMoveUpdate($event, $data); else updateDrawing($event);"
                    @mouseup="if (isResizing) window.PdfViewerManagers?.ResizeMoveSystem?.handleResizeEnd($event, $data, $refs); else if (isMoving) window.PdfViewerManagers?.ResizeMoveSystem?.handleMoveEnd($event, $data, $refs); else finishDrawing($event);"
                    @mouseleave="if (isResizing) window.PdfViewerManagers?.ResizeMoveSystem?.handleResizeEnd($event, $data, $refs); else if (isMoving) window.PdfViewerManagers?.ResizeMoveSystem?.handleMoveEnd($event, $data, $refs); else cancelDrawing($event);"
                    :class="(drawMode && !editorModalOpen) ? 'pointer-events-auto cursor-crosshair' : 'pointer-events-none'"
                    class="annotation-overlay absolute top-0 left-0"
                    :style="`z-index: 10; will-change: width, height; width: ${overlayWidth}; height: ${overlayHeight};`"
                >
                    <!-- Existing Annotations -->
                    <template x-for="anno in filteredAnnotations.filter(a => !hiddenAnnotations.includes(a.id) && isAnnotationVisibleInView(a) && isAnnotationVisibleInIsolation(a))" :key="anno.id">
                        <!-- Wrapper div to hide frame of isolated object itself (you "jumped into it") -->
                        <div x-show="!isolationMode || (isolationLevel === 'room' && anno.id !== isolatedRoomId) || (isolationLevel === 'location' && anno.id !== isolatedLocationId) || (isolationLevel === 'cabinet_run' && anno.id !== isolatedCabinetRunId)">
                            <div
                                x-data="{ showMenu: false }"
                                :style="`
                                    position: absolute;
                                    transform: translate(${anno.screenX}px, ${anno.screenY}px);
                                    width: ${anno.screenWidth}px;
                                    height: ${anno.screenHeight}px;
                                    border: ${activeAnnotationId === anno.id ? '3px' : '2px'} solid ${anno.color};
                                    background: ${anno.color}33;
                                    border-radius: 4px;
                                    pointer-events: ${anno.locked ? 'none' : 'auto'};
                                    cursor: ${anno.locked ? 'not-allowed' : (isMoving && activeAnnotationId === anno.id ? 'grabbing' : 'grab')};
                                    z-index: ${window.PdfViewerManagers?.AnnotationManager?.getAnnotationZIndex(anno, this) || 10};
                                    transition: ${(isResizing || isMoving) && activeAnnotationId === anno.id ? 'none' : 'all 0.2s'};
                                    will-change: transform;
                                    opacity: ${anno.locked ? 0.7 : 1};
                                    box-shadow: ${activeAnnotationId === anno.id ? '0 0 0 2px rgba(59, 130, 246, 0.3)' : 'none'};
                                `"
                                @click.stop="!anno.locked && handleNodeClick(anno)"
                                @dblclick.prevent.stop="!anno.locked && handleAnnotationDoubleClick(anno)"
                                @mousedown="!anno.locked && startMove($event, anno)"
                                @mouseenter="$el.style.background = anno.color + '66'; showMenu = true"
                                @mouseleave="$el.style.background = anno.color + '33'; showMenu = anno.locked"
                                class="annotation-marker group"
                            >
                            <!-- Annotation Label - Bottom Left -->
                            <div class="annotation-label absolute -bottom-7 left-0 bg-white dark:bg-gray-900 px-2 py-1 rounded text-xs font-medium whitespace-nowrap shadow-md border z-30" style="color: var(--gray-900); border-color: var(--primary-400); pointer-events: none;">
                                <span x-text="anno.label" class="dark:text-white"></span>
                            </div>

                            <!-- Edit/Lock/Delete Buttons (visible on hover or if locked) - Top Right Corner -->
                            <div
                                x-show="showMenu || anno.locked"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute -top-7 -right-2 flex gap-1 z-30 bg-white dark:bg-gray-900 px-2 py-1 rounded shadow-md border border-gray-300 dark:border-gray-600"
                                @click.stop
                            >
                                <!-- Edit Button -->
                                <x-filament::icon-button
                                    icon="heroicon-o-pencil"
                                    @click="editAnnotation(anno)"
                                    tooltip="Edit annotation"
                                    size="sm"
                                    color="primary"
                                />

                                <!-- Lock Button - Unlocked State -->
                                <span x-show="!anno.locked">
                                    <x-filament::icon-button
                                        icon="heroicon-o-lock-open"
                                        @click="window.PdfViewerManagers?.AnnotationManager?.toggleLockAnnotation(anno, $data)"
                                        tooltip="Lock annotation"
                                        size="sm"
                                        color="info"
                                    />
                                </span>

                                <!-- Lock Button - Locked State -->
                                <span x-show="anno.locked">
                                    <x-filament::icon-button
                                        icon="heroicon-s-lock-closed"
                                        @click="window.PdfViewerManagers?.AnnotationManager?.toggleLockAnnotation(anno, $data)"
                                        tooltip="Unlock annotation"
                                        size="sm"
                                        color="warning"
                                    />
                                </span>

                                <!-- Delete Button -->
                                <x-filament::icon-button
                                    icon="heroicon-o-x-mark"
                                    @click="deleteAnnotation(anno)"
                                    tooltip="Delete annotation"
                                    size="sm"
                                    color="danger"
                                />
                            </div>

                            <!-- Corner Resize Handles -->
                            <!-- Top-Left Corner -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'nw')"
                                :style="`
                                    position: absolute;
                                    top: -4px;
                                    left: -4px;
                                    width: 12px;
                                    height: 12px;
                                    background: ${anno.color};
                                    border: 2px solid white;
                                    border-radius: 50%;
                                    cursor: nw-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle"
                            ></div>

                            <!-- Top-Right Corner -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'ne')"
                                :style="`
                                    position: absolute;
                                    top: -4px;
                                    right: -4px;
                                    width: 12px;
                                    height: 12px;
                                    background: ${anno.color};
                                    border: 2px solid white;
                                    border-radius: 50%;
                                    cursor: ne-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle"
                            ></div>

                            <!-- Bottom-Left Corner -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'sw')"
                                :style="`
                                    position: absolute;
                                    bottom: -4px;
                                    left: -4px;
                                    width: 12px;
                                    height: 12px;
                                    background: ${anno.color};
                                    border: 2px solid white;
                                    border-radius: 50%;
                                    cursor: sw-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle"
                            ></div>

                            <!-- Bottom-Right Corner -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'se')"
                                :style="`
                                    position: absolute;
                                    bottom: -4px;
                                    right: -4px;
                                    width: 12px;
                                    height: 12px;
                                    background: ${anno.color};
                                    border: 2px solid white;
                                    border-radius: 50%;
                                    cursor: se-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 1 : 0};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle"
                            ></div>

                            <!-- Edge Resize Handles (Invisible hit areas) -->
                            <!-- Top Edge -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'n')"
                                :style="`
                                    position: absolute;
                                    top: -4px;
                                    left: 12px;
                                    right: 12px;
                                    height: 8px;
                                    cursor: n-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                                    background: ${anno.color};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle-edge"
                            ></div>

                            <!-- Right Edge -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'e')"
                                :style="`
                                    position: absolute;
                                    top: 12px;
                                    bottom: 12px;
                                    right: -4px;
                                    width: 8px;
                                    cursor: e-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                                    background: ${anno.color};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle-edge"
                            ></div>

                            <!-- Bottom Edge -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 's')"
                                :style="`
                                    position: absolute;
                                    bottom: -4px;
                                    left: 12px;
                                    right: 12px;
                                    height: 8px;
                                    cursor: s-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                                    background: ${anno.color};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle-edge"
                            ></div>

                            <!-- Left Edge -->
                            <div
                                @mousedown.prevent.stop="startResize($event, anno, 'w')"
                                :style="`
                                    position: absolute;
                                    top: 12px;
                                    bottom: 12px;
                                    left: -4px;
                                    width: 8px;
                                    cursor: w-resize;
                                    pointer-events: auto;
                                    z-index: 200;
                                    opacity: ${showMenu || activeAnnotationId === anno.id ? 0.3 : 0};
                                    background: ${anno.color};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle-edge"
                            ></div>

                        </div>
                        </div><!-- End wrapper div for hiding frames in isolation mode -->
                    </template>

                    <!-- Drawing Preview (Current Rectangle Being Drawn) -->
                    <template x-if="isDrawing && drawPreview">
                        <div
                            :style="`
                                position: absolute;
                                left: ${drawPreview.x}px;
                                top: ${drawPreview.y}px;
                                width: ${drawPreview.width}px;
                                height: ${drawPreview.height}px;
                                border: 2px dashed ${getDrawColor()};
                                background: ${getDrawColor()}22;
                                pointer-events: none;
                            `"
                        ></div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Filament Annotation Editor Component --}}
    @livewire('annotation-editor')

    {{-- Hierarchy Builder Modal Component --}}
    @livewire('hierarchy-builder-modal')


@once
    <!-- Load PDFObject.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.3.0/pdfobject.min.js"></script>

    <!-- PDF.js is loaded via annotations.js Vite bundle (pdfjs-dist v5.4.296) -->
    <!-- The bundled version is already configured with worker and exported to window.pdfjsLib -->

@endonce
