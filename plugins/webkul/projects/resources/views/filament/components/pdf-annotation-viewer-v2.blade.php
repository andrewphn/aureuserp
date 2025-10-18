@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
])

@php
    // Generate unique ID for this page's Alpine instance
    $viewerId = 'pdfViewerV2_' . $pdfPageId . '_' . uniqid();
@endphp

<div
    x-data="annotationSystemV2_{{ $viewerId }}()"
    x-init="init({{ $projectId }}, {{ $pdfPageId }}, '{{ $pdfUrl }}', {{ $pageNumber }})"
    wire:ignore
    class="annotation-system-v2 w-full h-full flex flex-col"
>
    <!-- Context Bar (Top - Sticky) -->
    <div class="context-bar sticky top-0 z-50 bg-white dark:bg-gray-900 border-b border-gray-300 dark:border-gray-600 p-4">
        <div class="flex items-center gap-4 flex-wrap">
            <!-- Project Context Display -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">📍 Context:</span>
                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="getContextLabel()"></span>
            </div>

            <!-- Room Autocomplete -->
            <div class="relative flex-1 max-w-xs">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Room</label>
                <input
                    type="text"
                    x-model="roomSearchQuery"
                    @input="searchRooms($event.target.value)"
                    @focus="showRoomDropdown = true"
                    @click.away="showRoomDropdown = false"
                    placeholder="Type to search or create..."
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm"
                />

                <!-- Room Suggestions Dropdown -->
                <div
                    x-show="showRoomDropdown && roomSuggestions.length > 0"
                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                >
                    <template x-for="room in roomSuggestions" :key="room.id">
                        <div
                            @click="selectRoom(room)"
                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex items-center gap-2"
                        >
                            <span x-show="!room.isNew" class="text-green-600">✓</span>
                            <span x-show="room.isNew" class="text-blue-600 font-bold">+</span>
                            <span x-text="room.name" class="text-sm"></span>
                            <span x-show="!room.isNew" class="text-xs text-gray-500 ml-auto">Existing</span>
                            <span x-show="room.isNew" class="text-xs text-blue-600 ml-auto">Create New</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Location Autocomplete -->
            <div class="relative flex-1 max-w-xs">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Location</label>
                <input
                    type="text"
                    x-model="locationSearchQuery"
                    @input="searchLocations($event.target.value)"
                    @focus="showLocationDropdown = true"
                    @click.away="showLocationDropdown = false"
                    :disabled="!activeRoomId"
                    placeholder="Select room first..."
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                />

                <!-- Location Suggestions Dropdown -->
                <div
                    x-show="showLocationDropdown && locationSuggestions.length > 0"
                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                >
                    <template x-for="location in locationSuggestions" :key="location.id">
                        <div
                            @click="selectLocation(location)"
                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer flex items-center gap-2"
                        >
                            <span x-show="!location.isNew" class="text-green-600">✓</span>
                            <span x-show="location.isNew" class="text-blue-600 font-bold">+</span>
                            <span x-text="location.name" class="text-sm"></span>
                            <span x-show="!location.isNew" class="text-xs text-gray-500 ml-auto">Existing</span>
                            <span x-show="location.isNew" class="text-xs text-blue-600 ml-auto">Create New</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Draw Mode Buttons -->
            <div class="flex items-center gap-2 ml-auto">
                <button
                    @click="setDrawMode('cabinet_run')"
                    :class="drawMode === 'cabinet_run' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    :disabled="!canDraw()"
                    class="px-4 py-2 rounded-lg hover:bg-blue-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Cabinet Run"
                >
                    📦 Draw Run
                </button>

                <button
                    @click="setDrawMode('cabinet')"
                    :class="drawMode === 'cabinet' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    :disabled="!canDraw()"
                    class="px-4 py-2 rounded-lg hover:bg-green-500 hover:text-white transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Draw Cabinet"
                >
                    🗄️ Draw Cabinet
                </button>

                <button
                    @click="clearContext()"
                    class="px-4 py-2 rounded-lg bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors text-sm"
                    title="Clear Context"
                >
                    ✖️ Clear
                </button>

                <button
                    @click="saveAnnotations()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                    title="Save All Annotations"
                >
                    💾 Save
                </button>
            </div>
        </div>

        <!-- Context Hint -->
        <div x-show="!canDraw()" class="mt-2 text-xs text-orange-600 dark:text-orange-400">
            ℹ️ Select a Room and Location before drawing
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar (Project Tree) -->
        <div class="tree-sidebar w-64 border-r border-gray-300 dark:border-gray-600 overflow-y-auto bg-gray-50 dark:bg-gray-800 p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Project Structure</h3>
                <button
                    @click="refreshTree()"
                    class="text-xs text-blue-600 hover:text-blue-700"
                    title="Refresh tree"
                >
                    🔄
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="text-center py-4">
                <span class="text-sm text-gray-500">Loading...</span>
            </div>

            <!-- Error State -->
            <div x-show="error" class="text-center py-4">
                <span class="text-sm text-red-600" x-text="error"></span>
            </div>

            <!-- Tree Content -->
            <div x-show="!loading && !error && tree">
                <template x-for="room in tree" :key="room.id">
                    <div class="tree-node mb-2">
                        <!-- Room Level -->
                        <div
                            @click="selectNode(room.id, 'room', room.name)"
                            :class="selectedNodeId === room.id ? 'bg-purple-100 dark:bg-purple-900 text-purple-900 dark:text-purple-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                            class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                        >
                            <button
                                @click.stop="toggleNode(room.id)"
                                class="w-4 h-4 flex items-center justify-center"
                            >
                                <span x-show="isExpanded(room.id)">▼</span>
                                <span x-show="!isExpanded(room.id)">▶</span>
                            </button>
                            <span class="text-lg">🏠</span>
                            <span class="text-sm font-medium flex-1" x-text="room.name"></span>
                            <span
                                x-show="room.annotation_count > 0"
                                class="badge bg-purple-600 text-white px-2 py-0.5 rounded-full text-xs"
                                x-text="room.annotation_count"
                            ></span>
                        </div>

                        <!-- Locations (Children) -->
                        <div x-show="isExpanded(room.id)" class="ml-6 mt-1">
                            <template x-for="location in room.children" :key="location.id">
                                <div class="tree-node mb-1">
                                    <!-- Location Level -->
                                    <div
                                        @click="selectNode(location.id, 'room_location', location.name, room.id)"
                                        :class="selectedNodeId === location.id ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                                    >
                                        <button
                                            @click.stop="toggleNode(location.id)"
                                            class="w-4 h-4 flex items-center justify-center"
                                        >
                                            <span x-show="isExpanded(location.id)">▼</span>
                                            <span x-show="!isExpanded(location.id)">▶</span>
                                        </button>
                                        <span class="text-lg">📍</span>
                                        <span class="text-sm flex-1" x-text="location.name"></span>
                                        <span
                                            x-show="location.annotation_count > 0"
                                            class="badge bg-indigo-600 text-white px-2 py-0.5 rounded-full text-xs"
                                            x-text="location.annotation_count"
                                        ></span>
                                    </div>

                                    <!-- Cabinet Runs (Children) -->
                                    <div x-show="isExpanded(location.id)" class="ml-6 mt-1">
                                        <template x-for="run in location.children" :key="run.id">
                                            <div class="tree-node mb-1">
                                                <!-- Cabinet Run Level -->
                                                <div
                                                    :class="selectedNodeId === run.id ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                                                    class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors text-sm"
                                                >
                                                    <span class="text-base">📦</span>
                                                    <span class="flex-1" x-text="run.name"></span>
                                                    <span
                                                        x-show="run.annotation_count > 0"
                                                        class="badge bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"
                                                        x-text="run.annotation_count"
                                                    ></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Add Room Button -->
                <button
                    @click="roomSearchQuery = ''; showRoomDropdown = true; $nextTick(() => $el.previousElementSibling?.focus())"
                    class="w-full mt-4 px-3 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                >
                    + Add Room
                </button>
            </div>
        </div>

        <!-- PDF Viewer (Center) -->
        <div class="pdf-viewer-container flex-1 bg-white dark:bg-gray-900 overflow-auto">
            <div
                x-ref="nutrientContainer"
                class="w-full h-full"
                style="min-height: 600px;"
            ></div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    import { contextBarComponent, projectTreeSidebarComponent } from '/plugins/webkul/projects/resources/js/annotations/index.js';

    if (typeof Alpine !== 'undefined') {
        // Main coordinator component
        Alpine.data('annotationSystemV2_{{ $viewerId }}', () => ({
            // Merge context bar + tree sidebar functionality
            ...contextBarComponent(),
            ...projectTreeSidebarComponent(),

            // PDF viewer state
            nutrientInstance: null,
            pdfPageId: null,
            pdfUrl: null,
            pageNumber: null,

            // Override init to coordinate everything
            async init(projectId, pdfPageId, pdfUrl, pageNumber) {
                this.projectId = projectId;
                this.pdfPageId = pdfPageId;
                this.pdfUrl = pdfUrl;
                this.pageNumber = pageNumber;

                // Load tree
                await this.loadProjectTree();

                // Load rooms for autocomplete
                await this.loadAvailableRooms();

                // Initialize PDF viewer
                await this.loadNutrient();

                // Listen for draw mode changes
                this.setupDrawModeListener();
            },

            // PDF viewer initialization
            async loadNutrient() {
                if (this.nutrientInstance || !this.$refs.nutrientContainer) {
                    console.log('⏭️  Skipping Nutrient load');
                    return;
                }

                try {
                    console.log('🚀 Loading Nutrient PDF viewer...');

                    // Load existing annotations
                    const response = await fetch(`/api/pdf/annotations/page/${this.pdfPageId}`);
                    const instantJson = await response.json();

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
                        toolbarItems: ['sidebar-thumbnails', 'zoom-in', 'zoom-out', 'spacer', 'search'],
                    });

                    console.log('✅ PDF viewer loaded!');

                    // Listen for annotation creation
                    this.nutrientInstance.addEventListener('annotations.create', async (annotations) => {
                        const newAnnotation = annotations.last();
                        if (newAnnotation && newAnnotation.get('type') === 'pspdfkit/shape/rectangle') {
                            await this.handleNewAnnotation(newAnnotation);
                        }
                    });
                } catch (error) {
                    console.error('❌ PDF viewer error:', error);
                    this.error = `Failed to load PDF viewer: ${error.message}`;
                }
            },

            // Setup draw mode listener
            setupDrawModeListener() {
                // When draw mode changes, enable/disable PDF drawing
                this.$watch('drawMode', async (newMode) => {
                    if (!this.nutrientInstance) return;

                    if (newMode) {
                        // Enable rectangle drawing
                        await this.nutrientInstance.setViewState(s =>
                            s.set('interactionMode', PSPDFKit.InteractionMode.SHAPE_RECTANGLE)
                        );
                        console.log('✅ Drawing mode enabled:', newMode);
                    } else {
                        // Disable drawing
                        await this.nutrientInstance.setViewState(s =>
                            s.set('interactionMode', PSPDFKit.InteractionMode.PAN)
                        );
                        console.log('🔄 Drawing mode disabled');
                    }
                });
            },

            // Handle new annotation drawn
            async handleNewAnnotation(annotation) {
                if (!this.canDraw()) {
                    alert('Please select a Room and Location first!');
                    // Delete the annotation
                    await this.nutrientInstance.delete(annotation.id);
                    return;
                }

                // Auto-label based on context
                const label = await this.generateAutoLabel();

                // Set color based on type
                let color;
                if (this.drawMode === 'cabinet_run') {
                    color = new PSPDFKit.Color({ r: 37, g: 99, b: 235 }); // Blue
                } else if (this.drawMode === 'cabinet') {
                    color = new PSPDFKit.Color({ r: 22, g: 163, b: 74 }); // Green
                }

                // Build context data
                const customData = {
                    annotation_type: this.drawMode,
                    label: label,
                    context: {
                        project_id: this.projectId,
                        room_id: this.activeRoomId,
                        room_location_id: this.activeLocationId,
                    }
                };

                const updatedAnnotation = annotation
                    .set('strokeColor', color)
                    .set('strokeWidth', 3)
                    .set('customData', customData)
                    .set('note', label);

                await this.nutrientInstance.update(updatedAnnotation);

                console.log('✅ Annotation created:', label);
            },

            // Generate auto-label based on context
            async generateAutoLabel() {
                // Count existing annotations of this type in this location
                const allAnnotations = await this.nutrientInstance.getAnnotations(this.pageNumber - 1);
                const existingCount = allAnnotations.filter(a => {
                    const data = a.customData;
                    return data?.annotation_type === this.drawMode &&
                           data?.context?.room_location_id === this.activeLocationId;
                }).size;

                if (this.drawMode === 'cabinet_run') {
                    return `Run ${existingCount + 1}`;
                } else if (this.drawMode === 'cabinet') {
                    return `Cabinet ${existingCount + 1}`;
                }

                return `Annotation ${existingCount + 1}`;
            },

            // Save annotations
            async saveAnnotations() {
                if (!this.nutrientInstance) return;

                try {
                    const instantJson = await this.nutrientInstance.exportInstantJSON();

                    const response = await fetch(`/api/pdf/annotations/page/${this.pdfPageId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            ...instantJson,
                            create_entities: true
                        }),
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(`Saved ${result.saved_count} annotations!`);
                        // Refresh tree to show new entities
                        await this.refresh();
                    } else {
                        alert('Failed to save: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    alert('Failed to save annotations');
                }
            },

            // Handle node selection from tree
            selectNode(nodeId, nodeType, nodeName, parentRoomId = null) {
                this.selectedNodeId = nodeId;
                this.selectedNodeType = nodeType;

                if (nodeType === 'room') {
                    this.selectRoom({ id: nodeId, name: nodeName, isNew: false });
                } else if (nodeType === 'room_location' && parentRoomId) {
                    // Select both room and location
                    const room = this.tree.find(r => r.id === parentRoomId);
                    if (room) {
                        this.selectRoom({ id: room.id, name: room.name, isNew: false });
                        this.selectLocation({ id: nodeId, name: nodeName, isNew: false });
                    }
                }
            },

            // Refresh tree
            async refreshTree() {
                await this.loadProjectTree();
            }
        }));
    }
</script>
@endpush
