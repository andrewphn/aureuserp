<x-filament-panels::page>
    {{-- Full-screen PDF Annotation Viewer --}}
    <div class="w-full" style="height: calc(100vh - 120px);">
        @if($pdfUrl)
            @include('webkul-project::filament.components.pdf-annotation-viewer', [
                'pdfPageId' => $pdfPage?->id,
                'pdfUrl' => $pdfUrl,
                'pageNumber' => $pageNumber,
                'projectId' => $projectId,
                'totalPages' => $totalPages,
                'pageType' => $pageType,
                'pageMap' => $pageMap,
            ])
        @else
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="text-red-500 text-lg font-semibold mb-2">
                        ⚠️ No PDF Found
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">
                        This project doesn't have a PDF document attached.
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- Load annotation system via Vite (bundles PDF.js + Alpine component with modular managers) --}}
    @once
        @vite('plugins/webkul/projects/resources/js/pdf-viewer.js')
    @endonce
</x-filament-panels::page>
