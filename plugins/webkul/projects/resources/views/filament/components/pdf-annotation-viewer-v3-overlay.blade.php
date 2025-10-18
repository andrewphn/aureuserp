@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
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
        projectId: {{ $projectId }}
    })"
    x-init="init()"
    wire:ignore
    class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900"
>
    <!-- Context Bar (Top - Sticky) -->
    <div class="context-bar sticky top-0 z-50 bg-blue-800 border-b border-blue-700 p-4">
        <div class="flex items-center gap-4 flex-wrap">
            <!-- V3 Header Title -->
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    ✨ V3 Annotation System - <span x-text="`Page ${currentPage}`">Page {{ $pageNumber }}</span>
                    <span class="px-2 py-1 text-xs rounded font-semibold bg-blue-600">Page-by-Page</span>
                </h2>
            </div>

            <!-- Project Context Display -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-blue-200">📍 Context:</span>
                <span class="text-sm text-blue-100" x-text="getContextLabel()"></span>
            </div>

            <!-- Room Autocomplete -->
            <div class="relative flex-1 max-w-xs">
                <label class="block text-xs font-medium text-blue-200 mb-1">Room</label>
                <input
                    type="text"
                    x-model="roomSearchQuery"
                    @input="searchRooms($event.target.value)"
                    @focus="showRoomDropdown = true"
                    @click.away="showRoomDropdown = false"
                    placeholder="Type to search or create..."
                    class="w-full px-3 py-2 rounded-lg border border-blue-600 bg-blue-900 text-white text-sm"
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
                <label class="block text-xs font-medium text-blue-200 mb-1">Location</label>
                <input
                    type="text"
                    x-model="locationSearchQuery"
                    @input="searchLocations($event.target.value)"
                    @focus="showLocationDropdown = true"
                    @click.away="showLocationDropdown = false"
                    :disabled="!activeRoomId"
                    placeholder="Select room first..."
                    class="w-full px-3 py-2 rounded-lg border border-blue-600 bg-blue-900 text-white text-sm disabled:opacity-50 disabled:cursor-not-allowed"
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
            <div class="flex items-center gap-2 border-r border-blue-600 pr-4">
                <button
                    @click="zoomOut()"
                    :disabled="zoomLevel <= zoomMin"
                    class="px-3 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Zoom Out (25%)"
                >
                    🔍−
                </button>
                <span class="text-sm text-blue-100 font-medium min-w-[4rem] text-center" x-text="`${getZoomPercentage()}%`"></span>
                <button
                    @click="zoomIn()"
                    :disabled="zoomLevel >= zoomMax"
                    class="px-3 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Zoom In (25%)"
                >
                    🔍+
                </button>
                <button
                    @click="resetZoom()"
                    class="px-3 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-600 transition-colors text-sm"
                    title="Reset Zoom (100%)"
                >
                    ⟲
                </button>
            </div>

            <!-- Pagination Controls (NEW - Phase 2) -->
            <div class="flex items-center gap-2 border-r border-blue-600 pr-4">
                <button
                    @click="previousPage()"
                    :disabled="currentPage <= 1"
                    class="px-3 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Previous Page"
                >
                    ← Prev
                </button>
                <span class="text-sm text-blue-100 font-medium min-w-[5rem] text-center" x-text="`Page ${currentPage} of ${totalPages}`"></span>
                <button
                    @click="nextPage()"
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-600 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Next Page"
                >
                    Next →
                </button>
            </div>

            <!-- Draw Mode Buttons -->
            <div class="flex items-center gap-2">
                <!-- Draw Location (only requires Room) -->
                <button
                    @click="setDrawMode('location')"
                    :class="drawMode === 'location' ? 'bg-purple-600 text-white ring-2 ring-purple-400' : 'bg-blue-700 text-blue-200'"
                    :disabled="!canDrawLocation()"
                    class="px-4 py-2 rounded-lg hover:bg-purple-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Location (Room required)"
                >
                    📍 Draw Location
                </button>

                <!-- Draw Cabinet Run (requires Room + Location) -->
                <button
                    @click="setDrawMode('cabinet_run')"
                    :class="drawMode === 'cabinet_run' ? 'bg-blue-600 text-white ring-2 ring-blue-400' : 'bg-blue-700 text-blue-200'"
                    :disabled="!canDraw()"
                    class="px-4 py-2 rounded-lg hover:bg-blue-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Cabinet Run (Room + Location required)"
                >
                    📦 Draw Run
                </button>

                <!-- Draw Cabinet (requires Room + Location) -->
                <button
                    @click="setDrawMode('cabinet')"
                    :class="drawMode === 'cabinet' ? 'bg-green-600 text-white ring-2 ring-green-400' : 'bg-blue-700 text-blue-200'"
                    :disabled="!canDraw()"
                    class="px-4 py-2 rounded-lg hover:bg-green-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Cabinet (Room + Location required)"
                >
                    🗄️ Draw Cabinet
                </button>

                <button
                    @click="clearContext()"
                    class="px-4 py-2 rounded-lg bg-blue-700 text-blue-200 hover:bg-blue-600 transition-colors text-sm"
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
                    @click="$dispatch('close-v3-modal')"
                    class="px-4 py-2 bg-blue-700 text-white rounded-full hover:bg-blue-600 transition-colors"
                    title="Close Viewer"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Context Hint -->
        <div x-show="!canDrawLocation()" class="mt-2 text-xs text-orange-400">
            ℹ️ Select a Room to draw Locations, or Room + Location to draw Cabinet Runs/Cabinets
        </div>

        <!-- PDF Loading Status -->
        <div x-show="!pdfReady" class="mt-2 text-xs text-yellow-400">
            ⏳ Loading PDF dimensions...
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

        <!-- PDF Viewer (Center) with HTML Overlay -->
        <div class="pdf-viewer-container flex-1 bg-white dark:bg-gray-900 overflow-hidden relative">
            <!-- PDF Container -->
            <div id="pdf-container-{{ $viewerId }}" class="relative w-full h-full overflow-auto">
                <!-- PDFObject.js embed goes here -->
                <div x-ref="pdfEmbed" class="w-full h-full min-h-full"></div>

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
                            @mouseenter="$el.style.background = anno.color + '66'"
                            @mouseleave="$el.style.background = anno.color + '33'"
                            @click="selectAnnotation(anno)"
                            class="annotation-marker"
                        >
                            <div class="annotation-label absolute -top-6 left-0 bg-gray-900 text-white px-2 py-1 rounded text-xs whitespace-nowrap">
                                <span x-text="anno.label"></span>
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

                // Pagination State (NEW - Phase 2)
                currentPage: config.pageNumber || 1,  // Current page being viewed
                pageType: config.pageType || null,    // Page type (cover_page, floor_plan, elevation, etc.)

                // PDF State
                pdfReady: false,
                pageDimensions: null,
                zoomLevel: 1.0,  // Current zoom level (1.0 = 100%)
                zoomMin: 0.25,   // Minimum zoom (25%)
                zoomMax: 3.0,    // Maximum zoom (300%)

                // Context State
                activeRoomId: null,
                activeRoomName: '',
                activeLocationId: null,
                activeLocationName: '',
                drawMode: null, // 'cabinet_run' or 'cabinet'

                // Tree State
                tree: [],
                expandedNodes: [],
                selectedNodeId: null,
                loading: false,
                error: null,

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
                                    measurementHeight: updatedAnnotation.measurementHeight
                                };
                                console.log('✓ Annotation updated from Livewire:', updatedAnnotation);
                            }
                        });

                        // Step 6: Initialize page observer for multi-page support
                        this.initPageObserver();

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

                // Display PDF using direct iframe embedding (bypassing PDFObject.js bug)
                async displayPdf() {
                    console.log('📄 Displaying PDF with direct iframe embedding...');
                    console.log('🔍 PDF URL:', this.pdfUrl);
                    console.log('🔍 Current Page:', this.currentPage);  // PHASE 2: Use currentPage

                    const embedContainer = this.$refs.pdfEmbed;

                    // Create iframe directly with PDF URL
                    const iframe = document.createElement('iframe');

                    // Build PDF URL with page parameter (PHASE 2: Use currentPage)
                    let pdfSrc = this.pdfUrl;
                    if (this.currentPage && this.currentPage > 1) {
                        pdfSrc += `#page=${this.currentPage}`;
                    }
                    // Add PDF.js parameters for better display
                    if (pdfSrc.includes('#')) {
                        pdfSrc += '&view=FitH&pagemode=none&toolbar=0';
                    } else {
                        pdfSrc += '#view=FitH&pagemode=none&toolbar=0';
                    }

                    iframe.src = pdfSrc;
                    iframe.style.width = '100%';
                    iframe.style.height = '100%';
                    iframe.style.border = 'none';
                    iframe.setAttribute('type', 'application/pdf');
                    iframe.setAttribute('title', 'PDF Document');

                    // Clear container and add iframe
                    embedContainer.innerHTML = '';
                    embedContainer.appendChild(iframe);

                    console.log('✓ PDF iframe created with src:', pdfSrc);

                    // Wait for iframe to load
                    await new Promise((resolve) => {
                        iframe.onload = () => {
                            console.log('✓ PDF iframe loaded successfully');
                            resolve();
                        };
                        // Fallback timeout in case onload doesn't fire
                        setTimeout(resolve, 2000);
                    });

                    this.pdfReady = true;
                    console.log(`✓ PDF page ${this.currentPage} displayed successfully`);

                    // Attach scroll listener to PDF iframe (will be removed in Phase 4)
                    await this.attachPdfScrollListener();
                },

                // Attach scroll listener to PDF iframe for scroll tracking
                async attachPdfScrollListener() {
                    // Wait for iframe to be fully loaded
                    await new Promise(resolve => setTimeout(resolve, 1000));

                    const iframe = this.$refs.pdfEmbed.querySelector('iframe');
                    if (!iframe) {
                        console.warn('⚠️ PDF iframe not found, scroll tracking disabled');
                        return;
                    }

                    this.pdfIframe = iframe;
                    console.log('✓ PDF iframe found, attaching scroll listener');

                    // Try to access iframe content (may be blocked by same-origin policy)
                    try {
                        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

                        // Listen for scroll events inside the iframe
                        iframeDocument.addEventListener('scroll', () => {
                            const scrollTop = iframeDocument.documentElement.scrollTop || iframeDocument.body.scrollTop;
                            const scrollLeft = iframeDocument.documentElement.scrollLeft || iframeDocument.body.scrollLeft;

                            this.scrollX = scrollLeft;
                            this.scrollY = scrollTop;

                            // Update annotation positions based on scroll
                            this.updateAnnotationPositions();
                        });

                        console.log('✓ PDF scroll listener attached');
                    } catch (e) {
                        console.warn('⚠️ Cannot access PDF iframe content (CORS)', e.message);
                        console.log('   Annotations may not track scroll correctly');
                    }
                },

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

                // Get overlay rect with caching (performance optimization)
                getOverlayRect() {
                    const now = Date.now();
                    if (this._overlayRect && (now - this._lastRectUpdate) < this._rectCacheMs) {
                        return this._overlayRect;
                    }

                    const overlay = this.$refs.annotationOverlay;
                    if (!overlay) return null;

                    this._overlayRect = overlay.getBoundingClientRect();
                    this._lastRectUpdate = now;
                    return this._overlayRect;
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

                // Coordinate transformation: PDF → Screen
                pdfToScreen(pdfX, pdfY, width = 0, height = 0) {
                    if (!this.pageDimensions) return { x: 0, y: 0, width: 0, height: 0 };

                    const rect = this.getOverlayRect();
                    if (!rect) return { x: 0, y: 0, width: 0, height: 0 };

                    // Normalize PDF coordinates
                    const normalizedX = pdfX / this.pageDimensions.width;
                    const normalizedY = (this.pageDimensions.height - pdfY) / this.pageDimensions.height;

                    // Convert to screen coordinates
                    let screenX = normalizedX * rect.width;
                    let screenY = normalizedY * rect.height;
                    const screenWidth = (width / this.pageDimensions.width) * rect.width;
                    const screenHeight = (height / this.pageDimensions.height) * rect.height;

                    // Account for PDF iframe scroll offset
                    screenX -= this.scrollX;
                    screenY -= this.scrollY;

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
                        createdAt: new Date()
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
                    console.log('📥 Loading annotations...');

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

                // Context methods
                canDrawLocation() {
                    // Location drawing only requires Room + PDF ready
                    return this.activeRoomId && this.pdfReady;
                },

                canDraw() {
                    // Cabinet Run and Cabinet drawing require Room + Location + PDF ready
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

                selectNode(nodeId, type, name, parentRoomId = null) {
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

                    // Apply CSS transform to PDF container
                    const pdfEmbed = this.$refs.pdfEmbed;
                    if (pdfEmbed) {
                        pdfEmbed.style.transform = `scale(${level})`;
                        pdfEmbed.style.transformOrigin = 'top left';
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
                        console.log(`📄 Navigating to page ${this.currentPage}`);
                        await this.displayPdf();
                        await this.loadAnnotations();
                    }
                },

                async previousPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                        console.log(`📄 Navigating to page ${this.currentPage}`);
                        await this.displayPdf();
                        await this.loadAnnotations();
                    }
                },

                async goToPage(pageNum) {
                    if (pageNum >= 1 && pageNum <= this.totalPages) {
                        this.currentPage = pageNum;
                        console.log(`📄 Navigating to page ${this.currentPage}`);
                        await this.displayPdf();
                        await this.loadAnnotations();
                    }
                },

                // Check if annotation is visible within viewport (culling optimization)
                isAnnotationVisible(anno) {
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
