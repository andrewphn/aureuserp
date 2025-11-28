@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
    'totalPages' => 1,
    'pageType' => null,
    'pageMap' => [],
])

@php
    // Generate unique ID for this viewer instance
    $viewerId = 'overlayViewer_' . $pdfPageId . '_' . uniqid();
@endphp

@once
    @vite('plugins/webkul/projects/resources/css/pdf-annotation-viewer.css')
@endonce

<div
    wire:ignore
    x-cloak
    x-data="annotationSystemV3({
        pdfUrl: '{{ $pdfUrl }}',
        pageNumber: {{ $pageNumber }},
        pdfPageId: {{ $pdfPageId ?? 'null' }},
        projectId: {{ $projectId }},
        totalPages: {{ $totalPages }},
        pageType: {{ $pageType ? "'" . $pageType . "'" : 'null' }},
        pageMap: {{ json_encode($pageMap) }}
    })"
    x-init="init()"
    class="w-full h-full flex flex-col bg-gray-100 dark:bg-gray-900 overflow-hidden"
>
    @include('webkul-project::components.pdf.context-bar')

    <!-- Isolation Mode Breadcrumb (NEW - Illustrator-style) -->
    <div x-show="isolationMode" x-transition class="isolation-breadcrumb bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 border-b-4 border-primary-500 px-6 py-4 shadow-lg">
        <div class="flex items-center gap-4">
            <!-- Lock Icon + Label -->
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                <span class="text-sm font-bold text-primary-900 dark:text-primary-100 uppercase tracking-wide">
                    Isolation Mode
                </span>
            </div>

            <!-- Breadcrumb Path (using computed property) -->
            <div class="flex items-center gap-2 text-sm font-semibold text-primary-800 dark:text-primary-200">
                <template x-for="(crumb, index) in isolationBreadcrumbs" :key="crumb.level">
                    <div class="flex items-center gap-2">
                        <!-- Chevron separator (skip for first item) -->
                        <template x-if="index > 0">
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 text-primary-600" />
                        </template>

                        <!-- Breadcrumb item -->
                        <span class="flex items-center gap-1.5">
                            <span class="text-lg" x-text="crumb.icon"></span>
                            <span x-text="crumb.label"></span>
                        </span>
                    </div>
                </template>
            </div>

            <!-- Exit Button -->
            <button
                @click="exitIsolationMode()"
                class="ml-auto px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-semibold shadow-md transition-all flex items-center gap-2"
                title="Exit Isolation Mode (Esc)"
            >
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-4 w-4" />
                <span>Exit Isolation</span>
            </button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content-area flex flex-1 min-h-0 overflow-hidden">
        @include('webkul-project::components.pdf.tree.sidebar')

        <!-- Context Menu Overlay (Global) -->
        <div
            x-show="contextMenu.show"
            @click.away="contextMenu.show = false"
            :style="`position: fixed; top: ${contextMenu.y}px; left: ${contextMenu.x}px; z-index: 9999;`"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[180px]"
        >
            <button
                @click="deleteTreeNode()"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 text-sm"
                style="color: var(--danger-600);"
            >
                <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                <span>Delete <span x-text="contextMenu.nodeType === 'room' ? 'Room' : contextMenu.nodeType === 'room_location' ? 'Location' : 'Cabinet Run'"></span></span>
            </button>
        </div>

        <!-- PDF Viewer (Center) with HTML Overlay -->
        <div class="pdf-viewer-container flex flex-col flex-1 min-h-0 bg-white dark:bg-gray-900 overflow-hidden relative">
            @include('webkul-project::components.pdf.canvas.loading-skeleton')

            @include('webkul-project::components.pdf.canvas.error-display')

            @include('webkul-project::components.pdf.canvas-container')
        </div>
    </div>

    {{-- Filament Annotation Editor Component --}}
    @livewire('annotation-editor')

    {{-- Hierarchy Builder Modal Component --}}
    @livewire('hierarchy-builder-modal')


@once
    <!-- Load PDFObject.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.3.0/pdfobject.min.js"></script>

    <!-- PDF.js is loaded via annotations.js Vite bundle (pdfjs-dist v5.4.296) -->
    <!-- The bundled version is already configured with worker and exported to window.pdfjsLib -->

@endonce
