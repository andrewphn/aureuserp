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

<div
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
    wire:ignore
    class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900"
>
    <!-- Context Bar (Top - Sticky) -->
    <div class="context-bar sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4 shadow-md">
        <div class="flex items-center gap-4 flex-wrap">
            <!-- V3 Header Title -->
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" style="color: var(--primary-600);" />
                    PDF Annotations
                    <span class="text-gray-600 dark:text-gray-400" x-text="`Page ${currentPage}`">Page {{ $pageNumber }}</span>
                    <span class="px-2 py-1 text-xs rounded-md font-semibold" style="background-color: var(--primary-50); color: var(--primary-700);">Page-by-Page</span>
                </h2>
            </div>

            <!-- Project Context Display -->
            <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                <x-filament::icon icon="heroicon-o-map-pin" class="h-4 w-4" style="color: var(--warning-600);" />
                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="getContextLabel()"></span>
            </div>

            <!-- Room Autocomplete -->
            <div class="relative flex-1 max-w-xs">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Room</label>
                <input
                    type="text"
                    x-model="roomSearchQuery"
                    @input="searchRooms($event.target.value)"
                    @focus="showRoomDropdown = true"
                    @click.away="showRoomDropdown = false"
                    placeholder="Type to search or create..."
                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 ring-primary-600"
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
            <div class="relative flex-1 max-w-xs">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                <input
                    type="text"
                    x-model="locationSearchQuery"
                    @input="searchLocations($event.target.value)"
                    @focus="showLocationDropdown = true"
                    @click.away="showLocationDropdown = false"
                    :disabled="!activeRoomId"
                    placeholder="Select room first..."
                    class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 ring-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
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

            <!-- Zoom Controls -->
            <div class="flex items-center gap-2 border-r border-gray-200 dark:border-gray-600 pr-4">
                <button
                    @click="zoomOut()"
                    :disabled="zoomLevel <= zoomMin"
                    class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Zoom Out (min: 100% fit to frame)"
                >
                    <x-filament::icon icon="heroicon-o-magnifying-glass-minus" class="h-4 w-4" />
                </button>
                <span class="text-sm text-gray-700 dark:text-white font-semibold min-w-[4rem] text-center bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg" x-text="`${getZoomPercentage()}%`"></span>
                <button
                    @click="zoomIn()"
                    :disabled="zoomLevel >= zoomMax"
                    class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Zoom In (max: 300%)"
                >
                    <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-4 w-4" />
                </button>
                <button
                    @click="resetZoom()"
                    class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium"
                    title="Reset Zoom (100% - fit to frame)"
                >
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />
                </button>
            </div>

            <!-- Pagination Controls with Page Type Selector (Phase 2 + Phase 3.1) -->
            <div class="flex items-center gap-2 border-r border-gray-200 dark:border-gray-600 pr-4">
                <button
                    @click="previousPage()"
                    :disabled="currentPage <= 1"
                    class="px-3 py-2 rounded-lg text-white text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
                    class="px-3 py-2 rounded-lg text-white text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background-color: var(--primary-600);"
                    onmouseover="this.style.backgroundColor='var(--primary-700)'"
                    onmouseout="this.style.backgroundColor='var(--primary-600)'"
                    title="Next Page"
                >
                    <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                </button>
            </div>

            <!-- Draw Mode Buttons (Icon-Only) -->
            <div class="flex items-center gap-2">
                <!-- Draw Room Boundary (no pre-selection required) -->
                <button
                    @click="setDrawMode('room')"
                    :class="drawMode === 'room' ? 'ring-2 shadow-lg' : ''"
                    :style="drawMode === 'room' ? 'background-color: var(--warning-600); color: white; border-color: var(--warning-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-3 py-2 rounded-lg hover:opacity-90 transition-all flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Room Boundary - Create new room"
                >
                    <x-filament::icon icon="heroicon-o-home" class="h-5 w-5" />
                </button>

                <!-- Draw Room Location (only requires Room) -->
                <button
                    @click="setDrawMode('location')"
                    :class="drawMode === 'location' ? 'ring-2 shadow-lg' : ''"
                    :style="drawMode === 'location' ? 'background-color: var(--info-600); color: white; border-color: var(--info-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    :disabled="!canDrawLocation()"
                    class="px-3 py-2 rounded-lg hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Room Location (Room required)"
                >
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
                </button>

                <!-- Draw Cabinet Run (requires Room + Location) -->
                <button
                    @click="setDrawMode('cabinet_run')"
                    :class="drawMode === 'cabinet_run' ? 'ring-2 shadow-lg' : ''"
                    :style="drawMode === 'cabinet_run' ? 'background-color: var(--primary-600); color: white; border-color: var(--primary-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    :disabled="!canDraw()"
                    class="px-3 py-2 rounded-lg hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Cabinet Run (Room + Location required)"
                >
                    <x-filament::icon icon="heroicon-o-rectangle-group" class="h-5 w-5" />
                </button>

                <!-- Draw Cabinet (requires Room + Location) -->
                <button
                    @click="setDrawMode('cabinet')"
                    :class="drawMode === 'cabinet' ? 'ring-2 shadow-lg' : ''"
                    :style="drawMode === 'cabinet' ? 'background-color: var(--success-600); color: white; border-color: var(--success-400);' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    :disabled="!canDraw()"
                    class="px-3 py-2 rounded-lg hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center border dark:bg-gray-700 dark:text-white"
                    title="Draw Cabinet (Room + Location required)"
                >
                    <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                </button>
            </div>

            <!-- View Type Toggle (Plan, Elevation, Section, Detail) -->
            <div class="flex items-center gap-2 border-l pl-3 dark:border-gray-600">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">View:</span>

                <!-- Plan View -->
                <button
                    @click="setViewType('plan')"
                    :class="activeViewType === 'plan' ? 'ring-2 shadow-md' : ''"
                    :style="activeViewType === 'plan' ? 'background-color: var(--primary-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-2 py-1 rounded text-xs font-semibold hover:opacity-90 transition-all dark:bg-gray-700 dark:text-white"
                    title="Plan View (Top-Down)"
                >
                    Plan
                </button>

                <!-- Elevation View -->
                <button
                    @click="setViewType('elevation', 'front')"
                    :class="activeViewType === 'elevation' ? 'ring-2 shadow-md' : ''"
                    :style="activeViewType === 'elevation' ? 'background-color: var(--warning-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-2 py-1 rounded text-xs font-semibold hover:opacity-90 transition-all dark:bg-gray-700 dark:text-white"
                    title="Elevation View (Side)"
                >
                    Elevation
                </button>

                <!-- Section View -->
                <button
                    @click="setViewType('section', 'A-A')"
                    :class="activeViewType === 'section' ? 'ring-2 shadow-md' : ''"
                    :style="activeViewType === 'section' ? 'background-color: var(--info-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-2 py-1 rounded text-xs font-semibold hover:opacity-90 transition-all dark:bg-gray-700 dark:text-white"
                    title="Section View (Cut-Through)"
                >
                    Section
                </button>

                <!-- Detail View -->
                <button
                    @click="setViewType('detail')"
                    :class="activeViewType === 'detail' ? 'ring-2 shadow-md' : ''"
                    :style="activeViewType === 'detail' ? 'background-color: var(--success-600); color: white;' : 'background-color: var(--gray-100); color: var(--gray-700);'"
                    class="px-2 py-1 rounded text-xs font-semibold hover:opacity-90 transition-all dark:bg-gray-700 dark:text-white"
                    title="Detail View (Zoomed)"
                >
                    Detail
                </button>

                <!-- Orientation Selector (for Elevation/Section) -->
                <template x-if="activeViewType === 'elevation' || activeViewType === 'section'">
                    <select
                        x-model="activeOrientation"
                        @change="setOrientation(activeOrientation)"
                        class="px-2 py-1 rounded text-xs border dark:bg-gray-700 dark:text-white dark:border-gray-600"
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

            <!-- Draw Mode Actions -->
            <div class="flex items-center gap-2 border-l pl-3 dark:border-gray-600">
                <button
                    @click="clearContext()"
                    class="px-3 py-2 rounded-lg text-white hover:opacity-90 transition-all text-sm font-semibold flex items-center gap-2"
                    style="background-color: var(--danger-600);"
                    title="Clear Context"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    Clear
                </button>

                <button
                    @click="saveAnnotations()"
                    class="px-3 py-2 rounded-lg text-white hover:opacity-90 transition-all text-sm font-semibold shadow-md flex items-center gap-2"
                    style="background-color: var(--success-600);"
                    title="Save All Annotations"
                >
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                    Save
                </button>

                <button
                    @click="$dispatch('close-v3-modal')"
                    class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-sm font-semibold flex items-center gap-2"
                    title="Close Viewer"
                >
                    <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4" />
                </button>
            </div>
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

            <!-- Breadcrumb Path -->
            <div class="flex items-center gap-2 text-sm font-semibold text-primary-800 dark:text-primary-200">
                <!-- Room Level -->
                <span class="flex items-center gap-1.5">
                    <span class="text-lg">🏠</span>
                    <span x-text="isolatedRoomName"></span>
                </span>

                <!-- Location Level (if in location isolation) -->
                <template x-if="isolationLevel === 'location'">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 text-primary-600" />
                        <span class="flex items-center gap-1.5">
                            <span class="text-lg">📍</span>
                            <span x-text="isolatedLocationName"></span>
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
    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar (Project Tree) -->
        <div class="tree-sidebar w-64 border-r border-gray-200 dark:border-gray-700 overflow-y-auto bg-white dark:bg-gray-800 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Project Structure</h3>

                <div class="flex items-center gap-1">
                    <!-- View Mode Toggle - Compact -->
                    <div class="flex gap-0.5 p-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                        <button
                            @click="treeViewMode = 'room'"
                            :class="treeViewMode === 'room' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="p-1 rounded transition-all"
                            :style="treeViewMode === 'room' ? 'color: var(--primary-600);' : 'color: var(--gray-500);'"
                            title="Group by Room"
                        >
                            <x-filament::icon icon="heroicon-o-home" class="h-3.5 w-3.5" />
                        </button>
                        <button
                            @click="treeViewMode = 'page'"
                            :class="treeViewMode === 'page' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="p-1 rounded transition-all"
                            :style="treeViewMode === 'page' ? 'color: var(--primary-600);' : 'color: var(--gray-500);'"
                            title="Group by Page"
                        >
                            <x-filament::icon icon="heroicon-o-document-text" class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <button
                        @click="refreshTree()"
                        class="p-1 hover:opacity-80 transition-opacity"
                        style="color: var(--primary-600);"
                        title="Refresh tree"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-path" class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="text-center py-4">
                <span class="text-sm text-gray-500">Loading...</span>
            </div>

            <!-- Error State -->
            <div x-show="error" class="text-center py-4">
                <span class="text-sm text-red-600" x-text="error"></span>
            </div>

            <!-- Tree Content - Room View -->
            <div x-show="!loading && !error && tree && treeViewMode === 'room'">
                <template x-for="room in tree" :key="room.id">
                    <div class="tree-node mb-2">
                        <!-- Room Level -->
                        <div
                            @click="selectNode(room.id, 'room', room.name)"
                            @dblclick.prevent.stop="enterIsolationMode({ type: 'room', id: room.id, label: room.name })"
                            @contextmenu.prevent.stop="showContextMenu($event, room.id, 'room', room.name)"
                            :class="selectedPath.includes(room.id) ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                        >
                            <button
                                @click.stop="toggleNode(room.id)"
                                class="w-4 h-4 flex items-center justify-center"
                            >
                                <span x-show="isExpanded(room.id)">▼</span>
                                <span x-show="!isExpanded(room.id)">▶</span>
                            </button>
                            <span class="text-lg">🏠</span>
                            <span class="text-sm font-medium flex-1" x-text="room.name"></span>
                            <span
                                x-show="room.annotation_count > 0"
                                class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                x-text="room.annotation_count"
                            ></span>
                        </div>

                        <!-- Locations (Children) -->
                        <div x-show="isExpanded(room.id)" class="tree-hierarchy-indent">
                            <template x-for="location in room.children" :key="location.id">
                                <div class="tree-node mb-1">
                                    <!-- Location Level -->
                                    <div
                                        @click="selectNode(location.id, 'room_location', location.name, room.id)"
                                        @dblclick.prevent.stop="enterIsolationMode({ type: 'location', id: location.id, label: location.name, roomId: room.id, roomName: room.name })"
                                        @contextmenu.prevent.stop="showContextMenu($event, location.id, 'room_location', location.name, room.id)"
                                        :class="selectedPath.includes(location.id) ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                                    >
                                        <button
                                            @click.stop="toggleNode(location.id)"
                                            class="w-4 h-4 flex items-center justify-center"
                                        >
                                            <span x-show="isExpanded(location.id)">▼</span>
                                            <span x-show="!isExpanded(location.id)">▶</span>
                                        </button>
                                        <span class="text-lg">📍</span>
                                        <span class="text-sm flex-1" x-text="location.name"></span>
                                        <span
                                            x-show="location.annotation_count > 0"
                                            class="badge bg-indigo-600 text-white px-2 py-0.5 rounded-full text-xs"
                                            x-text="location.annotation_count"
                                        ></span>
                                    </div>

                                    <!-- Cabinet Runs (Children) -->
                                    <div x-show="isExpanded(location.id)" class="tree-hierarchy-indent">
                                        <template x-for="run in location.children" :key="run.id">
                                            <div class="tree-node mb-1">
                                                <!-- Cabinet Run Level -->
                                                <div
                                                    @click="selectNode(run.id, 'cabinet_run', run.name, room.id, location.id)"
                                                    @dblclick.prevent.stop="enterIsolationMode({ type: 'cabinet_run', id: run.id, label: run.name, locationId: location.id, locationName: location.name, roomId: room.id, roomName: room.name })"
                                                    @contextmenu.prevent.stop="showContextMenu($event, run.id, 'cabinet_run', run.name, room.id, location.id)"
                                                    :class="selectedPath.includes(run.id) ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                    class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                >
                                                    <span class="text-base">📦</span>
                                                    <span class="flex-1" x-text="run.name"></span>
                                                    <span
                                                        x-show="run.annotation_count > 0"
                                                        class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                                        x-text="run.annotation_count"
                                                    ></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Add Room Button -->
                <button
                    @click="roomSearchQuery = ''; showRoomDropdown = true; $nextTick(() => $el.nextElementSibling.querySelector('input')?.focus())"
                    class="w-full mt-4 px-3 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                >
                    + Add Room
                </button>
            </div>

            <!-- Tree Content - Page View -->
            <div x-show="!loading && !error && treeViewMode === 'page'">
                <template x-for="page in getPageGroupedAnnotations()" :key="page.pageNumber">
                    <div class="tree-node mb-2">
                        <!-- Page Level -->
                        <div
                            @click="goToPage(page.pageNumber)"
                            :class="currentPage === page.pageNumber ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                        >
                            <button
                                @click.stop="toggleNode('page_' + page.pageNumber)"
                                class="w-4 h-4 flex items-center justify-center"
                            >
                                <span x-show="isExpanded('page_' + page.pageNumber)">▼</span>
                                <span x-show="!isExpanded('page_' + page.pageNumber)">▶</span>
                            </button>
                            <span class="text-lg">📄</span>
                            <span class="text-sm font-medium flex-1" x-text="`Page ${page.pageNumber}`"></span>
                            <span
                                x-show="page.annotations.length > 0"
                                class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                x-text="page.annotations.length"
                            ></span>
                        </div>

                        <!-- Annotations on this page (hierarchical) -->
                        <div x-show="isExpanded('page_' + page.pageNumber)" class="tree-hierarchy-indent">
                            <template x-for="anno in page.annotations" :key="anno.id">
                                <div class="tree-node mb-1">
                                    <!-- Root Annotation (Room or orphan) -->
                                    <div
                                        @click="selectAnnotation(anno)"
                                        @dblclick.prevent.stop="anno.type === 'room' && enterIsolationMode({ type: 'room', id: anno.roomId, label: anno.label })"
                                        :class="selectedAnnotation?.id === anno.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                    >
                                        <!-- Expand/collapse button if has children -->
                                        <button
                                            x-show="anno.children && anno.children.length > 0"
                                            @click.stop="toggleNode('anno_' + anno.id)"
                                            class="w-4 h-4 flex items-center justify-center"
                                        >
                                            <span x-show="isExpanded('anno_' + anno.id)">▼</span>
                                            <span x-show="!isExpanded('anno_' + anno.id)">▶</span>
                                        </button>
                                        <span class="w-4" x-show="!anno.children || anno.children.length === 0"></span>

                                        <span x-text="anno.type === 'room' ? '🏠' : anno.type === 'location' ? '📍' : anno.type === 'cabinet_run' ? '📦' : '🗄️'"></span>
                                        <span class="flex-1" x-text="anno.label"></span>

                                        <!-- Children count badge -->
                                        <span
                                            x-show="anno.children && anno.children.length > 0"
                                            class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                            x-text="anno.children.length"
                                        ></span>
                                    </div>

                                    <!-- Location Children (Level 2) -->
                                    <div x-show="isExpanded('anno_' + anno.id)" class="tree-hierarchy-indent">
                                        <template x-for="location in anno.children" :key="location.id">
                                            <div class="tree-node mb-1">
                                                <!-- Location Level -->
                                                <div
                                                    @click="selectAnnotation(location)"
                                                    @dblclick.prevent.stop="location.type === 'location' && enterIsolationMode({ type: 'location', id: location.roomLocationId, label: location.label, roomId: anno.roomId, roomName: anno.label })"
                                                    :class="selectedAnnotation?.id === location.id ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                    class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                >
                                                    <!-- Expand/collapse button if has children -->
                                                    <button
                                                        x-show="location.children && location.children.length > 0"
                                                        @click.stop="toggleNode('anno_' + location.id)"
                                                        class="w-4 h-4 flex items-center justify-center"
                                                    >
                                                        <span x-show="isExpanded('anno_' + location.id)">▼</span>
                                                        <span x-show="!isExpanded('anno_' + location.id)">▶</span>
                                                    </button>
                                                    <span class="w-4" x-show="!location.children || location.children.length === 0"></span>

                                                    <span>📍</span>
                                                    <span class="flex-1" x-text="location.label"></span>

                                                    <!-- Children count badge -->
                                                    <span
                                                        x-show="location.children && location.children.length > 0"
                                                        class="badge bg-indigo-600 text-white px-2 py-0.5 rounded-full text-xs"
                                                        x-text="location.children.length"
                                                    ></span>
                                                </div>

                                                <!-- Cabinet Run Children (Level 3) -->
                                                <div x-show="isExpanded('anno_' + location.id)" class="tree-hierarchy-indent">
                                                    <template x-for="run in location.children" :key="run.id">
                                                        <div class="tree-node mb-1">
                                                            <!-- Cabinet Run Level -->
                                                            <div
                                                                @click="selectAnnotation(run)"
                                                                @dblclick.prevent.stop="run.type === 'cabinet_run' && enterIsolationMode({ type: 'cabinet_run', id: run.cabinetRunId, label: run.label, locationId: location.roomLocationId, locationName: location.label, roomId: anno.roomId, roomName: anno.label })"
                                                                :class="selectedAnnotation?.id === run.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                                class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                            >
                                                                <!-- Expand/collapse button if has children -->
                                                                <button
                                                                    x-show="run.children && run.children.length > 0"
                                                                    @click.stop="toggleNode('anno_' + run.id)"
                                                                    class="w-4 h-4 flex items-center justify-center"
                                                                >
                                                                    <span x-show="isExpanded('anno_' + run.id)">▼</span>
                                                                    <span x-show="!isExpanded('anno_' + run.id)">▶</span>
                                                                </button>
                                                                <span class="w-4" x-show="!run.children || run.children.length === 0"></span>

                                                                <span>📦</span>
                                                                <span class="flex-1" x-text="run.label"></span>

                                                                <!-- Children count badge -->
                                                                <span
                                                                    x-show="run.children && run.children.length > 0"
                                                                    class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                                                    x-text="run.children.length"
                                                                ></span>
                                                            </div>

                                                            <!-- Cabinet Children (Level 4) -->
                                                            <div x-show="isExpanded('anno_' + run.id)" class="tree-hierarchy-indent">
                                                                <template x-for="cabinet in run.children" :key="cabinet.id">
                                                                    <div class="tree-node mb-1">
                                                                        <!-- Cabinet Level (Leaf) -->
                                                                        <div
                                                                            @click="selectAnnotation(cabinet)"
                                                                            :class="selectedAnnotation?.id === cabinet.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                                        >
                                                                            <span class="w-4"></span>
                                                                            <span>🗄️</span>
                                                                            <span class="flex-1" x-text="cabinet.label"></span>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="!annotations || annotations.length === 0" class="text-center py-8 text-sm text-gray-500">
                    No annotations yet
                </div>
            </div>
        </div>

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
        <div class="pdf-viewer-container flex-1 bg-white dark:bg-gray-900 overflow-hidden relative">
            <!-- PDF Container -->
            <div id="pdf-container-{{ $viewerId }}" class="relative w-full h-full overflow-auto">
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
                    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
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
                                <rect x="0" y="0" width="100%" height="100%" fill="white"/>

                                <!-- Black rectangle at selected annotation and its visible children = hide blur there -->
                                <!-- This excludes the focused area from the darkening blur in isolation mode -->
                                <g id="maskRects"></g>
                            </mask>
                        </defs>

                        <!-- Dark overlay with blur, masked to exclude annotation -->
                        <rect
                            x="0"
                            y="0"
                            width="100%"
                            height="100%"
                            fill="rgba(0, 0, 0, 0.65)"
                            filter="url(#blur)"
                            mask="url(#blurMask)"
                        />
                    </svg>
                </div>

                <!-- Annotation Overlay (HTML Elements) -->
                <div
                    x-ref="annotationOverlay"
                    @mousedown="startDrawing($event)"
                    @mousemove="if (isResizing) handleResize($event); else if (isMoving) handleMove($event); else updateDrawing($event);"
                    @mouseup="if (isResizing || isMoving) finishResizeOrMove($event); else finishDrawing($event);"
                    @mouseleave="if (isResizing || isMoving) finishResizeOrMove($event); else cancelDrawing($event);"
                    :class="(drawMode && !editorModalOpen) ? 'pointer-events-auto cursor-crosshair' : 'pointer-events-none'"
                    class="annotation-overlay absolute top-0 left-0"
                    style="z-index: 10; will-change: width, height;"
                >
                    <!-- Existing Annotations -->
                    <template x-for="anno in annotations.filter(a => !hiddenAnnotations.includes(a.id) && isAnnotationVisibleInView(a))" :key="anno.id">
                        <!-- Wrapper div to hide frame of isolated object itself (you "jumped into it") -->
                        <div x-show="!isolationMode || (isolationLevel === 'room' && anno.id !== isolatedRoomId) || (isolationLevel === 'location' && anno.id !== isolatedLocationId) || (isolationLevel === 'cabinet_run' && anno.id !== isolatedCabinetRunId)">
                            <div
                                x-data="{ showMenu: false }"
                                :style="`
                                    position: absolute;
                                    transform: translate(${anno.screenX}px, ${anno.screenY}px);
                                    width: ${anno.screenWidth}px;
                                    height: ${anno.screenHeight}px;
                                    border: 2px solid ${anno.color};
                                    background: ${anno.color}33;
                                    border-radius: 4px;
                                    pointer-events: auto;
                                    cursor: ${isMoving && activeAnnotationId === anno.id ? 'grabbing' : 'grab'};
                                    transition: all 0.2s;
                                    will-change: transform;
                                `"
                                @click="selectAnnotationContext(anno)"
                                @dblclick.prevent.stop="enterIsolationMode(anno)"
                                @mousedown="startMove($event, anno)"
                                @mouseenter="$el.style.background = anno.color + '66'; showMenu = true"
                                @mouseleave="$el.style.background = anno.color + '33'; showMenu = false"
                                class="annotation-marker group"
                            >
                            <!-- Annotation Label -->
                            <div class="annotation-label absolute -top-10 left-0 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg text-base font-bold whitespace-nowrap shadow-xl border-2" style="color: var(--gray-900); border-color: var(--primary-400);">
                                <span x-text="anno.label" class="dark:text-white"></span>
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
                                    z-index: 10;
                                    opacity: ${showMenu ? 1 : 0};
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
                                    z-index: 10;
                                    opacity: ${showMenu ? 1 : 0};
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
                                    z-index: 10;
                                    opacity: ${showMenu ? 1 : 0};
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
                                    z-index: 10;
                                    opacity: ${showMenu ? 1 : 0};
                                    transition: opacity 0.2s;
                                `"
                                class="resize-handle"
                            ></div>

                            <!-- Hover Action Menu -->
                            <div
                                x-show="showMenu"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute -top-10 -right-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-20 flex gap-1 p-1"
                                @click.stop
                            >
                                <!-- Edit Button -->
                                <button
                                    @click="editAnnotation(anno)"
                                    class="px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-1 text-sm font-medium"
                                    style="color: var(--primary-600);"
                                    title="Edit annotation"
                                >
                                    <x-filament::icon icon="heroicon-o-pencil" class="h-4 w-4" />
                                    <span>Edit</span>
                                </button>

                                <!-- Delete Button -->
                                <button
                                    @click="deleteAnnotation(anno)"
                                    class="px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-1 text-sm font-medium"
                                    style="color: var(--danger-600);"
                                    title="Delete annotation"
                                >
                                    <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                                    <span>Delete</span>
                                </button>
                            </div>
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
</div>

