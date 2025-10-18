/**
 * Alpine Component Factory
 * Creates Alpine.js component with all annotation functionality
 */

import { loadAllMetadata } from './context-loader.js';
import { createCascadeFilters } from './cascade-filters.js';
import { saveAnnotationsWithEntities } from './annotation-saver.js';
import { createCanvasRenderer } from './canvas-renderer.js';
import { createAnnotationDrawer } from './annotation-drawer.js';
import { createAnnotationEditor } from './annotation-editor.js';
import { createPageNavigator } from './page-navigator.js';

export function createAnnotationComponent(pdfjsLib) {
    // Create module instances
    const renderer = createCanvasRenderer();
    const drawer = createAnnotationDrawer();
    const editor = createAnnotationEditor();
    const navigator = createPageNavigator();
    const filters = createCascadeFilters();

    // Store PDF objects outside Alpine's reactive system
    let pdfPageCache = null;
    let pdfDocumentCache = null;

    return {
        // UI state
        imageLoaded: false,
        showModal: false,
        showAnnotationModal: false,
        showAnnotationV2Modal: false,
        error: false,
        modalImageLoaded: false,
        modalError: false,
        annotationViewerLoaded: false,
        isSaving: false,
        isDrawing: false,
        loadingMetadata: false,
        activeTab: 'metadata',  // Annotation modal tab state

        // PDF state
        currentPdfUrl: null,
        currentPageNum: null,
        totalPages: 1,
        pageInputValue: 1,
        baseScale: 1.0,
        zoomLevel: 1.0,
        rotation: 0,

        // Drawing state
        currentTool: 'rectangle',
        isDrawing: false,
        startX: 0,
        startY: 0,
        selectedAnnotationId: null,  // Legacy - will migrate to selectedAnnotationIds
        selectedAnnotationIds: [],   // NEW: Array for bulk selection

        // Resize/move state
        isResizing: false,
        isMoving: false,
        resizeHandle: null,
        moveStartX: 0,
        moveStartY: 0,

        // Undo/redo stacks
        undoStack: [],
        redoStack: [],

        // Clipboard (Phase 6b: Copy/Paste)
        clipboard: [],

        // Templates (Phase 6c: Annotation Templates)
        availableTemplates: [],

        // Auto-save (Phase 6e: Draft Persistence)
        draftSaveTimer: null,
        hasUnsavedChanges: false,
        lastSavedAt: null,
        annotationsLastModified: null,  // Database timestamp for conflict resolution

        // Measurement (Phase 6d: Measurement Tools)
        measurements: [],
        measurementMode: null, // 'distance' or 'area'

        // Saved view
        savedView: null,

        // Backend data
        pdfPageId: null,
        projectId: null,
        projectNumber: 'TFW-0001',
        projectContext: null,  // Stores partner, company, branch, address for cover page auto-population
        availableRooms: [],
        availableRoomLocations: [],
        availableCabinetRuns: [],
        availableCabinets: [],

        // Annotations
        annotations: [],

        // Annotation context
        annotationType: 'room',
        selectedRoomId: null,
        selectedRoomLocationId: null,
        selectedCabinetRunId: null,
        selectedCabinetId: null,
        selectedRunType: 'base',
        currentRoomType: '',

        // Measurement recording fields
        measurementLength: '',  // Room length in feet
        measurementWidth: '',  // Room width in feet
        measurementHeight: '',  // Room ceiling height in feet or cabinet height in inches
        measurementLengthInches: '',  // Cabinet run/cabinet length in inches
        measurementWidthInches: '',  // Cabinet width in inches
        measurementHeightInches: '',  // Cabinet height in inches
        measurementDepthInches: '',  // Cabinet depth in inches
        measurementLinearFeet: '',  // Calculated linear feet from inches
        measurementDoorCount: '',  // Number of doors/drawers

        // Page metadata
        pageType: '',  // 'floor_plan', 'elevation', 'detail', 'cover', 'other'

        // Cover page fields
        coverCustomerId: '',
        coverCompanyId: '',
        coverBranchId: '',
        coverAddressStreet1: '',
        coverAddressStreet2: '',
        coverAddressCity: '',
        coverAddressStateId: '',
        coverAddressZip: '',
        coverAddressCountryId: '',

        // Floor plan page fields
        floorPlanFloorNumber: '',
        floorPlanRoomsIncluded: [],  // Array of room IDs shown on this page
        floorPlanScale: '',
        floorPlanOrientation: '',
        floorPlanNotes: '',

        // Elevation page fields
        elevationRoomId: null,
        elevationRoomLocationId: null,
        elevationViewDirection: '',
        elevationCabinetRuns: [],  // Array of cabinet run IDs shown
        elevationScale: '',
        elevationShowHeights: true,
        elevationNotes: '',

        // Detail page fields
        detailType: '',  // "Cabinet", "Molding", "Hardware", "Connection"
        detailCabinetId: null,
        detailNumber: '',
        detailScale: '',
        detailNotes: '',

        // Other page fields
        otherDescription: '',
        otherReferenceInfo: '',
        otherNotes: '',

        // Filtered dropdowns
        filteredRoomLocations: [],
        filteredCabinetRuns: [],
        filteredCabinets: [],

        // Room type configuration
        roomColors: {
            kitchen: '#3B82F6',      // Blue
            pantry: '#10B981',       // Green
            laundry: '#F59E0B',      // Amber
            bathroom: '#EF4444',     // Red
            mudroom: '#8B5CF6',      // Purple
            office: '#EC4899',       // Pink
            bedroom: '#06B6D4',      // Cyan
            closet: '#84CC16'        // Lime
        },

        roomCodes: {
            kitchen: 'K',
            pantry: 'P',
            laundry: 'L',
            bathroom: 'B',
            mudroom: 'M',
            office: 'O',
            bedroom: 'BR',
            closet: 'C'
        },

        // ========== INITIALIZATION ==========
        async fallbackLoadPage(pdfUrl, pageNumber) {
            // Fallback to direct PDF.js if manager not available (shouldn't happen)
            console.warn('⚠️ PDF manager not available, using fallback direct load');
            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            const pdf = await loadingTask.promise;
            return await pdf.getPage(pageNumber);
        },

        async loadThumbnail(pdfUrl, pageNumber, pdfPageId) {
            this.currentPdfUrl = pdfUrl;
            this.currentPageNum = pageNumber;
            this.pdfPageId = pdfPageId;

            try {
                // Use shared PDF document manager (prevents duplicate PDF loads)
                // Access from window since it's exported globally in annotations.js
                const manager = window.PDFDocumentManager?.getInstance() || window.pdfManager;
                const page = manager ? await manager.getPage(pdfUrl, pageNumber) : await this.fallbackLoadPage(pdfUrl, pageNumber);

                const viewport = page.getViewport({ scale: 1.0 });

                // Responsive thumbnail sizing based on container width
                const canvas = this.$refs.thumbnail;
                const container = canvas.closest('.w-full');
                const containerWidth = container ? container.clientWidth : 300;

                // Calculate scale to fill container width (with small padding for border)
                const targetWidth = containerWidth - 16; // 16px for padding/border
                const scale = targetWidth / viewport.width;
                const scaledViewport = page.getViewport({ scale });

                canvas.width = scaledViewport.width;
                canvas.height = scaledViewport.height;

                const renderContext = {
                    canvasContext: canvas.getContext('2d'),
                    viewport: scaledViewport
                };

                await page.render(renderContext).promise;
                this.imageLoaded = true;
            } catch (err) {
                console.error('Error loading thumbnail:', err);
                this.error = true;
            }
        },

        async loadModalImage() {
            try {
                this.modalImageLoaded = false;
                this.modalError = false;

                // Use shared PDF document manager
                const manager = window.PDFDocumentManager?.getInstance() || window.pdfManager;
                const page = manager ? await manager.getPage(this.currentPdfUrl, this.currentPageNum) : await this.fallbackLoadPage(this.currentPdfUrl, this.currentPageNum);

                const viewport = page.getViewport({ scale: 1.0 });
                const scale = 1400 / viewport.width;
                const scaledViewport = page.getViewport({ scale });

                const canvas = this.$refs.modalCanvas;
                canvas.width = scaledViewport.width;
                canvas.height = scaledViewport.height;

                const renderContext = {
                    canvasContext: canvas.getContext('2d'),
                    viewport: scaledViewport
                };

                await page.render(renderContext).promise;
                this.modalImageLoaded = true;
            } catch (err) {
                console.error('Error loading modal PDF:', err);
                this.modalError = true;
            }
        },

        async loadCanvasAnnotationViewer() {
            try {
                // Load PDF document and page
                const result = await renderer.loadPdfPage(pdfjsLib, this.currentPdfUrl, this.currentPageNum);
                pdfDocumentCache = result.pdfDocument;
                pdfPageCache = result.pdfPage;
                this.totalPages = result.totalPages;
                this.pageInputValue = this.currentPageNum;

                // Calculate base scale
                const viewport = pdfPageCache.getViewport({ scale: 1.0 });
                this.baseScale = renderer.calculateBaseScale(viewport);

                // Load backend metadata
                await this.loadMetadata();

                // Initial render
                await this.renderCanvas();

                this.annotationViewerLoaded = true;
                console.log('✅ Canvas annotation viewer loaded successfully');
            } catch (error) {
                console.error('Canvas annotation loading error:', error);
                alert('Failed to load PDF annotation viewer: ' + error.message);
            }
        },

        async loadMetadata() {
            this.loadingMetadata = true;
            try {
                if (!this.pdfPageId) {
                    console.warn('No PDF page ID available for loading metadata');
                    return;
                }

                const metadata = await loadAllMetadata(this.pdfPageId);

                this.availableRooms = metadata.rooms;
                this.availableRoomLocations = metadata.roomLocations;
                this.availableCabinetRuns = metadata.cabinetRuns;
                this.availableCabinets = metadata.cabinets;
                this.projectId = metadata.projectId;
                this.projectNumber = metadata.projectNumber;
                this.annotations = metadata.annotations;
                this.annotationsLastModified = metadata.annotationsLastModified;  // Store for conflict resolution

                // Store project context for cover page auto-population
                if (metadata.projectContext) {
                    this.projectContext = metadata.projectContext;
                }

                // Also load page-specific metadata (page type, cover fields, etc.)
                await this.loadPageMetadata();

                console.log('✅ Loaded all metadata');
            } catch (error) {
                console.error('Failed to load metadata:', error);
            } finally {
                this.loadingMetadata = false;
            }
        },

        // ========== CANVAS RENDERING ==========
        async renderCanvas() {
            if (!pdfPageCache) return;

            await renderer.renderCanvas(
                pdfPageCache,
                this.$refs.pdfCanvas,
                this.$refs.annotationCanvas,
                this.baseScale,
                this.zoomLevel,
                this.rotation
            );

            // Redraw annotations with selection highlighting
            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
        },

        // ========== ZOOM CONTROLS ==========
        async zoomIn() {
            this.zoomLevel = renderer.zoomIn(this.zoomLevel);
            await this.renderCanvas();
        },

        async zoomOut() {
            this.zoomLevel = renderer.zoomOut(this.zoomLevel);
            await this.renderCanvas();
        },

        async resetZoom() {
            this.zoomLevel = renderer.resetZoom();
            await this.renderCanvas();
        },

        async fitToPage() {
            const container = this.$refs.pdfCanvas.closest('.flex-1');
            this.zoomLevel = renderer.calculateFitToPage(pdfPageCache, container, this.baseScale, this.rotation);
            await this.renderCanvas();
        },

        async fitToWidth() {
            const container = this.$refs.pdfCanvas.closest('.flex-1');
            this.zoomLevel = renderer.calculateFitToWidth(pdfPageCache, container, this.baseScale, this.rotation);
            await this.renderCanvas();
        },

        async fitToHeight() {
            const container = this.$refs.pdfCanvas.closest('.flex-1');
            this.zoomLevel = renderer.calculateFitToHeight(pdfPageCache, container, this.baseScale, this.rotation);
            await this.renderCanvas();
        },

        async actualSize() {
            this.zoomLevel = 1.0;
            await this.renderCanvas();
        },

        // ========== ROTATION ==========
        async rotateClockwise() {
            this.rotation = renderer.rotateClockwise(this.rotation);
            await this.renderCanvas();
        },

        async rotateCounterClockwise() {
            this.rotation = renderer.rotateCounterClockwise(this.rotation);
            await this.renderCanvas();
        },

        // ========== VIEW MANAGEMENT ==========
        resetView() {
            const view = renderer.resetView();
            this.zoomLevel = view.zoomLevel;
            this.rotation = view.rotation;
            this.renderCanvas();
        },

        saveCurrentView() {
            this.savedView = renderer.saveView(this.zoomLevel, this.rotation, this.currentPageNum);
            alert(`View saved! (${Math.round(this.zoomLevel * 100)}% zoom, ${this.rotation}° rotation)`);
        },

        async restoreSavedView() {
            if (!this.savedView) {
                alert('No saved view to restore');
                return;
            }

            this.zoomLevel = this.savedView.zoomLevel;
            this.rotation = this.savedView.rotation;

            if (this.savedView.pageNum !== this.currentPageNum) {
                await this.goToPage(this.savedView.pageNum);
            } else {
                await this.renderCanvas();
            }
        },

        // ========== PAGE NAVIGATION ==========
        async goToPage(pageNum) {
            const result = await navigator.goToPage(pdfDocumentCache, pageNum, this.totalPages);
            if (!result) return;

            this.currentPageNum = result.pageNum;
            this.pageInputValue = result.pageNum;
            this.annotations = [];
            pdfPageCache = result.pdfPage;
            await this.renderCanvas();
        },

        async goToFirstPage() {
            const result = await navigator.goToFirstPage(pdfDocumentCache, this.totalPages);
            if (result) {
                this.currentPageNum = result.pageNum;
                this.pageInputValue = result.pageNum;
                this.annotations = [];
                pdfPageCache = result.pdfPage;
                await this.renderCanvas();
            }
        },

        async goToLastPage() {
            const result = await navigator.goToLastPage(pdfDocumentCache, this.totalPages);
            if (result) {
                this.currentPageNum = result.pageNum;
                this.pageInputValue = result.pageNum;
                this.annotations = [];
                pdfPageCache = result.pdfPage;
                await this.renderCanvas();
            }
        },

        async goToNextPage() {
            const result = await navigator.goToNextPage(pdfDocumentCache, this.currentPageNum, this.totalPages);
            if (result) {
                this.currentPageNum = result.pageNum;
                this.pageInputValue = result.pageNum;
                this.annotations = [];
                pdfPageCache = result.pdfPage;
                await this.renderCanvas();
            }
        },

        async goToPreviousPage() {
            const result = await navigator.goToPreviousPage(pdfDocumentCache, this.currentPageNum, this.totalPages);
            if (result) {
                this.currentPageNum = result.pageNum;
                this.pageInputValue = result.pageNum;
                this.annotations = [];
                pdfPageCache = result.pdfPage;
                await this.renderCanvas();
            }
        },

        // ========== TOOLS ==========
        setTool(tool) {
            this.currentTool = tool;
            this.selectedAnnotationId = null;
            drawer.setCursor(this.$refs.annotationCanvas, tool);
        },

        // ========== DRAWING ==========
        startDrawing(e) {
            const rect = this.$refs.annotationCanvas.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;

            // If using select tool, check for resize handles or annotation clicks
            if (this.currentTool === 'select' && this.selectedAnnotationId) {
                // Find the selected annotation
                const selectedAnnotation = this.annotations.find(a => a.id === this.selectedAnnotationId);

                if (selectedAnnotation) {
                    // Check if clicking on a resize handle
                    const handlePos = drawer.getResizeHandle(
                        clickX,
                        clickY,
                        selectedAnnotation,
                        this.$refs.annotationCanvas
                    );

                    if (handlePos) {
                        // Start resizing
                        this.isResizing = true;
                        this.resizeHandle = handlePos;
                        this.startX = clickX;
                        this.startY = clickY;
                        return;
                    }

                    // Check if clicking inside the annotation (for moving)
                    const clickedAnnotation = drawer.getClickedAnnotation(
                        clickX,
                        clickY,
                        this.annotations,
                        this.$refs.annotationCanvas
                    );

                    if (clickedAnnotation && clickedAnnotation.id === this.selectedAnnotationId) {
                        // Start moving
                        this.isMoving = true;
                        this.moveStartX = clickX;
                        this.moveStartY = clickY;
                        return;
                    }
                }
            }

            // Check for annotation selection with select tool
            if (this.currentTool === 'select') {
                const clickedAnnotation = drawer.getClickedAnnotation(
                    clickX,
                    clickY,
                    this.annotations,
                    this.$refs.annotationCanvas
                );

                if (clickedAnnotation) {
                    // Save state before selecting (for undo)
                    const stateUpdate = editor.saveState(this.annotations, this.undoStack);
                    this.undoStack = stateUpdate.undoStack;
                    this.redoStack = stateUpdate.redoStack;

                    // Use toggleSelection to support Shift+Click bulk selection
                    this.toggleSelection(clickedAnnotation.id, e.shiftKey);
                } else {
                    // Deselect if clicking empty space (unless Shift is held)
                    if (!e.shiftKey) {
                        this.deselectAll();
                    }
                }
                return;
            }

            // Handle rectangle tool drawing
            const drawState = drawer.startDrawing(e, this.$refs.annotationCanvas, this.currentTool);
            if (drawState) {
                this.isDrawing = drawState.isDrawing;
                this.startX = drawState.startX;
                this.startY = drawState.startY;
            }
        },

        draw(e) {
            const rect = this.$refs.annotationCanvas.getBoundingClientRect();
            const currentX = e.clientX - rect.left;
            const currentY = e.clientY - rect.top;

            // Handle resizing
            if (this.isResizing && this.selectedAnnotationId) {
                const selectedAnnotation = this.annotations.find(a => a.id === this.selectedAnnotationId);
                if (selectedAnnotation) {
                    const newBounds = drawer.resizeAnnotation(
                        selectedAnnotation,
                        this.resizeHandle,
                        currentX,
                        currentY,
                        this.$refs.annotationCanvas
                    );

                    // Update annotation bounds
                    Object.assign(selectedAnnotation, newBounds);
                    drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
                }
                return;
            }

            // Handle moving
            if (this.isMoving && this.selectedAnnotationId) {
                const selectedAnnotation = this.annotations.find(a => a.id === this.selectedAnnotationId);
                if (selectedAnnotation) {
                    const deltaX = currentX - this.moveStartX;
                    const deltaY = currentY - this.moveStartY;

                    const newPos = drawer.moveAnnotation(
                        selectedAnnotation,
                        deltaX,
                        deltaY,
                        this.$refs.annotationCanvas
                    );

                    // Update annotation position
                    selectedAnnotation.x = newPos.x;
                    selectedAnnotation.y = newPos.y;

                    // Update move start for next frame
                    this.moveStartX = currentX;
                    this.moveStartY = currentY;

                    drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
                }
                return;
            }

            // Handle drawing new annotation
            if (!this.isDrawing) return;

            const drawState = {
                isDrawing: this.isDrawing,
                startX: this.startX,
                startY: this.startY
            };

            drawer.drawPreview(
                e,
                this.$refs.annotationCanvas,
                drawState,
                this.annotations,
                (annotations, canvas) => drawer.redrawAnnotations(annotations, canvas, this.selectedAnnotationId)
            );
        },

        stopDrawing(e) {
            // Handle finishing resize
            if (this.isResizing) {
                // Save state for undo
                const stateUpdate = editor.saveState(this.annotations, this.undoStack);
                this.undoStack = stateUpdate.undoStack;
                this.redoStack = stateUpdate.redoStack;

                this.isResizing = false;
                this.resizeHandle = null;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
                return;
            }

            // Handle finishing move
            if (this.isMoving) {
                // Save state for undo
                const stateUpdate = editor.saveState(this.annotations, this.undoStack);
                this.undoStack = stateUpdate.undoStack;
                this.redoStack = stateUpdate.redoStack;

                this.isMoving = false;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
                return;
            }

            // Handle finishing new annotation drawing
            if (!this.isDrawing) return;

            const drawState = {
                isDrawing: this.isDrawing,
                startX: this.startX,
                startY: this.startY
            };

            const options = {
                annotationType: this.annotationType,
                roomType: this.currentRoomType,
                projectNumber: this.projectNumber,
                roomCodes: this.roomCodes,
                roomColors: this.roomColors
            };

            const newAnnotation = drawer.stopDrawing(
                e,
                this.$refs.annotationCanvas,
                drawState,
                options,
                this.annotations
            );

            if (newAnnotation) {
                const stateUpdate = editor.saveState(this.annotations, this.undoStack);
                this.undoStack = stateUpdate.undoStack;
                this.redoStack = stateUpdate.redoStack;

                this.annotations.push(newAnnotation);
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
            }

            this.isDrawing = false;
        },

        cancelDrawing() {
            if (this.isDrawing) {
                this.isDrawing = false;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
            }
        },

        // ========== EDITING ==========
        undo() {
            const result = editor.undo(this.annotations, this.undoStack, this.redoStack);
            if (result) {
                this.annotations = result.annotations;
                this.undoStack = result.undoStack;
                this.redoStack = result.redoStack;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
            }
        },

        redo() {
            const result = editor.redo(this.annotations, this.undoStack, this.redoStack);
            if (result) {
                this.annotations = result.annotations;
                this.undoStack = result.undoStack;
                this.redoStack = result.redoStack;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
            }
        },

        deleteSelected() {
            const result = editor.deleteSelected(this.annotations, this.selectedAnnotationId);
            if (result) {
                const stateUpdate = editor.saveState(this.annotations, this.undoStack);
                this.undoStack = stateUpdate.undoStack;
                this.redoStack = stateUpdate.redoStack;

                this.annotations = result.annotations;
                this.selectedAnnotationId = result.selectedId;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
            }
        },

        removeAnnotation(index) {
            const stateUpdate = editor.saveState(this.annotations, this.undoStack);
            this.undoStack = stateUpdate.undoStack;
            this.redoStack = stateUpdate.redoStack;

            this.annotations = editor.removeAnnotation(this.annotations, index);
            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
        },

        clearLastAnnotation() {
            const stateUpdate = editor.saveState(this.annotations, this.undoStack);
            this.undoStack = stateUpdate.undoStack;
            this.redoStack = stateUpdate.redoStack;

            this.annotations = editor.clearLastAnnotation(this.annotations);
            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
        },

        clearAllAnnotations() {
            const stateUpdate = editor.saveState(this.annotations, this.undoStack);
            this.undoStack = stateUpdate.undoStack;
            this.redoStack = stateUpdate.redoStack;

            this.annotations = editor.clearAllAnnotations(this.annotations, confirm);
            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
        },

        // ========== CASCADE FILTERS ==========
        filterRoomLocations() {
            this.filteredRoomLocations = filters.filterRoomLocations(this.selectedRoomId, this.availableRoomLocations);
        },

        filterCabinetRuns() {
            this.filteredCabinetRuns = filters.filterCabinetRuns(this.selectedRoomLocationId, this.availableCabinetRuns);
        },

        filterCabinets() {
            this.filteredCabinets = filters.filterCabinets(this.selectedCabinetRunId, this.availableCabinets);
        },

        resetChildSelections() {
            const reset = filters.resetChildSelections();
            Object.assign(this, reset);
        },

        // ========== PAGE TYPE METADATA ==========
        resetPageTypeFields() {
            // Reset all page type-specific fields when changing page type
            if (this.pageType !== 'cover') {
                this.coverCustomerId = '';
                this.coverCompanyId = '';
                this.coverBranchId = '';
                this.coverAddressStreet1 = '';
                this.coverAddressStreet2 = '';
                this.coverAddressCity = '';
                this.coverAddressStateId = '';
                this.coverAddressZip = '';
                this.coverAddressCountryId = '';
            }
        },

        autoPopulateCoverPageFields() {
            // Auto-populate cover page fields from project context
            // Only populate if fields are currently empty
            if (!this.projectContext) {
                console.warn('⚠️ No project context available for auto-population');
                return;
            }

            // Populate customer ID (partner)
            if (!this.coverCustomerId && this.projectContext.partner) {
                this.coverCustomerId = String(this.projectContext.partner.id);
                console.log('✅ Auto-populated customer ID:', this.coverCustomerId);
            }

            // Populate company ID
            if (!this.coverCompanyId && this.projectContext.company) {
                this.coverCompanyId = String(this.projectContext.company.id);
                console.log('✅ Auto-populated company ID:', this.coverCompanyId);
            }

            // Populate branch ID
            if (!this.coverBranchId && this.projectContext.branch) {
                this.coverBranchId = String(this.projectContext.branch.id);
                console.log('✅ Auto-populated branch ID:', this.coverBranchId);
            }

            // Populate address fields
            if (this.projectContext.address) {
                if (!this.coverAddressStreet1 && this.projectContext.address.street1) {
                    this.coverAddressStreet1 = this.projectContext.address.street1;
                    console.log('✅ Auto-populated street1:', this.coverAddressStreet1);
                }

                if (!this.coverAddressStreet2 && this.projectContext.address.street2) {
                    this.coverAddressStreet2 = this.projectContext.address.street2;
                    console.log('✅ Auto-populated street2:', this.coverAddressStreet2);
                }

                if (!this.coverAddressCity && this.projectContext.address.city) {
                    this.coverAddressCity = this.projectContext.address.city;
                    console.log('✅ Auto-populated city:', this.coverAddressCity);
                }

                if (!this.coverAddressStateId && this.projectContext.address.state_id) {
                    this.coverAddressStateId = String(this.projectContext.address.state_id);
                    console.log('✅ Auto-populated state ID:', this.coverAddressStateId);
                }

                if (!this.coverAddressZip && this.projectContext.address.zip) {
                    this.coverAddressZip = this.projectContext.address.zip;
                    console.log('✅ Auto-populated zip:', this.coverAddressZip);
                }

                if (!this.coverAddressCountryId && this.projectContext.address.country_id) {
                    this.coverAddressCountryId = String(this.projectContext.address.country_id);
                    console.log('✅ Auto-populated country ID:', this.coverAddressCountryId);
                }
            }

            console.log('✅ Cover page auto-population complete');
        },

        async loadPageMetadata() {
            // Load existing page metadata from database
            if (!this.pdfPageId) return;

            try {
                const response = await fetch(`/api/pdf/page/${this.pdfPageId}/metadata`);
                if (response.ok) {
                    const data = await response.json();

                    // Load page type
                    this.pageType = data.page_type || '';

                    // Load cover page fields if this is a cover page
                    if (data.page_type === 'cover' && data.cover_metadata) {
                        this.coverCustomerId = data.cover_metadata.customer_id || '';
                        this.coverCompanyId = data.cover_metadata.company_id || '';
                        this.coverBranchId = data.cover_metadata.branch_id || '';
                        this.coverAddressStreet1 = data.cover_metadata.address_street1 || '';
                        this.coverAddressStreet2 = data.cover_metadata.address_street2 || '';
                        this.coverAddressCity = data.cover_metadata.address_city || '';
                        this.coverAddressStateId = data.cover_metadata.address_state_id || '';
                        this.coverAddressZip = data.cover_metadata.address_zip || '';
                        this.coverAddressCountryId = data.cover_metadata.address_country_id || '';
                    }

                    // Auto-populate cover page fields from project context if empty
                    if (data.page_type === 'cover' && this.projectContext) {
                        this.autoPopulateCoverPageFields();
                    }

                    console.log('✅ Loaded page metadata');
                }
            } catch (error) {
                console.error('Failed to load page metadata:', error);
            }
        },

        async savePageMetadata() {
            // Save page metadata separately from annotations
            if (!this.pdfPageId) return;

            const metadata = {
                page_type: this.pageType
            };

            // Add cover page-specific data
            if (this.pageType === 'cover') {
                metadata.cover_metadata = {
                    customer_id: this.coverCustomerId,
                    company_id: this.coverCompanyId,
                    branch_id: this.coverBranchId,
                    address_street1: this.coverAddressStreet1,
                    address_street2: this.coverAddressStreet2,
                    address_city: this.coverAddressCity,
                    address_state_id: this.coverAddressStateId,
                    address_zip: this.coverAddressZip,
                    address_country_id: this.coverAddressCountryId,
                };
            }

            try {
                const response = await fetch(`/api/pdf/page/${this.pdfPageId}/metadata`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(metadata)
                });

                if (!response.ok) {
                    throw new Error('Failed to save page metadata');
                }

                return true;
            } catch (error) {
                console.error('Failed to save page metadata:', error);
                throw error;
            }
        },

        // ========== SAVE ==========
        async saveAnnotations() {
            this.isSaving = true;

            try {
                // Save page metadata first (page type, cover fields, etc.)
                await this.savePageMetadata();

                // Save annotations if any exist
                if (this.annotations.length > 0) {
                    const context = {
                        selectedRoomId: this.selectedRoomId,
                        selectedRoomLocationId: this.selectedRoomLocationId,
                        selectedCabinetRunId: this.selectedCabinetRunId,
                        selectedRunType: this.selectedRunType
                    };

                    const result = await saveAnnotationsWithEntities(
                        this.pdfPageId,
                        this.annotations,
                        this.annotationType,
                        context
                    );

                    alert(`✅ Saved page metadata!\n✅ Saved ${result.count} annotations!\n✅ Created ${result.entities_created_count} entities!`);
                } else {
                    alert('✅ Saved page metadata!');
                }

                this.showAnnotationModal = false;
            } catch (error) {
                alert('❌ Failed to save: ' + error.message);
            } finally {
                this.isSaving = false;
            }
        },

        // ========== PHASE 6a: BULK SELECTION ==========
        toggleSelection(annotationId, isShiftClick) {
            if (isShiftClick) {
                // Add to selection or remove if already selected
                const index = this.selectedAnnotationIds.indexOf(annotationId);
                if (index > -1) {
                    this.selectedAnnotationIds.splice(index, 1);
                } else {
                    this.selectedAnnotationIds.push(annotationId);
                }
            } else {
                // Single selection (replace all)
                this.selectedAnnotationIds = [annotationId];
            }
            // Maintain backward compatibility
            this.selectedAnnotationId = this.selectedAnnotationIds.length > 0
                ? this.selectedAnnotationIds[0]
                : null;

            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
        },

        selectAll() {
            this.selectedAnnotationIds = this.annotations.map(a => a.id);
            this.selectedAnnotationId = this.selectedAnnotationIds[0] || null;
            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
        },

        deselectAll() {
            this.selectedAnnotationIds = [];
            this.selectedAnnotationId = null;
            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
        },

        // ========== PHASE 6b: COPY/PASTE ==========
        copySelected() {
            if (this.selectedAnnotationIds.length === 0) {
                alert('No annotations selected to copy');
                return;
            }

            this.clipboard = this.annotations
                .filter(a => this.selectedAnnotationIds.includes(a.id))
                .map(a => JSON.parse(JSON.stringify(a))); // Deep clone

            alert(`✅ Copied ${this.clipboard.length} annotation(s)`);
        },

        pasteFromClipboard() {
            if (this.clipboard.length === 0) {
                alert('Clipboard is empty');
                return;
            }

            const stateUpdate = editor.saveState(this.annotations, this.undoStack);
            this.undoStack = stateUpdate.undoStack;
            this.redoStack = stateUpdate.redoStack;

            const newAnnotations = this.clipboard.map(a => ({
                ...a,
                id: Date.now() + Math.random(), // New unique ID
                x: a.x + 0.05, // Offset by 5%
                y: a.y + 0.05,
            }));

            this.annotations.push(...newAnnotations);
            this.selectedAnnotationIds = newAnnotations.map(a => a.id);
            this.selectedAnnotationId = this.selectedAnnotationIds[0];
            this.markUnsavedChanges();

            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
            alert(`✅ Pasted ${newAnnotations.length} annotation(s)`);
        },

        // ========== PHASE 6c: ANNOTATION TEMPLATES ==========
        saveAsTemplate() {
            if (this.selectedAnnotationIds.length !== 1) {
                alert('Select exactly one annotation to save as template');
                return;
            }

            const annotation = this.annotations.find(a => a.id === this.selectedAnnotationIds[0]);
            const templateName = prompt('Enter template name:', `${annotation.annotation_type} Template`);

            if (!templateName) return;

            const template = {
                id: Date.now(),
                name: templateName,
                annotation_type: annotation.annotation_type,
                room_type: annotation.room_type,
                color: annotation.color,
                width: annotation.width,
                height: annotation.height,
            };

            this.availableTemplates.push(template);
            this.saveTemplatesToLocalStorage();
            alert(`✅ Template "${templateName}" saved!`);
        },

        applyTemplate(templateId) {
            const template = this.availableTemplates.find(t => t.id === templateId);
            if (!template) return;

            const stateUpdate = editor.saveState(this.annotations, this.undoStack);
            this.undoStack = stateUpdate.undoStack;
            this.redoStack = stateUpdate.redoStack;

            const newAnnotation = {
                id: Date.now(),
                x: 0.1,
                y: 0.1,
                width: template.width,
                height: template.height,
                text: this.generateLabel(template.annotation_type, template.room_type),
                room_type: template.room_type,
                color: template.color,
                annotation_type: template.annotation_type,
            };

            this.annotations.push(newAnnotation);
            this.selectedAnnotationIds = [newAnnotation.id];
            this.selectedAnnotationId = newAnnotation.id;
            this.markUnsavedChanges();

            drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
        },

        saveTemplatesToLocalStorage() {
            localStorage.setItem('pdf_annotation_templates', JSON.stringify(this.availableTemplates));
        },

        loadTemplatesFromLocalStorage() {
            const stored = localStorage.getItem('pdf_annotation_templates');
            if (stored) {
                this.availableTemplates = JSON.parse(stored);
            }
        },

        // ========== PHASE 6d: MEASUREMENT TOOLS ==========
        setMeasurementTool(type) {
            this.measurementMode = type; // 'distance' or 'area'
            this.currentTool = 'measurement';
        },

        calculateDistance(x1, y1, x2, y2) {
            // Convert normalized coords to canvas pixels
            const canvas = this.$refs.annotationCanvas;
            const px1 = x1 * canvas.width;
            const py1 = y1 * canvas.height;
            const px2 = x2 * canvas.width;
            const py2 = y2 * canvas.height;

            const pixels = Math.sqrt(Math.pow(px2 - px1, 2) + Math.pow(py2 - py1, 2));

            // Assuming standard 8.5x11 page at 72 DPI
            // Convert pixels to inches then to feet
            const inches = pixels / (this.baseScale * 72);
            const feet = inches / 12;

            return { pixels, inches, feet };
        },

        calculateArea(points) {
            // Shoelace formula for polygon area
            const canvas = this.$refs.annotationCanvas;
            let area = 0;

            for (let i = 0; i < points.length; i++) {
                const j = (i + 1) % points.length;
                const x1 = points[i].x * canvas.width;
                const y1 = points[i].y * canvas.height;
                const x2 = points[j].x * canvas.width;
                const y2 = points[j].y * canvas.height;

                area += x1 * y2;
                area -= x2 * y1;
            }

            area = Math.abs(area / 2);

            // Convert to square feet
            const sqInches = area / Math.pow(this.baseScale * 72, 2);
            const sqFeet = sqInches / 144;

            return { pixels: area, sqInches, sqFeet };
        },

        // ========== PHASE 6e: AUTO-SAVE DRAFTS ==========
        markUnsavedChanges() {
            this.hasUnsavedChanges = true;
            this.scheduleDraftSave();
        },

        scheduleDraftSave() {
            if (this.draftSaveTimer) {
                clearTimeout(this.draftSaveTimer);
            }

            this.draftSaveTimer = setTimeout(() => {
                this.saveDraft();
            }, 30000); // Auto-save after 30 seconds
        },

        saveDraft() {
            const draft = {
                pdfPageId: this.pdfPageId,
                annotations: this.annotations,
                savedAt: new Date().toISOString(),
            };

            localStorage.setItem(`pdf_annotation_draft_${this.pdfPageId}`, JSON.stringify(draft));
            this.lastSavedAt = draft.savedAt;
            console.log('✅ Draft auto-saved at', this.lastSavedAt);
        },

        loadDraft() {
            const stored = localStorage.getItem(`pdf_annotation_draft_${this.pdfPageId}`);
            if (!stored) return false;

            const draft = JSON.parse(stored);
            const draftTime = new Date(draft.savedAt);
            const dbTime = this.annotationsLastModified ? new Date(this.annotationsLastModified) : null;

            // Auto-resolve: If database is newer, clear old draft and use database
            if (dbTime && dbTime > draftTime) {
                console.log(`🔄 Database annotations (${dbTime.toLocaleString()}) are newer than draft (${draftTime.toLocaleString()}). Using database.`);
                localStorage.removeItem(`pdf_annotation_draft_${this.pdfPageId}`);
                return false;  // Keep database annotations (already loaded)
            }

            // Draft is newer or no database timestamp - ask user
            let message = `Restore unsaved annotations from ${draftTime.toLocaleString()}?`;
            if (dbTime) {
                message += `\n\n⚠️ Database has ${this.annotations.length} annotation(s) from ${dbTime.toLocaleString()}.\n\nClick OK to use LOCAL DRAFT (${draft.annotations.length} annotations)\nClick Cancel to use DATABASE (${this.annotations.length} annotations)`;
            }

            if (confirm(message)) {
                console.log(`✅ User chose to restore draft with ${draft.annotations.length} annotations`);
                this.annotations = draft.annotations;
                this.lastSavedAt = draft.savedAt;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
                return true;
            }

            console.log(`✅ User chose to keep database annotations (${this.annotations.length} annotations)`);
            // Clear old draft since user chose database
            localStorage.removeItem(`pdf_annotation_draft_${this.pdfPageId}`);
            return false;
        },

        clearDraft() {
            localStorage.removeItem(`pdf_annotation_draft_${this.pdfPageId}`);
            this.hasUnsavedChanges = false;
            this.lastSavedAt = null;
        },

        // ========== INIT ==========
        init() {
            // Load templates and drafts on initialization
            this.loadTemplatesFromLocalStorage();

            // Watch for modal open
            this.$watch('showModal', (value) => {
                if (value) {
                    setTimeout(() => this.loadModalImage(), 100);
                }
            });

            this.$watch('showAnnotationModal', (value) => {
                if (value && !this.annotationViewerLoaded) {
                    this.loadCanvasAnnotationViewer();
                    // Attempt to load draft after annotation viewer loads
                    setTimeout(() => {
                        this.loadDraft();
                    }, 500);
                }
            });

            // Watch for unsaved changes
            this.$watch('annotations', () => {
                if (this.annotationViewerLoaded) {
                    this.markUnsavedChanges();
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (!this.showAnnotationModal) return;

                // Undo/Redo
                if (e.ctrlKey && e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                }
                if (e.ctrlKey && e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                }

                // Copy/Paste (Phase 6b)
                if (e.ctrlKey && e.key === 'c') {
                    e.preventDefault();
                    this.copySelected();
                }
                if (e.ctrlKey && e.key === 'v') {
                    e.preventDefault();
                    this.pasteFromClipboard();
                }

                // Select All (Phase 6a)
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    this.selectAll();
                }

                // Delete
                if (e.key === 'Delete' && this.selectedAnnotationIds.length > 0) {
                    e.preventDefault();
                    this.deleteSelected();
                }

                // Deselect (Escape)
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.deselectAll();
                }

                // Tool shortcuts
                if (e.key === 'r' || e.key === 'R') {
                    e.preventDefault();
                    this.setTool('rectangle');
                }
                if (e.key === 'v' || e.key === 'V') {
                    e.preventDefault();
                    this.setTool('select');
                }
                if (e.key === 'm' || e.key === 'M') {
                    e.preventDefault();
                    this.setMeasurementTool('distance');
                }
            });
        }
    };
}
