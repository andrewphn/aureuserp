<div
    class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900"
    x-data="pdfThumbnailServerSide"
    x-init="loadThumbnail({{ $pdfId }}, {{ $pageNumber }})"
    wire:ignore
>
    <div
        class="w-full flex items-center justify-center p-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        style="min-height: 400px;"
        @click="showModal = true"
        title="Click to view larger preview"
    >
        <img
            x-ref="thumbnail"
            x-show="imageLoaded"
            class="max-w-full h-auto"
            alt="Page {{ $pageNumber }}"
        />
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
                    <img
                        x-ref="modalImage"
                        x-show="modalImageLoaded"
                        class="max-w-full h-auto"
                        alt="Page {{ $pageNumber }} - Full Size"
                    />
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

    <!-- Annotation Modal with Nutrient -->
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
            @if(isset($pdfPageId) && isset($pdfDocument))
                @include('webkul-project::filament.components.pdf-annotation-viewer', [
                    'pdfPageId' => $pdfPageId,
                    'pdfUrl' => \Illuminate\Support\Facades\Storage::disk('public')->url($pdfDocument->file_path),
                    'pageNumber' => $pageNumber,
                ])
            @else
                <div class="flex items-center justify-center h-full text-gray-500">
                    <p>PDF page data not available for annotation</p>
                </div>
            @endif

            <button
                @click="showAnnotationModal = false"
                class="absolute top-4 right-4 z-10 p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfThumbnailServerSide', () => ({
                imageLoaded: false,
                showModal: false,
                showAnnotationModal: false,
                error: false,
                modalImageLoaded: false,
                modalError: false,
                currentPdfId: null,
                currentPageNum: null,

                async loadThumbnail(pdfId, pageNum) {
                    this.currentPdfId = pdfId;
                    this.currentPageNum = pageNum;

                    try {
                        const response = await fetch(`/api/pdf/${pdfId}/page/${pageNum}/render?width=800`);
                        const data = await response.json();

                        if (data.url) {
                            this.$refs.thumbnail.src = data.url;
                            this.$refs.thumbnail.onload = () => {
                                this.imageLoaded = true;
                            };
                        } else {
                            this.error = true;
                        }
                    } catch (err) {
                        console.error('Error loading thumbnail:', err);
                        this.error = true;
                    }
                },

                async loadModalImage() {
                    if (this.modalImageLoaded) return;

                    try {
                        // Load higher resolution for modal (1400px width)
                        const response = await fetch(`/api/pdf/${this.currentPdfId}/page/${this.currentPageNum}/render?width=1400`);
                        const data = await response.json();

                        if (data.url) {
                            this.$refs.modalImage.src = data.url;
                            this.$refs.modalImage.onload = () => {
                                this.modalImageLoaded = true;
                            };
                        } else {
                            this.modalError = true;
                        }
                    } catch (err) {
                        console.error('Error loading modal image:', err);
                        this.modalError = true;
                    }
                },

                init() {
                    this.$watch('showModal', (value) => {
                        if (value) {
                            setTimeout(() => this.loadModalImage(), 100);
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
@endonce
