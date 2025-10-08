<div
    class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900"
    x-data="pdfThumbnailPdfJs"
    x-init="loadThumbnail('{{ $pdfUrl }}', {{ $pageNumber }}, {{ $pdfPageId ?? 'null' }})"
    wire:ignore
>
    <div
        class="w-full flex items-center justify-center p-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        style="min-height: 400px;"
        @click="showModal = true"
        title="Click to view larger preview"
    >
        <canvas
            x-ref="thumbnail"
            x-show="imageLoaded"
            class="max-w-full h-auto"
        ></canvas>
        <div x-show="!imageLoaded && !error" class="text-gray-500">
            <svg class="animate-spin h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <div x-show="error" class="text-red-500">
            Failed to load preview
        </div>
    </div>
    <div class="bg-gray-700 px-2 py-1 text-center flex items-center justify-between">
        <span class="text-sm font-medium text-white">Page {{ $pageNumber }}</span>
        <button
            @click.stop="showAnnotationModal = true"
            class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors"
            title="Annotate this page"
        >
            ✏️ Annotate
        </button>
    </div>

    <!-- Modal for larger preview -->
    <div
        x-show="showModal"
        x-cloak
        @click.away="showModal = false"
        @keydown.escape.window="showModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75"
        style="display: none;"
    >
        <div
            class="relative bg-white dark:bg-gray-900 rounded-lg shadow-2xl max-w-7xl w-full max-h-[90vh] flex flex-col"
            @click.stop
        >
            <div class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Page {{ $pageNumber }} - Full Preview
                </h3>
                <button
                    @click="showModal = false"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 relative overflow-auto" style="min-height: 600px;">
                <div class="p-4 flex items-center justify-center">
                    <canvas
                        x-ref="modalCanvas"
                        x-show="modalImageLoaded"
                        class="max-w-full h-auto"
                    ></canvas>
                    <div x-show="!modalImageLoaded && !modalError" class="text-gray-500">
                        <svg class="animate-spin h-16 w-16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div x-show="modalError" class="text-red-500">
                        Failed to load full preview
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Annotation Modal - Split Screen: Canvas + Metadata Panel -->
    <template x-teleport="body">
    <div
        x-show="showAnnotationModal"
        x-cloak
        @keydown.escape.window="showAnnotationModal = false"
        class="fixed inset-0 z-[9999] bg-black/90"
        style="display: none;"
    >
        <div class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900">
            <!-- Header with Close Button -->
            <div class="flex items-center justify-between bg-gray-800 p-4">
                <h2 class="text-lg font-semibold text-white">
                    🏷️ Annotate Page <span x-text="currentPageNum"></span>
                </h2>
                <button
                    @click="showAnnotationModal = false"
                    class="p-2 bg-gray-700 hover:bg-gray-600 rounded-full text-white transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Split Screen: Canvas (left) + Metadata Panel (right) -->
            <div class="flex-1 flex overflow-hidden">
                <!-- LEFT: PDF Canvas -->
                <div class="flex-1 flex flex-col bg-gray-900 overflow-auto">
                    <!-- Complete PDF Toolbar -->
                    <div class="bg-gray-800 p-3 border-b border-gray-700 flex items-center gap-3 flex-wrap">
                        <!-- Page Navigation -->
                        <div class="flex items-center gap-2 border-r border-gray-700 pr-3">
                            <button
                                @click="goToFirstPage()"
                                :disabled="currentPageNum <= 1"
                                class="px-2 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="First Page"
                            >
                                ⏮️
                            </button>
                            <button
                                @click="goToPreviousPage()"
                                :disabled="currentPageNum <= 1"
                                class="px-2 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Previous Page"
                            >
                                ⏪
                            </button>
                            <div class="flex items-center gap-1">
                                <input
                                    type="number"
                                    x-model.number="pageInputValue"
                                    @keydown.enter="goToPage(pageInputValue)"
                                    min="1"
                                    :max="totalPages"
                                    class="w-16 px-2 py-1 bg-gray-700 text-white text-center rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                                />
                                <span class="text-gray-400 text-sm">/ <span x-text="totalPages"></span></span>
                            </div>
                            <button
                                @click="goToNextPage()"
                                :disabled="currentPageNum >= totalPages"
                                class="px-2 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Next Page"
                            >
                                ⏩
                            </button>
                            <button
                                @click="goToLastPage()"
                                :disabled="currentPageNum >= totalPages"
                                class="px-2 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Last Page"
                            >
                                ⏭️
                            </button>
                        </div>

                        <!-- Zoom Controls -->
                        <div class="flex items-center gap-2 border-r border-gray-700 pr-3">
                            <button
                                @click="zoomOut()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors"
                                title="Zoom Out"
                            >
                                🔍−
                            </button>
                            <span class="text-white text-sm font-medium min-w-[60px] text-center" x-text="Math.round(zoomLevel * 100) + '%'"></span>
                            <button
                                @click="zoomIn()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors"
                                title="Zoom In"
                            >
                                🔍+
                            </button>
                            <button
                                @click="fitToPage()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors text-sm"
                                title="Fit to Page"
                            >
                                📄
                            </button>
                            <button
                                @click="fitToWidth()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors text-sm"
                                title="Fit to Width"
                            >
                                📐
                            </button>
                            <button
                                @click="actualSize()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors text-sm"
                                title="Actual Size (100%)"
                            >
                                1:1
                            </button>
                        </div>

                        <!-- Drawing Tools -->
                        <div class="flex items-center gap-2 border-r border-gray-700 pr-3">
                            <button
                                @click="setTool('rectangle')"
                                :class="currentTool === 'rectangle' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                                class="px-3 py-2 rounded font-medium transition-colors"
                                title="Draw Rectangle"
                            >
                                📦
                            </button>
                            <button
                                @click="setTool('select')"
                                :class="currentTool === 'select' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
                                class="px-3 py-2 rounded font-medium transition-colors"
                                title="Select Tool"
                            >
                                ↖️
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 border-r border-gray-700 pr-3">
                            <button
                                @click="undo()"
                                :disabled="undoStack.length === 0"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Undo (Ctrl+Z)"
                            >
                                ↶
                            </button>
                            <button
                                @click="redo()"
                                :disabled="redoStack.length === 0"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Redo (Ctrl+Y)"
                            >
                                ↷
                            </button>
                            <button
                                @click="deleteSelected()"
                                :disabled="selectedAnnotationId === null"
                                class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Delete Selected (Del)"
                            >
                                🗑️
                            </button>
                            <button
                                @click="clearAllAnnotations()"
                                class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                                title="Clear All"
                            >
                                🧹
                            </button>
                        </div>

                        <!-- View Controls -->
                        <div class="flex items-center gap-2 border-r border-gray-700 pr-3">
                            <button
                                @click="rotateClockwise()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors"
                                title="Rotate Clockwise (90°)"
                            >
                                ↻
                            </button>
                            <button
                                @click="rotateCounterClockwise()"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors"
                                title="Rotate Counter-Clockwise (90°)"
                            >
                                ↺
                            </button>
                            <button
                                @click="resetView()"
                                class="px-3 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors font-semibold"
                                title="Reset View (Zoom 100%, Rotation 0°)"
                            >
                                🔄 Reset
                            </button>
                        </div>

                        <!-- View Presets Dropdown -->
                        <div class="relative" x-data="{ showViewMenu: false }">
                            <button
                                @click="showViewMenu = !showViewMenu"
                                class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors flex items-center gap-1"
                                title="View Presets"
                            >
                                👁️ View <span x-show="showViewMenu">▲</span><span x-show="!showViewMenu">▼</span>
                            </button>
                            <div
                                x-show="showViewMenu"
                                @click.away="showViewMenu = false"
                                x-cloak
                                class="absolute top-full left-0 mt-1 bg-gray-800 border border-gray-600 rounded shadow-lg z-50 min-w-[180px]"
                                style="display: none;"
                            >
                                <button
                                    @click="saveCurrentView(); showViewMenu = false"
                                    class="w-full text-left px-4 py-2 text-white hover:bg-gray-700 transition-colors border-b border-gray-700"
                                >
                                    💾 Save Current View
                                </button>
                                <button
                                    @click="restoreSavedView(); showViewMenu = false"
                                    :disabled="savedView === null"
                                    class="w-full text-left px-4 py-2 text-white hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed border-b border-gray-700"
                                >
                                    📥 Restore Saved View
                                </button>
                                <button
                                    @click="fitToPage(); showViewMenu = false"
                                    class="w-full text-left px-4 py-2 text-white hover:bg-gray-700 transition-colors border-b border-gray-700"
                                >
                                    📄 Fit to Page
                                </button>
                                <button
                                    @click="fitToWidth(); showViewMenu = false"
                                    class="w-full text-left px-4 py-2 text-white hover:bg-gray-700 transition-colors border-b border-gray-700"
                                >
                                    📐 Fit to Width
                                </button>
                                <button
                                    @click="fitToHeight(); showViewMenu = false"
                                    class="w-full text-left px-4 py-2 text-white hover:bg-gray-700 transition-colors border-b border-gray-700"
                                >
                                    📏 Fit to Height
                                </button>
                                <button
                                    @click="actualSize(); showViewMenu = false"
                                    class="w-full text-left px-4 py-2 text-white hover:bg-gray-700 transition-colors"
                                >
                                    🔍 Actual Size (100%)
                                </button>
                            </div>
                        </div>

                        <!-- Status -->
                        <span class="ml-auto text-sm text-gray-400" x-text="`${annotations.length} annotation${annotations.length !== 1 ? 's' : ''}`"></span>
                    </div>

                    <!-- Canvas Area -->
                    <div class="flex-1 flex items-center justify-center p-6" x-show="annotationViewerLoaded">
                        <div class="relative">
                            <!-- PDF Canvas (bottom layer) -->
                            <canvas
                                x-ref="pdfCanvas"
                                class="border-2 border-gray-600 shadow-2xl"
                            ></canvas>
                            <!-- Annotation Canvas (top layer) -->
                            <canvas
                                x-ref="annotationCanvas"
                                class="absolute top-0 left-0 cursor-crosshair"
                                style="pointer-events: auto;"
                                @mousedown="startDrawing($event)"
                                @mousemove="draw($event)"
                                @mouseup="stopDrawing($event)"
                                @mouseleave="cancelDrawing()"
                            ></canvas>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div x-show="!annotationViewerLoaded" class="flex-1 flex items-center justify-center">
                        <div class="text-center text-gray-400">
                            <svg class="animate-spin h-12 w-12 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p>Loading PDF...</p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Metadata Panel -->
                <div class="w-96 flex-shrink-0 bg-white dark:bg-gray-800 border-l border-gray-700 flex flex-col overflow-y-auto">
                    <!-- Panel Header -->
                    <div class="bg-gray-100 dark:bg-gray-750 p-4 border-b border-gray-300 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Page Metadata
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Page <span x-text="currentPageNum"></span>
                        </p>
                    </div>

                    <!-- Metadata Form -->
                    <div class="flex-1 p-4 space-y-4 overflow-y-auto">
                        <!-- Page Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Page Type
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option>Floor Plan</option>
                                <option>Elevation</option>
                                <option>Detail</option>
                                <option>Cover</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <!-- Current Room for Annotation (Floor Plans with multiple rooms) -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <label class="block text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">
                                🎨 Drawing for Room
                            </label>
                            <select
                                x-model="currentRoomType"
                                @change="console.log('Selected room for annotation:', currentRoomType)"
                                class="w-full px-3 py-2 border border-blue-300 dark:border-blue-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-medium"
                            >
                                <option value="">-- Select Room to Draw --</option>
                                <option value="Kitchen">🔵 Kitchen</option>
                                <option value="Pantry">🎀 Pantry</option>
                                <option value="Bathroom">🟢 Bathroom</option>
                                <option value="Bedroom">🟣 Bedroom</option>
                                <option value="Living Room">🟠 Living Room</option>
                                <option value="Dining Room">🔴 Dining Room</option>
                                <option value="Office">🔷 Office</option>
                                <option value="Laundry">🟦 Laundry</option>
                                <option value="Closet">🟦 Closet</option>
                                <option value="Other">⚫ Other</option>
                            </select>
                            <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                <span x-show="currentRoomType">
                                    Next annotation will be <strong><span x-text="currentRoomType"></span></strong>
                                    <span class="inline-block w-3 h-3 rounded" :style="`background-color: ${roomColors[currentRoomType]}`"></span>
                                </span>
                                <span x-show="!currentRoomType">
                                    Select a room before drawing annotations
                                </span>
                            </p>
                        </div>

                        <!-- Drawing/Detail Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Drawing Number
                            </label>
                            <input
                                type="text"
                                placeholder="e.g., A-101, D-3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Page Notes
                            </label>
                            <textarea
                                rows="3"
                                placeholder="Special details about this page..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            ></textarea>
                        </div>

                        <!-- Annotations List (Grouped by Room) -->
                        <div class="border-t border-gray-300 dark:border-gray-600 pt-4 mt-4">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                                Labels on This Page (<span x-text="annotations.length"></span>)
                            </h4>

                            <!-- Group annotations by room_type -->
                            <div class="space-y-4">
                                <template x-for="roomType in Object.keys(roomColors).filter(rt => annotations.some(a => a.room_type === rt))" :key="roomType">
                                    <div class="border rounded-lg p-3" :style="`border-color: ${roomColors[roomType]}; border-width: 2px;`">
                                        <!-- Room Header -->
                                        <div class="flex items-center gap-2 mb-2 pb-2 border-b" :style="`border-color: ${roomColors[roomType]};`">
                                            <span class="inline-block w-4 h-4 rounded" :style="`background-color: ${roomColors[roomType]}`"></span>
                                            <h5 class="text-sm font-bold text-gray-900 dark:text-white" x-text="roomType"></h5>
                                            <span class="text-xs text-gray-500" x-text="`(${annotations.filter(a => a.room_type === roomType).length} labels)`"></span>
                                        </div>

                                        <!-- Annotations for this room -->
                                        <div class="space-y-2">
                                            <template x-for="(annotation, index) in annotations.filter(a => a.room_type === roomType)" :key="annotation.id">
                                                <div class="p-2 bg-gray-50 dark:bg-gray-750 rounded border border-gray-200 dark:border-gray-600">
                                                    <div class="flex items-start justify-between mb-2">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-block w-3 h-3 rounded" :style="`background-color: ${annotation.color}`"></span>
                                                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="annotation.text || `Label ${annotations.indexOf(annotation) + 1}`"></span>
                                                        </div>
                                                        <button
                                                            @click="removeAnnotation(annotations.indexOf(annotation))"
                                                            class="text-red-600 hover:text-red-700 text-xs"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                        <div class="space-y-2">
                                            <input
                                                type="text"
                                                x-model="annotation.text"
                                                placeholder="Label text..."
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            />
                                            <select
                                                x-model="annotation.cabinet_run_id"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                :disabled="loadingMetadata"
                                            >
                                                <option value="">-- Select Cabinet Run --</option>
                                                <template x-for="run in availableCabinetRuns" :key="run.id">
                                                    <option :value="run.id" x-text="`${run.name} (${run.room_name}) - ${run.cabinet_count} cabinets`"></option>
                                                </template>
                                            </select>
                                            <select
                                                x-model="annotation.room_id"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                :disabled="loadingMetadata || availableRooms.length === 0"
                                            >
                                                <option value="">-- Select Room --</option>
                                                <template x-for="room in availableRooms" :key="room.id">
                                                    <option :value="room.id" x-text="room.name"></option>
                                                </template>
                                            </select>
                                            <textarea
                                                x-model="annotation.notes"
                                                rows="2"
                                                placeholder="Additional notes..."
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            ></textarea>
                                        </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button (sticky bottom) -->
                    <div class="p-4 bg-gray-100 dark:bg-gray-750 border-t border-gray-300 dark:border-gray-700">
                        <button
                            @click="saveAnnotations()"
                            class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isSaving"
                            x-text="isSaving ? '💾 Saving...' : '💾 Save All Changes'"
                        >
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>