@once
    <!-- Load PDFObject.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.3.0/pdfobject.min.js"></script>

    <!-- Load PDF.js for metadata extraction -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>

    <!-- V3 Alpine Component -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('annotationSystemV3', (config) => ({
                // Configuration
                pdfUrl: config.pdfUrl,
                pageNumber: config.pageNumber,  // DEPRECATED: Use currentPage instead
                pdfPageId: config.pdfPageId,
                projectId: config.projectId,
                totalPages: config.totalPages || 1,  // NEW: Total pages in PDF
                pageMap: config.pageMap || {},  // NEW: Map of page_number => pdfPageId

                // Pagination State (NEW - Phase 2)
                currentPage: config.pageNumber || 1,  // Current page being viewed
                pageType: config.pageType || null,    // Page type (cover_page, floor_plan, elevation, etc.)

                // PDF State
                pdfReady: false,
                pageDimensions: null,
                canvasScale: 1.0,    // Canvas scale factor (PDF → Screen)
                zoomLevel: 1.0,  // Current zoom level (1.0 = 100% = fit to window)
                zoomMin: 1.0,    // Minimum zoom (100% = fit to frame, no smaller)
                zoomMax: 3.0,    // Maximum zoom (300%)

                // Context State
                activeRoomId: null,
                activeRoomName: '',
                activeLocationId: null,
                activeLocationName: '',
                drawMode: null, // 'cabinet_run' or 'cabinet'
                editorModalOpen: false, // Track if annotation editor modal is open

                // Isolation Mode State (NEW - Illustrator-style layer isolation)
                isolationMode: false,           // Whether we're in isolation mode
                isolationLevel: null,           // 'room', 'location', or 'cabinet_run'
                isolatedRoomId: null,          // Room being isolated
                isolatedRoomName: '',          // Name of isolated room
                isolatedLocationId: null,      // Location being isolated (if in location isolation)
                isolatedLocationName: '',      // Name of isolated location
                isolatedCabinetRunId: null,    // Cabinet run being isolated (if in cabinet run isolation)
                isolatedCabinetRunName: '',    // Name of isolated cabinet run
                isolationViewType: null,       // View type when isolation mode was entered
                isolationOrientation: null,    // View orientation when isolation mode was entered
                overlayWidth: '100%',          // Overlay width for blur layer sync
                overlayHeight: '100%',         // Overlay height for blur layer sync
                hiddenAnnotations: [],         // Array of annotation IDs to hide (for Alpine reactivity)

                // Tree State
                tree: [],
                expandedNodes: [],
                selectedNodeId: null,
                selectedPath: [], // Array of all ancestor IDs in the hierarchical path
                selectedAnnotation: null, // Currently selected annotation for highlighting
                loading: false,
                error: null,
                treeViewMode: 'room', // 'room' or 'page'
                treeSidebarState: 'full', // 'full', 'mini', 'hidden'

                // Context Menu State
                contextMenu: {
                    show: false,
                    x: 0,
                    y: 0,
                    nodeId: null,
                    nodeType: null,
                    nodeName: '',
                    parentRoomId: null
                },

                // Autocomplete State
                roomSearchQuery: '',
                locationSearchQuery: '',
                roomSuggestions: [],
                locationSuggestions: [],
                showRoomDropdown: false,
                showLocationDropdown: false,

                // Annotation State
                annotations: [],
                isDrawing: false,
                drawStart: null,
                drawPreview: null,

                // Resize and Move State
                isResizing: false,
                isMoving: false,
                resizeHandle: null, // 'nw', 'ne', 'sw', 'se'
                moveStart: null, // { x, y, annoStartX, annoStartY }
                resizeStart: null, // { x, y, annoStartX, annoStartY, annoStartWidth, annoStartHeight }
                activeAnnotationId: null, // Which annotation is being resized/moved

                // View Type State (Plan, Elevation, Section, Detail)
                activeViewType: 'plan', // 'plan', 'elevation', 'section', 'detail'
                activeOrientation: null, // 'front', 'back', 'left', 'right', 'A-A', etc.
                availableOrientations: {
                    elevation: ['front', 'back', 'left', 'right'],
                    section: ['A-A', 'B-B', 'C-C'],
                    detail: [] // Details use numeric scale instead
                },
                viewScale: 1.0, // Scale factor for detail views (2.0 = 2x zoom)

                // Multi-Parent Entity References
                annotationReferences: {}, // Map<annotationId, Array<{type, id, referenceType}>>

                // Page Observer State (for multi-page PDFs)
                pageObserver: null,
                visiblePages: [],

                // Performance Optimization - Cache overlay rect
                _overlayRect: null,
                _lastRectUpdate: 0,
                _rectCacheMs: 100, // Cache for 100ms

                // PDF iframe scroll tracking
                pdfIframe: null,
                scrollX: 0,
                scrollY: 0,

                // Initialize
                async init() {
                    console.log('🚀 V3 Annotation System initializing...');

                    try {
                        // Step 1: Extract PDF page dimensions using PDF.js (metadata only)
                        await this.extractPdfDimensions();

                        // Step 2: Display PDF using PDFObject.js
                        await this.displayPdf();

                        // Step 3: Load project tree
                        await this.loadTree();

                        // Step 4: Load existing annotations
                        await this.loadAnnotations();

                        // Step 5: Listen for annotation updates from Livewire
                        Livewire.on('annotation-updated', async (event) => {
                            const updatedAnnotation = event.annotation;

                            // For newly created annotations, the ID changed from temp_XXX to real database ID
                            // We need to find by either the new ID OR by checking if this was a temp annotation
                            let index = this.annotations.findIndex(a => a.id === updatedAnnotation.id);

                            // If not found by new ID, check if there's a temp annotation that needs ID replacement
                            if (index === -1) {
                                // Look for temp annotations that match the position (for newly created ones)
                                index = this.annotations.findIndex(a =>
                                    typeof a.id === 'string' &&
                                    a.id.startsWith('temp_') &&
                                    a.pdfX === updatedAnnotation.pdfX &&
                                    a.pdfY === updatedAnnotation.pdfY
                                );
                            }

                            if (index !== -1) {
                                // Replace the annotation (including ID for temp → real conversion)
                                this.annotations[index] = {
                                    ...this.annotations[index],
                                    id: updatedAnnotation.id, // Update ID (temp → real)
                                    label: updatedAnnotation.label,
                                    notes: updatedAnnotation.notes,
                                    parentId: updatedAnnotation.parentId,  // Update parent ID for hierarchy changes
                                    measurementWidth: updatedAnnotation.measurementWidth,
                                    measurementHeight: updatedAnnotation.measurementHeight,
                                    roomId: updatedAnnotation.roomId,
                                    roomName: updatedAnnotation.roomName,
                                    locationId: updatedAnnotation.locationId,
                                    locationName: updatedAnnotation.locationName,
                                    cabinetRunId: updatedAnnotation.cabinetRunId
                                };
                                console.log('✓ Annotation updated from Livewire (including parentId):', updatedAnnotation);

                                // Re-render annotations to show updated data
                                this.renderAnnotations();

                                // Refresh tree to show new annotation (with small delay to ensure DB write completes)
                                setTimeout(() => {
                                    this.refreshTree();
                                    console.log('🌳 Tree refreshed after annotation save');
                                }, 300);
                            } else {
                                console.warn('⚠️ Could not find annotation to update:', updatedAnnotation.id);
                            }
                            // Editor modal closed after save
                            this.editorModalOpen = false;
                        });

                        // Listen for annotation deletion from Livewire
                        Livewire.on('annotation-deleted', async (event) => {
                            const annotationId = event.annotationId;
                            const index = this.annotations.findIndex(a => a.id === annotationId);
                            if (index !== -1) {
                                // Remove annotation from array
                                this.annotations.splice(index, 1);
                                console.log('✓ Annotation deleted via Livewire:', annotationId);
                                // Re-render happens automatically via Alpine reactivity
                                // Refresh tree to update counts (with small delay to ensure DB write completes)
                                setTimeout(() => {
                                    this.refreshTree();
                                    console.log('🌳 Tree refreshed after annotation deletion');
                                }, 300);
                            }
                            // Editor modal closed
                            this.editorModalOpen = false;
                        });

                        // Listen for when annotation editor modal opens
                        Livewire.on('edit-annotation', () => {
                            this.editorModalOpen = true;
                            console.log('📝 Editor modal opened - disabling overlay pointer events');
                        });

                        // Listen for when annotation editor modal is closed (cancel/X button)
                        Livewire.on('annotation-editor-closed', () => {
                            this.editorModalOpen = false;
                            console.log('📝 Editor modal closed - re-enabling overlay pointer events');
                        });

                        // Listen for refresh-project-tree event from Livewire
                        Livewire.on('refresh-project-tree', () => {
                            console.log('🌳 Refreshing project tree from Livewire event');
                            this.refreshTree();
                        });

                        // Step 6: Initialize page observer for multi-page support
                        this.initPageObserver();

                        // Step 7: Setup keyboard shortcuts
                        window.addEventListener('keydown', (e) => {
                            // Escape key exits isolation mode
                            if (e.key === 'Escape' && this.isolationMode) {
                                this.exitIsolationMode();
                            }
                        });

                        // Step 8: Setup ResizeObserver to keep overlay locked to canvas (browser zoom safe)
                        const syncOverlayToCanvas = () => {
                            const canvas = this.$refs.pdfEmbed?.querySelector('canvas');
                            const overlay = this.$refs.annotationOverlay;
                            if (canvas && overlay) {
                                const canvasRect = canvas.getBoundingClientRect();
                                const width = `${canvasRect.width}px`;
                                const height = `${canvasRect.height}px`;

                                overlay.style.width = width;
                                overlay.style.height = height;

                                // Sync blur layer dimensions (reactive)
                                this.overlayWidth = width;
                                this.overlayHeight = height;

                                // Update annotation positions when size changes
                                this.updateAnnotationPositions();

                                console.log(`🔄 Overlay synced to canvas: ${canvasRect.width} × ${canvasRect.height}`);
                            }
                        };

                        // Observe canvas size changes (handles browser zoom)
                        const resizeObserver = new ResizeObserver(() => {
                            syncOverlayToCanvas();
                        });

                        // Start observing the PDF embed container
                        const embedContainer = this.$refs.pdfEmbed;
                        if (embedContainer) {
                            resizeObserver.observe(embedContainer);
                        }

                        // Step 9: Setup scroll listener to update isolation mask when panning
                        const pdfContainer = document.getElementById('pdf-container-{{ $viewerId }}');
                        if (pdfContainer) {
                            // Use requestAnimationFrame to throttle scroll updates
                            let scrollTicking = false;
                            pdfContainer.addEventListener('scroll', () => {
                                if (!scrollTicking && this.isolationMode) {
                                    window.requestAnimationFrame(() => {
                                        // Clear rect cache to force fresh getBoundingClientRect
                                        this._lastRectUpdate = 0;
                                        // Recalculate annotation positions with fresh canvas rect
                                        this.updateAnnotationPositions();
                                        // Redraw isolation mask with new positions
                                        this.updateIsolationMask();
                                        scrollTicking = false;
                                    });
                                    scrollTicking = true;
                                }
                            });
                        }

                        console.log('✅ V3 system ready!');
                    } catch (error) {
                        console.error('❌ Initialization error:', error);
                        this.error = error.message;
                    }
                },

                // Extract PDF dimensions using PDF.js (NO CANVAS RENDERING)
                async extractPdfDimensions() {
                    console.log('📏 Extracting PDF dimensions...');

                    const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
                    const pdf = await loadingTask.promise;
                    const page = await pdf.getPage(this.currentPage);  // PHASE 2: Use currentPage
                    const viewport = page.getViewport({ scale: 1.0 });

                    this.pageDimensions = {
                        width: viewport.width,
                        height: viewport.height
                    };

                    console.log(`✓ Page ${this.currentPage} dimensions: ${Math.round(this.pageDimensions.width)} × ${Math.round(this.pageDimensions.height)} pts`);

                    // Clean up to avoid memory leaks
                    await pdf.destroy();
                },

                // Display PDF using canvas rendering (PDF.js) - True page isolation
                async displayPdf() {
                    console.log('📄 Rendering PDF page to canvas (scroll-proof)...');
                    console.log('🔍 PDF URL:', this.pdfUrl);
                    console.log('🔍 Current Page:', this.currentPage);
                    console.log('🔍 Zoom Level:', this.zoomLevel);

                    const embedContainer = this.$refs.pdfEmbed;

                    try {
                        // Load PDF document
                        const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
                        const pdf = await loadingTask.promise;

                        // Get the specific page
                        const page = await pdf.getPage(this.currentPage);

                        // Get unscaled viewport for dimension reference
                        const unscaledViewport = page.getViewport({ scale: 1.0 });

                        // Calculate base scale to fit container width, then apply zoom
                        const containerWidth = embedContainer.clientWidth;
                        const baseScale = containerWidth / unscaledViewport.width;
                        const scale = baseScale * this.zoomLevel; // Apply zoom multiplier
                        const scaledViewport = page.getViewport({ scale });

                        // Create canvas with scaled dimensions
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.width = scaledViewport.width;
                        canvas.height = scaledViewport.height;

                        // At 100% zoom, fit to container width
                        // At higher zoom, allow overflow for scrolling
                        if (this.zoomLevel === 1.0) {
                            canvas.style.width = '100%';
                            canvas.style.height = 'auto';
                        } else {
                            canvas.style.width = `${scaledViewport.width}px`;
                            canvas.style.height = `${scaledViewport.height}px`;
                        }
                        canvas.style.display = 'block';

                        // Render PDF page to canvas
                        const renderContext = {
                            canvasContext: context,
                            viewport: scaledViewport
                        };

                        await page.render(renderContext).promise;

                        // Clear container and add canvas
                        embedContainer.innerHTML = '';
                        embedContainer.appendChild(canvas);

                        // LOCK overlay to canvas dimensions (browser zoom safe)
                        await this.$nextTick();
                        const overlay = this.$refs.annotationOverlay;
                        if (overlay) {
                            const canvasRect = canvas.getBoundingClientRect();
                            const width = `${canvasRect.width}px`;
                            const height = `${canvasRect.height}px`;

                            overlay.style.width = width;
                            overlay.style.height = height;

                            // Sync blur layer dimensions (reactive)
                            this.overlayWidth = width;
                            this.overlayHeight = height;

                            console.log(`🔒 Overlay locked to canvas: ${canvasRect.width} × ${canvasRect.height}`);
                        }

                        // Store canvas scale factor for coordinate transformations
                        this.canvasScale = scale;

                        console.log('✓ PDF page rendered to canvas');
                        console.log(`✓ Canvas dimensions: ${canvas.width} × ${canvas.height}`);
                        console.log(`✓ Canvas scale factor: ${scale.toFixed(3)}`);

                        this.pdfReady = true;

                        // Clean up PDF document
                        await pdf.destroy();

                        console.log(`✓ PDF page ${this.currentPage} displayed successfully`);
                    } catch (error) {
                        console.error('❌ Failed to render PDF:', error);
                        throw error;
                    }
                },

                // REMOVED: attachPdfScrollListener() - No longer needed with canvas rendering
                // Canvas pages are static images, no internal scrolling possible

                // Initialize IntersectionObserver for page visibility tracking
                initPageObserver() {
                    const container = document.getElementById(`pdf-container-{{ $viewerId }}`);

                    if (!container) {
                        console.warn('⚠️ PDF container not found, skipping page observer');
                        return;
                    }

                    this.pageObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            const pageIndex = entry.target.dataset.pageIndex;
                            if (entry.isIntersecting) {
                                console.log(`📄 Page ${pageIndex} visible`);
                                if (!this.visiblePages.includes(pageIndex)) {
                                    this.visiblePages.push(pageIndex);
                                }
                            } else {
                                console.log(`📄 Page ${pageIndex} hidden`);
                                this.visiblePages = this.visiblePages.filter(p => p !== pageIndex);
                            }
                        });
                    }, {
                        root: container,
                        threshold: 0.1 // 10% visible triggers callback
                    });

                    console.log('✓ Page observer initialized');
                },

                // Get canvas rect for coordinate transformations
                getCanvasRect() {
                    const now = Date.now();
                    if (this._overlayRect && (now - this._lastRectUpdate) < this._rectCacheMs) {
                        return this._overlayRect;
                    }

                    // Get the canvas element (not the overlay)
                    const canvas = this.$refs.pdfEmbed?.querySelector('canvas');
                    if (!canvas) return null;

                    this._overlayRect = canvas.getBoundingClientRect();
                    this._lastRectUpdate = now;
                    return this._overlayRect;
                },

                // Deprecated: Use getCanvasRect() instead
                getOverlayRect() {
                    return this.getCanvasRect();
                },

                // Coordinate transformation: Screen → PDF
                screenToPdf(screenX, screenY) {
                    if (!this.pageDimensions) return { x: 0, y: 0 };

                    const rect = this.getOverlayRect();
                    if (!rect) return { x: 0, y: 0 };

                    // Normalize to 0-1 range
                    const normalizedX = screenX / rect.width;
                    const normalizedY = screenY / rect.height;

                    // Convert to PDF coordinates (PDF y-axis is from bottom)
                    const pdfX = normalizedX * this.pageDimensions.width;
                    const pdfY = this.pageDimensions.height - (normalizedY * this.pageDimensions.height);

                    return {
                        x: pdfX,
                        y: pdfY,
                        normalized: { x: normalizedX, y: normalizedY }
                    };
                },

                // Coordinate transformation: PDF → Screen (Canvas)
                pdfToScreen(pdfX, pdfY, width = 0, height = 0) {
                    if (!this.pageDimensions) return { x: 0, y: 0, width: 0, height: 0 };

                    const canvasRect = this.getCanvasRect();
                    if (!canvasRect) return { x: 0, y: 0, width: 0, height: 0 };

                    // Normalize PDF coordinates to 0-1 range
                    const normalizedX = pdfX / this.pageDimensions.width;
                    const normalizedY = (this.pageDimensions.height - pdfY) / this.pageDimensions.height;

                    // Apply canvas scale to get screen coordinates
                    const screenX = normalizedX * canvasRect.width;
                    const screenY = normalizedY * canvasRect.height;
                    const screenWidth = (width / this.pageDimensions.width) * canvasRect.width;
                    const screenHeight = (height / this.pageDimensions.height) * canvasRect.height;

                    return {
                        x: screenX,
                        y: screenY,
                        width: screenWidth,
                        height: screenHeight
                    };
                },

                // Start drawing rectangle on mousedown
                startDrawing(event) {
                    // Check if we can draw based on mode
                    const canProceed = this.drawMode === 'room'
                        ? true  // Room boundary can be drawn anytime - room created in form
                        : this.drawMode === 'location'
                            ? this.canDrawLocation()
                            : this.canDraw();

                    if (!canProceed || !this.drawMode) return;

                    const overlay = this.$refs.annotationOverlay;
                    const rect = overlay.getBoundingClientRect();
                    const x = event.clientX - rect.left;
                    const y = event.clientY - rect.top;

                    // Start drawing
                    this.isDrawing = true;
                    this.drawStart = { x, y };
                    this.drawPreview = { x, y, width: 0, height: 0 };

                    console.log('🖱️ Started drawing at', { x, y });
                },

                // Update rectangle preview on mousemove
                updateDrawing(event) {
                    if (!this.isDrawing || !this.drawStart) return;

                    const overlay = this.$refs.annotationOverlay;
                    const rect = overlay.getBoundingClientRect();
                    const currentX = event.clientX - rect.left;
                    const currentY = event.clientY - rect.top;

                    // Update preview rectangle (normalize so top-left is always correct)
                    this.drawPreview = {
                        x: Math.min(this.drawStart.x, currentX),
                        y: Math.min(this.drawStart.y, currentY),
                        width: Math.abs(currentX - this.drawStart.x),
                        height: Math.abs(currentY - this.drawStart.y)
                    };
                },

                // Finish drawing on mouseup
                finishDrawing(event) {
                    if (!this.isDrawing) return;

                    console.log('🖱️ Finished drawing', this.drawPreview);

                    // Create annotation if rectangle is large enough (10px minimum)
                    if (this.drawPreview && this.drawPreview.width > 10 && this.drawPreview.height > 10) {
                        this.createAnnotation(this.drawPreview);
                    } else {
                        console.log('⚠️ Rectangle too small, discarded');
                    }

                    // Reset drawing state
                    this.isDrawing = false;
                    this.drawStart = null;
                    this.drawPreview = null;
                },

                // Cancel drawing if mouse leaves canvas
                cancelDrawing(event) {
                    if (!this.isDrawing) return;

                    console.log('❌ Drawing cancelled (mouse left canvas)');

                    // Reset drawing state without creating annotation
                    this.isDrawing = false;
                    this.drawStart = null;
                    this.drawPreview = null;
                },

                // === RESIZE AND MOVE HANDLERS ===

                // Start resizing annotation from corner handle
                startResize(event, anno, handle) {
                    console.log(`📐 Starting resize for annotation ${anno.id} from ${handle} corner`);

                    this.isResizing = true;
                    this.resizeHandle = handle;
                    this.activeAnnotationId = anno.id;

                    const overlay = this.$refs.annotationOverlay;
                    const rect = overlay.getBoundingClientRect();

                    this.resizeStart = {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top,
                        annoStartX: anno.screenX,
                        annoStartY: anno.screenY,
                        annoStartWidth: anno.screenWidth,
                        annoStartHeight: anno.screenHeight
                    };
                },

                // Start moving annotation
                startMove(event, anno) {
                    // Don't start move if clicking on edit/delete buttons or other UI elements
                    if (event.target.closest('.resize-handle') ||
                        event.target.closest('button') ||
                        event.target.closest('.annotation-label')) {
                        return;
                    }

                    // Prevent default to avoid text selection while dragging
                    event.preventDefault();

                    console.log(`✋ Starting move for annotation ${anno.id}`);

                    this.isMoving = true;
                    this.activeAnnotationId = anno.id;

                    const overlay = this.$refs.annotationOverlay;
                    const rect = overlay.getBoundingClientRect();

                    this.moveStart = {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top,
                        annoStartX: anno.screenX,
                        annoStartY: anno.screenY
                    };
                },

                // Handle resize during mouse move
                handleResize(event) {
                    if (!this.isResizing || !this.resizeStart || !this.activeAnnotationId) return;

                    const overlay = this.$refs.annotationOverlay;
                    const rect = overlay.getBoundingClientRect();
                    const currentX = event.clientX - rect.left;
                    const currentY = event.clientY - rect.top;

                    const deltaX = currentX - this.resizeStart.x;
                    const deltaY = currentY - this.resizeStart.y;

                    // Find the annotation being resized
                    const anno = this.annotations.find(a => a.id === this.activeAnnotationId);
                    if (!anno) return;

                    // Calculate new dimensions based on which corner is being dragged
                    let newX = anno.screenX;
                    let newY = anno.screenY;
                    let newWidth = anno.screenWidth;
                    let newHeight = anno.screenHeight;

                    switch (this.resizeHandle) {
                        case 'nw': // Top-left corner
                            newX = this.resizeStart.annoStartX + deltaX;
                            newY = this.resizeStart.annoStartY + deltaY;
                            newWidth = this.resizeStart.annoStartWidth - deltaX;
                            newHeight = this.resizeStart.annoStartHeight - deltaY;
                            break;
                        case 'ne': // Top-right corner
                            newY = this.resizeStart.annoStartY + deltaY;
                            newWidth = this.resizeStart.annoStartWidth + deltaX;
                            newHeight = this.resizeStart.annoStartHeight - deltaY;
                            break;
                        case 'sw': // Bottom-left corner
                            newX = this.resizeStart.annoStartX + deltaX;
                            newWidth = this.resizeStart.annoStartWidth - deltaX;
                            newHeight = this.resizeStart.annoStartHeight + deltaY;
                            break;
                        case 'se': // Bottom-right corner
                            newWidth = this.resizeStart.annoStartWidth + deltaX;
                            newHeight = this.resizeStart.annoStartHeight + deltaY;
                            break;
                    }

                    // Enforce minimum size (20px)
                    if (newWidth < 20 || newHeight < 20) return;

                    // Update annotation dimensions in place (reactive)
                    anno.screenX = newX;
                    anno.screenY = newY;
                    anno.screenWidth = newWidth;
                    anno.screenHeight = newHeight;
                },

                // Handle move during mouse move
                handleMove(event) {
                    if (!this.isMoving || !this.moveStart || !this.activeAnnotationId) return;

                    const overlay = this.$refs.annotationOverlay;
                    const rect = overlay.getBoundingClientRect();
                    const currentX = event.clientX - rect.left;
                    const currentY = event.clientY - rect.top;

                    const deltaX = currentX - this.moveStart.x;
                    const deltaY = currentY - this.moveStart.y;

                    // Find the annotation being moved
                    const anno = this.annotations.find(a => a.id === this.activeAnnotationId);
                    if (!anno) return;

                    // Update annotation position in place (reactive)
                    anno.screenX = this.moveStart.annoStartX + deltaX;
                    anno.screenY = this.moveStart.annoStartY + deltaY;

                    // Keep within bounds
                    const overlayWidth = rect.width;
                    const overlayHeight = rect.height;

                    if (anno.screenX < 0) anno.screenX = 0;
                    if (anno.screenY < 0) anno.screenY = 0;
                    if (anno.screenX + anno.screenWidth > overlayWidth) {
                        anno.screenX = overlayWidth - anno.screenWidth;
                    }
                    if (anno.screenY + anno.screenHeight > overlayHeight) {
                        anno.screenY = overlayHeight - anno.screenHeight;
                    }
                },

                // Finish resize or move and save to database
                finishResizeOrMove(event) {
                    if (!this.isResizing && !this.isMoving) return;

                    const operationType = this.isResizing ? 'resize' : 'move';
                    console.log(`✓ Finished ${operationType} for annotation ${this.activeAnnotationId}`);

                    // Find the annotation that was modified
                    const anno = this.annotations.find(a => a.id === this.activeAnnotationId);
                    if (!anno) {
                        this.resetResizeMove();
                        return;
                    }

                    // Convert screen coordinates back to PDF coordinates
                    const pdfTopLeft = this.screenToPdf(anno.screenX, anno.screenY);
                    const pdfBottomRight = this.screenToPdf(
                        anno.screenX + anno.screenWidth,
                        anno.screenY + anno.screenHeight
                    );

                    // Update PDF coordinates
                    anno.pdfX = pdfTopLeft.x;
                    anno.pdfY = pdfTopLeft.y;
                    anno.pdfWidth = Math.abs(pdfBottomRight.x - pdfTopLeft.x);
                    anno.pdfHeight = Math.abs(pdfTopLeft.y - pdfBottomRight.y);
                    anno.normalizedX = pdfTopLeft.normalized.x;
                    anno.normalizedY = pdfTopLeft.normalized.y;

                    // Save to database via Livewire
                    console.log(`💾 Saving ${operationType} changes to database...`);
                    Livewire.dispatch('update-annotation-position', {
                        annotationId: anno.id,
                        pdfX: anno.pdfX,
                        pdfY: anno.pdfY,
                        pdfWidth: anno.pdfWidth,
                        pdfHeight: anno.pdfHeight,
                        normalizedX: anno.normalizedX,
                        normalizedY: anno.normalizedY
                    });

                    // Reset state
                    this.resetResizeMove();
                },

                // Reset resize/move state
                resetResizeMove() {
                    this.isResizing = false;
                    this.isMoving = false;
                    this.resizeHandle = null;
                    this.resizeStart = null;
                    this.moveStart = null;
                    this.activeAnnotationId = null;
                },

                // === END RESIZE AND MOVE HANDLERS ===

                // === VIEW TYPE MANAGEMENT ===

                /**
                 * Set the active view type (plan, elevation, section, detail)
                 * @param {string} viewType - 'plan', 'elevation', 'section', 'detail'
                 * @param {string|null} orientation - Optional orientation for elevation/section
                 */
                setViewType(viewType, orientation = null) {
                    console.log(`📐 Switching to ${viewType} view${orientation ? ' (' + orientation + ')' : ''}`);

                    this.activeViewType = viewType;
                    this.activeOrientation = orientation;

                    // Reset orientation if switching to plan view
                    if (viewType === 'plan') {
                        this.activeOrientation = null;
                    }

                    // Filter annotations based on new view
                    this.updateAnnotationVisibility();

                    // Update UI to reflect new view
                    console.log(`✓ View switched to ${viewType}${orientation ? ' - ' + orientation : ''}`);
                },

                /**
                 * Set orientation for elevation or section views
                 * @param {string} orientation - 'front', 'back', 'left', 'right', 'A-A', etc.
                 */
                setOrientation(orientation) {
                    console.log(`🧭 Setting orientation to ${orientation}`);
                    this.activeOrientation = orientation;
                    this.updateAnnotationVisibility();
                },

                /**
                 * Check if annotation should be visible in current view
                 * @param {object} anno - Annotation object
                 * @returns {boolean}
                 */
                isAnnotationVisibleInView(anno) {
                    // If no view type specified on annotation, assume it's visible in plan view
                    const annoViewType = anno.viewType || 'plan';

                    // If we're in plan view, show all plan annotations
                    if (this.activeViewType === 'plan') {
                        return annoViewType === 'plan';
                    }

                    // If we're in elevation/section/detail view, show matching annotations
                    if (this.activeViewType === annoViewType) {
                        // If orientation is set, check if it matches
                        if (this.activeOrientation && anno.viewOrientation) {
                            return anno.viewOrientation === this.activeOrientation;
                        }
                        // If no orientation filter, show all annotations of this view type
                        return true;
                    }

                    return false;
                },

                /**
                 * Update visibility of all annotations based on current view
                 */
                updateAnnotationVisibility() {
                    // This will be used in the rendering loop to filter visible annotations
                    // The actual filtering happens in the x-for template
                    console.log(`🔍 Updating annotation visibility for ${this.activeViewType} view`);
                },

                /**
                 * Add entity reference to an annotation
                 * @param {number} annotationId
                 * @param {string} entityType - 'room', 'location', 'cabinet_run', 'cabinet'
                 * @param {number} entityId
                 * @param {string} referenceType - 'primary', 'secondary', 'context'
                 */
                addEntityReference(annotationId, entityType, entityId, referenceType = 'primary') {
                    if (!this.annotationReferences[annotationId]) {
                        this.annotationReferences[annotationId] = [];
                    }

                    // Check if reference already exists
                    const exists = this.annotationReferences[annotationId].some(
                        ref => ref.entity_type === entityType && ref.entity_id === entityId
                    );

                    if (!exists) {
                        this.annotationReferences[annotationId].push({
                            entity_type: entityType,
                            entity_id: entityId,
                            reference_type: referenceType
                        });

                        console.log(`✓ Added ${referenceType} reference: ${entityType} #${entityId} to annotation #${annotationId}`);
                    }
                },

                /**
                 * Remove entity reference from an annotation
                 * @param {number} annotationId
                 * @param {string} entityType
                 * @param {number} entityId
                 */
                removeEntityReference(annotationId, entityType, entityId) {
                    if (this.annotationReferences[annotationId]) {
                        this.annotationReferences[annotationId] = this.annotationReferences[annotationId].filter(
                            ref => !(ref.entity_type === entityType && ref.entity_id === entityId)
                        );

                        console.log(`✓ Removed reference: ${entityType} #${entityId} from annotation #${annotationId}`);
                    }
                },

                /**
                 * Get all entity references for an annotation
                 * @param {number} annotationId
                 * @returns {array}
                 */
                getEntityReferences(annotationId) {
                    return this.annotationReferences[annotationId] || [];
                },

                /**
                 * Get human-readable label for current view
                 * @returns {string}
                 */
                getCurrentViewLabel() {
                    if (this.activeViewType === 'plan') {
                        return 'Plan View';
                    } else if (this.activeViewType === 'elevation') {
                        const orientation = this.activeOrientation ? ` - ${this.activeOrientation.charAt(0).toUpperCase() + this.activeOrientation.slice(1)}` : '';
                        return `Elevation View${orientation}`;
                    } else if (this.activeViewType === 'section') {
                        const orientation = this.activeOrientation ? ` - ${this.activeOrientation}` : '';
                        return `Section View${orientation}`;
                    } else if (this.activeViewType === 'detail') {
                        return 'Detail View';
                    }
                    return 'Unknown View';
                },

                /**
                 * Get color for current view type badge
                 * @returns {string}
                 */
                getCurrentViewColor() {
                    if (this.activeViewType === 'plan') return 'var(--primary-600)';
                    if (this.activeViewType === 'elevation') return 'var(--warning-600)';
                    if (this.activeViewType === 'section') return 'var(--info-600)';
                    if (this.activeViewType === 'detail') return 'var(--success-600)';
                    return 'var(--gray-600)';
                },

                // === END VIEW TYPE MANAGEMENT ===

                // Create annotation from drawn rectangle
                createAnnotation(screenRect) {
                    // Convert screen coordinates to PDF coordinates
                    const pdfTopLeft = this.screenToPdf(screenRect.x, screenRect.y);
                    const pdfBottomRight = this.screenToPdf(
                        screenRect.x + screenRect.width,
                        screenRect.y + screenRect.height
                    );

                    // Determine parent annotation ID
                    let parentAnnotationId = null;

                    if (this.isolationMode) {
                        // Isolation mode: use isolated entity annotation as parent
                        parentAnnotationId = this.isolationLevel === 'room' ? this.isolatedRoomId :
                                           this.isolationLevel === 'location' ? this.isolatedLocationId :
                                           this.isolationLevel === 'cabinet_run' ? this.isolatedCabinetRunId :
                                           null;
                        console.log(`🎯 [createAnnotation] Isolation mode - parentId: ${parentAnnotationId}`);
                    } else {
                        // Normal mode: find parent annotation on current page based on selected entity
                        // When drawing a location, find the room annotation
                        if (this.drawMode === 'location' && this.activeRoomId) {
                            const roomAnno = this.findAnnotationByEntity('room', this.activeRoomId);
                            parentAnnotationId = roomAnno?.id || null;
                            console.log(`🎯 [createAnnotation] Normal mode - drawing location under room ${this.activeRoomId}, found parent: ${parentAnnotationId}`);
                        }
                        // When drawing a cabinet run or cabinet, find the location annotation
                        else if ((this.drawMode === 'cabinet_run' || this.drawMode === 'cabinet') && this.activeLocationId) {
                            const locationAnno = this.findAnnotationByEntity('room_location', this.activeLocationId);
                            parentAnnotationId = locationAnno?.id || null;
                            console.log(`🎯 [createAnnotation] Normal mode - drawing ${this.drawMode} under location ${this.activeLocationId}, found parent: ${parentAnnotationId}`);
                        }
                    }

                    const annotation = {
                        id: 'temp_' + Date.now(),
                        type: this.drawMode,
                        pdfX: pdfTopLeft.x,
                        pdfY: pdfTopLeft.y,
                        pdfWidth: Math.abs(pdfBottomRight.x - pdfTopLeft.x),
                        pdfHeight: Math.abs(pdfTopLeft.y - pdfBottomRight.y),
                        normalizedX: pdfTopLeft.normalized.x,
                        normalizedY: pdfTopLeft.normalized.y,
                        screenX: screenRect.x,
                        screenY: screenRect.y,
                        screenWidth: screenRect.width,
                        screenHeight: screenRect.height,
                        roomId: this.activeRoomId,
                        roomName: this.activeRoomName,
                        roomLocationId: this.drawMode === 'location' ? this.activeLocationId : null,  // For location annotations
                        cabinetRunId: this.drawMode === 'cabinet_run' ? this.activeLocationId : null,  // For cabinet run annotations (activeLocationId holds run ID in this context)
                        locationName: this.activeLocationName,
                        viewType: 'plan',  // Default to plan view (can be changed in UI later)
                        label: this.generateAnnotationLabel(),
                        color: this.getDrawColor(),
                        createdAt: new Date(),
                        pdfPageId: this.pdfPageId,  // Add pdfPageId for context loading
                        projectId: this.projectId,   // Add projectId for form loading
                        parentId: parentAnnotationId  // Set parent in both isolation and normal mode
                    };

                    this.annotations.push(annotation);
                    console.log('✓ Annotation created:', annotation);

                    // Dispatch to Livewire for editing annotation details
                    Livewire.dispatch('edit-annotation', { annotation: annotation });
                },

                // Generate auto-incrementing label
                generateAnnotationLabel() {
                    if (this.drawMode === 'room') {
                        // For room boundaries, use the room name
                        return this.activeRoomName || 'Room';
                    } else if (this.drawMode === 'location') {
                        // For locations, count within the room
                        const count = this.annotations.filter(a =>
                            a.type === 'location' &&
                            a.roomId === this.activeRoomId
                        ).length + 1;
                        return `Location ${count}`;
                    } else {
                        // For runs/cabinets, count within the location
                        const count = this.annotations.filter(a =>
                            a.type === this.drawMode &&
                            a.locationId === this.activeLocationId
                        ).length + 1;

                        if (this.drawMode === 'cabinet_run') {
                            return `Run ${count}`;
                        } else {
                            return `Cabinet ${count}`;
                        }
                    }
                },

                // Get color for current draw mode
                getDrawColor() {
                    if (this.drawMode === 'room') return '#f59e0b'; // Amber/Orange (room boundary)
                    if (this.drawMode === 'location') return '#9333ea'; // Purple
                    if (this.drawMode === 'cabinet_run') return '#3b82f6'; // Blue
                    return '#10b981'; // Green (cabinet)
                },

                // Load existing annotations
                async loadAnnotations() {
                    console.log(`📥 Loading annotations for page ${this.currentPage} (pdfPageId: ${this.pdfPageId})...`);

                    // PHASE 5: Clear existing annotations before loading new ones
                    this.annotations = [];

                    try {
                        const response = await fetch(`/api/pdf/page/${this.pdfPageId}/annotations`);
                        const data = await response.json();

                        if (data.success && data.annotations) {
                            // Convert loaded annotations to screen coordinates
                            this.annotations = data.annotations.map(anno => {
                                // Transform normalized coordinates to screen position
                                const screenPos = this.pdfToScreen(
                                    anno.x * this.pageDimensions.width,
                                    (1 - anno.y) * this.pageDimensions.height,  // Invert Y
                                    anno.width * this.pageDimensions.width,
                                    anno.height * this.pageDimensions.height
                                );

                                return {
                                    id: anno.id,
                                    type: anno.annotation_type,
                                    parentId: anno.parent_annotation_id,  // Parent annotation ID for hierarchy
                                    pdfX: anno.x * this.pageDimensions.width,
                                    pdfY: (1 - anno.y) * this.pageDimensions.height,
                                    pdfWidth: anno.width * this.pageDimensions.width,
                                    pdfHeight: anno.height * this.pageDimensions.height,
                                    normalizedX: anno.x,
                                    normalizedY: anno.y,
                                    screenX: screenPos.x,
                                    screenY: screenPos.y,
                                    screenWidth: screenPos.width,
                                    screenHeight: screenPos.height,
                                    roomId: anno.room_id,
                                    roomLocationId: anno.room_location_id,  // For location annotations
                                    cabinetRunId: anno.cabinet_run_id,  // For cabinet run annotations
                                    cabinetSpecId: anno.cabinet_specification_id,  // For cabinet annotations
                                    viewType: anno.view_type,  // Load view type (plan, elevation, section, detail)
                                    label: anno.text || 'Annotation',
                                    color: anno.color || this.getColorForType(anno.annotation_type),
                                    notes: anno.notes,
                                    pdfPageId: this.pdfPageId,  // Add pdfPageId for context
                                    projectId: this.projectId   // Add projectId for form loading
                                };
                            });

                            console.log(`✓ Loaded ${this.annotations.length} annotations`);
                        }
                    } catch (error) {
                        console.error('Failed to load annotations:', error);
                    }
                },

                // Save annotations
                async saveAnnotations() {
                    console.log('💾 Saving annotations...', this.annotations);

                    try {
                        // Transform annotations to API format
                        const annotationsData = this.annotations.map(anno => ({
                            annotation_type: anno.type,
                            parent_annotation_id: anno.parentId || null,  // CRITICAL: Save parent relationship for hierarchy
                            x: anno.normalizedX,
                            y: anno.normalizedY,
                            width: anno.pdfWidth / this.pageDimensions.width,
                            height: anno.pdfHeight / this.pageDimensions.height,
                            text: anno.label,
                            color: anno.color,
                            room_id: anno.roomId || null,
                            room_location_id: anno.roomLocationId || null,  // For location annotations
                            cabinet_run_id: anno.cabinetRunId || null,  // For cabinet run annotations
                            cabinet_specification_id: anno.cabinetSpecId || null,  // For cabinet annotations
                            view_type: anno.viewType || 'plan',  // Save view type (plan, elevation, section, detail)
                            notes: anno.notes || null,
                            room_type: anno.type,  // Use type as room_type for compatibility
                        }));

                        const response = await fetch(`/api/pdf/page/${this.pdfPageId}/annotations`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                annotations: annotationsData,
                                create_entities: false  // Don't auto-create entities for now
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            console.log(`✓ Saved ${data.count} annotations`);
                            alert(`Successfully saved ${data.count} annotations!`);

                            // Reload annotations to get server-assigned IDs
                            await this.loadAnnotations();
                        } else {
                            throw new Error(data.error || 'Failed to save annotations');
                        }
                    } catch (error) {
                        console.error('Failed to save annotations:', error);
                        alert(`Error saving annotations: ${error.message}`);
                    }
                },

                // Get color for annotation type (for loaded annotations)
                getColorForType(type) {
                    if (type === 'room') return '#f59e0b'; // Amber/Orange (room boundary)
                    if (type === 'location') return '#9333ea'; // Purple
                    if (type === 'cabinet_run') return '#3b82f6'; // Blue
                    return '#10b981'; // Green (cabinet)
                },

                // Context methods (Updated for isolation mode)
                canDrawLocation() {
                    // In room isolation mode: always enabled
                    if (this.isolationMode && this.isolationLevel === 'room') {
                        return this.pdfReady;
                    }
                    // Normal mode: requires room selection + PDF ready
                    return this.activeRoomId && this.pdfReady;
                },

                canDraw() {
                    // In location isolation mode: always enabled
                    if (this.isolationMode && this.isolationLevel === 'location') {
                        return this.pdfReady;
                    }
                    // Normal mode: requires room + location selection + PDF ready
                    return this.activeRoomId && this.activeLocationId && this.pdfReady;
                },

                setDrawMode(mode) {
                    // If turning OFF draw mode, just disable it
                    if (this.drawMode === mode) {
                        this.drawMode = null;
                        return;
                    }

                    // If turning ON draw mode, check for duplicates first
                    const existingAnnotation = this.checkForDuplicateEntity(mode);

                    if (existingAnnotation) {
                        // Duplicate found! Show notification and highlight existing
                        const entityName = existingAnnotation.label || 'This entity';

                        // Show notification
                        new FilamentNotification()
                            .title('Annotation Already Exists')
                            .warning()
                            .body(`${entityName} already has an annotation on this page. Highlighting it now.`)
                            .send();

                        // Highlight existing annotation with pulse effect
                        this.highlightAnnotation(existingAnnotation);

                        // Don't enter draw mode
                        return;
                    }

                    // No duplicate found - proceed with normal draw mode
                    this.drawMode = mode;
                },

                // Helper: Highlight an annotation temporarily
                highlightAnnotation(annotation) {
                    // Add temporary highlight state
                    const originalColor = annotation.color;
                    annotation.color = '#ff0000'; // Red highlight

                    // Force re-render
                    this.renderAnnotations();

                    // Pan to annotation
                    const centerX = annotation.screenX + annotation.screenWidth / 2;
                    const centerY = annotation.screenY + annotation.screenHeight / 2;

                    // Restore original color after 2 seconds
                    setTimeout(() => {
                        annotation.color = originalColor;
                        this.renderAnnotations();
                    }, 2000);

                    console.log(`🎯 Highlighted annotation: ${annotation.label}`);
                },

                clearContext() {
                    this.activeRoomId = null;
                    this.activeRoomName = '';
                    this.activeLocationId = null;
                    this.activeLocationName = '';
                    this.roomSearchQuery = '';
                    this.locationSearchQuery = '';
                    this.drawMode = null;
                },

                getContextLabel() {
                    if (!this.activeRoomName) return 'No context selected';
                    if (!this.activeLocationName) return `🏠 ${this.activeRoomName}`;
                    return `🏠 ${this.activeRoomName} → 📍 ${this.activeLocationName}`;
                },

                // Tree methods
                async loadTree() {
                    this.loading = true;
                    try {
                        const response = await fetch(`/api/projects/${this.projectId}/tree`);
                        this.tree = await response.json();
                        console.log('✓ Tree loaded:', this.tree);
                    } catch (error) {
                        this.error = 'Failed to load project tree';
                        console.error(error);
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshTree() {
                    await this.loadTree();
                },

                toggleNode(nodeId) {
                    const index = this.expandedNodes.indexOf(nodeId);
                    if (index > -1) {
                        this.expandedNodes.splice(index, 1);
                    } else {
                        this.expandedNodes.push(nodeId);
                    }
                },

                isExpanded(nodeId) {
                    return this.expandedNodes.includes(nodeId);
                },

                selectNode(nodeId, type, name, parentRoomId = null, parentLocationId = null, parentCabinetRunId = null) {
                    this.selectedNodeId = nodeId;

                    // Build the full hierarchical path from root to clicked node
                    const path = [];

                    if (type === 'room') {
                        // Room is the root - path contains only the room
                        path.push(nodeId);
                        this.activeRoomId = nodeId;
                        this.activeRoomName = name;
                        this.roomSearchQuery = name;
                        this.activeLocationId = null;
                        this.activeLocationName = '';
                        this.locationSearchQuery = '';
                    } else if (type === 'room_location') {
                        // Location - path includes room and location
                        if (parentRoomId) path.push(parentRoomId);
                        path.push(nodeId);
                        this.activeRoomId = parentRoomId;
                        this.activeLocationId = nodeId;
                        this.activeLocationName = name;
                        this.locationSearchQuery = name;
                    } else if (type === 'cabinet_run') {
                        // Cabinet run - path includes room, location, and cabinet run
                        if (parentRoomId) path.push(parentRoomId);
                        if (parentLocationId) path.push(parentLocationId);
                        path.push(nodeId);
                        this.activeRoomId = parentRoomId;
                        this.activeLocationId = parentLocationId;
                    } else if (type === 'cabinet') {
                        // Cabinet - path includes room, location, cabinet run, and cabinet
                        if (parentRoomId) path.push(parentRoomId);
                        if (parentLocationId) path.push(parentLocationId);
                        if (parentCabinetRunId) path.push(parentCabinetRunId);
                        path.push(nodeId);
                        this.activeRoomId = parentRoomId;
                        this.activeLocationId = parentLocationId;
                    }

                    // Store the complete hierarchical path
                    this.selectedPath = path;

                    console.log('🌳 Selected node:', { nodeId, type, name, path });
                },

                // Show context menu on right-click
                showContextMenu(event, nodeId, nodeType, nodeName, parentRoomId = null, parentLocationId = null) {
                    console.log('🖱️ Right-click detected!', { nodeId, nodeType, nodeName });

                    this.contextMenu = {
                        show: true,
                        x: event.clientX,
                        y: event.clientY,
                        nodeId: nodeId,
                        nodeType: nodeType,
                        nodeName: nodeName,
                        parentRoomId: parentRoomId,
                        parentLocationId: parentLocationId
                    };

                    console.log('✓ Context menu state updated:', this.contextMenu);
                },

                // Delete tree node (Room, Location, or Cabinet Run)
                async deleteTreeNode() {
                    const { nodeId, nodeType, nodeName } = this.contextMenu;

                    if (!confirm(`Are you sure you want to delete "${nodeName}"? This will also delete all associated annotations and data.`)) {
                        this.contextMenu.show = false;
                        return;
                    }

                    console.log(`🗑️ Deleting ${nodeType}:`, nodeId);

                    try {
                        let endpoint = '';

                        if (nodeType === 'room') {
                            endpoint = `/api/project/room/${nodeId}`;
                        } else if (nodeType === 'room_location') {
                            endpoint = `/api/project/location/${nodeId}`;
                        } else if (nodeType === 'cabinet_run') {
                            endpoint = `/api/project/cabinet-run/${nodeId}`;
                        }

                        const response = await fetch(endpoint, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            console.log(`✓ ${nodeType} deleted successfully`);

                            // Close context menu
                            this.contextMenu.show = false;

                            // Refresh tree to show updated structure
                            await this.refreshTree();

                            // Clear active context if deleted node was selected
                            if (this.selectedNodeId === nodeId) {
                                this.clearContext();
                                this.selectedNodeId = null;
                            }
                        } else {
                            throw new Error(data.error || `Failed to delete ${nodeType}`);
                        }
                    } catch (error) {
                        console.error(`Failed to delete ${nodeType}:`, error);
                        alert(`Error deleting ${nodeType}: ${error.message}`);
                        this.contextMenu.show = false;
                    }
                },

                // Autocomplete methods
                searchRooms(query) {
                    console.log('🔍 Searching rooms with query:', query);

                    // Get existing rooms from tree
                    const existingRooms = this.tree ? this.tree.map(room => ({
                        id: room.id,
                        name: room.name,
                        isNew: false
                    })) : [];

                    if (!query || query.trim() === '') {
                        // Empty query: show all existing rooms
                        this.roomSuggestions = existingRooms;
                        console.log(`✓ Showing ${existingRooms.length} existing rooms`);
                    } else {
                        // Filter existing rooms by query (case-insensitive)
                        const lowerQuery = query.toLowerCase();
                        const matchingRooms = existingRooms.filter(room =>
                            room.name.toLowerCase().includes(lowerQuery)
                        );

                        // Add "Create new" option at the top
                        this.roomSuggestions = [
                            { id: 'new_' + Date.now(), name: query, isNew: true },
                            ...matchingRooms
                        ];

                        console.log(`✓ Found ${matchingRooms.length} matching rooms, showing "Create ${query}" option`);
                    }
                },

                async selectRoom(room) {
                    console.log('🏠 Selecting room:', room);

                    // If this is a new room, create it via API first
                    if (room.isNew) {
                        console.log('📝 Creating new room:', room.name);

                        try {
                            const response = await fetch(`/api/project/${this.projectId}/rooms`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify({
                                    name: room.name,
                                    room_type: null // Can be enhanced later with room type selection
                                })
                            });

                            const data = await response.json();

                            if (data.success && data.room) {
                                console.log('✓ Room created successfully:', data.room);

                                // Set active context with the real room ID from database
                                this.activeRoomId = data.room.id;
                                this.activeRoomName = data.room.name;
                                this.roomSearchQuery = data.room.name;
                                this.showRoomDropdown = false;

                                // Refresh tree to show the new room
                                await this.refreshTree();

                                console.log('✓ Room added to project tree');
                            } else {
                                throw new Error(data.error || 'Failed to create room');
                            }
                        } catch (error) {
                            console.error('❌ Failed to create room:', error);
                            alert(`Error creating room: ${error.message}`);
                            this.showRoomDropdown = false;
                        }
                    } else {
                        // Existing room, just set the context
                        this.activeRoomId = room.id;
                        this.activeRoomName = room.name;
                        this.roomSearchQuery = room.name;
                        this.showRoomDropdown = false;
                        console.log('✓ Selected existing room:', room.name);
                    }
                },

                searchLocations(query) {
                    if (!this.activeRoomId) return;
                    // TODO: Implement fuzzy search
                    this.locationSuggestions = [
                        { id: 'new_' + Date.now(), name: query, isNew: true }
                    ];
                },

                selectLocation(location) {
                    this.activeLocationId = location.id;
                    this.activeLocationName = location.name;
                    this.locationSearchQuery = location.name;
                    this.showLocationDropdown = false;
                },

                selectAnnotation(anno) {
                    console.log('Selected annotation:', anno);
                    // Dispatch to Livewire component for editing
                    Livewire.dispatch('edit-annotation', { annotation: anno });
                },

                // NEW: Select annotation context for hierarchical tool enabling
                selectAnnotationContext(anno) {
                    console.log('🎯 Selecting annotation context:', anno.type, anno.label);

                    // Hierarchical context enabling based on annotation type
                    if (anno.type === 'location') {
                        // Clicking a location annotation:
                        // - Sets room context (from the location's parent)
                        // - Sets location context
                        // - Enables: Draw Cabinet Run, Draw Cabinet
                        this.activeRoomId = anno.roomId;
                        this.activeRoomName = anno.roomName || this.getRoomNameById(anno.roomId);
                        this.activeLocationId = anno.id;
                        this.activeLocationName = anno.label;

                        // Update search fields
                        this.roomSearchQuery = this.activeRoomName;
                        this.locationSearchQuery = anno.label;

                        console.log(`✓ Location context set: Room "${this.activeRoomName}" → Location "${anno.label}"`);
                        console.log('✓ Enabled tools: Draw Cabinet Run, Draw Cabinet');
                    }
                    else if (anno.type === 'cabinet_run') {
                        // Clicking a cabinet run annotation:
                        // - Sets room context (from the cabinet run's parent hierarchy)
                        // - Sets location context (parent location)
                        // - Enables: Draw Cabinet (inside this run)
                        this.activeRoomId = anno.roomId;
                        this.activeRoomName = anno.roomName || this.getRoomNameById(anno.roomId);
                        this.activeLocationId = anno.locationId;
                        this.activeLocationName = anno.locationName || this.getLocationNameById(anno.locationId);

                        // Update search fields
                        this.roomSearchQuery = this.activeRoomName;
                        this.locationSearchQuery = this.activeLocationName;

                        console.log(`✓ Cabinet Run context set: Room "${this.activeRoomName}" → Location "${this.activeLocationName}" → Run "${anno.label}"`);
                        console.log('✓ Enabled tools: Draw Cabinet');
                    }
                    else if (anno.type === 'cabinet') {
                        // Clicking a cabinet annotation:
                        // - Sets full hierarchy context
                        // - Enables: Draw Cabinet (sibling cabinets)
                        this.activeRoomId = anno.roomId;
                        this.activeRoomName = anno.roomName || this.getRoomNameById(anno.roomId);
                        this.activeLocationId = anno.locationId;
                        this.activeLocationName = anno.locationName || this.getLocationNameById(anno.locationId);

                        // Update search fields
                        this.roomSearchQuery = this.activeRoomName;
                        this.locationSearchQuery = this.activeLocationName;

                        console.log(`✓ Cabinet context set: Room "${this.activeRoomName}" → Location "${this.activeLocationName}" → Cabinet "${anno.label}"`);
                        console.log('✓ Enabled tools: Draw Cabinet (sibling)');
                    }

                    // Visual feedback: Select the corresponding tree node
                    this.selectedNodeId = anno.id;
                },

                // Helper: Get room name by ID from tree
                getRoomNameById(roomId) {
                    if (!this.tree || !roomId) return '';
                    const room = this.tree.find(r => r.id === roomId);
                    return room ? room.name : '';
                },

                // Helper: Get location name by ID from tree
                getLocationNameById(locationId) {
                    if (!this.tree || !locationId) return '';
                    for (const room of this.tree) {
                        const location = room.children?.find(l => l.id === locationId);
                        if (location) return location.name;
                    }
                    return '';
                },

                // Helper: Find annotation by entity ID on current page
                findAnnotationByEntity(entityType, entityId) {
                    if (!entityId || !this.annotations) return null;

                    console.log(`🔍 [findAnnotationByEntity] Looking for ${entityType} with ID ${entityId}`);

                    // Search through all annotations on this page
                    for (const anno of this.annotations) {
                        // Match based on entity type
                        if (entityType === 'room' && anno.roomId === entityId && anno.type === 'room') {
                            console.log(`✅ Found room annotation:`, anno);
                            return anno;
                        } else if (entityType === 'room_location' && anno.roomLocationId === entityId && anno.type === 'location') {
                            console.log(`✅ Found location annotation:`, anno);
                            return anno;
                        } else if (entityType === 'cabinet_run' && anno.cabinetRunId === entityId && anno.type === 'cabinet_run') {
                            console.log(`✅ Found cabinet run annotation:`, anno);
                            return anno;
                        }
                    }

                    console.log(`❌ No annotation found for ${entityType} with ID ${entityId}`);
                    return null;
                },

                // Helper: Check if entity already has annotation on current page
                // Returns existing annotation if duplicate found, null if safe to draw
                checkForDuplicateEntity(drawMode) {
                    if (!this.annotations) return null;

                    console.log(`🔍 [checkForDuplicateEntity] Checking for duplicates - mode: ${drawMode}`);

                    // Determine entity type and ID based on draw mode
                    let entityType = null;
                    let entityId = null;
                    let annotationType = null;

                    if (drawMode === 'room') {
                        entityType = 'room';
                        entityId = this.activeRoomId;
                        annotationType = 'room';
                    } else if (drawMode === 'location') {
                        entityType = 'room_location';
                        entityId = this.activeLocationId;
                        annotationType = 'location';
                    } else if (drawMode === 'cabinet_run') {
                        entityType = 'cabinet_run';
                        entityId = this.activeLocationId; // For cabinet runs, activeLocationId holds the run ID
                        annotationType = 'cabinet_run';
                    } else if (drawMode === 'cabinet') {
                        // For cabinets, we check cabinet_specification_id (not implemented yet)
                        // For now, allow multiple cabinets
                        return null;
                    }

                    // If no entity selected (creating new), allow drawing
                    if (!entityId) {
                        console.log(`✅ No entity selected - allowing new entity creation`);
                        return null;
                    }

                    // Search for existing annotation with this entity
                    const existing = this.findAnnotationByEntity(entityType, entityId);

                    if (existing) {
                        console.log(`⚠️  Duplicate found! Entity ${entityId} already has annotation:`, existing);
                        return existing;
                    }

                    console.log(`✅ No duplicate found - safe to draw`);
                    return null;
                },

                // Helper: Check if annotation should be visible in current isolation mode
                // Helper: Check if annotation is descendant of parent ID
                isDescendantOf(anno, parentId) {
                    console.log(`🔍 [isDescendantOf] Checking if ${anno?.id} (${anno?.label}) is descendant of ${parentId}`);
                    console.log(`   anno.parentId: ${anno?.parentId}, anno.type: ${anno?.type}`);

                    if (!anno || !parentId) {
                        console.log(`   ❌ Missing anno or parentId`);
                        return false;
                    }

                    // Direct child
                    if (anno.parentId === parentId) {
                        console.log(`   ✅ Direct child! parentId matches`);
                        return true;
                    }

                    // Recursive check through parent chain
                    if (anno.parentId) {
                        const parent = this.annotations.find(a => a.id === anno.parentId);
                        console.log(`   ⬆️ Has parent ID ${anno.parentId}, checking parent recursively...`);
                        return this.isDescendantOf(parent, parentId);
                    }

                    console.log(`   ❌ No parent ID, not a descendant`);
                    return false;
                },

                isAnnotationVisibleInIsolation(anno) {
                    if (!this.isolationMode) return true;

                    console.log(`👁️ [isAnnotationVisibleInIsolation] Checking ${anno.id} (${anno.label}) - type: ${anno.type}`);

                    // FIRST: Check view type compatibility (respects current active view, not isolation view)
                    // This allows users to switch views while in isolation mode
                    if (!this.isAnnotationVisibleInView(anno)) {
                        console.log(`   ❌ Not visible in current view`);
                        return false;
                    }

                    // THEN: Check hierarchy visibility using parent-child relationships
                    if (this.isolationLevel === 'room') {
                        console.log(`   🏠 Room isolation mode, isolated room: ${this.isolatedRoomId}`);

                        // Show the isolated room itself
                        if (anno.id === this.isolatedRoomId) {
                            console.log(`   ✅ This IS the isolated room`);
                            return true;
                        }

                        // Show all descendants of the isolated room
                        const isDescendant = this.isDescendantOf(anno, this.isolatedRoomId);
                        console.log(`   ${isDescendant ? '✅' : '❌'} Is descendant: ${isDescendant}`);
                        return isDescendant;

                    } else if (this.isolationLevel === 'location') {
                        // Show the isolated location itself
                        if (anno.id === this.isolatedLocationId) return true;

                        // Show all descendants of the isolated location (cabinet runs and cabinets only, not parent room)
                        if (this.isDescendantOf(anno, this.isolatedLocationId)) return true;

                        // Do NOT show parent layers (room) - isolation mode should focus only on this location and its children
                        return false;

                    } else if (this.isolationLevel === 'cabinet_run') {
                        // Show the isolated cabinet run itself
                        if (anno.id === this.isolatedCabinetRunId) return true;

                        // Show all descendants of the isolated cabinet run (cabinets only, not parent layers)
                        if (this.isDescendantOf(anno, this.isolatedCabinetRunId)) return true;

                        // Do NOT show parent layers (room/location) - isolation mode should focus only on this run and its children
                        return false;
                    }

                    return true;
                },

                // NEW: Enter Isolation Mode (Illustrator-style layer isolation)
                async enterIsolationMode(anno) {
                    console.log('🔒 Entering isolation mode for:', anno.type, anno.label);

                    // Store current view context for isolation mode
                    this.isolationViewType = this.activeViewType;
                    this.isolationOrientation = this.activeOrientation;
                    console.log(`📐 Isolation view context: ${this.isolationViewType}${this.isolationOrientation ? ` (${this.isolationOrientation})` : ''}`);

                    if (anno.type === 'cabinet_run') {
                        // Isolate at cabinet run level
                        this.isolationMode = true;
                        this.isolationLevel = 'cabinet_run';
                        this.isolatedRoomId = anno.roomId;
                        this.isolatedRoomName = anno.roomName || this.getRoomNameById(anno.roomId);
                        this.isolatedLocationId = anno.locationId;
                        this.isolatedLocationName = anno.locationName || this.getLocationNameById(anno.locationId);
                        this.isolatedCabinetRunId = anno.id;
                        this.isolatedCabinetRunName = anno.label;

                        // Set active context
                        this.activeRoomId = anno.roomId;
                        this.activeRoomName = this.isolatedRoomName;
                        this.activeLocationId = anno.locationId;
                        this.activeLocationName = this.isolatedLocationName;

                        // Update search fields
                        this.roomSearchQuery = this.isolatedRoomName;
                        this.locationSearchQuery = this.isolatedLocationName;

                        console.log(`✓ Cabinet Run isolation: 🏠 ${this.isolatedRoomName} → 📍 ${this.isolatedLocationName} → 🗄️ ${anno.label}`);
                    } else if (anno.type === 'location') {
                        // Isolate at location level
                        this.isolationMode = true;
                        this.isolationLevel = 'location';
                        this.isolatedRoomId = anno.roomId;
                        this.isolatedRoomName = anno.roomName || this.getRoomNameById(anno.roomId);
                        this.isolatedLocationId = anno.id;
                        this.isolatedLocationName = anno.label;
                        this.isolatedCabinetRunId = null;
                        this.isolatedCabinetRunName = '';

                        // Set active context
                        this.activeRoomId = anno.roomId;
                        this.activeRoomName = this.isolatedRoomName;
                        this.activeLocationId = anno.id;
                        this.activeLocationName = anno.label;

                        // Update search fields
                        this.roomSearchQuery = this.isolatedRoomName;
                        this.locationSearchQuery = anno.label;

                        console.log(`✓ Location isolation: 🏠 ${this.isolatedRoomName} → 📍 ${anno.label}`);
                    } else {
                        // For any other type, treat as room isolation
                        // This handles clicking on room annotations directly
                        // For room-type annotations, use the annotation's own ID as the roomId
                        // For other types, use their roomId property
                        const roomId = anno.type === 'room' ? anno.id : anno.roomId;
                        const roomName = anno.type === 'room' ? anno.label : (anno.roomName || this.getRoomNameById(anno.roomId));

                        this.isolationMode = true;
                        this.isolationLevel = 'room';
                        this.isolatedRoomId = roomId;
                        this.isolatedRoomName = roomName;
                        this.isolatedLocationId = null;
                        this.isolatedLocationName = '';
                        this.isolatedCabinetRunId = null;
                        this.isolatedCabinetRunName = '';

                        // Set active context
                        this.activeRoomId = roomId;
                        this.activeRoomName = roomName;
                        this.activeLocationId = null;
                        this.activeLocationName = '';
                        this.locationSearchQuery = '';

                        // Update search field
                        this.roomSearchQuery = roomName;

                        console.log(`✓ Room isolation: 🏠 ${roomName}`);
                    }

                    // Expand the isolated node in tree
                    if (!this.expandedNodes.includes(this.isolatedRoomId)) {
                        this.expandedNodes.push(this.isolatedRoomId);
                    }
                    if (this.isolatedLocationId && !this.expandedNodes.includes(this.isolatedLocationId)) {
                        this.expandedNodes.push(this.isolatedLocationId);
                    }
                    if (this.isolatedCabinetRunId && !this.expandedNodes.includes(this.isolatedCabinetRunId)) {
                        this.expandedNodes.push(this.isolatedCabinetRunId);
                    }

                    // Select the isolated node
                    this.selectedNodeId = this.isolationLevel === 'cabinet_run' ? this.isolatedCabinetRunId :
                                          this.isolationLevel === 'location' ? this.isolatedLocationId :
                                          this.isolatedRoomId;

                    // Clear previous hidden annotations
                    this.hiddenAnnotations = [];

                    // Populate hidden annotations based on isolation mode using helper function
                    this.annotations.forEach(a => {
                        if (!this.isAnnotationVisibleInIsolation(a)) {
                            console.log(`👁️ [ENTER ISOLATION] Hiding annotation ${a.id} (${a.label} - type: ${a.type})`);
                            this.hiddenAnnotations.push(a.id);
                        }
                    });

                    console.log(`👁️ [ENTER ISOLATION] Hidden annotations: [${this.hiddenAnnotations.join(', ')}]`);

                    // Zoom to fit annotation box on screen (double-click behavior)
                    await this.zoomToFitAnnotation(anno);

                    // Update the isolation mask to show visible annotations clearly
                    this.updateIsolationMask();
                },

                // NEW: Exit Isolation Mode
                async exitIsolationMode() {
                    console.log('🔓 Exiting isolation mode');

                    // Clear isolation state
                    this.isolationMode = false;
                    this.isolationLevel = null;
                    this.isolatedRoomId = null;
                    this.isolatedRoomName = '';
                    this.isolatedLocationId = null;
                    this.isolatedLocationName = '';
                    this.isolatedCabinetRunId = null;
                    this.isolatedCabinetRunName = '';
                    this.isolationViewType = null;
                    this.isolationOrientation = null;

                    // Clear active context
                    this.clearContext();

                    // Deselect node
                    this.selectedNodeId = null;

                    // Clear hidden annotations array (show all annotations)
                    console.log(`👁️ [EXIT ISOLATION] Clearing hidden annotations (was: [${this.hiddenAnnotations.join(', ')}])`);
                    this.hiddenAnnotations = [];

                    // Reset zoom to fit full page (100%)
                    await this.resetZoom();

                    console.log('✓ Returned to normal view with reset zoom');

                    // Update the isolation mask after exiting
                    this.updateIsolationMask();
                },

                // Update the SVG mask to exclude visible annotations from darkening blur
                updateIsolationMask() {
                    const maskRects = document.getElementById('maskRects');
                    if (!maskRects) return;

                    // Clear existing rects
                    maskRects.innerHTML = '';

                    // Get all visible annotations (not in hiddenAnnotations array)
                    const visibleAnnotations = this.annotations.filter(a => !this.hiddenAnnotations.includes(a.id));

                    // Create a black rect for each visible annotation
                    visibleAnnotations.forEach(anno => {
                        if (anno.screenX !== undefined && anno.screenWidth > 0 && anno.screenHeight > 0) {
                            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                            rect.setAttribute('x', (anno.screenX || 0) - 15);
                            rect.setAttribute('y', (anno.screenY || 0) - 15);
                            rect.setAttribute('width', (anno.screenWidth || 0) + 30);
                            rect.setAttribute('height', (anno.screenHeight || 0) + 30);
                            rect.setAttribute('fill', 'black');
                            rect.setAttribute('rx', '8');
                            rect.setAttribute('filter', 'url(#feather)');
                            maskRects.appendChild(rect);
                        }
                    });
                },

                // Edit annotation (NEW - Full CRUD)
                editAnnotation(anno) {
                    console.log('✏️ Editing annotation:', anno);
                    // Add pdfPageId and projectId to annotation for Livewire context loading
                    const annotationWithContext = {
                        ...anno,
                        pdfPageId: this.pdfPageId,
                        projectId: this.projectId
                    };
                    // Dispatch to Livewire component for editing
                    Livewire.dispatch('edit-annotation', { annotation: annotationWithContext });
                },

                // Delete annotation (NEW - Full CRUD)
                async deleteAnnotation(anno) {
                    if (!confirm(`Delete "${anno.label}"?`)) {
                        return;
                    }

                    console.log('🗑️ Deleting annotation:', anno);

                    // If it's a temporary annotation (not saved yet), just remove from array
                    if (anno.id.toString().startsWith('temp_')) {
                        this.annotations = this.annotations.filter(a => a.id !== anno.id);
                        console.log('✓ Temporary annotation removed from local state');
                        return;
                    }

                    // Otherwise, delete from server
                    try {
                        const response = await fetch(`/api/pdf/page/annotations/${anno.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Remove from local state
                            this.annotations = this.annotations.filter(a => a.id !== anno.id);
                            console.log('✓ Annotation deleted successfully');

                            // Refresh tree to update counts
                            await this.refreshTree();
                        } else {
                            throw new Error(data.error || 'Failed to delete annotation');
                        }
                    } catch (error) {
                        console.error('Failed to delete annotation:', error);
                        alert(`Error deleting annotation: ${error.message}`);
                    }
                },

                // Zoom methods
                zoomIn() {
                    const newZoom = Math.min(this.zoomLevel + 0.25, this.zoomMax);
                    this.setZoom(newZoom);
                },

                zoomOut() {
                    const newZoom = Math.max(this.zoomLevel - 0.25, this.zoomMin);
                    this.setZoom(newZoom);
                },

                resetZoom() {
                    this.setZoom(1.0);
                },

                async setZoom(level) {
                    this.zoomLevel = level;

                    // Invalidate overlay rect cache
                    this._overlayRect = null;

                    // Re-render PDF at new zoom level for high-res display
                    await this.displayPdf();

                    // Wait for DOM to update with new canvas size
                    await this.$nextTick();
                    await new Promise(resolve => setTimeout(resolve, 100));

                    // Invalidate cache again to force fresh rect
                    this._overlayRect = null;

                    // Re-render annotations at new zoom level
                    this.updateAnnotationPositions();

                    // Update isolation mask if in isolation mode
                    if (this.isolationMode) {
                        this.updateIsolationMask();
                    }

                    console.log(`🔍 Zoom set to ${Math.round(level * 100)}%`);
                },

                // Update annotation screen positions for current zoom level
                updateAnnotationPositions() {
                    if (!this.pageDimensions) return;

                    // Mutate annotations in-place to preserve Alpine reactivity
                    this.annotations.forEach(anno => {
                        const screenPos = this.pdfToScreen(
                            anno.pdfX,
                            anno.pdfY,
                            anno.pdfWidth,
                            anno.pdfHeight
                        );

                        anno.screenX = screenPos.x;
                        anno.screenY = screenPos.y;
                        anno.screenWidth = screenPos.width;
                        anno.screenHeight = screenPos.height;
                    });
                },

                getZoomPercentage() {
                    return Math.round(this.zoomLevel * 100);
                },

                // Zoom to fit annotation box on screen
                async zoomToFitAnnotation(anno) {
                    console.log('🔍 Zooming to fit annotation:', anno.label);

                    // Get container dimensions
                    const container = this.$refs.annotationOverlay;
                    if (!container) {
                        console.warn('⚠️ Container not found for zoom calculation');
                        return;
                    }

                    const containerRect = container.getBoundingClientRect();
                    const containerWidth = containerRect.width;
                    const containerHeight = containerRect.height;

                    // Add padding around annotation (20% margin)
                    const paddingFactor = 0.8; // 80% of container = 20% padding total

                    // Calculate required zoom to fit annotation width and height
                    const zoomX = (containerWidth * paddingFactor) / anno.screenWidth;
                    const zoomY = (containerHeight * paddingFactor) / anno.screenHeight;

                    // Use the smaller zoom to ensure both dimensions fit
                    let targetZoom = Math.min(zoomX, zoomY);

                    // Clamp to zoom limits
                    targetZoom = Math.max(this.zoomMin, Math.min(targetZoom, this.zoomMax));

                    // Apply the zoom
                    await this.setZoom(targetZoom);

                    // After zoom, scroll annotation to center of viewport
                    await this.$nextTick();

                    // Calculate annotation center in screen coordinates at new zoom
                    const annoScreenPos = this.pdfToScreen(
                        anno.pdfX + anno.pdfWidth / 2,
                        anno.pdfY - anno.pdfHeight / 2, // PDF Y is inverted
                        0,
                        0
                    );

                    // Get the canvas element
                    const canvas = this.$refs.pdfEmbed?.querySelector('canvas');
                    if (canvas) {
                        // Calculate scroll position to center the annotation
                        const canvasRect = canvas.getBoundingClientRect();
                        const scrollContainer = container.parentElement;

                        if (scrollContainer) {
                            // Center the annotation in viewport
                            scrollContainer.scrollLeft = annoScreenPos.x - containerWidth / 2;
                            scrollContainer.scrollTop = annoScreenPos.y - containerHeight / 2;
                        }
                    }

                    console.log(`✓ Zoomed to ${Math.round(targetZoom * 100)}% and centered annotation`);
                },

                // Pagination methods (NEW - Phase 2)
                async nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                        this.updatePdfPageId();
                        console.log(`📄 Navigating to page ${this.currentPage}, pdfPageId: ${this.pdfPageId}`);
                        await this.displayPdf();
                        await this.loadAnnotations();
                    }
                },

                async previousPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                        this.updatePdfPageId();
                        console.log(`📄 Navigating to page ${this.currentPage}, pdfPageId: ${this.pdfPageId}`);
                        await this.displayPdf();
                        await this.loadAnnotations();
                    }
                },

                async goToPage(pageNum) {
                    if (pageNum >= 1 && pageNum <= this.totalPages) {
                        this.currentPage = pageNum;
                        this.updatePdfPageId();
                        console.log(`📄 Navigating to page ${this.currentPage}, pdfPageId: ${this.pdfPageId}`);
                        await this.displayPdf();
                        await this.loadAnnotations();
                    }
                },

                // Update pdfPageId based on currentPage using pageMap (NEW - Phase 5)
                updatePdfPageId() {
                    const newPdfPageId = this.pageMap[this.currentPage];
                    if (newPdfPageId) {
                        this.pdfPageId = newPdfPageId;
                        console.log(`✓ Updated pdfPageId to ${this.pdfPageId} for page ${this.currentPage}`);
                    } else {
                        console.warn(`⚠️ No pdfPageId found for page ${this.currentPage} in pageMap`);
                    }
                },

                // Helper: Build hierarchical tree from flat annotations using parentId
                buildAnnotationTree(annotations) {
                    // Create a map for quick lookup
                    const annoMap = new Map();
                    annotations.forEach(anno => {
                        annoMap.set(anno.id, { ...anno, children: [] });
                    });

                    // Build the tree by connecting children to parents
                    const rootNodes = [];
                    annoMap.forEach(anno => {
                        if (anno.parentId && annoMap.has(anno.parentId)) {
                            // This annotation has a parent - add it as a child
                            annoMap.get(anno.parentId).children.push(anno);
                        } else {
                            // This is a root node (no parent or parent not in this page)
                            rootNodes.push(anno);
                        }
                    });

                    return rootNodes;
                },

                // Group annotations by page number for page view
                getPageGroupedAnnotations() {
                    // Get all unique page numbers from annotations and pageMap
                    const pages = new Map();

                    // Initialize pages from pageMap (all available pages)
                    Object.keys(this.pageMap).forEach(pageNum => {
                        pages.set(parseInt(pageNum), {
                            pageNumber: parseInt(pageNum),
                            annotations: []
                        });
                    });

                    // Add annotations to their respective pages
                    this.annotations.forEach(anno => {
                        const pageNum = this.currentPage; // All current annotations are on current page
                        if (pages.has(pageNum)) {
                            pages.get(pageNum).annotations.push(anno);
                        } else {
                            pages.set(pageNum, {
                                pageNumber: pageNum,
                                annotations: [anno]
                            });
                        }
                    });

                    // Build hierarchical tree for each page
                    pages.forEach((page, pageNum) => {
                        page.annotations = this.buildAnnotationTree(page.annotations);
                    });

                    // Convert to array and sort by page number
                    return Array.from(pages.values()).sort((a, b) => a.pageNumber - b.pageNumber);
                },

                // Check if annotation is visible (viewport culling + isolation mode filtering)
                isAnnotationVisible(anno) {
                    // ISOLATION MODE FILTERING (NEW)
                    if (this.isolationMode) {
                        // ALWAYS hide the selected annotation (the one being isolated)
                        if (anno.id === this.selectedNodeId) {
                            return false;
                        }

                        if (this.isolationLevel === 'room') {
                            // Room isolation: show only locations, cabinet runs, and cabinets in this room
                            if (anno.type === 'location' && anno.roomId === this.isolatedRoomId) {
                                // Pass through to viewport check
                            } else if (anno.type === 'cabinet_run' && anno.roomId === this.isolatedRoomId) {
                                // Pass through to viewport check
                            } else if (anno.type === 'cabinet' && anno.roomId === this.isolatedRoomId) {
                                // Pass through to viewport check
                            } else {
                                return false; // Hide all other annotations
                            }
                        } else if (this.isolationLevel === 'location') {
                            // Location isolation: show only cabinet runs and cabinets in this location
                            if (anno.type === 'cabinet_run' && anno.locationId === this.isolatedLocationId) {
                                // Pass through to viewport check
                            } else if (anno.type === 'cabinet' && anno.locationId === this.isolatedLocationId) {
                                // Pass through to viewport check
                            } else {
                                return false; // Hide all other annotations
                            }
                        }
                    }

                    // VIEWPORT CULLING (existing logic)
                    const rect = this.getOverlayRect();
                    if (!rect) return true; // Show by default if can't determine

                    // Get annotation bounds
                    const annoLeft = anno.screenX;
                    const annoTop = anno.screenY;
                    const annoRight = anno.screenX + anno.screenWidth;
                    const annoBottom = anno.screenY + anno.screenHeight;

                    // Check if annotation is within visible viewport (with buffer)
                    const buffer = 100; // 100px buffer outside viewport
                    const viewportWidth = rect.width;
                    const viewportHeight = rect.height;

                    const isVisible = (
                        annoRight >= -buffer &&
                        annoLeft <= viewportWidth + buffer &&
                        annoBottom >= -buffer &&
                        annoTop <= viewportHeight + buffer
                    );

                    return isVisible;
                }
            }));
        });
    </script>
@endonce
