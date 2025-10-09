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
        error: false,
        modalImageLoaded: false,
        modalError: false,
        annotationViewerLoaded: false,
        isSaving: false,
        isDrawing: false,
        loadingMetadata: false,

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

        // Measurement (Phase 6d: Measurement Tools)
        measurements: [],
        measurementMode: null, // 'distance' or 'area'

        // Saved view
        savedView: null,

        // Backend data
        pdfPageId: null,
        projectId: null,
        projectNumber: 'TFW-0001',
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
        async loadThumbnail(pdfUrl, pageNumber, pdfPageId) {
            this.currentPdfUrl = pdfUrl;
            this.currentPageNum = pageNumber;
            this.pdfPageId = pdfPageId;

            try {
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(pageNumber);

                const viewport = page.getViewport({ scale: 1.0 });
                const scale = 300 / viewport.width;
                const scaledViewport = page.getViewport({ scale });

                const canvas = this.$refs.thumbnail;
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

                const loadingTask = pdfjsLib.getDocument(this.currentPdfUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(this.currentPageNum);

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

        // ========== SAVE ==========
        async saveAnnotations() {
            if (this.annotations.length === 0) {
                alert('No annotations to save');
                return;
            }

            this.isSaving = true;

            try {
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

                alert(`✅ Saved ${result.count} annotations!\n✅ Created ${result.entities_created_count} entities!`);
                this.showAnnotationModal = false;
            } catch (error) {
                alert('❌ Failed to save annotations: ' + error.message);
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

            if (confirm(`Restore unsaved annotations from ${new Date(draft.savedAt).toLocaleString()}?`)) {
                this.annotations = draft.annotations;
                this.lastSavedAt = draft.savedAt;
                drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationIds);
                return true;
            }

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
