<div
    class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900"
    x-data="pdfThumbnailPdfJs"
    x-init="loadThumbnail('{{ $pdfUrl }}', {{ $pageNumber }})"
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

    <!-- Annotation Modal - Canvas-based Labeling System -->
    <div
        x-show="showAnnotationModal"
        x-cloak
        @keydown.escape.window="showAnnotationModal = false"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90"
        style="display: none;"
    >
        <div
            class="relative bg-white dark:bg-gray-900 rounded-lg shadow-2xl w-full h-full flex flex-col"
            @click.stop
        >
            <!-- Close Button -->
            <button
                @click="showAnnotationModal = false"
                class="absolute top-4 right-4 z-10 p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Canvas Annotation Container -->
            <div class="w-full h-full flex flex-col">
                <!-- Toolbar -->
                <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-800 p-3 border-b border-gray-300 dark:border-gray-600">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            🏷️ Label PDF Areas
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                @click="currentTool = 'rectangle'"
                                :class="currentTool === 'rectangle' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                                class="px-3 py-1 rounded text-xs font-medium transition-colors"
                                title="Draw rectangle to label area"
                            >
                                📦 Rectangle
                            </button>
                            <button
                                @click="clearLastAnnotation()"
                                class="px-3 py-1 bg-yellow-500 text-white rounded text-xs font-medium hover:bg-yellow-600 transition-colors"
                                title="Undo last annotation"
                            >
                                ↶ Undo
                            </button>
                            <button
                                @click="clearAllAnnotations()"
                                class="px-3 py-1 bg-red-500 text-white rounded text-xs font-medium hover:bg-red-600 transition-colors"
                                title="Clear all annotations"
                            >
                                🗑️ Clear All
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-600 dark:text-gray-400" x-text="`${annotations.length} labels`"></span>
                        <button
                            @click="saveAnnotations()"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isSaving || annotations.length === 0"
                            x-text="isSaving ? '💾 Saving...' : '💾 Save Labels'"
                        >
                        </button>
                    </div>
                </div>

                <!-- Canvas Container -->
                <div
                    class="flex-1 overflow-auto bg-gray-900 relative"
                    style="min-height: 600px;"
                    x-show="annotationViewerLoaded"
                >
                    <div class="flex items-center justify-center p-4">
                        <div class="relative" x-ref="canvasWrapper">
                            <!-- PDF Canvas (bottom layer) -->
                            <canvas
                                x-ref="pdfCanvas"
                                class="border border-gray-600"
                            ></canvas>
                            <!-- Annotation Canvas (top layer) -->
                            <canvas
                                x-ref="annotationCanvas"
                                class="absolute top-0 left-0 cursor-crosshair"
                                @mousedown="startDrawing($event)"
                                @mousemove="draw($event)"
                                @mouseup="stopDrawing($event)"
                                @mouseleave="cancelDrawing()"
                            ></canvas>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div
                    x-show="!annotationViewerLoaded"
                    class="flex items-center justify-center h-full text-gray-500"
                >
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm">Loading PDF labeling tool...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Label Details Modal (shows when annotation is completed) -->
    <div
        x-show="showLabelForm"
        x-cloak
        @keydown.escape.window="cancelLabel()"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/75"
        style="display: none;"
    >
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6"
            @click.stop
        >
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Label This Area</h3>

            <div class="space-y-4">
                <!-- Cabinet Run Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Link to Cabinet Run (Optional)
                    </label>
                    <select
                        x-model="currentLabel.cabinet_run_id"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">-- No Cabinet Run --</option>
                        <!-- Will be populated from backend -->
                    </select>
                </div>

                <!-- Room Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Link to Room (Optional)
                    </label>
                    <select
                        x-model="currentLabel.room_id"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">-- No Room --</option>
                        <!-- Will be populated from backend -->
                    </select>
                </div>

                <!-- Label Text -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Label Text
                    </label>
                    <input
                        type="text"
                        x-model="currentLabel.text"
                        placeholder="e.g., Base Cabinet Run A, Kitchen Island"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    />
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notes (Optional)
                    </label>
                    <textarea
                        x-model="currentLabel.notes"
                        rows="2"
                        placeholder="Additional notes..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    ></textarea>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button
                    @click="cancelLabel()"
                    class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="saveLabel()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Save Label
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script type="module">
        import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.8.69/+esm';

        // Set worker path
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.8.69/build/pdf.worker.min.mjs';

        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfThumbnailPdfJs', () => ({
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

                // Canvas annotation state
                annotations: [],
                currentTool: 'rectangle',
                isDrawing: false,
                startX: 0,
                startY: 0,
                currentRect: null,
                showLabelForm: false,
                currentLabel: {
                    text: '',
                    cabinet_run_id: '',
                    room_id: '',
                    notes: '',
                    x: 0,
                    y: 0,
                    width: 0,
                    height: 0
                },

                async loadThumbnail(pdfUrl, pageNum) {
                    this.currentPdfUrl = pdfUrl;
                    this.currentPageNum = pageNum;

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
                },

                async loadCanvasAnnotationViewer() {
                    try {
                        // Load PDF document
                        const loadingTask = pdfjsLib.getDocument(this.currentPdfUrl);
                        const pdf = await loadingTask.promise;

                        // Get the specific page
                        const page = await pdf.getPage(this.currentPageNum);

                        // Calculate scale for high-resolution viewing (2000px width)
                        const viewport = page.getViewport({ scale: 1.0 });
                        const scale = 2000 / viewport.width;
                        const scaledViewport = page.getViewport({ scale });

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

                        await page.render(renderContext).promise;
                        this.annotationViewerLoaded = true;

                        // Redraw any existing annotations
                        this.redrawAnnotations();

                        console.log('✅ Canvas annotation viewer loaded successfully');
                    } catch (error) {
                        console.error('Canvas annotation loading error:', error);
                        alert('Failed to load PDF annotation viewer: ' + error.message);
                    }
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
                        // Store rectangle coordinates for labeling
                        this.currentLabel = {
                            text: '',
                            cabinet_run_id: '',
                            room_id: '',
                            notes: '',
                            x: Math.min(this.startX, endX),
                            y: Math.min(this.startY, endY),
                            width: Math.abs(width),
                            height: Math.abs(height)
                        };

                        // Show label form
                        this.showLabelForm = true;
                    }

                    this.isDrawing = false;
                },

                cancelDrawing() {
                    if (this.isDrawing) {
                        this.isDrawing = false;
                        this.redrawAnnotations();
                    }
                },

                saveLabel() {
                    // Add annotation to array
                    this.annotations.push({
                        ...this.currentLabel,
                        id: Date.now(), // Temporary ID
                        color: '#3B82F6'
                    });

                    // Redraw canvas with new annotation
                    this.redrawAnnotations();

                    // Reset and close form
                    this.currentLabel = {
                        text: '',
                        cabinet_run_id: '',
                        room_id: '',
                        notes: '',
                        x: 0,
                        y: 0,
                        width: 0,
                        height: 0
                    };
                    this.showLabelForm = false;
                },

                cancelLabel() {
                    this.showLabelForm = false;
                    this.currentLabel = {
                        text: '',
                        cabinet_run_id: '',
                        room_id: '',
                        notes: '',
                        x: 0,
                        y: 0,
                        width: 0,
                        height: 0
                    };
                    this.redrawAnnotations();
                },

                redrawAnnotations() {
                    const canvas = this.$refs.annotationCanvas;
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Draw all saved annotations
                    this.annotations.forEach(annotation => {
                        ctx.strokeStyle = annotation.color || '#3B82F6';
                        ctx.lineWidth = 3;
                        ctx.strokeRect(annotation.x, annotation.y, annotation.width, annotation.height);

                        // Draw label text
                        if (annotation.text) {
                            ctx.fillStyle = annotation.color || '#3B82F6';
                            ctx.font = 'bold 16px sans-serif';
                            ctx.fillText(annotation.text, annotation.x + 5, annotation.y - 5);
                        }
                    });
                },

                clearLastAnnotation() {
                    if (this.annotations.length > 0) {
                        this.annotations.pop();
                        this.redrawAnnotations();
                    }
                },

                clearAllAnnotations() {
                    if (confirm('Clear all annotations? This cannot be undone.')) {
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
                        // Get PDF document ID from URL
                        const urlParams = new URLSearchParams(window.location.search);
                        const pdfId = urlParams.get('pdf');

                        if (!pdfId) {
                            throw new Error('PDF ID not found in URL');
                        }

                        // Prepare annotations data
                        const annotationsData = {
                            page_number: this.currentPageNum,
                            annotations: this.annotations.map(ann => ({
                                type: 'rectangle',
                                x: ann.x,
                                y: ann.y,
                                width: ann.width,
                                height: ann.height,
                                text: ann.text,
                                cabinet_run_id: ann.cabinet_run_id || null,
                                room_id: ann.room_id || null,
                                notes: ann.notes || null,
                                color: ann.color
                            }))
                        };

                        // Send to backend API
                        const response = await fetch(`/api/pdf/annotations/page/${pdfId}`, {
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
            }));
        });
    </script>
    @endpush
@endonce
