/**
 * PDF Annotation Viewer - Core Alpine.js Component
 * Main entry point that composes all manager modules
 */

// Import all managers
import { createInitialState } from './managers/state-manager.js';
import * as CoordTransform from './managers/coordinate-transform.js';
import * as PDFManager from './managers/pdf-manager.js';
import * as AnnotationManager from './managers/annotation-manager.js';
import * as DrawingSystem from './managers/drawing-system.js';
import * as ResizeMoveSystem from './managers/resize-move-system.js';
import * as UndoRedoManager from './managers/undo-redo-manager.js';
import * as IsolationMode from './managers/isolation-mode-manager.js';
import * as FilterSystem from './managers/filter-system.js';
import * as TreeManager from './managers/tree-manager.js';
import * as NavigationManager from './managers/navigation-manager.js';
import * as AutocompleteManager from './managers/autocomplete-manager.js';
import * as ZoomManager from './managers/zoom-manager.js';
import * as ViewTypeManager from './managers/view-type-manager.js';
import * as EntityReferenceManager from './managers/entity-reference-manager.js';
import * as UIHelpers from './managers/ui-helpers.js';

/**
 * Create PDF Annotation Viewer Alpine component
 * @param {Object} config - Configuration from Blade props
 * @returns {Object} Alpine.js component
 */
