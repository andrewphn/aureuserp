@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
])

<div
    x-data="pdfAnnotationViewer"
    x-init="init({{ $pdfPageId }}, '{{ $pdfUrl }}', {{ $pageNumber }})"
    wire:ignore
    class="w-full h-full flex flex-col"
>
    <!-- Toolbar -->
    <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-800 p-3 border-b border-gray-300 dark:border-gray-600">
        <div class="flex items-center gap-2">
            <!-- Annotation Mode Toggle -->
            <button
                @click="toggleAnnotationMode('cabinet_run')"
                :class="annotationMode === 'cabinet_run' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-blue-500 hover:text-white transition-colors"
                title="Draw Cabinet Run Box"
            >
                📦 Cabinet Run
            </button>
            <button
                @click="toggleAnnotationMode('cabinet')"
                :class="annotationMode === 'cabinet' ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-green-500 hover:text-white transition-colors"
                title="Draw Cabinet Box (within a Run)"
                :disabled="!selectedRunAnnotation"
            >
                🗄️ Cabinet
            </button>
            <span x-show="annotationMode" class="text-sm text-gray-600 dark:text-gray-400">
                Click and drag to draw a box
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button
                @click="saveAnnotations()"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
            >
                💾 Save Annotations
            </button>
            <button
                @click="exitAnnotationMode()"
                x-show="annotationMode"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
            >
                ✖️ Cancel
            </button>
        </div>
    </div>

    <!-- Nutrient Container -->
    <div
        x-ref="nutrientContainer"
        class="flex-1 bg-white dark:bg-gray-900"
        style="min-height: 600px;"
    ></div>

    <!-- Annotation Linking Modal -->
    <div
        x-show="showLinkingModal"
        x-cloak
        @click.away="closeLinkingModal()"
        @keydown.escape.window="closeLinkingModal()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75"
        style="display: none;"
    >
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-2xl max-w-2xl w-full p-6" @click.stop>
            <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                Create Annotation
            </h3>

            <!-- Annotation Type Selector -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    What are you annotating?
                </label>
                <select
                    x-model="annotationType"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    <option value="room">Room (Floor Plan)</option>
                    <option value="room_location">Room Location (Wall/Island)</option>
                    <option value="cabinet_run">Cabinet Run (Elevation)</option>
                    <option value="cabinet">Individual Cabinet</option>
                </select>
            </div>

            <!-- Room Annotation -->
            <div x-show="annotationType === 'room'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Room Name
                    </label>
                    <input
                        type="text"
                        x-model="currentAnnotationLabel"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        placeholder="e.g., Kitchen, Master Bath"
                    >
                </div>
            </div>

            <!-- Room Location Annotation -->
            <div x-show="annotationType === 'room_location'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Room
                    </label>
                    <select
                        x-model="selectedRoomId"
                        @change="filterRoomLocations()"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">-- Select Room --</option>
                        <template x-for="room in availableRooms" :key="room.id">
                            <option :value="room.id" x-text="room.display_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Location Name
                    </label>
                    <input
                        type="text"
                        x-model="currentAnnotationLabel"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        placeholder="e.g., North Wall, Island"
                    >
                </div>
            </div>

            <!-- Cabinet Run Selection (for cabinet_run annotations) -->
            <div x-show="annotationType === 'cabinet_run'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Room
                    </label>
                    <select
                        x-model="selectedRoomId"
                        @change="filterRoomLocations()"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">-- Select Room --</option>
                        <template x-for="room in availableRooms" :key="room.id">
                            <option :value="room.id" x-text="room.display_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Location
                    </label>
                    <select
                        x-model="selectedRoomLocationId"
                        @change="filterCabinetRuns()"
                        :disabled="!selectedRoomId"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">-- Select Location --</option>
                        <template x-for="location in filteredRoomLocations" :key="location.id">
                            <option :value="location.id" x-text="location.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Run Name
                    </label>
                    <input
                        type="text"
                        x-model="currentAnnotationLabel"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        placeholder="e.g., Base Run 1, Wall Run A"
                    >
                </div>
            </div>

            <!-- Cabinet Selection (for cabinet annotations) -->
            <div x-show="annotationType === 'cabinet'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Room
                    </label>
                    <select
                        x-model="selectedRoomId"
                        @change="filterRoomLocations()"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">-- Select Room --</option>
                        <template x-for="room in availableRooms" :key="room.id">
                            <option :value="room.id" x-text="room.display_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Location
                    </label>
                    <select
                        x-model="selectedRoomLocationId"
                        @change="filterCabinetRuns()"
                        :disabled="!selectedRoomId"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">-- Select Location --</option>
                        <template x-for="location in filteredRoomLocations" :key="location.id">
                            <option :value="location.id" x-text="location.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Cabinet Run
                    </label>
                    <select
                        x-model="selectedCabinetRunId"
                        @change="filterCabinets()"
                        :disabled="!selectedRoomLocationId"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">-- Select Cabinet Run --</option>
                        <template x-for="run in filteredCabinetRuns" :key="run.id">
                            <option :value="run.id" x-text="run.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Cabinet Label
                    </label>
                    <input
                        type="text"
                        x-model="currentAnnotationLabel"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        placeholder="e.g., Cabinet 1, Upper Corner"
                    >
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 mt-6">
                <button
                    @click="closeLinkingModal()"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="confirmLinking()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Link & Save
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfAnnotationViewer', () => ({
                nutrientInstance: null,
                pdfPageId: null,
                pdfUrl: null,
                pageNumber: null,
                annotationMode: null, // 'cabinet_run' or 'cabinet'
                selectedRunAnnotation: null, // Currently selected cabinet run annotation
                showLinkingModal: false,
                currentPendingAnnotation: null,
                currentAnnotationType: null,
                currentAnnotationLabel: '',
                selectedCabinetRunId: '',
                selectedCabinetId: '',
                availableCabinetRuns: [],
                cabinetsInRun: [],

                // Multi-pass annotation system state
                annotationType: 'cabinet_run', // 'room' | 'room_location' | 'cabinet_run' | 'cabinet'
                selectedRoomId: null,
                selectedRoomLocationId: null,
                projectId: null,

                // Available entities (from context API)
                availableRooms: [],
                availableRoomLocations: [],
                availableCabinets: [],

                // Filtered entities based on parent selection
                filteredRoomLocations: [],
                filteredCabinetRuns: [],
                filteredCabinets: [],

                async init(pdfPageId, pdfUrl, pageNumber) {
                    this.pdfPageId = pdfPageId;
                    this.pdfUrl = pdfUrl;
                    this.pageNumber = pageNumber;

                    await this.loadNutrient();
                    await this.loadAvailableCabinetRuns();
                },

                async loadNutrient() {
                    if (this.nutrientInstance || !this.$refs.nutrientContainer) return;

                    try {
                        // Load existing annotations from database
                        const annotationsResponse = await fetch(`/api/pdf/annotations/page/${this.pdfPageId}`);
                        const instantJson = await annotationsResponse.json();

                        this.nutrientInstance = await PSPDFKit.load({
                            container: this.$refs.nutrientContainer,
                            document: this.pdfUrl,
                            licenseKey: '{{ config("nutrient.license_key") }}',
                            baseUrl: '{{ config("nutrient.base_url") }}',
                            instantJSON: instantJson,
                            initialViewState: new PSPDFKit.ViewState({
                                currentPageIndex: this.pageNumber - 1,
                                zoom: PSPDFKit.ZoomMode.FIT_TO_WIDTH,
                            }),
                            toolbarItems: [
                                'sidebar-thumbnails',
                                'zoom-in',
                                'zoom-out',
                                'spacer',
                                'search',
                            ],
                        });

                        // Listen for annotation selection
                        this.nutrientInstance.addEventListener('annotations.change', () => {
                            this.updateSelectedAnnotation();
                        });

                    } catch (error) {
                        console.error('Nutrient loading error:', error);
                    }
                },

                toggleAnnotationMode(mode) {
                    if (this.annotationMode === mode) {
                        this.exitAnnotationMode();
                    } else {
                        this.annotationMode = mode;
                        this.enableDrawingMode();
                    }
                },

                exitAnnotationMode() {
                    this.annotationMode = null;
                    if (this.nutrientInstance) {
                        this.nutrientInstance.setViewState(s => s.set('interactionMode', PSPDFKit.InteractionMode.PAN));
                    }
                },

                async enableDrawingMode() {
                    if (!this.nutrientInstance) return;

                    // Set to shape drawing mode for rectangles
                    await this.nutrientInstance.setViewState(s =>
                        s.set('interactionMode', PSPDFKit.InteractionMode.SHAPE_RECTANGLE)
                    );

                    // Listen for new annotations created
                    const listener = async (annotations) => {
                        const newAnnotation = annotations.last();
                        if (newAnnotation && newAnnotation.get('type') === 'pspdfkit/shape/rectangle') {
                            await this.handleNewAnnotation(newAnnotation);
                        }
                    };

                    this.nutrientInstance.addEventListener('annotations.create', listener);
                },

                async handleNewAnnotation(annotation) {
                    this.currentPendingAnnotation = annotation;
                    this.currentAnnotationType = this.annotationMode;

                    // Set color based on type
                    const color = this.annotationMode === 'cabinet_run'
                        ? new PSPDFKit.Color({ r: 255, g: 0, b: 0 })
                        : new PSPDFKit.Color({ r: 0, g: 200, b: 0 });

                    const updatedAnnotation = annotation
                        .set('strokeColor', color)
                        .set('strokeWidth', 3)
                        .set('customData', {
                            annotation_type: this.annotationMode,
                            parent_id: this.selectedRunAnnotation?.customData?.db_id || null,
                        });

                    await this.nutrientInstance.update(updatedAnnotation);

                    // Show linking modal
                    if (this.annotationMode === 'cabinet') {
                        await this.loadCabinetsInRun();
                    }
                    this.showLinkingModal = true;
                },

                async loadAvailableCabinetRuns() {
                    // Load full context data from new Context API
                    try {
                        const response = await fetch(`/api/pdf/page/${this.pdfPageId}/context`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            }
                        });

                        if (response.ok) {
                            const contextData = await response.json();
                            const context = contextData.context || {};

                            // Populate all context data
                            this.availableRooms = context.rooms || [];
                            this.availableRoomLocations = context.room_locations || [];
                            this.availableCabinetRuns = context.cabinet_runs || [];
                            this.availableCabinets = context.cabinets || [];
                            this.projectId = context.project_id;

                            console.log('✅ Loaded context data:', {
                                rooms: this.availableRooms.length,
                                locations: this.availableRoomLocations.length,
                                runs: this.availableCabinetRuns.length,
                                cabinets: this.availableCabinets.length,
                                projectId: this.projectId
                            });
                        } else {
                            console.error('Failed to load context data:', response.status);
                            // Fallback to old endpoint for backwards compatibility
                            const fallbackResponse = await fetch(`/api/pdf/annotations/page/${this.pdfPageId}/cabinet-runs`);
                            const fallbackData = await fallbackResponse.json();
                            this.availableCabinetRuns = fallbackData.cabinet_runs || [];
                        }
                    } catch (error) {
                        console.error('Error loading context data:', error);
                        this.availableCabinetRuns = [];
                    }
                },

                async loadCabinetsInRun() {
                    if (!this.selectedRunAnnotation?.customData?.cabinet_run_id) {
                        this.cabinetsInRun = [];
                        return;
                    }

                    const response = await fetch(`/api/pdf/annotations/cabinet-run/${this.selectedRunAnnotation.customData.cabinet_run_id}/cabinets`);
                    const data = await response.json();
                    this.cabinetsInRun = data.cabinets || [];
                },

                // Cascade filtering functions for multi-pass annotation system
                filterRoomLocations() {
                    if (!this.selectedRoomId) {
                        this.filteredRoomLocations = [];
                        this.selectedRoomLocationId = null;
                        this.filteredCabinetRuns = [];
                        return;
                    }
                    this.filteredRoomLocations = this.availableRoomLocations.filter(
                        loc => loc.room_id == this.selectedRoomId
                    );
                    this.selectedRoomLocationId = null;
                    this.filteredCabinetRuns = [];
                },

                filterCabinetRuns() {
                    if (!this.selectedRoomLocationId) {
                        this.filteredCabinetRuns = [];
                        this.selectedCabinetRunId = null;
                        return;
                    }
                    this.filteredCabinetRuns = this.availableCabinetRuns.filter(
                        run => run.room_location_id == this.selectedRoomLocationId
                    );
                    this.selectedCabinetRunId = null;
                },

                filterCabinets() {
                    if (!this.selectedCabinetRunId) {
                        this.filteredCabinets = [];
                        this.selectedCabinetId = null;
                        return;
                    }
                    this.filteredCabinets = this.availableCabinets.filter(
                        cab => cab.cabinet_run_id == this.selectedCabinetRunId
                    );
                    this.selectedCabinetId = null;
                },

                async confirmLinking() {
                    if (!this.currentPendingAnnotation) return;

                    // Build context data based on annotation type
                    const context = {
                        project_id: this.projectId
                    };

                    if (this.annotationType === 'room') {
                        // Room annotation - just needs name
                        context.room_name = this.currentAnnotationLabel;
                    } else if (this.annotationType === 'room_location') {
                        // Room location - needs room_id and name
                        context.room_id = this.selectedRoomId;
                        context.location_name = this.currentAnnotationLabel;
                    } else if (this.annotationType === 'cabinet_run') {
                        // Cabinet run - needs room, location, and run name
                        context.room_id = this.selectedRoomId;
                        context.room_location_id = this.selectedRoomLocationId;
                        context.run_name = this.currentAnnotationLabel;
                    } else if (this.annotationType === 'cabinet') {
                        // Cabinet - needs full hierarchy
                        context.room_id = this.selectedRoomId;
                        context.room_location_id = this.selectedRoomLocationId;
                        context.cabinet_run_id = this.selectedCabinetRunId;
                        context.cabinet_label = this.currentAnnotationLabel;
                    }

                    const customData = {
                        annotation_type: this.annotationType,
                        label: this.currentAnnotationLabel,
                        context: context,
                        // Legacy fields for backward compatibility
                        parent_id: this.selectedRunAnnotation?.customData?.db_id || null,
                        cabinet_run_id: this.selectedCabinetRunId || null,
                        cabinet_specification_id: this.selectedCabinetId || null,
                    };

                    const updated = this.currentPendingAnnotation.set('customData', customData);
                    await this.nutrientInstance.update(updated);

                    console.log('✅ Annotation linked with context:', customData);

                    this.closeLinkingModal();
                    this.exitAnnotationMode();
                },

                closeLinkingModal() {
                    this.showLinkingModal = false;
                    this.currentPendingAnnotation = null;
                    this.currentAnnotationLabel = '';

                    // Reset all selection state
                    this.selectedRoomId = null;
                    this.selectedRoomLocationId = null;
                    this.selectedCabinetRunId = '';
                    this.selectedCabinetId = '';

                    // Clear filtered lists
                    this.filteredRoomLocations = [];
                    this.filteredCabinetRuns = [];
                    this.filteredCabinets = [];
                },

                async saveAnnotations() {
                    if (!this.nutrientInstance) return;

                    const instantJson = await this.nutrientInstance.exportInstantJSON();

                    const response = await fetch(`/api/pdf/annotations/page/${this.pdfPageId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            ...instantJson,
                            create_entities: true  // Enable automatic entity creation from annotations
                        }),
                    });

                    const result = await response.json();

                    if (result.success) {
                        let message = `Saved ${result.saved_count} annotations successfully!`;

                        // Show created entities if any
                        if (result.created_entities && result.created_entities.length > 0) {
                            message += '\n\nCreated entities:';
                            result.created_entities.forEach(entity => {
                                message += `\n- ${entity.type}: ${entity.name}`;
                            });
                        }

                        alert(message);
                        console.log('✅ Save result:', result);
                    } else {
                        alert('Failed to save annotations: ' + (result.message || 'Unknown error'));
                    }
                },

                updateSelectedAnnotation() {
                    // Track selected cabinet run annotation for nesting
                    const selected = this.nutrientInstance.getSelectedAnnotations();
                    if (selected.size > 0) {
                        const annotation = selected.first();
                        if (annotation.customData?.annotation_type === 'cabinet_run') {
                            this.selectedRunAnnotation = annotation;
                        }
                    }
                },
            }));
        });
    </script>
    @endpush
@endonce
