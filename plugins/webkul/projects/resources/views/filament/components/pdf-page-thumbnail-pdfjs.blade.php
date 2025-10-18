@php
    // Get the annotation system version from parent page
    $useV2 = false;
    if (isset($this) && method_exists($this, 'useAnnotationSystemV2')) {
        $useV2 = $this->useAnnotationSystemV2();
    }
@endphp

<div
    class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900"
    x-data="pdfThumbnailPdfJs"
    x-init="loadThumbnail('{{ $pdfUrl }}', {{ $pageNumber }}, {{ $pdfPageId ?? 'null' }});"
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
        <div class="flex items-center gap-2">
            @if($useV2)
                <span class="px-2 py-0.5 text-white text-xs rounded font-semibold" style="background-color: #9333ea;">V2</span>
            @endif
            @if($useV2)
                <a
                    href="{{ \Webkul\Project\Filament\Resources\ProjectResource::getUrl('annotate-v2', ['record' => $this->record->id, 'page' => $pageNumber, 'pdf' => $pdfId ?? $pdfDocument->id ?? null]) }}"
                    class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors inline-block"
                    title="Annotate this page (V2 Canvas System)"
                    target="_blank"
                >
                    ✏️ Annotate
                </a>
            @else
                <button
                    @click.stop="showAnnotationModal = true"
                    class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors"
                    title="Annotate this page (V1 System)"
                >
                    ✏️ Annotate
                </button>
            @endif
        </div>
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
        @keydown.delete.window="if (selectedAnnotationId && confirm('Delete this annotation? This cannot be undone.')) { deleteSelected(); }"
        @keydown.backspace.window="if (selectedAnnotationId && confirm('Delete this annotation? This cannot be undone.')) { deleteSelected(); }"
        @keydown.window="
            if ((event.ctrlKey || event.metaKey) && event.key === 'z' && !event.shiftKey) {
                event.preventDefault();
                undo();
            } else if ((event.ctrlKey || event.metaKey) && (event.key === 'y' || (event.key === 'z' && event.shiftKey))) {
                event.preventDefault();
                redo();
            }
        "
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
                                title="Select Tool (Click annotation to edit)"
                            >
                                ↖️
                            </button>
                        </div>

                        <!-- Selection Indicator & Actions -->
                        <div x-show="selectedAnnotationId !== null" class="flex items-center gap-2 border-r border-gray-700 pr-3">
                            <div class="flex items-center gap-2 px-3 py-2 bg-orange-600 text-white rounded font-medium">
                                <span>✏️</span>
                                <span class="text-sm">Editing</span>
                                <button
                                    @click="selectedAnnotationId = null; renderCanvas()"
                                    class="ml-1 text-xs bg-orange-700 hover:bg-orange-800 px-2 py-1 rounded"
                                    title="Deselect"
                                >
                                    ✕
                                </button>
                            </div>
                            <button
                                @click="if (confirm('Delete this annotation? This cannot be undone.')) { deleteSelected(); }"
                                class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors font-medium"
                                title="Delete annotation (Delete key)"
                                dusk="delete-annotation-button"
                            >
                                🗑️
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
                    <!-- Panel Header with Tabs -->
                    <div class="bg-gray-100 dark:bg-gray-750 p-4 border-b border-gray-300 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            Page <span x-text="currentPageNum"></span>
                        </h3>
                        <div class="flex gap-2 flex-wrap">
                            <button
                                @click="activeTab = 'metadata'; $dispatch('tab-changed', 'metadata')"
                                :class="activeTab === 'metadata' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-750'"
                                class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
                            >
                                📋 Metadata
                            </button>
                            <button
                                @click="activeTab = 'tags'; $dispatch('tab-changed', 'tags')"
                                :class="activeTab === 'tags' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-750'"
                                class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
                            >
                                🏷️ Tags
                            </button>
                            <button
                                @click="activeTab = 'discussion'; $dispatch('tab-changed', 'discussion')"
                                :class="activeTab === 'discussion' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-750'"
                                class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
                            >
                                💬 Discussion
                            </button>
                            <button
                                @click="activeTab = 'history'; $dispatch('tab-changed', 'history')"
                                :class="activeTab === 'history' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-750'"
                                class="px-3 py-1.5 rounded text-sm font-medium transition-colors"
                            >
                                📜 History
                            </button>
                        </div>
                    </div>

                    <!-- Fixed Save Button Header -->
                    <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 border-b-2 border-green-500 dark:border-green-600 p-3 shadow-md">
                        <button
                            @click="saveAnnotations()"
                            dusk="save-annotations-button"
                            class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed shadow-lg flex items-center justify-center gap-2"
                            :disabled="isSaving"
                        >
                            <span x-text="isSaving ? '💾 Saving...' : '💾 Save All Changes'"></span>
                            <span x-show="annotations.length > 0" class="bg-white/20 px-2 py-0.5 rounded-full text-xs" x-text="`(${annotations.length})`"></span>
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="flex-1 overflow-y-auto" @tab-changed.window="activeTab = $event.detail">

                        <!-- Metadata Tab -->
                        <div x-show="activeTab === 'metadata'" class="p-4 space-y-4">
                        <!-- Page Type (FIRST QUESTION) -->
                        <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-300 dark:border-green-700 rounded-lg p-3">
                            <label class="block text-sm font-bold text-green-900 dark:text-green-300 mb-2">
                                📄 What type of page is this?
                            </label>
                            <select
                                x-model="pageType"
                                @change="resetPageTypeFields()"
                                class="w-full px-3 py-2 border-2 border-green-400 dark:border-green-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-semibold"
                            >
                                <option value="">-- Select Page Type --</option>
                                <option value="floor_plan">Floor Plan</option>
                                <option value="elevation">Elevation</option>
                                <option value="detail">Detail</option>
                                <option value="cover">Cover</option>
                                <option value="other">Other</option>
                            </select>
                            <p class="text-xs text-green-700 dark:text-green-400 mt-1">
                                Identify the type of drawing on this page
                            </p>
                        </div>

                        <!-- COVER PAGE FIELDS (Only show when page type is Cover) -->
                        <div x-show="pageType === 'cover'" class="space-y-4">
                            <div class="bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-300 dark:border-purple-700 rounded-lg p-4">
                                <h3 class="text-sm font-bold text-purple-900 dark:text-purple-300 mb-3 flex items-center gap-2">
                                    📋 Cover Page Information
                                </h3>

                                <!-- Customer -->
                                <div class="mb-3">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Customer
                                    </label>
                                    <div class="flex gap-2 items-start">
                                        <select
                                            x-model="coverCustomerId"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            id="customer-select"
                                        >
                                            <option value="">-- Select Customer --</option>
                                            @foreach(\Webkul\Partner\Models\Partner::where('sub_type', 'customer')->orderBy('name')->get() as $partner)
                                                <option value="{{ $partner->id }}">
                                                    {{ $partner->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="flex gap-1">
                                            <button
                                                type="button"
                                                @click="window.open('/admin/partner/partners/create?sub_type=customer', 'createCustomer', 'width=800,height=600'); setTimeout(() => location.reload(), 3000);"
                                                class="px-2 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition-colors"
                                                title="Create New Customer"
                                            >
                                                ➕
                                            </button>
                                            <button
                                                type="button"
                                                @click="if(coverCustomerId) { window.open('/admin/partner/partners/' + coverCustomerId + '/edit', 'editCustomer', 'width=800,height=600'); setTimeout(() => location.reload(), 3000); } else { alert('Please select a customer first'); }"
                                                class="px-2 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors"
                                                title="Edit Selected Customer"
                                                :class="!coverCustomerId && 'opacity-50 cursor-not-allowed'"
                                            >
                                                ✏️
                                            </button>
                                            <button
                                                type="button"
                                                @click="location.reload()"
                                                class="px-2 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors"
                                                title="Refresh Customer List"
                                            >
                                                🔄
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Company -->
                                <div class="mb-3">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Company
                                    </label>
                                    <div class="flex gap-2 items-start">
                                        <select
                                            x-model="coverCompanyId"
                                            @change="coverBranchId = ''"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            id="company-select"
                                        >
                                            <option value="">-- Select Company --</option>
                                            @foreach(\Webkul\Support\Models\Company::whereNull('parent_id')->orderBy('name')->get() as $company)
                                                <option value="{{ $company->id }}">
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="flex gap-1">
                                            <button
                                                type="button"
                                                @click="window.open('/admin/support/companies/create', 'createCompany', 'width=800,height=600'); setTimeout(() => location.reload(), 3000);"
                                                class="px-2 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition-colors"
                                                title="Create New Company"
                                            >
                                                ➕
                                            </button>
                                            <button
                                                type="button"
                                                @click="location.reload()"
                                                class="px-2 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors"
                                                title="Refresh Company List"
                                            >
                                                🔄
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Branch (conditional on company selection) -->
                                <div x-show="coverCompanyId" class="mb-3">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Branch (Optional)
                                    </label>
                                    <div class="flex gap-2 items-start">
                                        <select
                                            x-model="coverBranchId"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            id="branch-select"
                                        >
                                            <option value="">-- Select Branch --</option>
                                            <template x-if="coverCompanyId">
                                                @foreach(\Webkul\Support\Models\Company::all() as $company)
                                                    @if($company->parent_id)
                                                        <option value="{{ $company->id }}"
                                                            x-show="coverCompanyId == {{ $company->parent_id }}">
                                                            {{ $company->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </template>
                                        </select>
                                        <div class="flex gap-1">
                                            <button
                                                type="button"
                                                @click="window.open('/admin/support/companies/create?parent_id=' + coverCompanyId, 'createBranch', 'width=800,height=600'); setTimeout(() => location.reload(), 3000);"
                                                class="px-2 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition-colors"
                                                title="Create New Branch"
                                            >
                                                ➕
                                            </button>
                                            <button
                                                type="button"
                                                @click="location.reload()"
                                                class="px-2 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded transition-colors"
                                                title="Refresh Branch List"
                                            >
                                                🔄
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Address Section -->
                                <div class="mt-4 pt-4 border-t border-purple-200 dark:border-purple-800">
                                    <h4 class="text-xs font-bold text-purple-900 dark:text-purple-300 mb-2">
                                        📍 Project Address
                                    </h4>

                                    <!-- Street Address Line 1 -->
                                    <div class="mb-2">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Street Address Line 1
                                        </label>
                                        <input
                                            type="text"
                                            x-model="coverAddressStreet1"
                                            placeholder="123 Main Street"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                        />
                                    </div>

                                    <!-- Street Address Line 2 -->
                                    <div class="mb-2">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Street Address Line 2
                                        </label>
                                        <input
                                            type="text"
                                            x-model="coverAddressStreet2"
                                            placeholder="Apt, Suite, Unit, etc. (optional)"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                        />
                                    </div>

                                    <!-- City, State, Zip (Grid) -->
                                    <div class="grid grid-cols-3 gap-2 mb-2">
                                        <!-- City -->
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                                City
                                            </label>
                                            <input
                                                type="text"
                                                x-model="coverAddressCity"
                                                placeholder="City"
                                                class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            />
                                        </div>

                                        <!-- State -->
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                                State
                                            </label>
                                            <select
                                                x-model="coverAddressStateId"
                                                class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            >
                                                <option value="">State</option>
                                                @foreach(\Webkul\Support\Models\State::where('country_id', 226)->orderBy('name')->get() as $state)
                                                    <option value="{{ $state->id }}">{{ $state->code }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Zip -->
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                                Zip Code
                                            </label>
                                            <input
                                                type="text"
                                                x-model="coverAddressZip"
                                                placeholder="Zip"
                                                class="w-full px-2 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>

                                    <!-- Country -->
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Country
                                        </label>
                                        <select
                                            x-model="coverAddressCountryId"
                                            @change="coverAddressStateId = ''"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                        >
                                            <option value="">-- Select Country --</option>
                                            @foreach(\Webkul\Support\Models\Country::orderBy('name')->get() as $country)
                                                <option value="{{ $country->id }}">
                                                    {{ $country->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ANNOTATION OPTIONS (Only show after page type is selected) -->
                        <div x-show="pageType && pageType !== 'cover'" class="space-y-4">

                        <!-- TASK 3: Annotation Type Selector -->
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-300 dark:border-indigo-700 rounded-lg p-3">
                            <label class="block text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-2">
                                📍 What are you annotating?
                            </label>
                            <select
                                x-model="annotationType"
                                @change="resetChildSelections()"
                                dusk="annotation-type-selector"
                                class="w-full px-3 py-2 border-2 border-indigo-400 dark:border-indigo-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-semibold"
                            >
                                <option value="room">🏠 Room (entire space)</option>
                                <option value="room_location">📍 Room Location (wall/area)</option>
                                <option value="cabinet_run">📏 Cabinet Run</option>
                                <option value="cabinet">🗄️ Individual Cabinet</option>
                                <option value="dimension">📐 Dimension/Measurement</option>
                            </select>
                            <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-1">
                                Select what type of entity you're marking on this page
                            </p>
                        </div>

                        <!-- TASK 4: Cascading Context Dropdowns -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700 rounded-lg p-3 space-y-3">
                            <h4 class="text-sm font-bold text-blue-900 dark:text-blue-300">
                                🎯 Annotation Context
                            </h4>

                            <!-- Room Selection (always shown) -->
                            <div>
                                <label class="block text-xs font-semibold text-blue-800 dark:text-blue-400 mb-1">
                                    Select Room
                                </label>
                                <select
                                    x-model="selectedRoomId"
                                    @change="filterRoomLocations(); resetChildSelections();"
                                    :disabled="loadingMetadata || availableRooms.length === 0"
                                    dusk="room-dropdown"
                                    class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                >
                                    <option value="">-- Select Room --</option>
                                    <template x-for="room in availableRooms" :key="room.id">
                                        <option :value="room.id" x-text="`${room.name} (${room.room_type})`"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-blue-600 dark:text-blue-500 mt-1" x-show="annotationType === 'room'">
                                    ✓ Drawing new rooms will use this as template
                                </p>
                            </div>

                            <!-- Room Location Selection (if needed) -->
                            <div x-show="annotationType === 'room_location' || annotationType === 'cabinet_run' || annotationType === 'cabinet'">
                                <label class="block text-xs font-semibold text-blue-800 dark:text-blue-400 mb-1">
                                    Select Room Location
                                </label>
                                <select
                                    x-model="selectedRoomLocationId"
                                    @change="filterCabinetRuns();"
                                    :disabled="!selectedRoomId || loadingMetadata"
                                    dusk="room-location-dropdown"
                                    class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
                                >
                                    <option value="">-- Select Location --</option>
                                    <template x-for="location in filteredRoomLocations" :key="location.id">
                                        <option :value="location.id" x-text="`${location.name} (${location.location_type})`"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-blue-600 dark:text-blue-500 mt-1" x-show="annotationType === 'room_location' && !selectedRoomId">
                                    ⚠️ Select a room first
                                </p>
                            </div>

                            <!-- Cabinet Run Selection (if needed) -->
                            <div x-show="annotationType === 'cabinet_run' || annotationType === 'cabinet'">
                                <label class="block text-xs font-semibold text-blue-800 dark:text-blue-400 mb-1">
                                    Select Cabinet Run
                                </label>
                                <select
                                    x-model="selectedCabinetRunId"
                                    @change="filterCabinets();"
                                    :disabled="!selectedRoomLocationId || loadingMetadata"
                                    dusk="cabinet-run-dropdown"
                                    class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
                                >
                                    <option value="">-- Select Cabinet Run --</option>
                                    <template x-for="run in filteredCabinetRuns" :key="run.id">
                                        <option :value="run.id" x-text="`${run.name} (${run.run_type})`"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-blue-600 dark:text-blue-500 mt-1" x-show="!selectedRoomLocationId">
                                    ⚠️ Select a room location first
                                </p>
                            </div>

                            <!-- Cabinet Selection (if annotating individual cabinets) -->
                            <div x-show="annotationType === 'cabinet'">
                                <label class="block text-xs font-semibold text-blue-800 dark:text-blue-400 mb-1">
                                    Select Cabinet (optional)
                                </label>
                                <select
                                    x-model="selectedCabinetId"
                                    :disabled="!selectedCabinetRunId || loadingMetadata"
                                    dusk="cabinet-dropdown"
                                    class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
                                >
                                    <option value="">-- Link to existing cabinet --</option>
                                    <template x-for="cabinet in filteredCabinets" :key="cabinet.id">
                                        <option :value="cabinet.id" x-text="`${cabinet.name || cabinet.code} - ${cabinet.width}W x ${cabinet.height}H`"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-blue-600 dark:text-blue-500 mt-1" x-show="!selectedCabinetId">
                                    💡 Leave empty to create new cabinet from annotation
                                </p>
                            </div>

                            <!-- Context Summary -->
                            <div class="bg-white dark:bg-gray-800 rounded p-2 border border-blue-200 dark:border-blue-800" dusk="context-summary">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    📋 Active Context:
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    <span x-show="!selectedRoomId">No context selected</span>
                                    <span x-show="selectedRoomId && annotationType === 'room'" x-text="`Creating new rooms in: ${availableRooms.find(r => r.id == selectedRoomId)?.name}`"></span>
                                    <span x-show="selectedRoomId && annotationType === 'room_location' && !selectedRoomLocationId" x-text="`Room: ${availableRooms.find(r => r.id == selectedRoomId)?.name}`"></span>
                                    <span x-show="selectedRoomLocationId && annotationType === 'room_location'" x-text="`${availableRooms.find(r => r.id == selectedRoomId)?.name} → ${filteredRoomLocations.find(l => l.id == selectedRoomLocationId)?.name}`"></span>
                                    <span x-show="selectedCabinetRunId && annotationType === 'cabinet_run'" x-text="`${availableRooms.find(r => r.id == selectedRoomId)?.name} → ${filteredCabinetRuns.find(r => r.id == selectedCabinetRunId)?.name}`"></span>
                                    <span x-show="selectedCabinetRunId && annotationType === 'cabinet'" x-text="`Run: ${filteredCabinetRuns.find(r => r.id == selectedCabinetRunId)?.name}${selectedCabinetId ? ' → Cabinet #' + selectedCabinetId : ' (new cabinet)'}`"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Measurement Recording Fields -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-300 dark:border-yellow-700 rounded-lg p-3 space-y-3">
                            <h4 class="text-sm font-bold text-yellow-900 dark:text-yellow-300 flex items-center gap-2">
                                📏 Measurements
                            </h4>

                            <!-- Room Measurements -->
                            <div x-show="annotationType === 'room'" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Length (ft)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementLength"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="12.5"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Width (ft)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementWidth"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="10.0"
                                        >
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Ceiling Height (ft)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementHeight"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="8.0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Square Footage
                                        </label>
                                        <input
                                            type="number"
                                            step="0.1"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400"
                                            placeholder="Auto calculated"
                                            :value="measurementLength && measurementWidth ? (measurementLength * measurementWidth).toFixed(2) : ''"
                                            readonly
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Cabinet Run Measurements -->
                            <div x-show="annotationType === 'cabinet_run'" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Total Length (inches)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementLengthInches"
                                            step="0.125"
                                            @input="measurementLinearFeet = measurementLengthInches ? (measurementLengthInches / 12).toFixed(2) : ''"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="120"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Linear Feet
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementLinearFeet"
                                            step="0.01"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400"
                                            placeholder="Auto calculated"
                                            readonly
                                        >
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Height (inches)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementHeightInches"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="36"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Depth (inches)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementDepthInches"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="24"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Cabinet Measurements -->
                            <div x-show="annotationType === 'cabinet'" class="space-y-3">
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Width (in)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementWidthInches"
                                            step="0.125"
                                            @input="measurementLinearFeet = measurementWidthInches ? (measurementWidthInches / 12).toFixed(2) : ''"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="36"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Height (in)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementHeightInches"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="30"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Depth (in)
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementDepthInches"
                                            step="0.125"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="12"
                                        >
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Linear Feet
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementLinearFeet"
                                            step="0.01"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400"
                                            placeholder="Auto calculated"
                                            readonly
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                            Doors/Drawers
                                        </label>
                                        <input
                                            type="number"
                                            x-model="measurementDoorCount"
                                            step="1"
                                            min="0"
                                            class="w-full px-2 py-1.5 text-sm border border-yellow-300 dark:border-yellow-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            placeholder="2"
                                        >
                                    </div>
                                </div>
                            </div>
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
                        <!-- END ANNOTATION OPTIONS (page type conditional) -->

                        </div>
                        <!-- END Metadata Tab -->

                        <!-- Tags Tab (Lazy-loaded Livewire Component) -->
                        <div x-show="activeTab === 'tags'" class="p-4" style="display: none;">
                            @php
                                $project = isset($pdfPage) ? ($pdfPage?->pdfDocument?->module) : null;
                            @endphp

                            @if($project)
                                @livewire('project-tags-loader', ['projectId' => $project->id])
                            @else
                                <div class="text-center text-gray-500 py-8">
                                    <p class="text-sm">Project tags will be available after saving annotations.</p>
                                </div>
                            @endif
                        </div>
                        <!-- END Tags Tab -->

                        <!-- Discussion Tab (Chatter) -->
                        <div x-show="activeTab === 'discussion'" class="h-full flex flex-col" style="display: none;">
                            @if($pdfPage)
                            <div class="flex-1 overflow-y-auto">
                                <livewire:chatter-panel
                                    :record="$pdfPage"
                                    :activityPlans="collect()"
                                    resource=""
                                    lazy
                                />
                            </div>
                            @else
                            <div class="p-4 text-center text-gray-500">
                                <p>Discussion will be available after saving annotations.</p>
                            </div>
                            @endif
                        </div>

                        <!-- History Tab -->
                        <div x-show="activeTab === 'history'" class="p-4" style="display: none;" x-data="{ historyLoading: true, historyEntries: [] }" x-init="
                            $watch('activeTab', (value) => {
                                if (value === 'history' && historyEntries.length === 0) {
                                    historyLoading = true;
                                    fetch(`/api/pdf/page/${pdfPageId}/annotations/history`)
                                        .then(res => res.json())
                                        .then(data => {
                                            historyEntries = data.history || [];
                                            historyLoading = false;
                                        })
                                        .catch(err => {
                                            console.error('Failed to load history:', err);
                                            historyLoading = false;
                                        });
                                }
                            })
                        ">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                                Annotation History
                            </h4>

                            <!-- Loading State -->
                            <div x-show="historyLoading" class="text-center py-8">
                                <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 mt-2">Loading history...</p>
                            </div>

                            <!-- History Timeline -->
                            <div x-show="!historyLoading" class="space-y-3">
                                <template x-if="historyEntries.length === 0">
                                    <p class="text-sm text-gray-500 text-center py-8">No history yet</p>
                                </template>

                                <template x-for="entry in historyEntries" :key="entry.id">
                                    <div class="border-l-2 border-gray-300 dark:border-gray-600 pl-3 pb-3">
                                        <div class="flex items-start gap-2">
                                            <span class="inline-block px-2 py-1 text-xs rounded font-medium"
                                                  :class="{
                                                      'bg-green-100 text-green-800': entry.action === 'created',
                                                      'bg-blue-100 text-blue-800': entry.action === 'updated',
                                                      'bg-red-100 text-red-800': entry.action === 'deleted',
                                                      'bg-purple-100 text-purple-800': entry.action === 'moved',
                                                      'bg-yellow-100 text-yellow-800': entry.action === 'resized',
                                                      'bg-gray-100 text-gray-800': ['selected', 'copied', 'pasted'].includes(entry.action)
                                                  }"
                                                  x-text="entry.action.toUpperCase()"></span>
                                        </div>
                                        <p class="text-sm text-gray-900 dark:text-white mt-1">
                                            <strong x-text="entry.user.name"></strong>
                                            <span x-text="` ${entry.action} an annotation`"></span>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1" x-text="entry.created_at_human"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                    <!-- END Tab Content -->

                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- V2 Annotation Modal -->
    @if($useV2)
    <template x-teleport="body">
    <div
        x-show="showAnnotationV2Modal"
        x-cloak
        @keydown.escape.window="showAnnotationV2Modal = false"
        @close-v2-modal.window="showAnnotationV2Modal = false"
        class="fixed inset-0 z-[9999] bg-black/90"
        style="display: none;"
        wire:ignore.self
    >
        <!-- V2 Viewer Content (Canvas-based, no Nutrient) -->
        <div wire:ignore>
            @include('webkul-project::filament.components.pdf-annotation-viewer-v2-canvas', [
                'pdfPageId' => $pdfPageId ?? null,
                'pdfUrl' => $pdfUrl,
                'pageNumber' => $pageNumber,
                'projectId' => $this->record->id ?? null,
            ])
        </div>
    </div>
    </template>
    @endif
</div>

{{-- Load annotation system via Vite (bundles PDF.js + Alpine component) --}}
@once
    @vite('resources/js/annotations.js')
@endonce
