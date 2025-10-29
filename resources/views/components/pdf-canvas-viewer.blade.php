@props([
    'document' => null,
    'height' => '700px',
    'showControls' => true,
    'showDocumentInfo' => true,
])

@php
    // Build pageMap from document pages
    $pageMap = [];
    if ($document && $document->pages) {
        foreach ($document->pages as $page) {
            $pageMap[$page->page_number] = $page->id;
        }
    }
@endphp

@if($document)
    <div class="w-full"
         x-data="{
            currentPage: 1,
            totalPages: {{ $document->page_count ?? 1 }},
            pdfUrl: '{{ Storage::disk('public')->url($document->file_path) }}',
            loading: false,
            error: null,
            zoomLevel: 1.0,
            showAnnotations: true,
            annotations: [],
            canvasScale: 1.0,
            canvasWidth: 0,
            canvasHeight: 0,
            pdfPageId: null,
            pageMap: {{ json_encode($pageMap) }},
            pageDimensions: { width: 0, height: 0 },

            async init() {
                console.log('📄 PDF Canvas Viewer initializing...');
                this.updatePdfPageId();
                await this.displayPdf();
                await this.loadAnnotations();
            },

            updatePdfPageId() {
                const newPdfPageId = this.pageMap[this.currentPage];
                if (newPdfPageId) {
                    this.pdfPageId = newPdfPageId;
                    console.log(`✓ Updated pdfPageId to ${this.pdfPageId} for page ${this.currentPage}`);
                } else {
                    console.warn(`⚠️ No pdfPageId found for page ${this.currentPage}`);
                }
            },

            async displayPdf() {
                if (!window.pdfjsLib) {
                    this.error = 'PDF.js library not loaded';
                    console.error('❌ PDF.js not available');
                    return;
                }

                this.loading = true;
                this.error = null;
                const canvasContainer = this.$refs.pdfCanvas;

                try {
                    console.log('📄 Rendering PDF page to canvas...');
                    console.log('🔍 PDF URL:', this.pdfUrl);
                    console.log('🔍 Current Page:', this.currentPage);

                    // Load PDF document
                    const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
                    const pdf = await loadingTask.promise;

                    // Get the specific page
                    const page = await pdf.getPage(this.currentPage);

                    // Get unscaled viewport for dimension reference
                    const unscaledViewport = page.getViewport({ scale: 1.0 });

                    // Store page dimensions for annotation coordinate transformations
                    this.pageDimensions = {
                        width: unscaledViewport.width,
                        height: unscaledViewport.height
                    };

                    // Calculate scale to fit container width
                    const containerWidth = canvasContainer.clientWidth;
                    const baseScale = containerWidth / unscaledViewport.width;
                    const scale = baseScale * this.zoomLevel;
                    const scaledViewport = page.getViewport({ scale });

                    // Create canvas with scaled dimensions
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.width = scaledViewport.width;
                    canvas.height = scaledViewport.height;

                    // Style canvas to fit container
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
                    canvasContainer.innerHTML = '';
                    canvasContainer.appendChild(canvas);

                    // Store canvas scale and dimensions for annotation positioning
                    this.canvasScale = scale;
                    this.canvasWidth = canvas.width;
                    this.canvasHeight = canvas.height;

                    console.log('✓ PDF page rendered successfully');
                    console.log(`✓ Canvas dimensions: ${canvas.width} × ${canvas.height}`);

                    // Clean up PDF document
                    await pdf.destroy();

                    this.loading = false;
                } catch (error) {
                    console.error('❌ Failed to render PDF:', error);
                    this.error = 'Failed to load PDF page';
                    this.loading = false;
                }
            },

            async loadAnnotations() {
                if (!this.pdfPageId) {
                    console.warn('⚠️ No pdfPageId available, skipping annotation load');
                    this.annotations = [];
                    return;
                }

                console.log(`📥 Loading annotations for page ${this.currentPage} (pdfPageId: ${this.pdfPageId})...`);

                try {
                    const response = await fetch(`/api/pdf/page/${this.pdfPageId}/annotations`);
                    const data = await response.json();

                    if (data.success && data.annotations) {
                        // Transform normalized coordinates to screen coordinates
                        this.annotations = data.annotations.map(anno => {
                            // Calculate screen position from normalized coordinates
                            const screenX = anno.x * this.pageDimensions.width * this.canvasScale;
                            const screenY = (1 - anno.y) * this.pageDimensions.height * this.canvasScale; // Invert Y
                            const screenWidth = anno.width * this.pageDimensions.width * this.canvasScale;
                            const screenHeight = anno.height * this.pageDimensions.height * this.canvasScale;

                            return {
                                id: anno.id,
                                x: screenX,
                                y: screenY,
                                width: screenWidth,
                                height: screenHeight,
                                label: anno.text || anno.annotation_type,
                                color: anno.color || '#3b82f6',
                                type: anno.annotation_type
                            };
                        });

                        console.log(`✓ Loaded and transformed ${this.annotations.length} annotations`);
                    } else {
                        this.annotations = [];
                    }
                } catch (error) {
                    console.warn('Could not load annotations:', error);
                    this.annotations = [];
                }
            },

            toggleAnnotations() {
                this.showAnnotations = !this.showAnnotations;
                console.log(`📝 Annotations ${this.showAnnotations ? 'shown' : 'hidden'}`);
            },

            async prevPage() {
                if (this.currentPage <= 1) return;
                this.currentPage--;
                this.updatePdfPageId();
                await this.displayPdf();
                await this.loadAnnotations();
            },

            async nextPage() {
                if (this.currentPage >= this.totalPages) return;
                this.currentPage++;
                this.updatePdfPageId();
                await this.displayPdf();
                await this.loadAnnotations();
            }
         }"
         x-init="init()"
    >
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800">
            @if($document->mime_type === 'application/pdf')
                <!-- PDF Page Display with Canvas -->
                <div class="relative bg-white flex items-center justify-center overflow-auto" style="height: {{ $height }};">
                    <!-- Loading Indicator -->
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Loading page...</span>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div x-show="error" class="absolute inset-0 flex items-center justify-center bg-white z-10">
                        <div class="text-center">
                            <div class="text-red-500 text-lg font-semibold mb-2">
                                ⚠️ Error
                            </div>
                            <p class="text-gray-600 dark:text-gray-400" x-text="error"></p>
                        </div>
                    </div>

                    <!-- Canvas Container -->
                    <div x-ref="pdfCanvas" class="w-full h-full flex items-center justify-center relative">
                    </div>

                    <!-- Annotation Overlay (positioned as absolute overlay, NOT inside canvas container) -->
                    <div
                        x-ref="annotationOverlay"
                        class="absolute top-0 left-0 pointer-events-none"
                        style="z-index: 10;"
                        :style="`width: ${canvasWidth}px; height: ${canvasHeight}px;`"
                    >
                        <template x-for="annotation in annotations" :key="annotation.id">
                            <div
                                x-show="showAnnotations"
                                class="absolute border-2 rounded"
                                :style="`
                                    transform: translate(${annotation.x}px, ${annotation.y}px);
                                    width: ${annotation.width}px;
                                    height: ${annotation.height}px;
                                    border-color: ${annotation.color || '#3b82f6'};
                                    background-color: ${annotation.color || '#3b82f6'}33;
                                `"
                            >
                                <!-- Annotation Label -->
                                <div
                                    x-show="annotation.label"
                                    class="absolute -top-6 left-0 px-2 py-1 text-xs font-semibold rounded shadow-lg whitespace-nowrap"
                                    :style="`
                                        background-color: ${annotation.color || '#3b82f6'};
                                        color: white;
                                    `"
                                    x-text="annotation.label"
                                ></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Navigation Controls -->
                @if($showControls)
                    <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <!-- Previous Button -->
                            <button
                                @click="prevPage()"
                                :disabled="currentPage <= 1"
                                :class="currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                <span>Previous</span>
                            </button>

                            <!-- Page Counter and Annotation Toggle -->
                            <div class="flex items-center gap-4">
                                <!-- Page Counter -->
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Page</span>
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="currentPage"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">of</span>
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="totalPages"></span>
                                </div>

                                <!-- Annotation Toggle -->
                                <button
                                    @click="toggleAnnotations()"
                                    :class="showAnnotations ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors hover:opacity-80"
                                    title="Toggle annotations"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span x-show="showAnnotations">Hide</span>
                                    <span x-show="!showAnnotations">Show</span>
                                    <span>Annotations</span>
                                    <span x-show="annotations.length > 0" class="ml-1 px-1.5 py-0.5 rounded-full text-xs font-bold bg-primary-500 text-white" x-text="annotations.length"></span>
                                </button>
                            </div>

                            <!-- Next Button -->
                            <button
                                @click="nextPage()"
                                :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                <span>Next</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            @else
                <!-- Single Image Display -->
                <div class="relative bg-gray-50 dark:bg-gray-900 flex items-center justify-center" style="height: {{ $height }};">
                    <img
                        src="{{ Storage::disk('public')->url($document->file_path) }}"
                        alt="{{ $document->file_name }}"
                        class="max-w-full max-h-full object-contain shadow-lg p-4"
                    />
                </div>
            @endif

            <!-- Document Info Footer -->
            @if($showDocumentInfo)
                <div class="border-t border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $document->file_name }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($document->document_type)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                                    {{ ucfirst(str_replace('_', ' ', $document->document_type)) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @if($document->notes)
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                            {{ $document->notes }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Load PDF.js library for canvas rendering --}}
    @once
        @vite('resources/js/annotations.js')
    @endonce
@else
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800 p-8">
        <div class="text-center text-gray-500">
            No document provided
        </div>
    </div>
@endif
