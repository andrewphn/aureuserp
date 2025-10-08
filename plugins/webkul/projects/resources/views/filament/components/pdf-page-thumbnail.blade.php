<div
    class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900"
    x-data="pdfThumbnail"
    x-init="initCanvas('{{ $pdfUrl }}', {{ $pageNumber }})"
    wire:ignore
>
    <div
        class="w-full flex items-center justify-center p-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
        style="min-height: 400px;"
        @click="showModal = true"
        title="Click to view larger preview"
    >
        <canvas
            x-ref="canvas"
            class="max-w-full h-auto"
        ></canvas>
    </div>
    <div class="bg-gray-700 px-2 py-1 text-center">
        <span class="text-sm font-medium text-white">Page {{ $pageNumber }}</span>
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
            class="relative bg-white dark:bg-gray-900 rounded-lg shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-auto"
            @click.stop
        >
            <div class="sticky top-0 z-10 flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
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
            <div class="p-4 flex items-center justify-center">
                <canvas
                    x-ref="modalCanvas"
                    class="max-w-full h-auto"
                ></canvas>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfThumbnail', () => ({
                rendered: false,
                modalRendered: false,
                showModal: false,
                pdfDoc: null,
                currentPdfUrl: null,
                currentPageNum: null,

                initCanvas(pdfUrl, pageNum) {
                    if (this.rendered) return;

                    this.currentPdfUrl = pdfUrl;
                    this.currentPageNum = pageNum;

                    const canvas = this.$refs.canvas;
                    const container = canvas.parentElement;

                    pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
                        this.pdfDoc = pdf;
                        pdf.getPage(pageNum).then(page => {
                            // Calculate scale to fit container width (with padding)
                            const containerWidth = container.clientWidth - 16;
                            const unscaledViewport = page.getViewport({ scale: 1 });
                            const scale = containerWidth / unscaledViewport.width;

                            const viewport = page.getViewport({ scale: scale });
                            const context = canvas.getContext('2d');

                            canvas.height = viewport.height;
                            canvas.width = viewport.width;

                            page.render({
                                canvasContext: context,
                                viewport: viewport
                            }).promise.then(() => {
                                this.rendered = true;
                            });
                        });
                    }).catch(error => {
                        console.error('PDF rendering error:', error);
                    });
                },

                renderModalCanvas() {
                    if (this.modalRendered || !this.pdfDoc) return;

                    const modalCanvas = this.$refs.modalCanvas;
                    if (!modalCanvas) return;

                    const modalContainer = modalCanvas.parentElement;

                    this.pdfDoc.getPage(this.currentPageNum).then(page => {
                        // Get available width in modal (accounting for padding)
                        const maxWidth = modalContainer.clientWidth - 32;

                        // Calculate scale to fit modal width
                        const unscaledViewport = page.getViewport({ scale: 1 });
                        const scale = Math.min(maxWidth / unscaledViewport.width, 3); // Cap at 3x for quality

                        const viewport = page.getViewport({ scale: scale });
                        const context = modalCanvas.getContext('2d');

                        modalCanvas.height = viewport.height;
                        modalCanvas.width = viewport.width;

                        page.render({
                            canvasContext: context,
                            viewport: viewport
                        }).promise.then(() => {
                            this.modalRendered = true;
                        });
                    });
                },

                init() {
                    this.$watch('showModal', (value) => {
                        if (value && !this.modalRendered) {
                            setTimeout(() => this.renderModalCanvas(), 50);
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
@endonce
