{{-- FilamentPHP Infolist wrapper for the universal PDF Canvas Viewer component --}}
@php
    $document = $getState();
@endphp

<x-pdf-canvas-viewer
    :document="$document"
    height="700px"
    :showControls="true"
    :showDocumentInfo="true"
/>
