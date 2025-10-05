@php
    use App\Services\NutrientService;

    $nutrientService = app(NutrientService::class);

    // Get field state
    $documentId = $getDocumentId() ?? $getState();
    $documentUrl = $getDocumentUrl();
    $enableAnnotations = $getEnableAnnotations();
    $readonly = $getReadonly();
    $toolbarItems = $getToolbarItems();
    $height = $getHeight();
    $theme = $getTheme();
    $initialPage = $getInitialPage();

    // Generate unique container ID for this field instance
    $containerId = 'pdf-viewer-field-' . uniqid();

    // Build viewer configuration
    $viewerConfig = [
        'enableAnnotations' => $enableAnnotations && !$readonly,
        'initialViewState' => [
            'pageIndex' => $initialPage - 1,
        ],
        'theme' => $theme,
    ];

    // Set toolbar items if provided
    if ($toolbarItems !== null) {
        $nutrientService->setToolbarItems($toolbarItems);
    }

    // Disable annotations if readonly
    if ($readonly) {
        $nutrientService->setAnnotationsEnabled(false);
    }

    // Get SDK configuration
    $sdkConfig = $nutrientService->getSDKConfiguration($viewerConfig);
    $sdkConfigJson = json_encode($sdkConfig);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="filamentPdfViewerField({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            containerId: '{{ $containerId }}',
            documentId: {{ $documentId ? "'$documentId'" : 'null' }},
            documentUrl: {{ $documentUrl ? "'$documentUrl'" : 'null' }},
            sdkConfig: {{ $sdkConfigJson }},
            enableAnnotations: {{ $enableAnnotations ? 'true' : 'false' }},
            readonly: {{ $readonly ? 'true' : 'false' }}
        })"
        x-init="init()"
        class="filament-pdf-viewer-field"
        wire:ignore
    >
        <!-- Loading State -->
        <div
            x-show="loading"
            x-cloak
            class="pdf-viewer-loading"
            style="height: {{ $height }}; display: flex; align-items: center; justify-content: center; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem;"
        >
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-gray-600" x-text="loadingMessage">Loading PDF viewer...</p>
            </div>
        </div>

        <!-- Error State -->
        <div
            x-show="error && !loading"
            x-cloak
            class="pdf-viewer-error"
            style="height: {{ $height }}; display: flex; align-items: center; justify-content: center; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem;"
        >
            <div class="text-center max-w-md px-4">
                <svg class="h-12 w-12 text-red-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Failed to load PDF viewer</h3>
                <p class="mt-2 text-sm text-gray-600" x-text="errorMessage">An error occurred while loading the PDF viewer.</p>

                <div class="mt-6 space-x-4">
                    <button
                        @click="retry()"
                        type="button"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                    >
                        Retry
                    </button>

                    <button
                        x-show="documentUrl"
                        @click="window.open(documentUrl, '_blank')"
                        type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition inline-block"
                    >
                        Open in New Tab
                    </button>
                </div>
            </div>
        </div>

        <!-- PDF Viewer Container -->
        <div
            x-show="!loading && !error"
            id="{{ $containerId }}"
            class="pdf-viewer-container"
            style="height: {{ $height }}; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;"
        ></div>

        <!-- Annotation Save Status -->
        <div
            x-show="!loading && !error && enableAnnotations && !readonly"
            x-cloak
            class="pdf-viewer-status mt-2 flex items-center justify-between text-sm"
        >
            <div class="flex items-center space-x-2">
                <span
                    x-show="saving"
                    class="flex items-center text-blue-600"
                >
                    <svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>

                <span
                    x-show="!saving && lastSaved"
                    class="text-green-600"
                >
                    <svg class="h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Saved <span x-text="lastSaved"></span>
                </span>
            </div>

            <div class="text-gray-500">
                <span x-text="annotationCount"></span> annotations
            </div>
        </div>
    </div>
