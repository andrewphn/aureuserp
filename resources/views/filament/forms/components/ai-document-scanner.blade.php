<div>
    <div
        x-data="aiDocumentScanner({
            apiEndpoint: '{{ $getApiEndpoint() }}',
            documentType: '{{ $getDocumentType() }}',
            fieldMappings: @js($getFieldMappings()),
            showCamera: {{ $getShowCamera() ? 'true' : 'false' }},
            autoApply: {{ $getAutoApply() ? 'true' : 'false' }}
        })"
        class="ai-document-scanner"
    >
        {{-- Header with Help --}}
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                AI Document Scanner - {{ $getDocumentTypeLabel() }}
            </h3>
            <button
                type="button"
                x-on:click="showHelp = !showHelp"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <x-heroicon-m-question-mark-circle class="w-5 h-5" />
            </button>
        </div>

        {{-- Help Text --}}
        <div
            x-show="showHelp"
            x-collapse
            class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300"
        >
            <p class="mb-2"><strong>{{ $getDocumentTypeDescription() }}</strong></p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>Upload:</strong> Select an image file (JPEG, PNG, PDF)</li>
                @if($getShowCamera())
                <li><strong>Camera:</strong> Take a photo directly from your device</li>
                @endif
                <li><strong>Verification:</strong> Data will be verified against existing POs and products</li>
            </ul>
            <p class="mt-2 text-xs">AI will extract data and match to your database. Review before applying.</p>
        </div>

        {{-- Upload Controls --}}
        <div class="mb-4">
            {{-- File Drop Zone --}}
            <div
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)"
                :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/20': isDragging }"
                class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center transition-colors"
            >
                <div x-show="!isLoading && !previewUrl" class="space-y-3">
                    <div class="flex justify-center">
                        <x-heroicon-o-document-arrow-up class="w-12 h-12 text-gray-400" />
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-medium">Drop your document here, or</p>
                        <div class="flex justify-center gap-2 mt-2">
                            <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 cursor-pointer transition-colors">
                                <x-heroicon-m-folder-open class="w-4 h-4" />
                                <span>Browse Files</span>
                                <input
                                    type="file"
                                    x-ref="fileInput"
                                    x-on:change="handleFileSelect($event)"
                                    accept="{{ $getAcceptedFileTypes() }}"
                                    class="hidden"
                                />
                            </label>
                            @if($getShowCamera())
                            <button
                                type="button"
                                x-on:click="startCamera()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                            >
                                <x-heroicon-m-camera class="w-4 h-4" />
                                <span>Take Photo</span>
                            </button>
                            @endif
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        JPEG, PNG, WebP, or PDF up to {{ $getMaxFileSize() }}MB
                    </p>
                </div>

                {{-- Image Preview --}}
                <div x-show="previewUrl && !isLoading" class="relative">
                    <img
                        :src="previewUrl"
                        alt="Document preview"
                        class="max-h-48 mx-auto rounded-lg shadow-sm"
                    />
                    <button
                        type="button"
                        x-on:click="clearFile()"
                        class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                    >
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                    </button>
                </div>

                {{-- Loading State --}}
                <div x-show="isLoading" class="py-8">
                    <div class="flex flex-col items-center gap-3">
                        <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="loadingMessage"></p>
                    </div>
                </div>
            </div>

            {{-- Scan Button --}}
            <div x-show="previewUrl && !isLoading && !result" class="mt-3 flex justify-center">
                <button
                    type="button"
                    x-on:click="scanDocument()"
                    class="inline-flex items-center gap-2 px-6 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
                >
                    <x-heroicon-m-sparkles class="w-5 h-5" />
                    <span>Scan with AI</span>
                </button>
            </div>
        </div>

        {{-- Camera Modal --}}
        <div
            x-show="showCameraModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
            x-cloak
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full mx-4 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white">Take Photo</h4>
                    <button
                        type="button"
                        x-on:click="stopCamera()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <x-heroicon-m-x-mark class="w-6 h-6" />
                    </button>
                </div>
                <div class="p-4">
                    <video x-ref="cameraVideo" autoplay playsinline class="w-full rounded-lg bg-black"></video>
                    <canvas x-ref="cameraCanvas" class="hidden"></canvas>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-3">
                    <button
                        type="button"
                        x-on:click="capturePhoto()"
                        class="inline-flex items-center gap-2 px-6 py-2 rounded-full text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 transition-colors"
                    >
                        <x-heroicon-m-camera class="w-5 h-5" />
                        <span>Capture</span>
                    </button>
                    <button
                        type="button"
                        x-on:click="stopCamera()"
                        class="inline-flex items-center gap-2 px-6 py-2 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    >
                        <span>Cancel</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Error Message --}}
        <div
            x-show="error"
            x-transition
            class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-start gap-2"
        >
            <x-heroicon-m-exclamation-circle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-red-700 dark:text-red-300 flex-1">
                <p x-text="error"></p>
            </div>
            <button
                type="button"
                x-on:click="error = null"
                class="text-red-400 hover:text-red-600"
            >
                <x-heroicon-m-x-mark class="w-4 h-4" />
            </button>
        </div>

        {{-- Results Panel --}}
        <div
            x-show="result"
            x-transition
            class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
        >
            {{-- Result Header --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-check-circle class="w-5 h-5 text-green-500" />
                    <span class="font-medium text-green-800 dark:text-green-200">Document Scanned</span>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Confidence Badge --}}
                    <span
                        x-show="result?.confidence"
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': result?.confidence >= 0.8,
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': result?.confidence >= 0.5 && result?.confidence < 0.8,
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': result?.confidence < 0.5
                        }"
                    >
                        <span x-text="Math.round((result?.confidence || 0) * 100) + '% confidence'"></span>
                    </span>
                </div>
            </div>

            {{-- Vendor Match --}}
            <div x-show="result?.vendor_match || result?.vendor" class="mb-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vendor</span>
                        <p class="font-medium text-gray-900 dark:text-white" x-text="result?.vendor?.name || result?.vendor_match?.name || 'Unknown'"></p>
                    </div>
                    <span
                        :class="result?.vendor_match?.matched ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'"
                        class="px-2 py-1 rounded-full text-xs font-medium"
                        x-text="result?.vendor_match?.matched ? 'Matched' : 'Not Found'"
                    ></span>
                </div>
            </div>

            {{-- PO Match --}}
            <div x-show="result?.po_match || result?.po_reference" class="mb-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Purchase Order</span>
                        <p class="font-medium text-gray-900 dark:text-white" x-text="result?.po_match?.name || result?.po_reference || 'N/A'"></p>
                    </div>
                    <span
                        x-show="result?.po_match"
                        :class="result?.po_match?.matched ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'"
                        class="px-2 py-1 rounded-full text-xs font-medium"
                        x-text="result?.po_match?.matched ? 'Matched' : 'Not Found'"
                    ></span>
                </div>
            </div>

            {{-- Line Items Summary --}}
            <div x-show="result?.line_items?.length || result?.lines?.length" class="mb-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Line Items</span>
                    <button
                        type="button"
                        x-on:click="showLineItems = !showLineItems"
                        class="text-xs text-primary-600 hover:text-primary-700"
                    >
                        <span x-text="showLineItems ? 'Hide Details' : 'Show Details'"></span>
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-2 text-sm text-center">
                    <div class="p-2 bg-white dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="(result?.line_items || result?.lines || []).length"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Lines</p>
                    </div>
                    <div class="p-2 bg-white dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold text-green-600" x-text="result?.summary?.matched_products || countMatched()"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Matched</p>
                    </div>
                    <div class="p-2 bg-white dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold text-yellow-600" x-text="result?.summary?.unmatched_products || countUnmatched()"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Unmatched</p>
                    </div>
                </div>

                {{-- Line Items Details --}}
                <div x-show="showLineItems" x-collapse class="mt-3 space-y-2">
                    <template x-for="(line, index) in (result?.line_items || result?.lines || [])" :key="index">
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-sm text-gray-900 dark:text-white" x-text="line.description || line.product_name || 'Unknown Product'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        SKU: <span x-text="line.sku || 'N/A'"></span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium" x-text="'Qty: ' + (line.quantity_shipped || line.quantity || 0)"></p>
                                    <span
                                        :class="(line.product_match?.matched || line.product_matched) ? 'text-green-600' : 'text-yellow-600'"
                                        class="text-xs"
                                        x-text="(line.product_match?.matched || line.product_matched) ? 'Matched' : 'Not Found'"
                                    ></span>
                                </div>
                            </div>
                            {{-- Verification Status --}}
                            <div x-show="line.verification" class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-2 text-xs">
                                    <template x-if="line.verification?.status === 'exact'">
                                        <span class="text-green-600">Quantity matches order</span>
                                    </template>
                                    <template x-if="line.verification?.status === 'partial'">
                                        <span class="text-yellow-600">Partial shipment</span>
                                    </template>
                                    <template x-if="line.verification?.status === 'over'">
                                        <span class="text-red-600">Over shipped!</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2 mt-4">
                <button
                    type="button"
                    x-on:click="applyResult()"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                >
                    <x-heroicon-m-check class="w-4 h-4" />
                    Apply to Form
                </button>
                <button
                    type="button"
                    x-on:click="clearResult()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                >
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Clear
                </button>
            </div>
        </div>

        {{-- Applied Success Message --}}
        <div
            x-show="applied"
            x-transition
            class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center gap-2"
        >
            <x-heroicon-m-information-circle class="w-5 h-5 text-blue-500" />
            <span class="text-sm text-blue-700 dark:text-blue-300">
                Form fields have been populated. Review the data before saving.
            </span>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('aiDocumentScanner', (config) => ({
                        // Configuration
                        apiEndpoint: config.apiEndpoint,
                        documentType: config.documentType,
                        fieldMappings: config.fieldMappings,
                        showCamera: config.showCamera,
                        autoApply: config.autoApply,

                        // State
                        isLoading: false,
                        loadingMessage: 'Analyzing document...',
                        isDragging: false,
                        error: null,
                        result: null,
                        applied: false,
                        showHelp: false,
                        showLineItems: false,
                        showCameraModal: false,
                        previewUrl: null,
                        selectedFile: null,
                        cameraStream: null,

                        // Methods
                        handleDrop(event) {
                            this.isDragging = false;
                            const files = event.dataTransfer.files;
                            if (files.length > 0) {
                                this.processFile(files[0]);
                            }
                        },

                        handleFileSelect(event) {
                            const files = event.target.files;
                            if (files.length > 0) {
                                this.processFile(files[0]);
                            }
                        },

                        processFile(file) {
                            // Validate file type
                            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                            if (!allowedTypes.includes(file.type)) {
                                this.error = 'Invalid file type. Please upload JPEG, PNG, WebP, or PDF.';
                                return;
                            }

                            // Validate file size (10MB max)
                            if (file.size > 10 * 1024 * 1024) {
                                this.error = 'File too large. Maximum size is 10MB.';
                                return;
                            }

                            this.selectedFile = file;
                            this.error = null;
                            this.result = null;
                            this.applied = false;

                            // Create preview
                            if (file.type.startsWith('image/')) {
                                this.previewUrl = URL.createObjectURL(file);
                            } else {
                                // For PDFs, show a placeholder
                                this.previewUrl = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><text x="12" y="16" text-anchor="middle" font-size="6" fill="#666">PDF</text></svg>');
                            }
                        },

                        clearFile() {
                            this.selectedFile = null;
                            this.previewUrl = null;
                            this.error = null;
                            this.result = null;
                            if (this.$refs.fileInput) {
                                this.$refs.fileInput.value = '';
                            }
                        },

                        async scanDocument() {
                            if (!this.selectedFile) {
                                this.error = 'Please select a file first';
                                return;
                            }

                            this.isLoading = true;
                            this.loadingMessage = 'Uploading document...';
                            this.error = null;
                            this.result = null;
                            this.applied = false;

                            try {
                                const formData = new FormData();
                                formData.append('document', this.selectedFile);
                                formData.append('type', this.documentType);

                                this.loadingMessage = 'AI is analyzing your document...';

                                const response = await fetch(this.apiEndpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                    body: formData
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    throw new Error(data.message || data.error || 'Scan failed');
                                }

                                if (data.success && data.data) {
                                    this.result = data.data;
                                    console.log('Document Scan Result:', this.result);

                                    // Auto-apply if configured
                                    if (this.autoApply) {
                                        this.applyResult();
                                    }
                                } else {
                                    throw new Error(data.message || 'Could not extract document data');
                                }

                            } catch (err) {
                                console.error('Document Scan Error:', err);
                                this.error = err.message || 'Failed to scan document. Please try again.';
                            } finally {
                                this.isLoading = false;
                            }
                        },

                        // Camera methods
                        async startCamera() {
                            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                this.error = 'Camera not supported on this device';
                                return;
                            }

                            try {
                                this.showCameraModal = true;
                                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                                    video: { facingMode: 'environment' },
                                    audio: false
                                });
                                this.$refs.cameraVideo.srcObject = this.cameraStream;
                            } catch (err) {
                                this.error = 'Could not access camera: ' + err.message;
                                this.showCameraModal = false;
                            }
                        },

                        stopCamera() {
                            if (this.cameraStream) {
                                this.cameraStream.getTracks().forEach(track => track.stop());
                                this.cameraStream = null;
                            }
                            this.showCameraModal = false;
                        },

                        capturePhoto() {
                            const video = this.$refs.cameraVideo;
                            const canvas = this.$refs.cameraCanvas;

                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;

                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0);

                            canvas.toBlob((blob) => {
                                const file = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
                                this.processFile(file);
                                this.stopCamera();
                            }, 'image/jpeg', 0.9);
                        },

                        // Result methods
                        countMatched() {
                            const lines = this.result?.line_items || this.result?.lines || [];
                            return lines.filter(l => l.product_match?.matched || l.product_matched).length;
                        },

                        countUnmatched() {
                            const lines = this.result?.line_items || this.result?.lines || [];
                            return lines.filter(l => !(l.product_match?.matched || l.product_matched)).length;
                        },

                        applyResult() {
                            if (!this.result) return;

                            const livewireComponent = this.findLivewireComponent();

                            if (livewireComponent) {
                                this.applyViaLivewire(livewireComponent);
                            } else {
                                this.applyDirectly();
                            }

                            this.applied = true;

                            // Show success notification
                            if (window.Filament && window.Filament.notifications) {
                                window.Filament.notifications.notification({
                                    title: 'Form Updated',
                                    description: 'Document data has been applied to the form.',
                                    status: 'success',
                                });
                            }
                        },

                        findLivewireComponent() {
                            const element = this.$el.closest('[wire\\:id]');
                            if (element) {
                                const wireId = element.getAttribute('wire:id');
                                return window.Livewire?.find(wireId);
                            }
                            return null;
                        },

                        applyViaLivewire(component) {
                            // Apply field mappings
                            Object.entries(this.fieldMappings).forEach(([resultField, formField]) => {
                                const value = this.getNestedValue(this.result, resultField);
                                if (value !== null && value !== undefined && value !== '' && value !== 'null') {
                                    component.set(`data.${formField}`, value);
                                    console.log(`Set ${formField} to:`, value);
                                }
                            });

                            component.$refresh();
                        },

                        applyDirectly() {
                            const form = this.$el.closest('form') || this.$el.closest('[wire\\:id]');
                            if (!form) return;

                            Object.entries(this.fieldMappings).forEach(([resultField, formField]) => {
                                const value = this.getNestedValue(this.result, resultField);
                                if (value !== null && value !== undefined) {
                                    this.setFormField(form, formField, value);
                                }
                            });
                        },

                        getNestedValue(obj, path) {
                            return path.split('.').reduce((current, key) => current?.[key], obj);
                        },

                        setFormField(form, fieldName, value) {
                            const selectors = [
                                `input[name*="${fieldName}"]`,
                                `select[name*="${fieldName}"]`,
                                `textarea[name*="${fieldName}"]`,
                                `[wire\\:model*="${fieldName}"]`,
                            ];

                            for (const selector of selectors) {
                                const field = form.querySelector(selector);
                                if (field) {
                                    field.value = value;
                                    field.dispatchEvent(new Event('input', { bubbles: true }));
                                    field.dispatchEvent(new Event('change', { bubbles: true }));
                                    break;
                                }
                            }
                        },

                        clearResult() {
                            this.result = null;
                            this.applied = false;
                            this.showLineItems = false;
                        },
                    }));
                });
            </script>
        @endpush
    @endonce
</div>
