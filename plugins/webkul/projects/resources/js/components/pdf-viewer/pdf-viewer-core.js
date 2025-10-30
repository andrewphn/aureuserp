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
            await AnnotationManager.saveAnnotations(this, () => this.loadAnnotations(), silent);
        },

        async deleteAnnotation(anno) {
            await AnnotationManager.deleteAnnotation(anno, this, () => TreeManager.refreshTree(this));
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
            // Direct inline resize handler - updates annotation during drag
            if (!this.isResizing || !this.resizeStart) return;

            const deltaX = event.clientX - this.resizeStart.mouseX;
            const deltaY = event.clientY - this.resizeStart.mouseY;

            const annotation = this.annotations.find(a => a.id === this.activeAnnotationId);
            if (!annotation) return;

            let newX = this.resizeStart.annoX;
            let newY = this.resizeStart.annoY;
            let newWidth = this.resizeStart.annoWidth;
            let newHeight = this.resizeStart.annoHeight;

            const handle = this.resizeHandle;

            // Horizontal adjustments
            if (handle.includes('w')) {
                newX = this.resizeStart.annoX + deltaX;
                newWidth = this.resizeStart.annoWidth - deltaX;
            } else if (handle.includes('e')) {
                newWidth = this.resizeStart.annoWidth + deltaX;
            }

            // Vertical adjustments
            if (handle.includes('n')) {
                newY = this.resizeStart.annoY + deltaY;
                newHeight = this.resizeStart.annoHeight - deltaY;
            } else if (handle.includes('s')) {
                newHeight = this.resizeStart.annoHeight + deltaY;
            }

            // Enforce minimum size
            const minSize = 20;
            if (newWidth < minSize || newHeight < minSize) return;

            // Update annotation screen coordinates
            annotation.screenX = newX;
            annotation.screenY = newY;
            annotation.screenWidth = newWidth;
            annotation.screenHeight = newHeight;
        },

        handleMove(event) {
            // Direct inline move handler - updates annotation position during drag
            if (!this.isMoving || !this.moveStart) return;

            const deltaX = event.clientX - this.moveStart.mouseX;
            const deltaY = event.clientY - this.moveStart.mouseY;

            const annotation = this.annotations.find(a => a.id === this.activeAnnotationId);
            if (!annotation) return;

            // Update screen position
            annotation.screenX = this.moveStart.annoX + deltaX;
            annotation.screenY = this.moveStart.annoY + deltaY;
        },

        finishResizeOrMove(event) {
            // Finish resize or move operation - save to server
            if (this.isResizing) {
                // Finish resize
                const annotation = this.annotations.find(a => a.id === this.activeAnnotationId);
                if (annotation) {
                    // Convert screen coordinates back to PDF coordinates
                    const pdfTopLeft = CoordTransform.screenToPdf(
                        annotation.screenX,
                        annotation.screenY,
                        this.$refs,
                        this
                    );
                    const pdfBottomRight = CoordTransform.screenToPdf(
                        annotation.screenX + annotation.screenWidth,
                        annotation.screenY + annotation.screenHeight,
                        this.$refs,
                        this
                    );

                    // Update PDF coordinates
                    annotation.pdfX = pdfTopLeft.x;
                    annotation.pdfY = pdfTopLeft.y;
                    annotation.pdfWidth = Math.abs(pdfBottomRight.x - pdfTopLeft.x);
                    annotation.pdfHeight = Math.abs(pdfTopLeft.y - pdfBottomRight.y);
                    annotation.normalizedX = pdfTopLeft.normalized.x;
                    annotation.normalizedY = pdfTopLeft.normalized.y;
                }

                this.isResizing = false;
                this.resizeHandle = null;
                this.resizeStart = null;
                this.activeAnnotationId = null;

            } else if (this.isMoving) {
                // Finish move
                const annotation = this.annotations.find(a => a.id === this.activeAnnotationId);
                if (annotation) {
                    // Convert screen coordinates back to PDF coordinates
                    const pdfPos = CoordTransform.screenToPdf(
                        annotation.screenX,
                        annotation.screenY,
                        this.$refs,
                        this
                    );

                    // Update PDF coordinates (keep width/height the same)
                    annotation.pdfX = pdfPos.x;
                    annotation.pdfY = pdfPos.y;
                    annotation.normalizedX = pdfPos.normalized.x;
                    annotation.normalizedY = pdfPos.normalized.y;
                }

                this.isMoving = false;
                this.moveStart = null;
                this.activeAnnotationId = null;
            }

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
            await TreeManager.refreshTree(this);
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
            if (this.activeViewType === 'plan') return 'var(--primary-600)';
            if (this.activeViewType === 'elevation') return 'var(--warning-600)';
            if (this.activeViewType === 'section') return 'var(--info-600)';
            if (this.activeViewType === 'detail') return 'var(--success-600)';
            return 'var(--gray-600)';
        },

        // Intelligent Label Positioning
        getLabelPositionClasses(anno) {
            const overlayRect = CoordTransform.getOverlayRect(this.$refs, this);
            if (!overlayRect) return '-top-10 left-0';

            const spaceAbove = anno.screenY;
            const spaceBelow = overlayRect.height - (anno.screenY + anno.screenHeight);
            const spaceLeft = anno.screenX;
            const spaceRight = overlayRect.width - (anno.screenX + anno.screenWidth);

            // Prefer above if there's room
            if (spaceAbove >= 40) return '-top-10 left-0';
            // Otherwise below
            if (spaceBelow >= 40) return '-bottom-10 left-0';
            // If no vertical space, try right
            if (spaceRight >= 100) return 'top-0 -right-2 translate-x-full';
            // Last resort: left
            return 'top-0 -left-2 -translate-x-full';
        },

        // Intelligent Button Positioning
        getButtonPositionClasses(anno) {
            const overlayRect = CoordTransform.getOverlayRect(this.$refs, this);
            if (!overlayRect) return '-top-7 right-0';

            const spaceAbove = anno.screenY;
            const spaceRight = overlayRect.width - (anno.screenX + anno.screenWidth);

            // Prefer top-right corner
            if (spaceAbove >= 30) return '-top-7 right-0';
            // Otherwise bottom-right
            return '-bottom-7 right-0';
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
