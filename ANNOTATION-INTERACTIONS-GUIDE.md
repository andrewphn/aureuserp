# Annotation Interactions Module Guide

## Overview

The `annotation-interactions.js` module provides clean, reusable functionality for moving and resizing PDF annotations. This separates interaction logic from the main Alpine component, making it easier to maintain and test.

## File Location

```
plugins/webkul/projects/resources/js/annotations/annotation-interactions.js
```

## Features

### 1. **Drag-to-Move**
- Click and drag annotations to reposition them
- Automatically constrains movement to container bounds
- Real-time visual feedback
- Recalculates PDF coordinates after move

### 2. **Resize Handles**
- Four corner handles (NW, NE, SW, SE)
- Enforces minimum size constraints
- Maintains aspect ratio options (future)
- Smooth resize with visual feedback

### 3. **Coordinate Management**
- Automatically converts between screen and PDF coordinates
- Updates normalized coordinates for database storage
- Handles zoom level transformations

## Usage

### Option 1: Import Helpers into Alpine Component

```javascript
import { createInteractionHelpers } from './annotation-interactions.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('annotationSystemV3', (config) => ({
        // Spread interaction helpers into component
        ...createInteractionHelpers(),

        // Your existing component data
        pdfUrl: config.pdfUrl,
        annotations: [],
        // ... etc

        async init() {
            // Initialize interactions system
            this.initInteractions();

            // ... rest of init
        },

        destroy() {
            // Cleanup interactions
            this.destroyInteractions();
        }
    }));
});
```

### Option 2: Manual Integration

```javascript
import { AnnotationInteractions } from './annotation-interactions.js';

// In your Alpine component
let interactions = null;

Alpine.data('annotationSystemV3', (config) => ({
    init() {
        interactions = new AnnotationInteractions({
            onUpdate: (annotation, change) => {
                // Called during drag/resize for real-time updates
                this.updateAnnotationCoordinates(annotation);
            },
            onMoveEnd: (annotation, movement) => {
                // Called when move completes
                console.log('Moved annotation:', annotation.label);
                this.saveAnnotation(annotation);
            },
            onResizeEnd: (annotation, resize) => {
                // Called when resize completes
                console.log('Resized annotation:', annotation.label);
                this.saveAnnotation(annotation);
            }
        });
    },

    // Start move on annotation mousedown
    startMove(event, annotation) {
        const container = this.$refs.annotationOverlay;
        interactions.initMove(event, annotation, container);
    },

    // Start resize on handle mousedown
    startResize(event, annotation, handle) {
        const container = this.$refs.annotationOverlay;
        interactions.initResize(event, annotation, handle, container);
    }
}));
```

### Blade Template Integration

```blade
<!-- Annotation element -->
<div
    x-data="{ showHandles: false }"
    @mousedown="startMove($event, anno)"
    @mouseenter="showHandles = true"
    @mouseleave="showHandles = false"
    :style="`
        position: absolute;
        left: ${anno.screenX}px;
        top: ${anno.screenY}px;
        width: ${anno.screenWidth}px;
        height: ${anno.screenHeight}px;
        border: 2px solid ${anno.color};
        cursor: move;
    `"
    class="annotation-box"
>
    <!-- Annotation label -->
    <div class="annotation-label" x-text="anno.label"></div>

    <!-- Resize handles (shown on hover) -->
    <template x-if="showHandles">
        <div>
            <!-- Northwest handle -->
            <div
                class="resize-handle"
                @mousedown.stop="startResize($event, anno, 'nw')"
                style="position: absolute; top: -4px; left: -4px; width: 8px; height: 8px; background: white; border: 2px solid blue; border-radius: 50%; cursor: nw-resize;"
            ></div>

            <!-- Northeast handle -->
            <div
                class="resize-handle"
                @mousedown.stop="startResize($event, anno, 'ne')"
                style="position: absolute; top: -4px; right: -4px; width: 8px; height: 8px; background: white; border: 2px solid blue; border-radius: 50%; cursor: ne-resize;"
            ></div>

            <!-- Southwest handle -->
            <div
                class="resize-handle"
                @mousedown.stop="startResize($event, anno, 'sw')"
                style="position: absolute; bottom: -4px; left: -4px; width: 8px; height: 8px; background: white; border: 2px solid blue; border-radius: 50%; cursor: sw-resize;"
            ></div>

            <!-- Southeast handle -->
            <div
                class="resize-handle"
                @mousedown.stop="startResize($event, anno, 'se')"
                style="position: absolute; bottom: -4px; right: -4px; width: 8px; height: 8px; background: white; border: 2px solid blue; border-radius: 50%; cursor: se-resize;"
            ></div>
        </div>
    </template>
</div>
```

