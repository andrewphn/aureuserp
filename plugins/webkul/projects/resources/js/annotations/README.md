# Annotation System JavaScript Modules

Modular JavaScript for PDF annotation functionality. These modules reduce the size of the blade component and improve maintainability.

## Module Structure

### `context-loader.js`
Handles API calls to load dropdown data, existing annotations, and metadata.

**Exports**:
- `loadAnnotationContext(pdfPageId)` - Loads rooms, locations, runs, cabinets
- `loadExistingAnnotations(pdfPageId)` - Loads saved annotations for page
- `loadCabinetRuns(pdfPageId)` - Loads cabinet runs for page
- `loadProjectNumber(pdfPageId)` - Loads project number
- `loadAllMetadata(pdfPageId)` - Loads all metadata in parallel

### `cascade-filters.js`
Manages hierarchical dropdown filtering logic.

**Exports**:
- `createCascadeFilters()` - Returns filter object with methods:
  - `filterRoomLocations(selectedRoomId, allRoomLocations)`
  - `filterCabinetRuns(selectedRoomLocationId, allCabinetRuns)`
  - `filterCabinets(selectedCabinetRunId, allCabinets)`
  - `resetChildSelections()`

### `annotation-saver.js`
Handles saving annotations with automatic entity creation.

**Exports**:
- `saveAnnotationsWithEntities(pdfPageId, annotations, annotationType, context)` - Saves annotations and creates linked entities

### `canvas-renderer.js`
PDF.js canvas rendering, zoom, rotation, and view management.

**Exports**:
- `createCanvasRenderer()` - Returns renderer object with methods:
  - `renderCanvas(pdfPageCache, pdfCanvas, annotationCanvas, baseScale, zoomLevel, rotation)`
  - `calculateFitToPage(pdfPageCache, container, baseScale, rotation)`
  - `calculateFitToWidth(pdfPageCache, container, baseScale, rotation)`
  - `calculateFitToHeight(pdfPageCache, container, baseScale, rotation)`
  - `zoomIn(currentZoom, step, max)`
  - `zoomOut(currentZoom, step, min)`
  - `resetZoom()`
  - `rotateClockwise(currentRotation)`
  - `rotateCounterClockwise(currentRotation)`
  - `resetView()`
  - `saveView(zoomLevel, rotation, pageNum)`
  - `loadPdfPage(pdfjsLib, pdfUrl, pageNum)`
  - `calculateBaseScale(viewport, metadataPanelWidth, padding)`

### `annotation-drawer.js`
Drawing interactions for creating annotation rectangles on canvas.

**Exports**:
- `createAnnotationDrawer()` - Returns drawer object with methods:
  - `startDrawing(e, canvas, currentTool)`
  - `drawPreview(e, canvas, drawState, annotations, redrawCallback)`
  - `stopDrawing(e, canvas, drawState, options, existingAnnotations)`
  - `getAnnotationColor(annotationType, roomType, roomColors)`
  - `generateLabel(annotationType, roomType, projectNumber, roomCodes, annotationCount)`
  - `redrawAnnotations(annotations, canvas)`
  - `setCursor(canvas, tool)`

### `annotation-editor.js`
Undo/redo, selection, and deletion of annotations.

**Exports**:
- `createAnnotationEditor()` - Returns editor object with methods:
  - `saveState(annotations, undoStack, maxStackSize)`
  - `undo(annotations, undoStack, redoStack)`
  - `redo(annotations, undoStack, redoStack)`
  - `deleteSelected(annotations, selectedId)`
  - `removeAnnotation(annotations, index)`
  - `clearLastAnnotation(annotations)`
  - `clearAllAnnotations(annotations, confirmCallback)`
  - `selectAnnotation(annotations, x, y, tolerance)`
  - `deselectAnnotation()`

### `page-navigator.js`
PDF page navigation (next, previous, first, last, go to page).

**Exports**:
- `createPageNavigator()` - Returns navigator object with methods:
  - `goToPage(pdfDocument, pageNum, totalPages)`
  - `goToFirstPage(pdfDocument, totalPages)`
  - `goToLastPage(pdfDocument, totalPages)`
  - `goToNextPage(pdfDocument, currentPage, totalPages)`
  - `goToPreviousPage(pdfDocument, currentPage, totalPages)`
  - `isValidPageNumber(pageNum, totalPages)`
  - `sanitizePageInput(input, totalPages)`

## Usage in Blade Components

```javascript
// Import modules
import { loadAnnotationContext, loadExistingAnnotations } from './context-loader.js';
import { createCascadeFilters } from './cascade-filters.js';
import { saveAnnotationsWithEntities } from './annotation-saver.js';

// In Alpine.js component
async function init() {
    // Load context data
    const context = await loadAnnotationContext(this.pdfPageId);
    if (context) {
        this.availableRooms = context.rooms;
        this.availableRoomLocations = context.room_locations;
        // ...
    }

    // Load existing annotations
    this.annotations = await loadExistingAnnotations(this.pdfPageId);

    // Setup cascade filters
    const filters = createCascadeFilters();
    this.filterRoomLocations = () => {
        this.filteredRoomLocations = filters.filterRoomLocations(
            this.selectedRoomId,
            this.availableRoomLocations
        );
    };
}

async function save() {
    const result = await saveAnnotationsWithEntities(
        this.pdfPageId,
        this.annotations,
        this.annotationType,
        {
            selectedRoomId: this.selectedRoomId,
            selectedRoomLocationId: this.selectedRoomLocationId,
            selectedRunType: this.selectedRunType
        }
    );
}
```

## Benefits

✅ **Smaller Files**: Blade component stays under 1500 lines
✅ **Reusability**: Modules can be used across multiple components
✅ **Testability**: Pure functions are easier to unit test
✅ **Maintainability**: Single responsibility principle
✅ **Performance**: Only load needed modules

## File Sizes

| Module | Lines | Purpose |
|--------|-------|---------|
| context-loader.js | 65 | API data loading |
| cascade-filters.js | 60 | Dropdown filtering |
| annotation-saver.js | 100 | Save with entity creation |
| **Total** | **225** | **Extracted from 2000+ line component** |

## Next Steps

1. Import these modules in the blade component
2. Replace inline functions with module calls
3. Add annotation editing modules (separate file)
4. Add version migration modules (separate file)
