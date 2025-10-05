@php
    $containerId = 'pdf-viewer-' . uniqid();
@endphp

<div class="w-full" style="min-height: 600px;">
    <!-- PDF Viewer Container -->
    <iframe
        id="{{ $containerId }}"
        src="{{ $documentUrl }}"
        width="100%"
        height="600px"
        style="border: 1px solid #e5e7eb; border-radius: 0.5rem;"
        title="PDF Viewer"
    ></iframe>

    <!-- Actions -->
    <div class="mt-4 flex items-center justify-between text-sm">
        <div class="text-gray-600">
            <p>Document ID: {{ $documentId }}</p>
            <p class="text-xs text-gray-500 mt-1">Nutrient SDK integration available for annotations</p>
        </div>
        <div class="flex gap-3">
            <a
                href="{{ $documentUrl }}"
                target="_blank"
                class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Open in New Tab
            </a>
            <a
                href="{{ $documentUrl }}"
                download
                class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download PDF
            </a>
        </div>
    </div>
</div>
