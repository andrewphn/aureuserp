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
        startX: 0,
        startY: 0,
        selectedAnnotationId: null,

        // Undo/redo stacks
        undoStack: [],
        redoStack: [],

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
            // If using select tool, check for annotation clicks
            if (this.currentTool === 'select') {
                const rect = this.$refs.annotationCanvas.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const clickY = e.clientY - rect.top;

                const clickedAnnotation = drawer.getClickedAnnotation(
                    clickX,
                    clickY,
                    this.annotations,
                    this.$refs.annotationCanvas
                );

                if (clickedAnnotation) {
                    // Select the annotation
                    this.selectedAnnotationId = clickedAnnotation.id;
                    drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
                } else {
                    // Deselect if clicking empty space
                    this.selectedAnnotationId = null;
                    drawer.redrawAnnotations(this.annotations, this.$refs.annotationCanvas, this.selectedAnnotationId);
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

        // ========== INIT ==========
        init() {
            // Watch for modal open
            this.$watch('showModal', (value) => {
                if (value) {
                    setTimeout(() => this.loadModalImage(), 100);
                }
            });

            this.$watch('showAnnotationModal', (value) => {
                if (value && !this.annotationViewerLoaded) {
                    this.loadCanvasAnnotationViewer();
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (!this.showAnnotationModal) return;

                if (e.ctrlKey && e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                }
                if (e.ctrlKey && e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                }
                if (e.key === 'Delete' && this.selectedAnnotationId !== null) {
                    e.preventDefault();
                    this.deleteSelected();
                }
                if (e.key === 'r' || e.key === 'R') {
                    e.preventDefault();
                    this.setTool('rectangle');
                }
                if (e.key === 'v' || e.key === 'V') {
                    e.preventDefault();
                    this.setTool('select');
                }
            });
        }
    };
}