## API Reference

### AnnotationInteractions Class

#### Constructor Options

```javascript
new AnnotationInteractions({
    onUpdate: (annotation, change) => {},     // Called during drag/resize
    onMoveStart: (annotation) => {},          // Called when move starts
    onMoveEnd: (annotation, movement) => {},  // Called when move ends
    onResizeStart: (annotation, handle) => {}, // Called when resize starts
    onResizeEnd: (annotation, resize) => {},  // Called when resize ends
});
```

#### Methods

**initMove(event, annotation, containerEl)**
- Starts drag-to-move interaction
- `event`: mousedown event
- `annotation`: annotation data object
- `containerEl`: container element for bounds calculation

**initResize(event, annotation, handle, containerEl)**
- Starts resize interaction
- `event`: mousedown event on resize handle
- `annotation`: annotation data object
- `handle`: 'nw', 'ne', 'sw', or 'se'
- `containerEl`: container element for bounds calculation

**destroy()**
- Cleanup method - removes all event listeners
- Call when component unmounts

### Helper Functions

**createInteractionHelpers()**
- Returns object with interaction methods ready to spread into Alpine component
- Includes: `initInteractions()`, `startMove()`, `startResize()`, `recalculatePdfCoordinates()`, `destroyInteractions()`

**generateResizeHandles(annotation)**
- Generates HTML string for resize handles
- Returns 4 corner handles positioned absolutely
- Useful for server-side rendering or template generation

## Integration with V3 Overlay System

The module is designed to work seamlessly with the existing V3 annotation system:

1. **Screen coordinates**: Handles drag/resize in screen space
2. **PDF coordinates**: Automatically recalculates PDF coordinates after changes
3. **Normalized coordinates**: Updates normalized (0-1) coordinates for database storage
4. **Zoom aware**: Works correctly at any zoom level
5. **Canvas rendering**: Compatible with canvas-based PDF rendering

## Benefits of Separation

### ✅ **Maintainability**
- Interaction logic isolated from component logic
- Easy to update drag/resize behavior without touching component

### ✅ **Testability**
- Can unit test interaction logic independently
- Mock callbacks to verify behavior

### ✅ **Reusability**
- Use in multiple viewers (V2, V3, future versions)
- Can be used in other projects

### ✅ **Performance**
- Efficient event handling with proper cleanup
- Optimized coordinate calculations

### ✅ **Code Organization**
- Clear separation of concerns
- Smaller, focused files
- Better team collaboration

## Next Steps

To integrate this into the current V3 system:

1. ✅ Module created at `plugins/webkul/projects/resources/js/annotations/annotation-interactions.js`
2. ⏳ Import into V3 overlay blade file's `<script>` tag
3. ⏳ Add resize handles to annotation template
4. ⏳ Wire up `startMove()` and `startResize()` handlers
5. ⏳ Test drag and resize functionality
6. ⏳ Add visual feedback (cursor changes, handle highlights)
7. ⏳ Add save-on-change functionality

## Example: Complete Integration

See `plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer-v3-overlay.blade.php` for the full implementation example with:
- Resize handles shown on hover
- Smooth drag and resize
- Automatic coordinate updates
- Save to database after changes
