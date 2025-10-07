<div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900">
    <div class="w-full h-96 relative">
        <iframe
            src="{{ $pdfUrl }}#page={{ $pageNumber }}&view=FitH"
            class="w-full h-full pointer-events-none"
            frameborder="0"
            style="transform: scale(0.95); transform-origin: top center;"
        ></iframe>
    </div>
    <div class="bg-gray-700 px-2 py-1 text-center">
        <span class="text-sm font-medium text-white">Page {{ $pageNumber }}</span>
    </div>
</div>