@once
    @push('scripts')
    <script type="module">
        import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.8.69/+esm';

        // Set worker path
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.8.69/build/pdf.worker.min.mjs';

        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfThumbnailPdfJs', () => {
                // Store PDF objects outside Alpine's reactive system to avoid Livewire proxy issues
                let pdfPageCache = null;
                let pdfDocumentCache = null;

                return {
                    imageLoaded: false,
                    showModal: false,
                    showAnnotationModal: false,
                    error: false,
                    modalImageLoaded: false,
                    modalError: false,
                    currentPdfUrl: null,
                    currentPageNum: null,
                    annotationViewerLoaded: false,
                    isSaving: false,

                    // PDF document state
                    totalPages: 1,
                    pageInputValue: 1,
                    rotation: 0,

                    // Backend data
                    pdfPageId: null,
                    projectId: null,
                    availableCabinetRuns: [],
                    availableRooms: [],
                    availableRoomLocations: [],
                    availableCabinets: [],
                    loadingMetadata: false,

                    // Annotation context state
                    annotationType: 'room', // 'room' | 'room_location' | 'cabinet_run' | 'cabinet' | 'dimension'
                    selectedRoomId: null,
                    selectedRoomLocationId: null,
                    selectedCabinetRunId: null,
                    selectedCabinetId: null,
                    selectedRunType: 'base',

                    // Filtered entities based on parent selection
                    filteredRoomLocations: [],
                    filteredCabinetRuns: [],
                    filteredCabinets: [],

                    // Canvas annotation state
                    annotations: [],
                    currentTool: 'rectangle',
                    isDrawing: false,
                    startX: 0,
                    startY: 0,
                    selectedAnnotationId: null,

                    // Room color palette for multi-room floor plans
                    roomColors: {
                        'Kitchen': '#3B82F6',      // Blue
                        'Bathroom': '#10B981',     // Green
                        'Bedroom': '#8B5CF6',      // Purple
                        'Living Room': '#F59E0B',  // Orange
                        'Dining Room': '#EF4444',  // Red
                        'Office': '#06B6D4',       // Cyan
                        'Pantry': '#EC4899',       // Pink
                        'Laundry': '#14B8A6',      // Teal
                        'Closet': '#6366F1',       // Indigo
                        'Other': '#6B7280'         // Gray
                    },
                    // Room code mapping for labels (Project# + Code)
                    roomCodes: {
                        'Kitchen': 'K',
                        'Bathroom': 'B',
                        'Bedroom': 'BR',
                        'Living Room': 'LR',
                        'Dining Room': 'DR',
                        'Office': 'O',
                        'Pantry': 'P',
                        'Laundry': 'LA',
                        'Closet': 'CL',
                        'Other': 'OT'
                    },
                    currentRoomType: '',  // Track currently selected room for new annotations
                    projectNumber: 'TFW-0001',  // Project number prefix for labels (will be dynamic later)

                    // Undo/Redo stacks
                    undoStack: [],
                    redoStack: [],

                    // Zoom state
                    zoomLevel: 1.0,
                    baseScale: 1.0,

                    // Saved view state
                    savedView: null,

                async loadThumbnail(pdfUrl, pageNum, pdfPageId = null) {
                    this.currentPdfUrl = pdfUrl;
                    this.currentPageNum = pageNum;
                    this.pdfPageId = pdfPageId;

                    try {
                        // Load PDF document
                        const loadingTask = pdfjsLib.getDocument(pdfUrl);
                        const pdf = await loadingTask.promise;

                        // Get the specific page
                        const page = await pdf.getPage(pageNum);

                        // Calculate scale for 800px width
                        const viewport = page.getViewport({ scale: 1.0 });
                        const scale = 800 / viewport.width;
                        const scaledViewport = page.getViewport({ scale });

                        // Set canvas dimensions
                        const canvas = this.$refs.thumbnail;
                        canvas.width = scaledViewport.width;
                        canvas.height = scaledViewport.height;

                        // Render the page
                        const renderContext = {
                            canvasContext: canvas.getContext('2d'),
                            viewport: scaledViewport
                        };

                        await page.render(renderContext).promise;
                        this.imageLoaded = true;

                    } catch (err) {
                        console.error('Error loading PDF thumbnail:', err);
                        this.error = true;
                        this.imageLoaded = false;
                    }
                },

                async loadModalImage() {
                    if (this.modalImageLoaded) return;

                    try {
                        // Load PDF document
                        const loadingTask = pdfjsLib.getDocument(this.currentPdfUrl);
                        const pdf = await loadingTask.promise;

                        // Get the specific page
                        const page = await pdf.getPage(this.currentPageNum);

                        // Calculate scale for 1400px width (higher resolution)
                        const viewport = page.getViewport({ scale: 1.0 });
                        const scale = 1400 / viewport.width;
                        const scaledViewport = page.getViewport({ scale });

                        // Set canvas dimensions
                        const canvas = this.$refs.modalCanvas;
                        canvas.width = scaledViewport.width;
                        canvas.height = scaledViewport.height;

                        // Render the page
                        const renderContext = {
                            canvasContext: canvas.getContext('2d'),
                            viewport: scaledViewport
                        };

                        await page.render(renderContext).promise;
                        this.modalImageLoaded = true;

                    } catch (err) {
                        console.error('Error loading modal PDF:', err);
                        this.modalError = true;
                    }
                },

                init() {
                    this.$watch('showModal', (value) => {
                        if (value) {
                            setTimeout(() => this.loadModalImage(), 100);
                        }
                    });

                    this.$watch('showAnnotationModal', (value) => {
                        if (value && !this.annotationViewerLoaded) {
                            this.loadCanvasAnnotationViewer();
                        }
                    });

                    // Keyboard shortcuts
                    document.addEventListener('keydown', (e) => {
                        if (!this.showAnnotationModal) return;

                        // Ctrl+Z = Undo
                        if (e.ctrlKey && e.key === 'z') {
                            e.preventDefault();
                            this.undo();
                        }
                        // Ctrl+Y = Redo
                        if (e.ctrlKey && e.key === 'y') {
                            e.preventDefault();
                            this.redo();
                        }
                        // Delete = Delete selected
                        if (e.key === 'Delete' && this.selectedAnnotationId !== null) {
                            e.preventDefault();
                            this.deleteSelected();
                        }
                        // R = Rectangle tool
                        if (e.key === 'r' || e.key === 'R') {
                            e.preventDefault();
                            this.setTool('rectangle');
                        }
                        // V = Select tool
                        if (e.key === 'v' || e.key === 'V') {
                            e.preventDefault();
                            this.setTool('select');
                        }
                    });
                },

                async loadCanvasAnnotationViewer() {
                    try {
                        // Load PDF document and store in closure (not reactive)
                        const loadingTask = pdfjsLib.getDocument(this.currentPdfUrl);
                        pdfDocumentCache = await loadingTask.promise;
                        this.totalPages = pdfDocumentCache.numPages;
                        this.pageInputValue = this.currentPageNum;

                        // Get the specific page and store it in closure (not reactive)
                        pdfPageCache = await pdfDocumentCache.getPage(this.currentPageNum);

                        // Calculate base scale for responsive canvas (viewport - panel - padding)
                        const viewport = pdfPageCache.getViewport({ scale: 1.0 });
                        const metadataPanelWidth = 384; // w-96 = 384px
                        const padding = 100; // margins and padding
                        const maxCanvasWidth = window.innerWidth - metadataPanelWidth - padding;
                        this.baseScale = maxCanvasWidth / viewport.width;

                        // Load backend metadata (cabinet runs, rooms, project number, existing annotations)
                        await this.loadMetadata();

                        // Initial render at 100% zoom
                        await this.renderCanvas();

                        this.annotationViewerLoaded = true;
                        console.log('✅ Canvas annotation viewer loaded successfully');
                    } catch (error) {
                        console.error('Canvas annotation loading error:', error);
                        alert('Failed to load PDF annotation viewer: ' + error.message);
                    }
                },

                async loadMetadata() {
                    this.loadingMetadata = true;
                    try {
                        // Use pdfPageId from component prop
                        if (!this.pdfPageId) {
                            console.warn('No PDF page ID available for loading metadata');
                            return;
                        }

                        const pdfPageId = this.pdfPageId;

                        // Load cabinet runs
                        const cabinetRunsResponse = await fetch(`/api/pdf/annotations/page/${pdfPageId}/cabinet-runs`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (cabinetRunsResponse.ok) {
                            const data = await cabinetRunsResponse.json();
                            this.availableCabinetRuns = data.cabinet_runs || [];
                            console.log('✅ Loaded cabinet runs:', this.availableCabinetRuns.length);
                        }

                        // Load project number from related project
                        const projectNumberResponse = await fetch(`/api/pdf/page/${pdfPageId}/project-number`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (projectNumberResponse.ok) {
                            const projectData = await projectNumberResponse.json();
                            this.projectNumber = projectData.project_number || 'TFW-0001';
                            console.log('✅ Loaded project number:', this.projectNumber);
                        } else {
                            console.warn('⚠️ Failed to load project number, using default');
                            this.projectNumber = 'TFW-0001'; // Fallback to default
                        }

                        // Load existing annotations for this page
                        const annotationsResponse = await fetch(`/api/pdf/page/${pdfPageId}/annotations`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (annotationsResponse.ok) {
                            const annotationsData = await annotationsResponse.json();
                            this.annotations = annotationsData.annotations || [];
                            console.log('✅ Loaded existing annotations:', this.annotations.length);
                        } else {
                            console.warn('⚠️ No existing annotations found');
                            this.annotations = [];
                        }

                        // Load context data (rooms, locations, runs, cabinets) for dropdowns
                        const contextResponse = await fetch(`/api/pdf/page/${pdfPageId}/context`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (contextResponse.ok) {
                            const contextData = await contextResponse.json();
                            if (contextData.success) {
                                this.availableRooms = contextData.context.rooms || [];
                                this.availableRoomLocations = contextData.context.room_locations || [];
                                this.availableCabinetRuns = contextData.context.cabinet_runs || [];
                                this.availableCabinets = contextData.context.cabinets || [];
                                this.projectId = contextData.context.project_id;
                                console.log('✅ Loaded context data:',
                                    this.availableRooms.length, 'rooms,',
                                    this.availableRoomLocations.length, 'locations,',
                                    this.availableCabinetRuns.length, 'runs,',
                                    this.availableCabinets.length, 'cabinets'
                                );
                            }
                        } else {
                            console.warn('⚠️ Failed to load context data');
                        }

                    } catch (error) {
                        console.error('Failed to load metadata:', error);
                    } finally {
                        this.loadingMetadata = false;
                    }
                },

                async renderCanvas() {
                    if (!pdfPageCache) return;

                    const currentScale = this.baseScale * this.zoomLevel;
                    const scaledViewport = pdfPageCache.getViewport({
                        scale: currentScale,
                        rotation: this.rotation
                    });

                    // Set PDF canvas dimensions
                    const pdfCanvas = this.$refs.pdfCanvas;
                    pdfCanvas.width = scaledViewport.width;
                    pdfCanvas.height = scaledViewport.height;

                    // Set annotation canvas dimensions (same as PDF canvas)
                    const annotationCanvas = this.$refs.annotationCanvas;
                    annotationCanvas.width = scaledViewport.width;
                    annotationCanvas.height = scaledViewport.height;

                    // Render the PDF page
                    const renderContext = {
                        canvasContext: pdfCanvas.getContext('2d'),
                        viewport: scaledViewport
                    };

                    await pdfPageCache.render(renderContext).promise;

                    // Redraw any existing annotations
                    this.redrawAnnotations();
                },

                // ========== PAGE NAVIGATION ==========
                async goToPage(pageNum) {
                    if (!pdfDocumentCache || pageNum < 1 || pageNum > this.totalPages) return;

                    this.currentPageNum = pageNum;
                    this.pageInputValue = pageNum;

                    // Clear annotations for new page
                    this.annotations = [];

                    // Load the new page
                    pdfPageCache = await pdfDocumentCache.getPage(pageNum);
                    await this.renderCanvas();
                },

                goToFirstPage() {
                    this.goToPage(1);
                },

                goToPreviousPage() {
                    if (this.currentPageNum > 1) {
                        this.goToPage(this.currentPageNum - 1);
                    }
                },

                goToNextPage() {
                    if (this.currentPageNum < this.totalPages) {
                        this.goToPage(this.currentPageNum + 1);
                    }
                },

                goToLastPage() {
                    this.goToPage(this.totalPages);
                },

                // ========== ZOOM MODES ==========
                async fitToPage() {
                    if (!pdfPageCache) return;

                    const viewport = pdfPageCache.getViewport({ scale: 1.0, rotation: this.rotation });
                    const canvasContainer = this.$refs.pdfCanvas.closest('.flex-1');
                    const containerHeight = canvasContainer.clientHeight - 100; // padding
                    const containerWidth = canvasContainer.clientWidth - 100;

                    // Calculate scale to fit both dimensions
                    const scaleX = containerWidth / viewport.width;
                    const scaleY = containerHeight / viewport.height;
                    const optimalScale = Math.min(scaleX, scaleY);

                    this.zoomLevel = optimalScale / this.baseScale;
                    await this.renderCanvas();
                },

                async fitToWidth() {
                    if (!pdfPageCache) return;

                    const viewport = pdfPageCache.getViewport({ scale: 1.0, rotation: this.rotation });
                    const canvasContainer = this.$refs.pdfCanvas.closest('.flex-1');
                    const containerWidth = canvasContainer.clientWidth - 100; // padding

                    const optimalScale = containerWidth / viewport.width;
                    this.zoomLevel = optimalScale / this.baseScale;
                    await this.renderCanvas();
                },

                async actualSize() {
                    this.zoomLevel = 1.0;
                    await this.renderCanvas();
                },

                // ========== ROTATION ==========
                async rotateClockwise() {
                    this.rotation = (this.rotation + 90) % 360;
                    await this.renderCanvas();
                },

                async rotateCounterClockwise() {
                    this.rotation = (this.rotation - 90 + 360) % 360;
                    await this.renderCanvas();
                },

                // ========== VIEW MANAGEMENT ==========
                resetView() {
                    // Reset to default view: 100% zoom, 0° rotation
                    this.zoomLevel = 1.0;
                    this.rotation = 0;
                    this.renderCanvas();
                    console.log('✅ View reset to defaults (100% zoom, 0° rotation)');
                },

                saveCurrentView() {
                    // Save current zoom and rotation as a preset
                    this.savedView = {
                        zoomLevel: this.zoomLevel,
                        rotation: this.rotation,
                        pageNum: this.currentPageNum
                    };
                    console.log('✅ Current view saved:', this.savedView);
                    alert(`View saved! (${Math.round(this.zoomLevel * 100)}% zoom, ${this.rotation}° rotation)`);
                },

                async restoreSavedView() {
                    if (!this.savedView) {
                        alert('No saved view to restore');
                        return;
                    }

                    // Restore saved view settings
                    this.zoomLevel = this.savedView.zoomLevel;
                    this.rotation = this.savedView.rotation;

                    // Navigate to saved page if different
                    if (this.savedView.pageNum !== this.currentPageNum) {
                        await this.goToPage(this.savedView.pageNum);
                    } else {
                        await this.renderCanvas();
                    }

                    console.log('✅ View restored:', this.savedView);
                },

                async fitToHeight() {
                    if (!pdfPageCache) return;

                    const viewport = pdfPageCache.getViewport({ scale: 1.0, rotation: this.rotation });
                    const canvasContainer = this.$refs.pdfCanvas.closest('.flex-1');
                    const containerHeight = canvasContainer.clientHeight - 100; // padding

                    const optimalScale = containerHeight / viewport.height;
                    this.zoomLevel = optimalScale / this.baseScale;
                    await this.renderCanvas();
                },

                // ========== TOOLS ==========
                setTool(tool) {
                    this.currentTool = tool;
                    this.selectedAnnotationId = null;

                    // Change cursor based on tool
                    const canvas = this.$refs.annotationCanvas;
                    if (canvas) {
                        canvas.style.cursor = tool === 'rectangle' ? 'crosshair' : 'default';
                    }
                },

                // ========== UNDO/REDO ==========
                saveState() {
                    // Save current annotations state to undo stack
                    this.undoStack.push(JSON.parse(JSON.stringify(this.annotations)));
                    // Clear redo stack when new action is performed
                    this.redoStack = [];
                    // Limit undo stack to 20 items
                    if (this.undoStack.length > 20) {
                        this.undoStack.shift();
                    }
                },

                undo() {
                    if (this.undoStack.length === 0) return;

                    // Save current state to redo stack
                    this.redoStack.push(JSON.parse(JSON.stringify(this.annotations)));

                    // Restore previous state
                    this.annotations = this.undoStack.pop();
                    this.redrawAnnotations();
                },

                redo() {
                    if (this.redoStack.length === 0) return;

                    // Save current state to undo stack
                    this.undoStack.push(JSON.parse(JSON.stringify(this.annotations)));

                    // Restore next state
                    this.annotations = this.redoStack.pop();
                    this.redrawAnnotations();
                },

                // ========== SELECTION & DELETION ==========
                deleteSelected() {
                    if (this.selectedAnnotationId === null) return;

                    const index = this.annotations.findIndex(a => a.id === this.selectedAnnotationId);
                    if (index !== -1) {
                        this.saveState();
                        this.annotations.splice(index, 1);
                        this.selectedAnnotationId = null;
                        this.redrawAnnotations();
                    }
                },

                zoomIn() {
                    this.zoomLevel = Math.min(this.zoomLevel + 0.25, 3.0); // Max 300%
                    this.renderCanvas();
                },

                zoomOut() {
                    this.zoomLevel = Math.max(this.zoomLevel - 0.25, 0.5); // Min 50%
                    this.renderCanvas();
                },

                resetZoom() {
                    this.zoomLevel = 1.0;
                    this.renderCanvas();
                },

                startDrawing(e) {
                    if (this.currentTool !== 'rectangle') return;

                    const rect = this.$refs.annotationCanvas.getBoundingClientRect();
                    this.isDrawing = true;
                    this.startX = e.clientX - rect.left;
                    this.startY = e.clientY - rect.top;
                },

                draw(e) {
                    if (!this.isDrawing || this.currentTool !== 'rectangle') return;

                    const rect = this.$refs.annotationCanvas.getBoundingClientRect();
                    const currentX = e.clientX - rect.left;
                    const currentY = e.clientY - rect.top;

                    // Clear canvas and redraw all annotations
                    this.redrawAnnotations();

                    // Draw current rectangle being drawn
                    const ctx = this.$refs.annotationCanvas.getContext('2d');
                    ctx.strokeStyle = '#3B82F6'; // Blue
                    ctx.lineWidth = 3;
                    ctx.strokeRect(
                        this.startX,
                        this.startY,
                        currentX - this.startX,
                        currentY - this.startY
                    );
                },

                stopDrawing(e) {
                    if (!this.isDrawing || this.currentTool !== 'rectangle') return;

                    const rect = this.$refs.annotationCanvas.getBoundingClientRect();
                    const endX = e.clientX - rect.left;
                    const endY = e.clientY - rect.top;

                    const width = endX - this.startX;
                    const height = endY - this.startY;

                    // Only create annotation if rectangle has meaningful size
                    if (Math.abs(width) > 10 && Math.abs(height) > 10) {
                        const canvas = this.$refs.annotationCanvas;

                        // Save state before adding new annotation
                        this.saveState();

                        // Store coordinates as normalized values (0-1) relative to canvas dimensions
                        // This makes them zoom-independent
                        // Determine color based on current room type
                        const annotationColor = this.currentRoomType && this.roomColors[this.currentRoomType]
                            ? this.roomColors[this.currentRoomType]
                            : '#3B82F6'; // Default blue if no room selected

                        // Generate label with project number + room code
                        // Format: TFW-0001-K (for Kitchen), TFW-0001-P (for Pantry), etc.
                        let labelText = '';
                        if (this.currentRoomType && this.roomCodes[this.currentRoomType]) {
                            const roomCode = this.roomCodes[this.currentRoomType];
                            labelText = this.projectNumber
                                ? `${this.projectNumber}-${roomCode}`
                                : roomCode;
                        } else {
                            // Fallback if no room selected
                            const labelNumber = this.annotations.length + 1;
                            labelText = this.projectNumber
                                ? `${this.projectNumber}-${labelNumber}`
                                : `Label ${labelNumber}`;
                        }

                        this.annotations.push({
                            id: Date.now(),
                            // Normalized coordinates (0-1 range)
                            x: Math.min(this.startX, endX) / canvas.width,
                            y: Math.min(this.startY, endY) / canvas.height,
                            width: Math.abs(width) / canvas.width,
                            height: Math.abs(height) / canvas.height,
                            text: labelText,
                            room_type: this.currentRoomType || '',  // Associate with room type
                            cabinet_run_id: '',
                            room_id: '',
                            notes: '',
                            color: annotationColor,
                            annotation_type: 'room'  // This is a room-level annotation
                        });

                        // Redraw canvas with new annotation
                        this.redrawAnnotations();
                    }

                    this.isDrawing = false;
                },

                cancelDrawing() {
                    if (this.isDrawing) {
                        this.isDrawing = false;
                        this.redrawAnnotations();
                    }
                },

                removeAnnotation(index) {
                    this.saveState();
                    this.annotations.splice(index, 1);
                    this.redrawAnnotations();
                },

                redrawAnnotations() {
                    const canvas = this.$refs.annotationCanvas;
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Draw all saved annotations
                    // Convert normalized coordinates (0-1) to actual canvas pixels
                    this.annotations.forEach(annotation => {
                        const x = annotation.x * canvas.width;
                        const y = annotation.y * canvas.height;
                        const width = annotation.width * canvas.width;
                        const height = annotation.height * canvas.height;

                        ctx.strokeStyle = annotation.color || '#3B82F6';
                        ctx.lineWidth = 3;
                        ctx.strokeRect(x, y, width, height);

                        // Draw label text
                        if (annotation.text) {
                            ctx.fillStyle = annotation.color || '#3B82F6';
                            ctx.font = 'bold 16px sans-serif';
                            ctx.fillText(annotation.text, x + 5, y - 5);
                        }
                    });
                },

                clearLastAnnotation() {
                    if (this.annotations.length > 0) {
                        this.saveState();
                        this.annotations.pop();
                        this.redrawAnnotations();
                    }
                },

                clearAllAnnotations() {
                    if (confirm('Clear all annotations? This cannot be undone.')) {
                        this.saveState();
                        this.annotations = [];
                        this.redrawAnnotations();
                    }
                },

                async saveAnnotations() {
                    if (this.annotations.length === 0) {
                        alert('No annotations to save');
                        return;
                    }

                    this.isSaving = true;

                    try {
                        // Use pdfPageId from component state
                        if (!this.pdfPageId) {
                            throw new Error('PDF Page ID not available');
                        }

                        // Prepare annotations data
                        const annotationsData = {
                            annotations: this.annotations.map(ann => ({
                                type: 'rectangle',
                                x: ann.x,
                                y: ann.y,
                                width: ann.width,
                                height: ann.height,
                                text: ann.text,
                                room_type: ann.room_type || null,
                                color: ann.color || null,
                                cabinet_run_id: ann.cabinet_run_id || null,
                                room_id: ann.room_id || null,
                                notes: ann.notes || null,
                                annotation_type: ann.annotation_type || 'room'
                            }))
                        };

                        // Send to backend API
                        const response = await fetch(`/api/pdf/page/${this.pdfPageId}/annotations`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(annotationsData)
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.error || 'Failed to save annotations');
                        }

                        // Show success message
                        alert(`✅ Saved ${this.annotations.length} label(s) successfully!`);
                        console.log('✅ Annotations saved:', data);

                        // Close annotation modal
                        this.showAnnotationModal = false;

                    } catch (error) {
                        console.error('Failed to save annotations:', error);
                        alert('❌ Failed to save annotations: ' + error.message);
                    } finally {
                        this.isSaving = false;
                    }
                }
                }; // End of return object
            }); // End of Alpine.data
        }); // End of alpine:init
    </script>
    @endpush
@endonce
