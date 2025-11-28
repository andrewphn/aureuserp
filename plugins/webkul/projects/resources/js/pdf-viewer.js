/**
 * PDF Viewer Entry Point
 * Vite bundle entry for PDF annotation viewer
 */

import * as pdfjsLib from 'pdfjs-dist';
import { createPdfViewerComponent } from './components/pdf-viewer/pdf-viewer-core.js';

// Configure PDF.js worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

// Export PDF.js globally for component use
window.pdfjsLib = pdfjsLib;
import * as AnnotationManager from './components/pdf-viewer/managers/annotation-manager.js';
import * as TreeManager from './components/pdf-viewer/managers/tree-manager.js';
import * as CoordTransform from './components/pdf-viewer/managers/coordinate-transform.js';
import * as DrawingSystem from './components/pdf-viewer/managers/drawing-system.js';
import * as ResizeMoveSystem from './components/pdf-viewer/managers/resize-move-system.js';
import * as StateManager from './components/pdf-viewer/managers/state-manager.js';
import * as IsolationModeManager from './components/pdf-viewer/managers/isolation-mode-manager.js';
import * as VisibilityToggleManager from './components/pdf-viewer/managers/visibility-toggle-manager.js';
import * as ZoomManager from './components/pdf-viewer/managers/zoom-manager.js';
import * as ViewTypeManager from './components/pdf-viewer/managers/view-type-manager.js';
import * as EntityReferenceManager from './components/pdf-viewer/managers/entity-reference-manager.js';
import * as FilterSystem from './components/pdf-viewer/managers/filter-system.js';
import * as NavigationManager from './components/pdf-viewer/managers/navigation-manager.js';
import * as UndoRedoManager from './components/pdf-viewer/managers/undo-redo-manager.js';
import * as AutocompleteManager from './components/pdf-viewer/managers/autocomplete-manager.js';

// Make available globally for Alpine.js
window.createPdfViewerComponent = createPdfViewerComponent;

// Export managers globally for inline Alpine components
window.PdfViewerManagers = {
    AnnotationManager,
    TreeManager,
    CoordTransform,
    DrawingSystem,
    ResizeMoveSystem,
    StateManager,
    IsolationModeManager,
    VisibilityToggleManager,
    ZoomManager,
    ViewTypeManager,
    EntityReferenceManager,
    FilterSystem,
    NavigationManager,
    UndoRedoManager,
    AutocompleteManager
};

// Register Alpine component when Alpine is available
if (typeof Alpine !== 'undefined') {
    Alpine.data('annotationSystemV3', createPdfViewerComponent);
    Alpine.data('annotationSystemV2', createPdfViewerComponent); // Backward compatibility
    console.log('✅ PDF Annotation System loaded - Alpine components registered (V2 + V3)');
} else {
    // Wait for Alpine to be ready
    document.addEventListener('alpine:init', () => {
        Alpine.data('annotationSystemV3', createPdfViewerComponent);
        Alpine.data('annotationSystemV2', createPdfViewerComponent); // Backward compatibility
        console.log('✅ PDF Annotation System loaded - Alpine components registered (V2 + V3)');
    });
}

// Export for ES module imports
export default createPdfViewerComponent;

console.log('✓ PDF Viewer component loaded and ready');
