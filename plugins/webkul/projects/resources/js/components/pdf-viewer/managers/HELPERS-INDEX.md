# PDF Viewer Manager Helper Functions Index

**Automatically Generated Quick Reference Guide**
**Last Updated:** 2025-11-21
**Total Functions:** 141
**Managers:** 19

> 💡 This file is auto-generated. Run `node generate-helpers-index.js` to update.

---

## 📋 Table of Contents

- [Annotation Manager](#annotation-manager)
- [Autocomplete Manager](#autocomplete-manager)
- [Coordinate Transform](#coordinate-transform)
- [Drawing System](#drawing-system)
- [Entity Lookup](#entity-lookup)
- [Entity Reference Manager](#entity-reference-manager)
- [Filter System](#filter-system)
- [Hierarchy Detection Manager](#hierarchy-detection-manager)
- [Isolation Mode Manager](#isolation-mode-manager)
- [Navigation Manager](#navigation-manager)
- [PDF Manager](#pdf-manager)
- [Resize Move System](#resize-move-system)
- [State Manager](#state-manager)
- [Tree Manager](#tree-manager)
- [Ui Helpers](#ui-helpers)
- [Undo Redo Manager](#undo-redo-manager)
- [View Type Manager](#view-type-manager)
- [Visibility Toggle Manager](#visibility-toggle-manager)
- [Zoom Manager](#zoom-manager)

---

## Annotation Manager

**File:** `annotation-manager.js`
**Description:** Handles annotation loading, transformation, and editing operations

### Exported Functions

#### `editAnnotation(annotation, state)`
**Line:** 341
**Purpose:** Edit annotation (open Livewire modal)
**Parameters:**
- `annotation` (Object) - Annotation to edit
- `state` (Object) - Component state

#### `findAnnotationByEntity(entityType, entityId, state)`
**Line:** 362
**Purpose:** Find annotation by entity type and ID
**Parameters:**
- `entityType` (String) - Entity type (room, room_location, cabinet_run)
- `entityId` (Number) - Entity ID
- `state` (Object) - Component state
**Returns:** Found annotation or null

#### `checkForDuplicateEntity(drawMode, state)`
**Line:** 390
**Purpose:** Check if entity already has annotation on current page
**Parameters:**
- `drawMode` (String) - Current draw mode
- `state` (Object) - Component state
**Returns:** Existing annotation or null

#### `highlightAnnotation(annotation, state)`
**Line:** 446
**Purpose:** Highlight annotation temporarily
**Parameters:**
- `annotation` (Object) - Annotation to highlight
- `state` (Object) - Component state

#### `getAnnotationZIndex(annotation, state)`
**Line:** 464
**Purpose:** Get z-index for annotation (selected annotations come to front for easier interaction)
**Parameters:**
- `annotation` (Object) - Annotation object
- `state` (Object) - Component state
**Returns:** Z-index value

#### `toggleLockAnnotation(annotation, state)`
**Line:** 474
**Purpose:** Toggle lock state for annotation
**Parameters:**
- `annotation` (Object) - Annotation to lock/unlock
- `state` (Object) - Component state

### Internal Functions

#### `transformAnnotationFromAPI(anno, state, refs)`
**Line:** 63
**Purpose:** Transform annotation data from API to internal format

#### `populateParentConnections(anno, state, visited = new Set()`
**Line:** 108
**Purpose:** Recursively populate parent entity connections for annotation

#### `applyIsolationFilter(state)`
**Line:** 156
**Purpose:** Apply isolation filter to annotations

#### `isAnnotationVisibleInIsolation(anno, state)`
**Line:** 435
**Purpose:** Helper functions /


## Autocomplete Manager

**File:** `autocomplete-manager.js`
**Description:** Provides search functionality for rooms and locations

### Exported Functions

#### `searchRooms(query, state)`
**Line:** 13
**Purpose:** Search rooms
**Parameters:**
- `query` (String) - Search query
- `state` (Object) - Component state

#### `searchLocations(query, state)`
**Line:** 111
**Purpose:** Search locations
**Parameters:**
- `query` (String) - Search query
- `state` (Object) - Component state

#### `clearRoomSearch(state)`
**Line:** 210
**Purpose:** Clear room search
**Parameters:**
- `state` (Object) - Component state

#### `clearLocationSearch(state)`
**Line:** 220
**Purpose:** Clear location search
**Parameters:**
- `state` (Object) - Component state


## Coordinate Transform

**File:** `coordinate-transform.js`
**Description:** Converts between PDF coordinates and screen coordinates

### Exported Functions

#### `initializeCoordinateSystem(state)`
**Line:** 10
**Purpose:** Initialize coordinate transformation system
**Parameters:**
- `state` (Object) - Component state

#### `getEffectiveZoom(state)`
**Line:** 28
**Purpose:** Get effective CSS zoom factor (for debugging only) NOTE: This detects CSS zoom property, NOT browser zoom (Ctrl/Cmd +/-) Browser zoom is automatically handled by getBoundingClientRect() Keeping for debugging purposes only - no longer used in coordinate calculations
**Parameters:**
- `state` (Object) - Component state
**Returns:** CSS zoom factor (usually 1.0)

#### `invalidateZoomCache(state)`
**Line:** 48
**Purpose:** Invalidate CSS zoom cache (call after zoom changes)
**Parameters:**
- `state` (Object) - Component state

#### `getCanvasRect(refs, state)`
**Line:** 58
**Purpose:** Get canvas bounding rectangle (cached for performance)
**Parameters:**
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state
**Returns:** Canvas bounding rectangle

#### `getOverlayRect(refs, state)`
**Line:** 88
**Purpose:** Get cached overlay bounding rectangle (uses canvas rect)
**Parameters:**
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state
**Returns:** Bounding rectangle

#### `getCanvasScale(refs, state)`
**Line:** 98
**Purpose:** Calculate canvas scale based on actual vs natural dimensions
**Parameters:**
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state
**Returns:** Scale factor

#### `pdfToScreen(pdfX, pdfY, pdfWidth = 0, pdfHeight = 0, refs, state)`
**Line:** 124
**Purpose:** Convert PDF coordinates to screen coordinates
**Parameters:**
- `pdfX` (Number) - PDF X coordinate
- `pdfY` (Number) - PDF Y coordinate (bottom-left origin)
- `pdfWidth` (Number) - PDF width
- `pdfHeight` (Number) - PDF height
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state
**Returns:** Screen position {x, y, width, height}

#### `screenToPdf(screenX, screenY, refs, state)`
**Line:** 168
**Purpose:** Convert screen coordinates to PDF coordinates
**Parameters:**
- `screenX` (Number) - Screen X position
- `screenY` (Number) - Screen Y position
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state
**Returns:** PDF position {x, y, normalized: {x, y}}

#### `getAnnotationScreenPosition(annotation, refs, state)`
**Line:** 211
**Purpose:** Get annotation screen position from stored PDF coordinates
**Parameters:**
- `annotation` (Object) - Annotation object
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state
**Returns:** Screen position {x, y, width, height}

#### `syncOverlayToCanvas(refs, state)`
**Line:** 227
**Purpose:** Sync overlay dimensions to match canvas
**Parameters:**
- `refs` (Object) - Alpine.js $refs
- `state` (Object) - Component state

#### `updateAnnotationPositions(state, refs)`
**Line:** 255
**Purpose:** Update all annotation screen positions for current zoom/scale
**Parameters:**
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `renderAnnotations(state, refs)`
**Line:** 282
**Purpose:** Render/update annotations (lockout-aware wrapper for updateAnnotationPositions) Re-calculates screen positions for all annotations
**Parameters:**
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `pointInRect(x, y, rect)`
**Line:** 300
**Purpose:** Check if point is within rectangle
**Parameters:**
- `x` (Number) - Point X
- `y` (Number) - Point Y
- `rect` (Object) - Rectangle {x, y, width, height}
**Returns:** True if point is inside

#### `setupBrowserZoomHandler(state, refs, callbacks)`
**Line:** 316
**Purpose:** Setup browser zoom/resize handler to keep annotations aligned
**Parameters:**
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs
- `callbacks` (Object) - Callback functions
**Returns:** Cleanup function


## Drawing System

**File:** `drawing-system.js`
**Description:** Handles interactive drawing of annotations and context management

### Exported Functions

#### `startDrawing(event, state, refs)`
**Line:** 18
**Purpose:** Start drawing annotation
**Parameters:**
- `event` (MouseEvent) - Mouse down event
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `updateDrawPreview(event, state, refs)`
**Line:** 54
**Purpose:** Update drawing preview
**Parameters:**
- `event` (MouseEvent) - Mouse move event
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `finishDrawing(event, state, refs)`
**Line:** 88
**Purpose:** Finish drawing and create annotation
**Parameters:**
- `event` (MouseEvent) - Mouse up event
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `cancelDrawing(state)`
**Line:** 115
**Purpose:** Cancel drawing operation (e.g., mouse left canvas)
**Parameters:**
- `state` (Object) - Component state

#### `setDrawMode(mode, state, checkDuplicateCallback, highlightCallback)`
**Line:** 308
**Purpose:** Set draw mode (toggle on/off)
**Parameters:**
- `mode` (String) - Draw mode to set
- `state` (Object) - Component state
- `checkDuplicateCallback` (Function) - Callback to check for duplicates
- `highlightCallback` (Function) - Callback to highlight existing annotation

#### `canDrawLocation(state)`
**Line:** 347
**Purpose:** Check if user can draw location (requires room context)
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if can draw location

#### `canDraw(state)`
**Line:** 361
**Purpose:** Check if user can draw cabinet run or cabinet (requires location context)
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if can draw

#### `clearContext(state)`
**Line:** 378
**Purpose:** Clear drawing context
**Parameters:**
- `state` (Object) - Component state

#### `getContextLabel(state)`
**Line:** 393
**Purpose:** Get context label for display
**Parameters:**
- `state` (Object) - Component state
**Returns:** Context label

#### `activateDrawingLockout(state)`
**Line:** 429
**Purpose:** Activate drawing lockout to prevent interruptions during micro-adjustments Resets timer if called again (for continuous drawing adjustments)
**Parameters:**
- `state` (Object) - Component state

#### `isDrawingLocked(state)`
**Line:** 460
**Purpose:** Check if drawing lockout is active (prevents saves/updates)
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if lockout is active

#### `clearDrawingLockout(state)`
**Line:** 468
**Purpose:** Clear drawing lockout immediately (call on mouseup/draw complete)
**Parameters:**
- `state` (Object) - Component state

#### `setDrawingContextFromNode(anno, state)`
**Line:** 484
**Purpose:** Set drawing context based on tree node click Automatically configures the appropriate context so newly drawn annotations are properly linked as children in the hierarchy
**Parameters:**
- `anno` (Object) - Annotation object from tree node
- `state` (Object) - Component state

### Internal Functions

#### `resetDrawing(state)`
**Line:** 131
**Purpose:** Reset drawing state

#### `createAnnotationFromDrawing(state, refs)`
**Line:** 142
**Purpose:** Create annotation from drawn rectangle

#### `generateAnnotationLabel(state)`
**Line:** 269
**Purpose:** Generate auto-incrementing label for annotation

#### `getDrawColor(state)`
**Line:** 297
**Purpose:** Get color for current draw mode

#### `findAnnotationByEntity(entityType, entityId, state)`
**Line:** 402
**Purpose:** Helper: Find annotation by entity (placeholder - actual function in annotation-manager) /


## Entity Lookup

**File:** `entity-lookup.js`

### Exported Functions

#### `getRoomNameById(roomId, state)`
**Line:** 15
**Purpose:** Get room name by entity ID
**Parameters:**
- `roomId` (Number) - Room entity ID
- `state` (Object) - Component state containing tree structure
**Returns:** Room name or empty string if not found

#### `getLocationNameById(locationId, state)`
**Line:** 27
**Purpose:** Get location name by entity ID
**Parameters:**
- `locationId` (Number) - Location entity ID
- `state` (Object) - Component state containing tree structure
**Returns:** Location name or empty string if not found

#### `getCabinetRunNameById(cabinetRunId, state)`
**Line:** 42
**Purpose:** Get cabinet run name by entity ID
**Parameters:**
- `cabinetRunId` (Number) - Cabinet run entity ID
- `state` (Object) - Component state containing tree structure
**Returns:** Cabinet run name or empty string if not found


## Entity Reference Manager

**File:** `entity-reference-manager.js`
**Description:** Manages entity relationships and references

### Exported Functions

#### `addEntityReference(annotationId, entityType, entityId, referenceType = 'primary', state)`
**Line:** 18
**Purpose:** Add entity reference to an annotation
**Parameters:**
- `annotationId` (number) - ID of the annotation
- `entityType` (string) - 'room', 'location', 'cabinet_run', 'cabinet'
- `entityId` (number) - ID of the entity
- `referenceType` (string) - 'primary', 'secondary', 'context'
- `state` (Object) - Component state

#### `removeEntityReference(annotationId, entityType, entityId, state)`
**Line:** 46
**Purpose:** Remove entity reference from an annotation
**Parameters:**
- `annotationId` (number) - ID of the annotation
- `entityType` (string) - 'room', 'location', 'cabinet_run', 'cabinet'
- `entityId` (number) - ID of the entity
- `state` (Object) - Component state

#### `getEntityReferences(annotationId, state)`
**Line:** 62
**Purpose:** Get all entity references for an annotation
**Parameters:**
- `annotationId` (number) - ID of the annotation
- `state` (Object) - Component state
**Returns:** Array of entity references

#### `getReferencesByType(annotationId, entityType, state)`
**Line:** 73
**Purpose:** Get references by entity type
**Parameters:**
- `annotationId` (number) - ID of the annotation
- `entityType` (string) - 'room', 'location', 'cabinet_run', 'cabinet'
- `state` (Object) - Component state
**Returns:** Array of entity references matching the type

#### `hasEntityReference(annotationId, entityType, entityId, state)`
**Line:** 86
**Purpose:** Check if annotation has reference to specific entity
**Parameters:**
- `annotationId` (number) - ID of the annotation
- `entityType` (string) - 'room', 'location', 'cabinet_run', 'cabinet'
- `entityId` (number) - ID of the entity
- `state` (Object) - Component state
**Returns:** {boolean}

#### `clearAnnotationReferences(annotationId, state)`
**Line:** 96
**Purpose:** Clear all references for an annotation
**Parameters:**
- `annotationId` (number) - ID of the annotation
- `state` (Object) - Component state


## Filter System

**File:** `filter-system.js`
**Description:** Filters annotations by type, view, room, location, etc.

### Exported Functions

#### `getFilteredAnnotations(state)`
**Line:** 13
**Purpose:** Get filtered annotations based on all active filters
**Parameters:**
- `state` (Object) - Component state
**Returns:** Filtered annotations

#### `countActiveFilters(state)`
**Line:** 89
**Purpose:** Count active filters
**Parameters:**
- `state` (Object) - Component state
**Returns:** Number of active filters

#### `getActiveFilterChips(state)`
**Line:** 112
**Purpose:** Get active filter chips for display
**Parameters:**
- `state` (Object) - Component state
**Returns:** Filter chips

#### `getAvailableFilterOptions(state)`
**Line:** 231
**Purpose:** Get available filter options based on current annotations
**Parameters:**
- `state` (Object) - Component state
**Returns:** Available options

#### `clearAllFilters(state)`
**Line:** 272
**Purpose:** Clear all filters
**Parameters:**
- `state` (Object) - Component state

#### `removeFilterChip(chip, state)`
**Line:** 295
**Purpose:** Remove individual filter chip
**Parameters:**
- `chip` (Object) - Filter chip to remove
- `state` (Object) - Component state

#### `applyFilterPreset(presetName, state)`
**Line:** 323
**Purpose:** Apply filter preset
**Parameters:**
- `presetName` (String) - Preset name (myWork, recent, unlinked, all)
- `state` (Object) - Component state

#### `isPresetActive(presetName, state)`
**Line:** 352
**Purpose:** Check if preset is currently active
**Parameters:**
- `presetName` (String) - Preset name
- `state` (Object) - Component state
**Returns:** True if preset is active

#### `getFilteredPageNumbers(state)`
**Line:** 374
**Purpose:** Get filtered page numbers (for navigation)
**Parameters:**
- `state` (Object) - Component state
**Returns:** Page numbers that match filters

### Internal Functions

#### `formatType(type)`
**Line:** 402
**Purpose:** Helper functions /

#### `formatViewType(viewType)`
**Line:** 412


## Hierarchy Detection Manager

**File:** `hierarchy-detection-manager.js`
**Description:** Detects and validates hierarchical relationships

### Exported Functions

#### `detectMissingHierarchy(drawMode, state)`
**Line:** 22
**Purpose:** Detect missing hierarchy levels based on current context and draw mode
**Parameters:**
- `drawMode` (String) - Type of annotation being drawn
- `state` (Object) - Component state with active context
**Returns:** Array of missing level objects: [{type: 'room_location', level: 1}, ...]

#### `getEntityDefaults(entityType, annotation, state)`
**Line:** 63
**Purpose:** Get smart defaults for creating a missing entity
**Parameters:**
- `entityType` (String) - Type of entity to create
- `annotation` (Object) - Annotation data
- `state` (Object) - Component state
**Returns:** Default values for entity creation

#### `getEntityDisplayName(entityType)`
**Line:** 116
**Purpose:** Get user-friendly display name for entity type
**Parameters:**
- `entityType` (String) - Entity type
**Returns:** Display name

#### `canSaveDirectly(drawMode, state)`
**Line:** 132
**Purpose:** Check if annotation can be saved without modal (complete hierarchy)
**Parameters:**
- `drawMode` (String) - Type of annotation being drawn
- `state` (Object) - Component state
**Returns:** True if can save directly


## Isolation Mode Manager

**File:** `isolation-mode-manager.js`
**Description:** Illustrator-style focus mode for hierarchical editing

### Exported Functions

#### `isAnnotationVisibleInIsolation(anno, state)`
**Line:** 219
**Purpose:** Check if annotation is visible in current isolation mode
**Parameters:**
- `anno` (Object) - Annotation to check
- `state` (Object) - Component state
**Returns:** True if visible

#### `updateIsolationMask(state)`
**Line:** 351
**Purpose:** Update SVG isolation mask
**Parameters:**
- `state` (Object) - Component state

#### `getIsolationBreadcrumbs(state)`
**Line:** 425
**Purpose:** Get breadcrumb path for isolation mode
**Parameters:**
- `state` (Object) - Component state
**Returns:** Breadcrumb items

### Internal Functions

#### `applyIsolationVisibilityFilter(state)`
**Line:** 200
**Purpose:** Apply isolation visibility filter to annotations

#### `isAnnotationVisibleInView(anno, state)`
**Line:** 285
**Purpose:** Check if annotation is visible in current view type

#### `isDescendantOf(anno, parentEntityId, state)`
**Line:** 314
**Purpose:** Check if annotation is descendant of entity

#### `createMaskRect(annotation)`
**Line:** 402
**Purpose:** Create SVG rect for mask cutout


## Navigation Manager

**File:** `navigation-manager.js`
**Description:** Handles PDF page navigation

### Exported Functions

#### `canGoNext(state, getFilteredPageNumbers)`
**Line:** 166
**Purpose:** Check if can navigate to next page
**Parameters:**
- `state` (Object) - Component state
- `getFilteredPageNumbers` (Function) - Function to get filtered pages
**Returns:** True if can go next

#### `canGoPrevious(state, getFilteredPageNumbers)`
**Line:** 178
**Purpose:** Check if can navigate to previous page
**Parameters:**
- `state` (Object) - Component state
- `getFilteredPageNumbers` (Function) - Function to get filtered pages
**Returns:** True if can go previous

### Internal Functions

#### `updatePdfPageId(pageNum, state)`
**Line:** 141
**Purpose:** Update pdfPageId based on current page

#### `getAllPages(state)`
**Line:** 156
**Purpose:** Get all page numbers


## PDF Manager

**File:** `pdf-manager.js`
**Description:** Manages PDF rendering and page observation

### Exported Functions

#### `setupPageObserver(state, refs, loadAnnotationsCallback)`
**Line:** 182
**Purpose:** Setup page observer for lazy loading annotations
**Parameters:**
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs
- `loadAnnotationsCallback` (Function) - Callback to load annotations

### Internal Functions

#### `calculateCanvasScale(refs, state)`
**Line:** 274
**Purpose:** Calculate canvas scale (internal helper)


## Resize Move System

**File:** `resize-move-system.js`
**Description:** Handles annotation resizing and moving operations

### Exported Functions

#### `startResize(event, annotation, handle, state)`
**Line:** 16
**Purpose:** Start resizing annotation
**Parameters:**
- `event` (MouseEvent) - Mouse down event
- `annotation` (Object) - Annotation being resized
- `handle` (String) - Resize handle direction (n, ne, e, se, s, sw, w, nw)
- `state` (Object) - Component state

#### `handleResizeEnd(event, state, refs)`
**Line:** 130
**Purpose:** Finish resizing and save changes
**Parameters:**
- `event` (MouseEvent) - Mouse up event
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `startMove(event, annotation, state)`
**Line:** 232
**Purpose:** Start moving annotation
**Parameters:**
- `event` (MouseEvent) - Mouse down event
- `annotation` (Object) - Annotation being moved
- `state` (Object) - Component state

#### `handleMoveEnd(event, state, refs)`
**Line:** 313
**Purpose:** Finish moving and save changes
**Parameters:**
- `event` (MouseEvent) - Mouse up event
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

#### `getResizeCursor(handle)`
**Line:** 505
**Purpose:** Get cursor style for resize handle
**Parameters:**
- `handle` (String) - Handle direction (n, ne, e, se, s, sw, w, nw)
**Returns:** CSS cursor value

#### `getResizeHandles()`
**Line:** 523
**Purpose:** Get all resize handle directions
**Returns:** Array of handle directions

#### `getHandlePosition(handle, size = 8)`
**Line:** 533
**Purpose:** Get position style for resize handle
**Parameters:**
- `handle` (String) - Handle direction
- `size` (Number) - Handle size in pixels
**Returns:** Position styles

#### `activateResizeLockout(state)`
**Line:** 560
**Purpose:** Activate resize/move lockout to prevent interruptions during adjustments Lockout stays active until save completes - no auto-expire timer
**Parameters:**
- `state` (Object) - Component state

#### `isResizeLocked(state)`
**Line:** 578
**Purpose:** Check if resize/move lockout is active (prevents saves/updates)
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if lockout is active

#### `clearResizeLockout(state)`
**Line:** 586
**Purpose:** Clear resize/move lockout after save completes
**Parameters:**
- `state` (Object) - Component state

#### `handleResize(event, state)`
**Line:** 598
**Purpose:** Handle real-time resize updates during drag
**Parameters:**
- `event` (MouseEvent) - Mouse move event
- `state` (Object) - Component state

#### `handleMove(event, state)`
**Line:** 647
**Purpose:** Handle real-time move updates during drag
**Parameters:**
- `event` (MouseEvent) - Mouse move event
- `state` (Object) - Component state

#### `finishResizeOrMove(event, state, refs)`
**Line:** 668
**Purpose:** Finish resize or move operation and save changes
**Parameters:**
- `event` (MouseEvent) - Mouse up event
- `state` (Object) - Component state
- `refs` (Object) - Alpine.js $refs

### Internal Functions

#### `updateResize(event, state)`
**Line:** 63
**Purpose:** Update resize dimensions

#### `finishResize(annotation, state, refs)`
**Line:** 202
**Purpose:** Finish resize and update PDF coordinates

#### `updateMove(event, state)`
**Line:** 279
**Purpose:** Update annotation position

#### `finishMove(annotation, state, refs)`
**Line:** 381
**Purpose:** Finish move and update PDF coordinates

#### `debouncedSaveAnnotation(annotation, state)`
**Line:** 402
**Purpose:** Debounced save to prevent excessive API calls


## State Manager

**File:** `state-manager.js`
**Description:** Initializes and manages component state

### Exported Functions

#### `createInitialState(config)`
**Line:** 11
**Purpose:** Create initial state object for Alpine.js component
**Parameters:**
- `config` (Object) - Configuration from Blade component
**Returns:** Initial state object

#### `getColorForType(type)`
**Line:** 176
**Purpose:** Get color for annotation type
**Parameters:**
- `type` (String) - Annotation type
**Returns:** Color hex code

#### `getViewTypeLabel(viewType, orientation = null)`
**Line:** 192
**Purpose:** Get human-readable view type label
**Parameters:**
- `viewType` (String) - View type
- `orientation` (String) - Optional orientation
**Returns:** Formatted label

#### `getViewTypeColor(viewType)`
**Line:** 217
**Purpose:** Get color for view type badge
**Parameters:**
- `viewType` (String) - View type
**Returns:** CSS color variable

#### `selectAnnotationContext(anno, state, callbacks)`
**Line:** 234
**Purpose:** Select annotation context for hierarchical tool enabling Sets active room/location context based on clicked annotation
**Parameters:**
- `anno` (Object) - Annotation object
- `state` (Object) - Component state
- `callbacks` (Object) - Callback functions { getRoomNameById, getLocationNameById }


## Tree Manager

**File:** `tree-manager.js`
**Description:** Builds and manages hierarchical tree structure

### Exported Functions

#### `toggleNode(nodeId, state)`
**Line:** 98
**Purpose:** Toggle node expansion
**Parameters:**
- `nodeId` (String|Number) - Node ID to toggle
- `state` (Object) - Component state

#### `isExpanded(nodeId, state)`
**Line:** 113
**Purpose:** Check if node is expanded
**Parameters:**
- `nodeId` (String|Number) - Node ID to check
- `state` (Object) - Component state
**Returns:** True if expanded

#### `showContextMenu(event, params, state)`
**Line:** 287
**Purpose:** Show context menu for tree node
**Parameters:**
- `event` (MouseEvent) - Right-click event
- `params` (Object) - Node parameters
- `state` (Object) - Component state

#### `buildAnnotationTree(annotations)`
**Line:** 418
**Purpose:** Build hierarchical tree from flat annotations
**Parameters:**
- `annotations` (Array<Object>) - Annotations array
**Returns:** Tree structure

#### `getPageGroupedAnnotations(state)`
**Line:** 445
**Purpose:** Group annotations by page number
**Parameters:**
- `state` (Object) - Component state
**Returns:** Page-grouped annotations

### Internal Functions

#### `ensureChildrenProperty(nodes)`
**Line:** 75
**Purpose:** Ensure all tree nodes have a children property


## Ui Helpers

**File:** `ui-helpers.js`

### Exported Functions

#### `getLabelPositionClasses(anno, refs, state)`
**Line:** 15
**Purpose:** Get intelligent label position classes based on available space
**Parameters:**
- `anno` (Object) - Annotation object with screen coordinates
- `refs` (Object) - Alpine.js $refs object
- `state` (Object) - Component state
**Returns:** Tailwind CSS positioning classes

#### `getButtonPositionClasses(anno, refs, state)`
**Line:** 41
**Purpose:** Get intelligent button position classes based on available space
**Parameters:**
- `anno` (Object) - Annotation object with screen coordinates
- `refs` (Object) - Alpine.js $refs object
- `state` (Object) - Component state
**Returns:** Tailwind CSS positioning classes


## Undo Redo Manager

**File:** `undo-redo-manager.js`
**Description:** Manages undo/redo history stack

### Exported Functions

#### `pushToHistory(state, action)`
**Line:** 13
**Purpose:** Push current state to history stack
**Parameters:**
- `state` (Object) - Component state
- `action` (String) - Action description

#### `undo(state)`
**Line:** 46
**Purpose:** Undo last action
**Parameters:**
- `state` (Object) - Component state

#### `redo(state)`
**Line:** 67
**Purpose:** Redo last undone action
**Parameters:**
- `state` (Object) - Component state

#### `canUndo(state)`
**Line:** 89
**Purpose:** Check if undo is available
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if can undo

#### `canRedo(state)`
**Line:** 98
**Purpose:** Check if redo is available
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if can redo

#### `clearHistory(state)`
**Line:** 106
**Purpose:** Clear history stack
**Parameters:**
- `state` (Object) - Component state

#### `getHistoryInfo(state)`
**Line:** 117
**Purpose:** Get history info for debugging
**Parameters:**
- `state` (Object) - Component state
**Returns:** History information

#### `setupUndoRedoKeyboards(state)`
**Line:** 133
**Purpose:** Setup keyboard shortcuts for undo/redo
**Parameters:**
- `state` (Object) - Component state


## View Type Manager

**File:** `view-type-manager.js`
**Description:** Manages view types (plan, elevation, section, detail)

### Exported Functions

#### `setViewType(viewType, orientation = null, state, callbacks)`
**Line:** 17
**Purpose:** Set view type and optional orientation
**Parameters:**
- `viewType` (string) - 'plan', 'elevation', 'section', or 'detail'
- `orientation` (string|null) - 'front', 'back', 'left', 'right', 'A-A', etc.
- `state` (Object) - Component state
- `callbacks` (Object) - Callback functions

#### `setOrientation(orientation, state, callbacks)`
**Line:** 43
**Purpose:** Set orientation for elevation or section views
**Parameters:**
- `orientation` (string) - 'front', 'back', 'left', 'right', 'A-A', etc.
- `state` (Object) - Component state
- `callbacks` (Object) - Callback functions

#### `isAnnotationVisibleInView(anno, state)`
**Line:** 58
**Purpose:** Check if annotation should be visible in current view
**Parameters:**
- `anno` (Object) - Annotation object
- `state` (Object) - Component state
**Returns:** {boolean}

#### `updateAnnotationVisibility(state)`
**Line:** 84
**Purpose:** Update visibility of all annotations based on current view
**Parameters:**
- `state` (Object) - Component state

#### `getCurrentViewLabel(state)`
**Line:** 95
**Purpose:** Get human-readable label for current view
**Parameters:**
- `state` (Object) - Component state
**Returns:** {string}

#### `getCurrentViewColor(state)`
**Line:** 119
**Purpose:** Get color CSS variable for current view type
**Parameters:**
- `state` (Object) - Component state
**Returns:** CSS color variable


## Visibility Toggle Manager

**File:** `visibility-toggle-manager.js`
**Description:** Controls visibility of rooms, locations, and annotations

### Exported Functions

#### `toggleRoomVisibility(roomId, state)`
**Line:** 11
**Purpose:** Toggle visibility for all annotations in a room
**Parameters:**
- `roomId` (Number) - Room ID to toggle
- `state` (Object) - Component state

#### `isRoomVisible(roomId, state)`
**Line:** 40
**Purpose:** Check if room has any visible annotations
**Parameters:**
- `roomId` (Number) - Room ID to check
- `state` (Object) - Component state
**Returns:** True if any annotations are visible

#### `toggleLocationVisibility(locationId, state)`
**Line:** 51
**Purpose:** Toggle visibility for all annotations in a location
**Parameters:**
- `locationId` (Number) - Location ID to toggle
- `state` (Object) - Component state

#### `isLocationVisible(locationId, state)`
**Line:** 83
**Purpose:** Check if location has any visible annotations
**Parameters:**
- `locationId` (Number) - Location ID to check
- `state` (Object) - Component state
**Returns:** True if any annotations are visible

#### `toggleCabinetRunVisibility(runId, state)`
**Line:** 97
**Purpose:** Toggle visibility for all annotations in a cabinet run
**Parameters:**
- `runId` (Number) - Cabinet run ID to toggle
- `state` (Object) - Component state

#### `isCabinetRunVisible(runId, state)`
**Line:** 129
**Purpose:** Check if cabinet run has any visible annotations
**Parameters:**
- `runId` (Number) - Cabinet run ID to check
- `state` (Object) - Component state
**Returns:** True if any annotations are visible

#### `toggleAnnotationVisibility(annotationId, state)`
**Line:** 143
**Purpose:** Toggle visibility for an individual annotation (e.g., a single cabinet)
**Parameters:**
- `annotationId` (Number) - Annotation ID to toggle
- `state` (Object) - Component state

#### `isAnnotationVisible(annotationId, state)`
**Line:** 163
**Purpose:** Check if an individual annotation is visible
**Parameters:**
- `annotationId` (Number) - Annotation ID to check
- `state` (Object) - Component state
**Returns:** True if annotation is visible

#### `hasAnnotationsOnCurrentPage(entityId, entityType, state)`
**Line:** 174
**Purpose:** Check if entity has annotations on current page
**Parameters:**
- `entityId` (Number) - Entity ID (room, location, or cabinet run)
- `entityType` (String) - Type: 'room', 'location', or 'cabinet_run'
- `state` (Object) - Component state
**Returns:** True if entity has annotations on current page


## Zoom Manager

**File:** `zoom-manager.js`
**Description:** Handles zoom operations and calculations

### Exported Functions

#### `getZoomPercentage(state)`
**Line:** 145
**Purpose:** Get zoom percentage for display
**Parameters:**
- `state` (Object) - Component state
**Returns:** Zoom percentage (e.g., 100 for 100%)

#### `isAtMinZoom(state)`
**Line:** 324
**Purpose:** Check if at minimum zoom
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if at minimum zoom

#### `isAtMaxZoom(state)`
**Line:** 333
**Purpose:** Check if at maximum zoom
**Parameters:**
- `state` (Object) - Component state
**Returns:** True if at maximum zoom

### Internal Functions

#### `findScrollContainer(element, axis)`
**Line:** 298
**Purpose:** Find scroll container for specific axis


---

## 🔧 Common Patterns

### Name Lookup Helpers

Multiple managers implement these lookup helpers:

```javascript
// Pattern: Get entity name by ID
getRoomNameById(roomId, state)
getLocationNameById(locationId, state)
getCabinetRunNameById(cabinetRunId, state)
```

**Found in:** annotation-manager, drawing-system, isolation-mode-manager, tree-manager, filter-system

---

**Document Generated By:** Helper Index Generator Script
**Script:** `generate-helpers-index.js`
**Architecture:** Manager Pattern with Functional Exports
**Framework:** Alpine.js v3 + Vite
