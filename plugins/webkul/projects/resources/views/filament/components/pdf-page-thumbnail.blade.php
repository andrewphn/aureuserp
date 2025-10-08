<div
    class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900"
    x-data="pdfThumbnail"
    x-init="initCanvas('{{ $pdfUrl }}', {{ $pageNumber }})"
    wire:ignore
>
    <div class="w-full flex items-center justify-center p-2" style="min-height: 400px;">
        <canvas
            x-ref="canvas"
            class="max-w-full h-auto"
        ></canvas>
    </div>
    <div class="bg-gray-700 px-2 py-1 text-center">
        <span class="text-sm font-medium text-white">Page {{ $pageNumber }}</span>
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

                initCanvas(pdfUrl, pageNum) {
                    if (this.rendered) return;

                    const canvas = this.$refs.canvas;
                    const container = canvas.parentElement;

                    pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
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
                }
            }));
        });
    </script>
    @endpush
@endonce
