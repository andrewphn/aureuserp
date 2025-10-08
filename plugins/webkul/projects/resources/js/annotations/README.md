# Annotation System JavaScript Modules

Modular JavaScript for PDF annotation functionality. These modules reduce the size of the blade component and improve maintainability.

## Module Structure

### `context-loader.js`
Handles API calls to load dropdown data and existing annotations.

**Exports**:
- `loadAnnotationContext(pdfPageId)` - Loads rooms, locations, runs, cabinets
- `loadExistingAnnotations(pdfPageId)` - Loads saved annotations for page

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
