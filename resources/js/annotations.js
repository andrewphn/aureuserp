/**
 * Annotation System Entry Point
 *
 * Bundles PDF.js and annotation system for production use.
 * Registers Alpine component globally for use in Blade templates.
 */

import * as pdfjsLib from 'pdfjs-dist';
import { createAnnotationComponent } from '../../plugins/webkul/projects/resources/js/annotations/alpine-component-factory.js';
import { PDFDocumentManager } from './pdf-document-manager.js';

// Configure PDF.js worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

// Initialize PDF Document Manager (singleton)
const pdfManager = PDFDocumentManager.getInstance();

// Export for debugging in console
window.pdfjsLib = pdfjsLib;
window.PDFDocumentManager = PDFDocumentManager;
window.pdfManager = pdfManager;
window.createAnnotationComponent = createAnnotationComponent;

console.log('✅ PDF Document Manager initialized (shared instance)');

// Auto-register Alpine component when Alpine initializes
document.addEventListener('alpine:init', () => {
    Alpine.data('pdfThumbnailPdfJs', () => {
        return createAnnotationComponent(pdfjsLib);
    });

    // Register V2 Canvas Viewer Component
    // Use a function that returns non-reactive data to prevent Livewire interference
    Alpine.data('annotationSystemV2', (config) => {
        // Store PDF page outside of Alpine's reactivity to prevent Livewire wrapping
        let _pdfPage = null;

        return {
        // PDF rendering state
        pdfUrl: config.pdfUrl,
        pageNumber: config.pageNumber,
        pdfPageId: config.pdfPageId,
        projectId: config.projectId,
        scale: 1.5,

        // Getters for private PDF page
        get pdfPage() { return _pdfPage; },
        set pdfPage(val) { _pdfPage = val; },

        // Context state
        activeRoomId: null,
        activeRoomName: '',
        activeLocationId: null,
        activeLocationName: '',
        drawMode: null,

        // Autocomplete state
        roomSearchQuery: '',
        locationSearchQuery: '',
        roomSuggestions: [],
        locationSuggestions: [],
        showRoomDropdown: false,
        showLocationDropdown: false,
        availableRooms: [],
        availableLocations: [],

        // Tree sidebar state
        tree: [],
        loading: false,
        error: null,
        expandedNodes: [],
        selectedNodeId: null,
        selectedNodeType: null,

        // Drawing state
        isDrawing: false,
        startX: 0,
        startY: 0,
        currentX: 0,
        currentY: 0,
        annotations: [],

        async init() {
            await this.loadPdf();
            await this.loadAvailableRooms();
            await this.loadProjectTree();
        },

        async loadPdf() {
            try {
                const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(this.pageNumber);
                // Store in closure variable to avoid Livewire proxy wrapping
                _pdfPage = page;
                await this.renderPage();
            } catch (error) {
                console.error('PDF load error:', error);
                this.error = 'Failed to load PDF';
            }
        },

        async renderPage() {
            const canvas = this.$refs.pdfCanvas;
            const ctx = canvas.getContext('2d');
            const viewport = this.pdfPage.getViewport({ scale: this.scale });

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await this.pdfPage.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;
        },

        startDrawing(event) {
            if (!this.canDraw()) return;

            const rect = this.$refs.drawCanvas.getBoundingClientRect();
            this.isDrawing = true;
            this.startX = event.clientX - rect.left;
            this.startY = event.clientY - rect.top;
            this.currentX = this.startX;
            this.currentY = this.startY;
        },

        draw(event) {
            if (!this.isDrawing) return;

            const rect = this.$refs.drawCanvas.getBoundingClientRect();
            this.currentX = event.clientX - rect.left;
            this.currentY = event.clientY - rect.top;

            this.redrawCanvas();
        },

        stopDrawing(event) {
            if (!this.isDrawing) return;
            this.isDrawing = false;

            const annotation = {
                x: Math.min(this.startX, this.currentX),
                y: Math.min(this.startY, this.currentY),
                width: Math.abs(this.currentX - this.startX),
                height: Math.abs(this.currentY - this.startY),
                type: this.drawMode,
                roomId: this.activeRoomId,
                locationId: this.activeLocationId,
                color: this.getColorForType(this.drawMode)
            };

            this.annotations.push(annotation);
            this.redrawCanvas();
        },

        redrawCanvas() {
            const canvas = this.$refs.drawCanvas;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            this.annotations.forEach(ann => {
                ctx.strokeStyle = ann.color;
                ctx.lineWidth = 3;
                ctx.strokeRect(ann.x, ann.y, ann.width, ann.height);
            });

            if (this.isDrawing) {
                const x = Math.min(this.startX, this.currentX);
                const y = Math.min(this.startY, this.currentY);
                const width = Math.abs(this.currentX - this.startX);
                const height = Math.abs(this.currentY - this.startY);

                ctx.strokeStyle = this.getColorForType(this.drawMode);
                ctx.lineWidth = 3;
                ctx.strokeRect(x, y, width, height);
            }
        },

        getColorForType(type) {
            const colors = {
                'cabinet_run': '#2563eb',
                'cabinet': '#16a34a'
            };
            return colors[type] || '#000000';
        },

        async loadAvailableRooms() {
            try {
                const response = await fetch(`/api/project/${this.projectId}/rooms`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.availableRooms = data.rooms || [];
                }
            } catch (error) {
                console.error('Failed to load rooms:', error);
            }
        },

        async loadLocationsForRoom(roomId) {
            try {
                const response = await fetch(`/api/project/room/${roomId}/locations`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.availableLocations = data.locations || [];
                }
            } catch (error) {
                console.error('Failed to load locations:', error);
            }
        },

        async searchRooms(query) {
            this.roomSearchQuery = query;
            this.showRoomDropdown = true;

            if (!query || query.trim() === '') {
                this.roomSuggestions = this.availableRooms.slice(0, 10);
                return;
            }

            const searchLower = query.toLowerCase();
            const matches = this.availableRooms.filter(room =>
                room.name.toLowerCase().includes(searchLower)
            );

            this.roomSuggestions = [
                ...matches,
                { id: 'new', name: query, isNew: true }
            ];
        },

        async searchLocations(query) {
            this.locationSearchQuery = query;
            this.showLocationDropdown = true;

            if (!this.activeRoomId) {
                this.locationSuggestions = [];
                return;
            }

            if (!query || query.trim() === '') {
                this.locationSuggestions = this.availableLocations.slice(0, 10);
                return;
            }

            const searchLower = query.toLowerCase();
            const matches = this.availableLocations.filter(loc =>
                loc.name.toLowerCase().includes(searchLower)
            );

            this.locationSuggestions = [
                ...matches,
                { id: 'new', name: query, isNew: true }
            ];
        },

        async selectRoom(room) {
            if (room.isNew) {
                const created = await this.createRoom(room.name);
                if (created) {
                    this.activeRoomId = created.id;
                    this.activeRoomName = created.name;
                    this.availableRooms.push(created);
                }
            } else {
                this.activeRoomId = room.id;
                this.activeRoomName = room.name;
            }

            this.roomSearchQuery = this.activeRoomName;
            this.showRoomDropdown = false;

            await this.loadLocationsForRoom(this.activeRoomId);

            this.activeLocationId = null;
            this.activeLocationName = '';
            this.locationSearchQuery = '';
        },

        async selectLocation(location) {
            if (location.isNew) {
                const created = await this.createLocation(location.name);
                if (created) {
                    this.activeLocationId = created.id;
                    this.activeLocationName = created.name;
                    this.availableLocations.push(created);
                }
            } else {
                this.activeLocationId = location.id;
                this.activeLocationName = location.name;
            }

            this.locationSearchQuery = this.activeLocationName;
            this.showLocationDropdown = false;
        },

        async createRoom(name) {
            try {
                const response = await fetch(`/api/project/${this.projectId}/rooms`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ name })
                });
                if (response.ok) {
                    const data = await response.json();
                    return data.room;
                }
            } catch (error) {
                console.error('Failed to create room:', error);
            }
            return null;
        },

        async createLocation(name) {
            try {
                const response = await fetch(`/api/project/room/${this.activeRoomId}/locations`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ name })
                });
                if (response.ok) {
                    const data = await response.json();
                    return data.location;
                }
            } catch (error) {
                console.error('Failed to create location:', error);
            }
            return null;
        },

        setDrawMode(mode) {
            if (this.drawMode === mode) {
                this.drawMode = null;
            } else {
                this.drawMode = mode;
            }
        },

        clearContext() {
            this.activeRoomId = null;
            this.activeRoomName = '';
            this.activeLocationId = null;
            this.activeLocationName = '';
            this.drawMode = null;
            this.roomSearchQuery = '';
            this.locationSearchQuery = '';
        },

        canDraw() {
            if (this.drawMode === 'cabinet_run') return this.activeRoomId !== null && this.activeLocationId !== null;
            if (this.drawMode === 'cabinet') return this.activeRoomId !== null && this.activeLocationId !== null;
            return false;
        },

        getContextLabel() {
            if (this.activeRoomName && this.activeLocationName) {
                return `${this.activeRoomName} > ${this.activeLocationName}`;
            } else if (this.activeRoomName) {
                return this.activeRoomName;
            }
            return 'No context selected';
        },

        async loadProjectTree() {
            this.loading = true;
            try {
                const response = await fetch(`/api/project/${this.projectId}/tree`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.tree = data.tree || [];
                }
            } catch (error) {
                console.error('Failed to load tree:', error);
                this.error = 'Failed to load project structure';
            } finally {
                this.loading = false;
            }
        },

        isExpanded(nodeId) {
            return this.expandedNodes.includes(nodeId);
        },

        toggleNode(nodeId) {
            const index = this.expandedNodes.indexOf(nodeId);
            if (index > -1) {
                this.expandedNodes.splice(index, 1);
            } else {
                this.expandedNodes.push(nodeId);
            }
        },

        selectNode(nodeId, nodeType, nodeName, parentRoomId = null) {
            this.selectedNodeId = nodeId;
            this.selectedNodeType = nodeType;

            if (nodeType === 'room') {
                this.selectRoom({ id: nodeId, name: nodeName, isNew: false });
            } else if (nodeType === 'room_location' && parentRoomId) {
                const room = this.tree.find(r => r.id === parentRoomId);
                if (room) {
                    this.selectRoom({ id: room.id, name: room.name, isNew: false });
                    this.selectLocation({ id: nodeId, name: nodeName, isNew: false });
                }
            }
        },

        async refreshTree() {
            await this.loadProjectTree();
        },

        async saveAnnotations() {
            if (this.annotations.length === 0) {
                alert('No annotations to save');
                return;
            }

            try {
                const response = await fetch(`/api/pdf/annotations/page/${this.pdfPageId}/canvas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        annotations: this.annotations,
                        page_number: this.pageNumber,
                        create_entities: true
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`Saved ${result.saved_count} annotations!`);
                    await this.refreshTree();
                } else {
                    alert('Failed to save: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Save error:', error);
                alert('Failed to save annotations');
            }
        }
    };  // End of return object
    });  // End of Alpine.data closure

    console.log('✅ PDF Annotation System loaded - Alpine components registered');
});