export function createPdfViewerComponent(config) {
    return {
        // Initialize state
        ...createInitialState(config),

        // Computed properties
        get filteredAnnotations() {
            return FilterSystem.getFilteredAnnotations(this);
        },

        get activeFiltersCount() {
            return FilterSystem.countActiveFilters(this);
        },

        get activeFilterChips() {
            return FilterSystem.getActiveFilterChips(this);
        },

        get availableFilterOptions() {
            return FilterSystem.getAvailableFilterOptions(this);
        },

        get filteredPageNumbers() {
            return FilterSystem.getFilteredPageNumbers(this);
        },

        get isolationBreadcrumbs() {
            return IsolationMode.getIsolationBreadcrumbs(this);
        },

        // Filter panel computed properties
        get availableTypes() {
            const types = [...new Set(this.annotations.map(a => a.type))];
            return types.sort();
        },

        get availableRooms() {
            const rooms = new Map();
            this.annotations.forEach(a => {
                if (a.roomId && a.roomName) {
                    rooms.set(a.roomId, { id: a.roomId, name: a.roomName });
                }
            });
            return Array.from(rooms.values()).sort((a, b) => a.name.localeCompare(b.name));
        },

        get availableLocations() {
            const locations = new Map();
            this.annotations.forEach(a => {
                if (a.locationId && a.locationName) {
                    locations.set(a.locationId, { id: a.locationId, name: a.locationName });
                }
            });
            return Array.from(locations.values()).sort((a, b) => a.name.localeCompare(b.name));
        },

        get availableViewTypes() {
            const viewTypes = [...new Set(this.annotations.map(a => a.viewType || 'plan'))];
            return viewTypes.sort();
        },

        get availableVerticalZones() {
            const zones = [...new Set(this.annotations.map(a => a.verticalZone).filter(Boolean))];
            return zones.sort();
        },

        get filteredTree() {
            // Return tree filtered by current filter settings
            // For now, return the full tree (filtering logic can be added later)
            return this.tree;
        },

        get visibleAnnotationsList() {
            // Filter annotations for rendering on canvas overlay
            return this.annotations.filter(a =>
                !this.hiddenAnnotations.includes(a.id) &&
                ViewTypeManager.isAnnotationVisibleInView(a, this)
            );
        },

        // Lifecycle
        async init() {
            if (this._initialized) {
                console.warn('⚠️ PDF Viewer already initialized');
                return;
            }

            console.log('🚀 Initializing PDF Annotation Viewer...');

            try {
                // Initialize coordinate system
                CoordTransform.initializeCoordinateSystem(this);

                // Initialize PDF system
                await PDFManager.initializePdfSystem(this, this.$refs, {
                    $nextTick: () => this.$nextTick()
                });

                // Load project tree
                await TreeManager.loadTree(this);

                // Load annotations
                await AnnotationManager.loadAnnotations(this, this.$refs);

                // Setup undo/redo keyboard shortcuts
                UndoRedoManager.setupUndoRedoKeyboards(this);

                // Setup browser zoom handler to keep annotations aligned
                this._cleanupBrowserZoomHandler = CoordTransform.setupBrowserZoomHandler(
                    this,
                    this.$refs,
                    {
                        updateIsolationMask: () => IsolationMode.updateIsolationMask(this)
                    }
                );

                // Setup Livewire event listeners
                this.setupLivewireListeners();

                // Mark as initialized
                this._initialized = true;
                this.systemReady = true;

                console.log('✓ PDF Viewer initialized successfully');
            } catch (error) {
                console.error('❌ PDF Viewer initialization failed:', error);
                this.error = error.message;
            }
        },

        // Cleanup on component destroy
        destroy() {
            console.log('🧹 Cleaning up PDF Viewer...');

            // Cleanup browser zoom handler
            if (this._cleanupBrowserZoomHandler) {
                this._cleanupBrowserZoomHandler();
                this._cleanupBrowserZoomHandler = null;
            }

            console.log('✓ PDF Viewer cleaned up');
        },

        // Livewire integration
        setupLivewireListeners() {
            window.Livewire.on('annotation-created', (event) => {
                console.log('📥 Livewire: annotation-created', event);
                AnnotationManager.loadAnnotations(this, this.$refs);
            });

            window.Livewire.on('annotation-updated', (event) => {
                console.log('📥 Livewire: annotation-updated', event);
                AnnotationManager.loadAnnotations(this, this.$refs);
            });

            window.Livewire.on('annotation-editor-closed', (event) => {
                console.log('📥 Livewire: annotation-editor-closed', event);
                // Remove any temporary annotations that weren't saved
                const beforeCount = this.annotations.length;
                this.annotations = this.annotations.filter(anno =>
                    !anno.id.toString().startsWith('temp_')
                );
                const afterCount = this.annotations.length;
                if (beforeCount > afterCount) {
                    console.log(`✓ Cleaned up ${beforeCount - afterCount} temporary annotation(s)`);
                }
            });

            window.Livewire.on('hierarchy-completed', (event) => {
                console.log('📥 Livewire: hierarchy-completed', event);
                const { annotation, createdIds, context } = event;

                // Find the temp annotation in state
                const tempAnno = this.annotations.find(a => a.id === annotation.id);
                if (tempAnno) {
                    // Update annotation with created entity IDs
                    if (createdIds.room) tempAnno.roomId = createdIds.room;
                    if (createdIds.room_location) tempAnno.roomLocationId = createdIds.room_location;
                    if (createdIds.cabinet_run) tempAnno.cabinetRunId = createdIds.cabinet_run;
                    if (createdIds.cabinet) tempAnno.cabinetSpecId = createdIds.cabinet;

                    console.log('✓ Updated annotation with hierarchy:', tempAnno);

                    // CRITICAL: Populate locationId field needed for isolation mode visibility
                    // This matches the logic in populateParentConnections() (annotation-manager.js lines 130-137)
                    if (tempAnno.parentId) {
                        const parentAnno = this.annotations.find(a => a.id === tempAnno.parentId);
                        if (parentAnno && parentAnno.type === 'location') {
                            tempAnno.locationId = parentAnno.roomLocationId;
                            tempAnno.locationName = parentAnno.label;
                            console.log('✓ Populated locationId from parent:', tempAnno.locationId);
                        }
                    }

                    // If in isolation mode, check if annotation should be visible
                    if (this.isolationMode) {
                        console.log('🔍 Checking visibility for newly created annotation in isolation mode');

                        // Import isAnnotationVisibleInIsolation
                        import('./managers/isolation-mode-manager.js').then(isolationModule => {
                            const isVisible = isolationModule.isAnnotationVisibleInIsolation(tempAnno, this);

                            if (!isVisible && !this.hiddenAnnotations.includes(tempAnno.id)) {
                                this.hiddenAnnotations.push(tempAnno.id);
                                console.log(`👁️ Hiding newly created annotation ${tempAnno.id} (not visible in current isolation)`);
                            } else if (isVisible && this.hiddenAnnotations.includes(tempAnno.id)) {
                                this.hiddenAnnotations = this.hiddenAnnotations.filter(id => id !== tempAnno.id);
                                console.log(`👁️ Showing newly created annotation ${tempAnno.id} (visible in current isolation)`);
                            }
                        });
                    }
                }

                // Update active context for future annotations
                if (context.roomId) this.activeRoomId = context.roomId;
                if (context.locationId) this.activeLocationId = context.locationId;
                if (context.cabinetRunId) this.activeCabinetRunId = context.cabinetRunId;

                // Save the annotation with complete hierarchy
                this.saveAnnotations(true); // silent = true
            });
        },

        // PDF Management
        getCanvasScale() {
            return CoordTransform.getCanvasScale(this.$refs, this);
        },

        pdfToScreen(pdfX, pdfY, pdfWidth, pdfHeight) {
            return CoordTransform.pdfToScreen(pdfX, pdfY, pdfWidth, pdfHeight, this.$refs, this);
        },

        screenToPdf(screenX, screenY) {
            return CoordTransform.screenToPdf(screenX, screenY, this.$refs, this);
        },

        syncOverlayToCanvas() {
            CoordTransform.syncOverlayToCanvas(this.$refs, this);
        },

        getOverlayRect() {
            return CoordTransform.getOverlayRect(this.$refs, this);
        },

        // Annotation Management
        async loadAnnotations() {
            await AnnotationManager.loadAnnotations(this, this.$refs);
        },

        async saveAnnotations(silent = false) {
            await AnnotationManager.saveAnnotations(
                this,
                async () => {
                    await this.loadAnnotations();

                    // CRITICAL: Wait for Alpine to render annotations before tree refresh
                    // Tree refresh causes DOM layout shifts that misalign annotations
                    await this.$nextTick();

                    await this.refreshTree();

                    // Final sync after tree is stable to ensure annotations are aligned
                    await this.$nextTick();
                    this.syncOverlayToCanvas();
                },
                silent
            );
        },

        async deleteAnnotation(anno) {
            await AnnotationManager.deleteAnnotation(anno, this, () => this.refreshTree());
        },

        editAnnotation(anno) {
            AnnotationManager.editAnnotation(anno, this);
        },

        selectAnnotation(anno) {
            AnnotationManager.editAnnotation(anno, this);
        },

        // Drawing System
        startDrawing(event) {
            DrawingSystem.startDrawing(event, this, this.$refs);
        },

        updateDrawPreview(event) {
            DrawingSystem.updateDrawPreview(event, this, this.$refs);
        },

        finishDrawing(event) {
            DrawingSystem.finishDrawing(event, this, this.$refs);
        },

        setDrawMode(mode) {
            DrawingSystem.setDrawMode(
                mode,
                this,
                (m) => AnnotationManager.checkForDuplicateEntity(m, this),
                (a) => AnnotationManager.highlightAnnotation(a, this)
            );
        },

        canDrawLocation() {
            return DrawingSystem.canDrawLocation(this);
        },

        canDraw() {
            return DrawingSystem.canDraw(this);
        },

        clearContext() {
            DrawingSystem.clearContext(this);
        },

        getContextLabel() {
            return DrawingSystem.getContextLabel(this);
        },

        // Resize & Move
        startResize(event, anno, handle) {
            ResizeMoveSystem.startResize(event, anno, handle, this);
        },

        startMove(event, anno) {
            ResizeMoveSystem.startMove(event, anno, this);
        },

        // Inline handlers for template (aliases for manager functions)
        updateDrawing(event) {
            // Alias for updateDrawPreview used in template
            this.updateDrawPreview(event);
        },

        cancelDrawing(event) {
            // Cancel drawing operation
            if (this.isDrawing) {
                this.isDrawing = false;
                this.drawStart = null;
                this.drawPreview = null;
            }
        },

        handleResize(event) {
            ResizeMoveSystem.handleResize(event, this);
        },

        handleMove(event) {
            ResizeMoveSystem.handleMove(event, this);
        },

        finishResizeOrMove(event) {
            // Finish resize or move operation using manager
            ResizeMoveSystem.finishResizeOrMove(event, this, this.$refs);

            // Debounced auto-save: wait 1 second after final mouseup
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }

            this.autoSaveTimeout = setTimeout(() => {
                // Silent save (no alert)
                this.saveAnnotations(true);
            }, 1000);
        },

        selectAnnotationContext(anno) {
            // Select tree node from annotation click
            if (anno.type === 'room') {
                this.selectNode(anno.roomId, 'room', anno.label || anno.name, null, null, null);
            } else if (anno.type === 'room_location' || anno.type === 'location') {
                this.selectNode(
                    anno.roomLocationId || anno.id,
                    'room_location',
                    anno.label || anno.name,
                    anno.roomId,
                    null,
                    null
                );
            } else if (anno.type === 'cabinet_run') {
                this.selectNode(
                    anno.cabinetRunId || anno.id,
                    'cabinet_run',
                    anno.label || anno.name,
                    anno.roomId,
                    anno.roomLocationId || anno.locationId,
                    null
                );
            } else if (anno.type === 'cabinet') {
                this.selectNode(
                    anno.id,
                    'cabinet',
                    anno.label || anno.name,
                    anno.roomId,
                    anno.roomLocationId || anno.locationId,
                    anno.cabinetRunId
                );
            }
        },

        // Undo/Redo
        undo() {
            UndoRedoManager.undo(this);
        },

        redo() {
            UndoRedoManager.redo(this);
        },

        canUndo() {
            return UndoRedoManager.canUndo(this);
        },

        canRedo() {
            return UndoRedoManager.canRedo(this);
        },

        // Isolation Mode
        async enterIsolationMode(anno) {
            await IsolationMode.enterIsolationMode(anno, this, {
                zoomToFitAnnotation: (a) => ZoomManager.zoomToFitAnnotation(a, this, this.$refs, this.getCallbacks()),
                syncOverlayToCanvas: () => this.syncOverlayToCanvas(),
                $nextTick: () => this.$nextTick()
            });
        },

        async exitIsolationMode() {
            await IsolationMode.exitIsolationMode(this, {
                clearContext: () => this.clearContext(),
                resetZoom: () => ZoomManager.resetZoom(this, this.$refs, this.getCallbacks())
            });
        },

        isAnnotationVisibleInIsolation(anno) {
            return IsolationMode.isAnnotationVisibleInIsolation(anno, this);
        },

        updateIsolationMask() {
            IsolationMode.updateIsolationMask(this);
        },

        // Filter System
        clearAllFilters() {
            FilterSystem.clearAllFilters(this);
        },

        removeFilter(chip) {
            FilterSystem.removeFilterChip(chip, this);
        },

        applyPreset(preset) {
            FilterSystem.applyFilterPreset(preset, this);
        },

        isPresetActive(preset) {
            return FilterSystem.isPresetActive(preset, this);
        },

        hasActiveFilters() {
            return this.activeFiltersCount > 0;
        },

        // Tree Management
        async loadTree() {
            await TreeManager.loadTree(this);
        },

        async refreshTree() {
            await TreeManager.refreshTree(this, {
                $refs: this.$refs,
                ...this.getCallbacks()
            });
        },

        toggleNode(nodeId) {
            TreeManager.toggleNode(nodeId, this);
        },

        isExpanded(nodeId) {
            return TreeManager.isExpanded(nodeId, this);
        },

        async selectNode(nodeId, type, name, parentRoomId, parentLocationId, parentCabinetRunId) {
            await TreeManager.selectNode(
                { nodeId, type, name, parentRoomId, parentLocationId, parentCabinetRunId },
                this,
                {
                    navigateToNodePage: (node, parentLoc, parentRoom) =>
                        TreeManager.navigateToNodePage(node, parentLoc, parentRoom, this, this.getCallbacks())
                }
            );
        },

        showContextMenu(event, nodeId, nodeType, nodeName, parentRoomId, parentLocationId) {
            TreeManager.showContextMenu(event, { nodeId, nodeType, nodeName, parentRoomId, parentLocationId }, this);
        },

        async deleteTreeNode() {
            await TreeManager.deleteTreeNode(this, () => this.refreshTree());
        },

        async navigateToNodeOnDoubleClick(nodeId, nodeType, parentRoomId, parentLocationId) {
            await TreeManager.navigateOnDoubleClick(
                { nodeId, nodeType, parentRoomId, parentLocationId },
                this,
                { goToPage: (page) => this.goToPage(page) }
            );
        },

        // Navigation
        async nextPage() {
            await NavigationManager.nextPage(this, this.getCallbacks());
        },

        async previousPage() {
            await NavigationManager.previousPage(this, this.getCallbacks());
        },

        async goToPage(pageNum) {
            await NavigationManager.goToPage(pageNum, this, this.getCallbacks());
        },

        canGoNext() {
            return NavigationManager.canGoNext(this, () => this.filteredPageNumbers);
        },

        canGoPrevious() {
            return NavigationManager.canGoPrevious(this, () => this.filteredPageNumbers);
        },

        // Autocomplete
        searchRooms(query) {
            AutocompleteManager.searchRooms(query, this);
        },

        async selectRoom(room) {
            await AutocompleteManager.selectRoom(room, this, () => this.refreshTree());
        },

        searchLocations(query) {
            AutocompleteManager.searchLocations(query, this);
        },

        async selectLocation(location) {
            await AutocompleteManager.selectLocation(location, this, () => this.refreshTree());
        },

        // Zoom
        async zoomIn() {
            await ZoomManager.zoomIn(this, this.$refs, this.getCallbacks());
        },

        async zoomOut() {
            await ZoomManager.zoomOut(this, this.$refs, this.getCallbacks());
        },

        async resetZoom() {
            await ZoomManager.resetZoom(this, this.$refs, this.getCallbacks());
        },

        getZoomPercentage() {
            return ZoomManager.getZoomPercentage(this);
        },

        async zoomToFitAnnotation(anno) {
            await ZoomManager.zoomToFitAnnotation(anno, this, this.$refs, this.getCallbacks());
        },

        isAtMinZoom() {
            return ZoomManager.isAtMinZoom(this);
        },

        isAtMaxZoom() {
            return ZoomManager.isAtMaxZoom(this);
        },

        // View Type Management
        setViewType(viewType, orientation = null) {
            ViewTypeManager.setViewType(viewType, orientation, this, {
                updateAnnotationVisibility: () => this.updateAnnotationVisibility()
            });
        },

        setOrientation(orientation) {
            ViewTypeManager.setOrientation(orientation, this, {
                updateAnnotationVisibility: () => this.updateAnnotationVisibility()
            });
        },

        isAnnotationVisibleInView(anno) {
            return ViewTypeManager.isAnnotationVisibleInView(anno, this);
        },

        updateAnnotationVisibility() {
            ViewTypeManager.updateAnnotationVisibility(this);
        },

        getCurrentViewLabel() {
            return ViewTypeManager.getCurrentViewLabel(this);
        },

        // Entity Reference Management
        addEntityReference(annotationId, entityType, entityId, referenceType = 'primary') {
            EntityReferenceManager.addEntityReference(annotationId, entityType, entityId, referenceType, this);
        },

        removeEntityReference(annotationId, entityType, entityId) {
            EntityReferenceManager.removeEntityReference(annotationId, entityType, entityId, this);
        },

        getEntityReferences(annotationId) {
            return EntityReferenceManager.getEntityReferences(annotationId, this);
        },

        getReferencesByType(annotationId, entityType) {
            return EntityReferenceManager.getReferencesByType(annotationId, entityType, this);
        },

        hasEntityReference(annotationId, entityType, entityId) {
            return EntityReferenceManager.hasEntityReference(annotationId, entityType, entityId, this);
        },

        clearAnnotationReferences(annotationId) {
            EntityReferenceManager.clearAnnotationReferences(annotationId, this);
        },

        // Page Grouping
        getPageGroupedAnnotations() {
            // Use TreeManager's implementation which correctly builds annotation trees
            return TreeManager.getPageGroupedAnnotations(this);
        },

        // View Color
        getCurrentViewColor() {
            return ViewTypeManager.getCurrentViewColor(this);
        },

        // Intelligent Label Positioning
        getLabelPositionClasses(anno) {
            return UIHelpers.getLabelPositionClasses(anno, this.$refs, this);
        },

        // Intelligent Button Positioning
        getButtonPositionClasses(anno) {
            return UIHelpers.getButtonPositionClasses(anno, this.$refs, this);
        },

        // Annotation Interaction Handlers
        handleNodeClick(anno) {
            // Handle clicking on an annotation in tree or canvas
            this.selectedAnnotation = anno;
            this.activeAnnotationId = anno.id;
            console.log('👆 Node clicked:', anno.label || anno.id, 'type:', anno.type);

            // Auto-set drawing context based on clicked node
            DrawingSystem.setDrawingContextFromNode(anno, this);
        },

        handleAnnotationDoubleClick(anno) {
            // Handle double-clicking an annotation on canvas - enter isolation mode
            console.log('👆👆 Annotation double-clicked:', anno.label || anno.id);
            IsolationMode.enterIsolationMode(anno, this, this.getCallbacks());
        },

        handleNodeDoubleClick(type, id, ...args) {
            // Handle double-clicking a tree node - enter isolation mode
            console.log('👆👆 Tree node double-clicked:', type, id);

            // Find the annotation to isolate
            let annoToIsolate = null;

            if (type === 'room') {
                annoToIsolate = this.annotations.find(a =>
                    a.type === 'room' && a.roomId === id
                );
            } else if (type === 'room_location' || type === 'location') {
                const [roomId] = args;
                annoToIsolate = this.annotations.find(a =>
                    a.type === 'location' &&
                    a.roomLocationId === id &&
                    a.roomId === roomId
                );
            } else if (type === 'cabinet_run') {
                const [roomId, locationId] = args;
                annoToIsolate = this.annotations.find(a =>
                    a.type === 'cabinet_run' &&
                    a.cabinetRunId === id &&
                    a.roomId === roomId &&
                    (a.locationId === locationId || a.roomLocationId === locationId)
                );
            } else if (type === 'cabinet') {
                const [roomId, locationId, cabinetRunId] = args;
                annoToIsolate = this.annotations.find(a =>
                    a.type === 'cabinet' &&
                    a.id === id
                );
            }

            if (annoToIsolate) {
                IsolationMode.enterIsolationMode(annoToIsolate, this, this.getCallbacks());
            } else {
                console.warn('⚠️ Could not find annotation to isolate:', type, id);
            }
        },

        handleTreeNodeDoubleClick(type, id, label, ...args) {
            // Alternative handler for tree node double-click with label parameter
            console.log('👆👆 Tree node double-clicked (with label):', type, label, id);

            // Build annotation object for isolation
            let annoToIsolate = null;

            if (type === 'room') {
                annoToIsolate = {
                    type: 'room',
                    id: id,
                    label: label,
                    roomId: id
                };
            } else if (type === 'location') {
                const [roomId, roomName, locationId, locationName] = args;
                annoToIsolate = {
                    type: 'location',
                    id: id,
                    label: label,
                    roomId: roomId,
                    roomName: roomName,
                    roomLocationId: locationId || id,
                    locationId: locationId || id,
                    locationName: locationName || label
                };
            } else if (type === 'cabinet_run') {
                const [roomId, roomName, locationId, locationName, cabinetRunId] = args;
                annoToIsolate = {
                    type: 'cabinet_run',
                    id: id,
                    label: label,
                    roomId: roomId,
                    roomName: roomName,
                    locationId: locationId,
                    roomLocationId: locationId,
                    locationName: locationName,
                    cabinetRunId: cabinetRunId || id
                };
            }

            if (annoToIsolate) {
                IsolationMode.enterIsolationMode(annoToIsolate, this, this.getCallbacks());
            } else {
                console.warn('⚠️ Could not create annotation for isolation:', type, label);
            }
        },

        // Helper: Get callbacks object for manager functions
        getCallbacks() {
            return {
                displayPdf: () => PDFManager.displayPdf(this, this.$refs),
                loadAnnotations: () => this.loadAnnotations(),
                getFilteredPageNumbers: () => this.filteredPageNumbers,
                updateIsolationMask: () => this.updateIsolationMask(),
                syncOverlayToCanvas: () => this.syncOverlayToCanvas(),
                resetZoom: () => this.resetZoom(),
                $nextTick: () => this.$nextTick()
            };
        }
    };
}

// Export for use in Blade template
export default createPdfViewerComponent;
