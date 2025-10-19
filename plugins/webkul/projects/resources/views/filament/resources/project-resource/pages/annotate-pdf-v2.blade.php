<x-filament-panels::page>
    <x-slot name="footer">
        {{ $this->form }}
    </x-slot>

    {{-- Full-screen V3 HTML Overlay PDF Annotation Viewer --}}
    <div class="w-full" style="height: calc(100vh - 120px);">
        @if($pdfUrl)
            @include('webkul-project::filament.components.pdf-annotation-viewer-v3-overlay', [
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
</x-filament-panels::page>
