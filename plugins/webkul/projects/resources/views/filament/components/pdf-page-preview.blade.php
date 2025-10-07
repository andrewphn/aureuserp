<div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900 mb-4">
    <div class="aspect-[8.5/11] relative">
        <iframe
            src="{{ $pdfUrl }}#page={{ $pageNumber }}"
            class="w-full h-full pointer-events-none"
            frameborder="0"
        ></iframe>
        <div class="absolute inset-0 bg-transparent pointer-events-none"></div>
    </div>
</div>
