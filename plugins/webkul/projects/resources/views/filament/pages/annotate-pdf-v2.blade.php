<x-filament-panels::page>
    {{-- Full-screen V3 HTML Overlay PDF Annotation Viewer --}}
    <div class="w-full h-screen overflow-hidden">
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

    {{-- Load entity store and core app functionality --}}
    @once
        @vite('resources/js/app.js')
    @endonce

    {{-- Load refactored PDF viewer system via Vite (includes manager-based architecture) --}}
    @once
        @vite('plugins/webkul/projects/resources/js/pdf-viewer.js')
    @endonce

    {{-- Set active project context for global footer --}}
    <script>
        document.addEventListener('alpine:init', () => {
            // Wait for entity store to be ready
            const setProjectContext = () => {
                if (window.Alpine && Alpine.store('entityStore')) {
                    const entityStore = Alpine.store('entityStore');
                    entityStore.setActiveContext('project', {{ $projectId }}, null, true);
                    console.log('[AnnotatePdfV2] Set active project context:', {{ $projectId }});
                } else {
                    // Retry if store not ready yet
                    setTimeout(setProjectContext, 100);
                }
            };
            setProjectContext();
        });
    </script>
</x-filament-panels::page>
