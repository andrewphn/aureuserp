<div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
    <div class="w-full flex items-center justify-center p-2" style="min-height: 400px;">
        <canvas
            id="pdf-canvas-{{ $pageNumber }}"
            class="max-w-full h-auto"
            data-pdf-url="{{ $pdfUrl }}"
            data-page-number="{{ $pageNumber }}"
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

        function renderPdfThumbnails() {
            document.querySelectorAll('canvas[data-pdf-url]').forEach(canvas => {
                // Skip if already rendered
                if (canvas.dataset.rendered === 'true') {
                    return;
                }

                const url = canvas.getAttribute('data-pdf-url');
                const pageNum = parseInt(canvas.getAttribute('data-page-number'));
                const container = canvas.parentElement;

                pdfjsLib.getDocument(url).promise.then(pdf => {
                    pdf.getPage(pageNum).then(page => {
                        // Calculate scale to fit container width (with padding)
                        const containerWidth = container.clientWidth - 16; // Account for padding
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
                            canvas.dataset.rendered = 'true';
                        });
                    });
                });
            });
        }

        // Initial render on page load
        document.addEventListener('DOMContentLoaded', renderPdfThumbnails);

        // Re-render after Livewire updates
        document.addEventListener('livewire:navigated', renderPdfThumbnails);
        Livewire.hook('morph.updated', () => {
            setTimeout(renderPdfThumbnails, 100);
        });
    </script>
    @endpush
@endonce
