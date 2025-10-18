@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
])

@php
    // Generate unique ID for this viewer instance
    $viewerId = 'canvasViewer_' . $pdfPageId . '_' . uniqid();
@endphp

<div
    x-cloak
    x-data="annotationSystemV2({
        pdfUrl: '{{ $pdfUrl }}',
        pageNumber: {{ $pageNumber }},
        pdfPageId: {{ $pdfPageId }},
        projectId: {{ $projectId }}
    })"
    x-init="init()"
    wire:ignore
    class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900"
>
    <!-- Context Bar (Top - Sticky) -->
    <div class="context-bar sticky top-0 z-50 bg-purple-800 border-b border-purple-700 p-4">
        <div class="flex items-center gap-4 flex-wrap">
            <!-- V2 Header Title -->
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    🚀 V2 Annotation System - Page {{ $pageNumber }}
                    <span class="px-2 py-1 text-xs rounded font-semibold bg-purple-600">Context-First</span>
                </h2>
            </div>

            <!-- Project Context Display -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-purple-200">📍 Context:</span>
                <span class="text-sm text-purple-100" x-text="getContextLabel()"></span>
            </div>

            <!-- Room Autocomplete -->
            <div class="relative flex-1 max-w-xs">
                <label class="block text-xs font-medium text-purple-200 mb-1">Room</label>
                <input
                    type="text"
                    x-model="roomSearchQuery"
                    @input="searchRooms($event.target.value)"
                    @focus="showRoomDropdown = true"
                    @click.away="showRoomDropdown = false"
                    placeholder="Type to search or create..."
                    class="w-full px-3 py-2 rounded-lg border border-purple-600 bg-purple-900 text-white text-sm"
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
                <label class="block text-xs font-medium text-purple-200 mb-1">Location</label>
                <input
                    type="text"
                    x-model="locationSearchQuery"
                    @input="searchLocations($event.target.value)"
                    @focus="showLocationDropdown = true"
                    @click.away="showLocationDropdown = false"
                    :disabled="!activeRoomId"
                    placeholder="Select room first..."
                    class="w-full px-3 py-2 rounded-lg border border-purple-600 bg-purple-900 text-white text-sm disabled:opacity-50 disabled:cursor-not-allowed"
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

            <!-- Draw Mode Buttons -->
            <div class="flex items-center gap-2 ml-auto">
                <button
                    @click="setDrawMode('cabinet_run')"
                    :class="drawMode === 'cabinet_run' ? 'bg-blue-600 text-white' : 'bg-purple-700 text-purple-200'"
                    :disabled="!canDraw()"
                    class="px-4 py-2 rounded-lg hover:bg-blue-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Cabinet Run"
                >
                    📦 Draw Run
                </button>

                <button
                    @click="setDrawMode('cabinet')"
                    :class="drawMode === 'cabinet' ? 'bg-green-600 text-white' : 'bg-purple-700 text-purple-200'"
                    :disabled="!canDraw()"
                    class="px-4 py-2 rounded-lg hover:bg-green-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Cabinet"
                >
                    🗄️ Draw Cabinet
                </button>

                <button
                    @click="clearContext()"
                    class="px-4 py-2 rounded-lg bg-purple-700 text-purple-200 hover:bg-purple-600 transition-colors text-sm"
                    title="Clear Context"
                >
                    ✖️ Clear
                </button>

                <button
                    @click="saveAnnotations()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                    title="Save All Annotations"
                >
                    💾 Save
                </button>

                <button
                    @click="$dispatch('close-v2-modal')"
                    class="px-4 py-2 bg-purple-700 text-white rounded-full hover:bg-purple-600 transition-colors"
                    title="Close Viewer"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Context Hint -->
        <div x-show="!canDraw()" class="mt-2 text-xs text-orange-400">
            ℹ️ Select a Room and Location before drawing
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar (Project Tree) -->
        <div class="tree-sidebar w-64 border-r border-gray-300 dark:border-gray-600 overflow-y-auto bg-gray-50 dark:bg-gray-800 p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Project Structure</h3>
                <button
                    @click="refreshTree()"
                    class="text-xs text-blue-600 hover:text-blue-700"
                    title="Refresh tree"
                >
                    🔄
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="text-center py-4">
                <span class="text-sm text-gray-500">Loading...</span>
            </div>

            <!-- Error State -->
            <div x-show="error" class="text-center py-4">
                <span class="text-sm text-red-600" x-text="error"></span>
            </div>

            <!-- Tree Content -->
            <div x-show="!loading && !error && tree">
                <template x-for="room in tree" :key="room.id">
                    <div class="tree-node mb-2">
                        <!-- Room Level -->
                        <div
                            @click="selectNode(room.id, 'room', room.name)"
                            :class="selectedNodeId === room.id ? 'bg-purple-100 dark:bg-purple-900 text-purple-900 dark:text-purple-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
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
                                class="badge bg-purple-600 text-white px-2 py-0.5 rounded-full text-xs"
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
        </div>

        <!-- PDF Viewer (Center) -->
        <div class="pdf-viewer-container flex-1 bg-white dark:bg-gray-900 overflow-auto relative">
            <!-- PDF Canvas (Background) -->
            <canvas
                x-ref="pdfCanvas"
                class="absolute top-0 left-0"
            ></canvas>

            <!-- Drawing Canvas (Overlay) -->
            <canvas
                x-ref="drawCanvas"
                @mousedown="startDrawing($event)"
                @mousemove="draw($event)"
                @mouseup="stopDrawing($event)"
                @mouseleave="stopDrawing($event)"
                :width="pdfPage ? pdfPage.getViewport({ scale: scale }).width : 0"
                :height="pdfPage ? pdfPage.getViewport({ scale: scale }).height : 0"
                :style="'cursor: ' + (canDraw() && drawMode ? 'crosshair' : 'default')"
                class="absolute top-0 left-0"
            ></canvas>
        </div>
    </div>
</div>