</x-dynamic-component>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('filamentPdfViewerField', (config) => ({
        state: config.state,
        containerId: config.containerId,
        documentId: config.documentId,
        documentUrl: config.documentUrl,
        sdkConfig: config.sdkConfig,
        enableAnnotations: config.enableAnnotations,
        readonly: config.readonly,

        // State
        loading: true,
        loadingMessage: 'Loading PDF viewer...',
        error: false,
        errorMessage: '',
        instance: null,
        saving: false,
        lastSaved: null,
        annotationCount: 0,

        async init() {
            if (!this.documentUrl && !this.documentId) {
                this.showError('No document URL or ID provided');
                return;
            }

            try {
                await this.loadViewer();
            } catch (err) {
                console.error('PDF Viewer initialization error:', err);
                this.showError(err.message || 'Failed to initialize PDF viewer');
            }
        },

        async loadViewer() {
            this.loadingMessage = 'Loading Nutrient SDK...';

            // Dynamically import Nutrient SDK
            if (!window.PSPDFKit) {
                try {
                    const PSPDFKit = await import('@nutrient-sdk/viewer');
                    window.PSPDFKit = PSPDFKit.default || PSPDFKit;
                } catch (err) {
                    throw new Error('Failed to load Nutrient SDK. Please check your internet connection.');
                }
            }

            this.loadingMessage = 'Loading document...';

            // Get document URL if documentId provided
            let docUrl = this.documentUrl;
            if (this.documentId && !docUrl) {
                docUrl = `/api/pdf/${this.documentId}/file`;
            }

            // Build instance configuration
            const instanceConfig = {
                ...this.sdkConfig,
                container: `#${this.containerId}`,
                document: docUrl,
                baseUrl: '/vendor/nutrient/',
            };

            // Load Nutrient instance
            this.instance = await window.PSPDFKit.load(instanceConfig);

            console.log('Nutrient PDF viewer loaded successfully', this.instance);

            // Setup annotation handlers if enabled
            if (this.enableAnnotations && !this.readonly) {
                this.setupAnnotationHandlers();
            }

            // Load existing annotations
            if (this.documentId) {
                await this.loadAnnotations();
            }

            this.loading = false;
        },

        setupAnnotationHandlers() {
            if (!this.instance) return;

            // Listen for annotation changes
            this.instance.addEventListener('annotations.create', () => this.saveAnnotations());
            this.instance.addEventListener('annotations.update', () => this.saveAnnotations());
            this.instance.addEventListener('annotations.delete', () => this.saveAnnotations());

            // Listen for successful save completion
            this.instance.addEventListener('annotations.didSave', () => {
                console.log('Annotations saved by Nutrient SDK');
                this.updateAnnotationCount();
            });

            // Update annotation count
            this.updateAnnotationCount();
        },

        async loadAnnotations() {
            if (!this.documentId) return;

            try {
                const response = await fetch(`/api/pdf/${this.documentId}/annotations`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load annotations');
                }

                const data = await response.json();

                // Import annotations into viewer
                if (data.annotations && data.annotations.length > 0) {
                    const instantJSON = {
                        format: 'https://pspdfkit.com/instant-json/v1',
                        annotations: data.annotations
                    };

                    await this.instance.importInstantJSON(instantJSON);
                    this.updateAnnotationCount();
                }
            } catch (err) {
                console.error('Failed to load annotations:', err);
            }
        },

        async saveAnnotations() {
            if (!this.documentId || this.saving || this.readonly) return;

            this.saving = true;

            try {
                // Export current annotations
                const instantJSON = await this.instance.exportInstantJSON();

                // Send to backend
                const response = await fetch(`/api/pdf/${this.documentId}/annotations`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        annotations: instantJSON.annotations
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to save annotations');
                }

                const now = new Date();
                this.lastSaved = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                this.updateAnnotationCount();

            } catch (err) {
                console.error('Failed to save annotations:', err);
            } finally {
                this.saving = false;
            }
        },

        updateAnnotationCount() {
            if (!this.instance) return;

            this.instance.getAnnotations(0).then(annotations => {
                this.annotationCount = annotations.size;
            });
        },

        showError(message) {
            this.error = true;
            this.errorMessage = message;
            this.loading = false;
        },

        retry() {
            this.error = false;
            this.errorMessage = '';
            this.loading = true;
            this.init();
        }
    }));
});
</script>
@endpush

@push('styles')
<style>
.filament-pdf-viewer-field .pdf-viewer-container {
    position: relative;
}

.filament-pdf-viewer-field [x-cloak] {
    display: none !important;
}
</style>
@endpush
