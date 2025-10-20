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
        pdfPageId: {{ $pdfPageId }},
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
    <div class="context-bar sticky top-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4 shadow-md">
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
                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
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
                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
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
    <div x-show="isolationMode" x-transition class="isolation-breadcrumb bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 border-b-4 border-primary-500 px-6 py-4 shadow-lg">
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
                            @contextmenu.prevent.stop="showContextMenu($event, room.id, 'room', room.name)"
                            :class="selectedNodeId === room.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
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
                        <div x-show="isExpanded(room.id)" class="ml-6 mt-1">
                            <template x-for="location in room.children" :key="location.id">
                                <div class="tree-node mb-1">
                                    <!-- Location Level -->
                                    <div
                                        @click="selectNode(location.id, 'room_location', location.name, room.id)"
                                        @contextmenu.prevent.stop="showContextMenu($event, location.id, 'room_location', location.name, room.id)"
                                        :class="selectedNodeId === location.id ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
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
                                    <div x-show="isExpanded(location.id)" class="ml-6 mt-1">
                                        <template x-for="run in location.children" :key="run.id">
                                            <div class="tree-node mb-1">
                                                <!-- Cabinet Run Level -->
                                                <div
                                                    @click="selectNode(run.id, 'cabinet_run', run.name, room.id, location.id)"
                                                    @contextmenu.prevent.stop="showContextMenu($event, run.id, 'cabinet_run', run.name, room.id, location.id)"
                                                    :class="selectedNodeId === run.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
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
                    @click="roomSearchQuery = ''; showRoomDropdown = true"
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

                        <!-- Annotations on this page -->
                        <div x-show="isExpanded('page_' + page.pageNumber)" class="ml-6 mt-1">
                            <template x-for="anno in page.annotations" :key="anno.id">
                                <div class="tree-node mb-1">
                                    <div
                                        @click="selectAnnotation(anno)"
                                        class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors hover:bg-gray-100 dark:hover:bg-gray-700 text-sm"
                                    >
                                        <span x-text="anno.type === 'location' ? '📍' : anno.type === 'cabinet_run' ? '📦' : '🗄️'"></span>
                                        <span class="flex-1" x-text="anno.label"></span>
                                        <span class="text-xs text-gray-500" x-text="anno.roomName || 'No room'"></span>
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

                <!-- Isolation Mode Dimming Overlay (NEW - Filament-style backdrop) -->
                <div
                    x-show="isolationMode"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm pointer-events-none"
                    style="z-index: 5;"
                >
                </div>

                <!-- Annotation Overlay (HTML Elements) -->
                <div
                    x-ref="annotationOverlay"
                    @mousedown="startDrawing($event)"
                    @mousemove="updateDrawing($event)"
                    @mouseup="finishDrawing($event)"
                    @mouseleave="cancelDrawing($event)"
                    :class="drawMode ? 'pointer-events-auto cursor-crosshair' : 'pointer-events-none'"
                    :style="`z-index: 10; transform: scale(${zoomLevel}); transform-origin: top left;`"
                    class="annotation-overlay absolute top-0 left-0 w-full h-full"
                >
                    <!-- Existing Annotations -->
                    <template x-for="anno in annotations" :key="anno.id">
                        <div
                            x-show="isAnnotationVisible(anno)"
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
                                cursor: pointer;
                                transition: all 0.2s;
                                will-change: transform;
                            `"
                            @click="selectAnnotationContext(anno)"
                            @dblclick.stop="enterIsolationMode(anno)"
                            @mouseenter="$el.style.background = anno.color + '66'; showMenu = true"
                            @mouseleave="$el.style.background = anno.color + '33'; showMenu = false"
                            class="annotation-marker group"
                        >
                            <!-- Annotation Label -->
                            <div class="annotation-label absolute -top-10 left-0 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg text-base font-bold whitespace-nowrap shadow-xl border-2" style="color: var(--gray-900); border-color: var(--primary-400);">
                                <span x-text="anno.label" class="dark:text-white"></span>
                            </div>

                            <!-- Hover Action Menu -->
                            <div
                                x-show="showMenu"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute -top-10 -right-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl z-50 flex gap-1 p-1"
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

                // Isolation Mode State (NEW - Illustrator-style layer isolation)
                isolationMode: false,           // Whether we're in isolation mode
                isolationLevel: null,           // 'room' or 'location'
                isolatedRoomId: null,          // Room being isolated
                isolatedRoomName: '',          // Name of isolated room
                isolatedLocationId: null,      // Location being isolated (if in location isolation)
                isolatedLocationName: '',      // Name of isolated location

                // Tree State
                tree: [],
                expandedNodes: [],
                selectedNodeId: null,
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
                        window.addEventListener('annotation-updated', (event) => {
                            const updatedAnnotation = event.detail.annotation;
                            const index = this.annotations.findIndex(a => a.id === updatedAnnotation.id);
                            if (index !== -1) {
                                this.annotations[index] = {
                                    ...this.annotations[index],
                                    label: updatedAnnotation.label,
                                    notes: updatedAnnotation.notes,
                                    measurementWidth: updatedAnnotation.measurementWidth,
                                    measurementHeight: updatedAnnotation.measurementHeight,
                                    roomId: updatedAnnotation.roomId,
                                    roomName: updatedAnnotation.roomName,
                                    locationId: updatedAnnotation.locationId,
                                    locationName: updatedAnnotation.locationName,
                                    cabinetRunId: updatedAnnotation.cabinetRunId
                                };
                                console.log('✓ Annotation updated from Livewire:', updatedAnnotation);
                                // Re-render annotations to show updated data
                                this.renderAnnotations();
                            }
                        });

                        // Listen for annotation deletion from Livewire
                        window.addEventListener('annotation-deleted', (event) => {
                            const annotationId = event.detail.annotationId;
                            const index = this.annotations.findIndex(a => a.id === annotationId);
                            if (index !== -1) {
                                // Remove annotation from array
                                this.annotations.splice(index, 1);
                                console.log('✓ Annotation deleted via Livewire:', annotationId);
                                // Re-render happens automatically via Alpine reactivity
                                // Refresh tree to update counts
                                this.refreshTree();
                            }
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

                    const embedContainer = this.$refs.pdfEmbed;

                    try {
                        // Load PDF document
                        const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
                        const pdf = await loadingTask.promise;

                        // Get the specific page
                        const page = await pdf.getPage(this.currentPage);

                        // Get unscaled viewport for dimension reference
                        const unscaledViewport = page.getViewport({ scale: 1.0 });

                        // Calculate scale to fit container width
                        const containerWidth = embedContainer.clientWidth;
                        const scale = containerWidth / unscaledViewport.width;
                        const scaledViewport = page.getViewport({ scale });

                        // Create canvas with scaled dimensions
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.width = scaledViewport.width;
                        canvas.height = scaledViewport.height;
                        canvas.style.width = '100%';
                        canvas.style.height = 'auto';
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
                    const canProceed = this.drawMode === 'location'
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

                // Create annotation from drawn rectangle
                createAnnotation(screenRect) {
                    // Convert screen coordinates to PDF coordinates
                    const pdfTopLeft = this.screenToPdf(screenRect.x, screenRect.y);
                    const pdfBottomRight = this.screenToPdf(
                        screenRect.x + screenRect.width,
                        screenRect.y + screenRect.height
                    );

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
                        locationId: this.activeLocationId,
                        locationName: this.activeLocationName,
                        label: this.generateAnnotationLabel(),
                        color: this.getDrawColor(),
                        createdAt: new Date(),
                        pdfPageId: this.pdfPageId,  // Add pdfPageId for context loading
                        projectId: this.projectId    // Add projectId for form loading
                    };

                    this.annotations.push(annotation);
                    console.log('✓ Annotation created:', annotation);

                    // Dispatch to Livewire for editing annotation details
                    Livewire.dispatch('edit-annotation', { annotation: annotation });
                },

                // Generate auto-incrementing label
                generateAnnotationLabel() {
                    if (this.drawMode === 'location') {
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
                                    locationId: anno.cabinet_run_id,  // Map cabinet_run_id to locationId
                                    label: anno.text || 'Annotation',
                                    color: anno.color || this.getColorForType(anno.annotation_type),
                                    notes: anno.notes
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
                            x: anno.normalizedX,
                            y: anno.normalizedY,
                            width: anno.normalizedX ? (anno.pdfWidth / this.pageDimensions.width) : 0,
                            height: anno.normalizedY ? (anno.pdfHeight / this.pageDimensions.height) : 0,
                            text: anno.label,
                            color: anno.color,
                            room_id: anno.roomId,
                            cabinet_run_id: anno.locationId,  // Map locationId to cabinet_run_id
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
                    this.drawMode = this.drawMode === mode ? null : mode;
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

                selectNode(nodeId, type, name, parentRoomId = null, parentLocationId = null) {
                    this.selectedNodeId = nodeId;

                    if (type === 'room') {
                        this.activeRoomId = nodeId;
                        this.activeRoomName = name;
                        this.roomSearchQuery = name;
                        this.activeLocationId = null;
                        this.activeLocationName = '';
                        this.locationSearchQuery = '';
                    } else if (type === 'room_location') {
                        this.activeRoomId = parentRoomId;
                        this.activeLocationId = nodeId;
                        this.activeLocationName = name;
                        this.locationSearchQuery = name;
                    } else if (type === 'cabinet_run') {
                        this.activeRoomId = parentRoomId;
                        this.activeLocationId = parentLocationId;
                        // Cabinet runs don't set active context, just selected
                    }
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
                    // TODO: Implement fuzzy search
                    this.roomSuggestions = [
                        { id: 'new_' + Date.now(), name: query, isNew: true }
                    ];
                },

                selectRoom(room) {
                    this.activeRoomId = room.id;
                    this.activeRoomName = room.name;
                    this.roomSearchQuery = room.name;
                    this.showRoomDropdown = false;
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

                // NEW: Enter Isolation Mode (Illustrator-style layer isolation)
                enterIsolationMode(anno) {
                    console.log('🔒 Entering isolation mode for:', anno.type, anno.label);

                    if (anno.type === 'location') {
                        // Isolate at location level
                        this.isolationMode = true;
                        this.isolationLevel = 'location';
                        this.isolatedRoomId = anno.roomId;
                        this.isolatedRoomName = anno.roomName || this.getRoomNameById(anno.roomId);
                        this.isolatedLocationId = anno.id;
                        this.isolatedLocationName = anno.label;

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
                        const roomId = anno.type === 'room' ? anno.id : anno.roomId;
                        const roomName = anno.type === 'room' ? anno.label : (anno.roomName || this.getRoomNameById(anno.roomId));

                        this.isolationMode = true;
                        this.isolationLevel = 'room';
                        this.isolatedRoomId = roomId;
                        this.isolatedRoomName = roomName;
                        this.isolatedLocationId = null;
                        this.isolatedLocationName = '';

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

                    // Select the isolated node
                    this.selectedNodeId = this.isolationLevel === 'location' ? this.isolatedLocationId : this.isolatedRoomId;
                },

                // NEW: Exit Isolation Mode
                exitIsolationMode() {
                    console.log('🔓 Exiting isolation mode');

                    // Clear isolation state
                    this.isolationMode = false;
                    this.isolationLevel = null;
                    this.isolatedRoomId = null;
                    this.isolatedRoomName = '';
                    this.isolatedLocationId = null;
                    this.isolatedLocationName = '';

                    // Clear active context
                    this.clearContext();

                    // Deselect node
                    this.selectedNodeId = null;

                    console.log('✓ Returned to normal view');
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

                setZoom(level) {
                    this.zoomLevel = level;

                    // Invalidate overlay rect cache
                    this._overlayRect = null;

                    // Apply responsive CSS transform to PDF canvas
                    const canvas = this.$refs.pdfEmbed?.querySelector('canvas');
                    const pdfEmbed = this.$refs.pdfEmbed;

                    if (canvas && pdfEmbed) {
                        // Apply zoom transform to canvas
                        canvas.style.transform = `scale(${level})`;
                        canvas.style.transformOrigin = 'top left';

                        // Update container size to accommodate scaled canvas
                        const scaledWidth = canvas.width * level;
                        const scaledHeight = canvas.height * level;
                        pdfEmbed.style.minWidth = `${scaledWidth}px`;
                        pdfEmbed.style.minHeight = `${scaledHeight}px`;
                    }

                    // Re-render annotations at new zoom level
                    this.updateAnnotationPositions();

                    console.log(`🔍 Zoom set to ${Math.round(level * 100)}%`);
                },

                // Update annotation screen positions for current zoom level
                updateAnnotationPositions() {
                    if (!this.pageDimensions) return;

                    this.annotations = this.annotations.map(anno => {
                        const screenPos = this.pdfToScreen(
                            anno.pdfX,
                            anno.pdfY,
                            anno.pdfWidth,
                            anno.pdfHeight
                        );

                        return {
                            ...anno,
                            screenX: screenPos.x,
                            screenY: screenPos.y,
                            screenWidth: screenPos.width,
                            screenHeight: screenPos.height
                        };
                    });
                },

                getZoomPercentage() {
                    return Math.round(this.zoomLevel * 100);
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

                    // Convert to array and sort by page number
                    return Array.from(pages.values()).sort((a, b) => a.pageNumber - b.pageNumber);
                },

                // Check if annotation is visible (viewport culling + isolation mode filtering)
                isAnnotationVisible(anno) {
                    // ISOLATION MODE FILTERING (NEW)
                    if (this.isolationMode) {
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
