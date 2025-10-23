# Isolation Mode Hierarchy Support

## Overview

Isolation mode supports the complete 4-level hierarchy of annotations:
1. **Room** (top level)
2. **Location** (child of room)
3. **Cabinet Run** (child of location)
4. **Cabinet** (child of cabinet run)

## How It Works

When you double-click any annotation in the hierarchy, isolation mode activates and shows **that level plus all children and parents**.

### Room Level Isolation

**When you double-click a Room:**

✅ **Visible:**
- The room boundary itself
- All locations within this room
- All cabinet runs within those locations
- All cabinets within those runs

❌ **Hidden:**
- Other rooms
- Locations in other rooms
- Cabinet runs in other rooms
- Cabinets in other rooms

**Example:**
```
Double-click "Kitchen" room
→ Shows: Kitchen boundary + Island location + Base Run + Base Cabinet 1, 2, 3
→ Hides: Bathroom, Bedroom, etc.
```

### Location Level Isolation

**When you double-click a Location:**

✅ **Visible:**
- The parent room (context)
- The location itself
- All cabinet runs within this location
- All cabinets within those runs

❌ **Hidden:**
- Other locations (even in same room)
- Cabinet runs in other locations
- Cabinets in other locations

**Example:**
```
Double-click "Island" location
→ Shows: Kitchen room + Island + Base Run + Base Cabinet 1, 2, 3
→ Hides: Pantry location, Sink location, etc.
```

### Cabinet Run Level Isolation

**When you double-click a Cabinet Run:**

✅ **Visible:**
- The parent room (context)
- The parent location (context)
- The cabinet run itself
- All cabinets within this run

❌ **Hidden:**
- Other cabinet runs (even in same location)
- Cabinets in other runs

**Example:**
```
Double-click "Base Run" cabinet run
→ Shows: Kitchen room + Island location + Base Run + Base Cabinet 1, 2, 3
→ Hides: Upper Run, Tall Run, etc.
```

### Cabinet Level Isolation

Currently **not implemented** - individual cabinets are the leaf nodes, so you would just select/edit them directly rather than isolating.

## Technical Implementation

The hierarchy is managed in `isAnnotationVisibleInIsolation()` at **pdf-annotation-viewer.blade.php:2387-2446**:

### Room Level Code
```javascript
if (this.isolationLevel === 'room') {
    // Show the isolated room itself
    if (anno.id === this.isolatedRoomId) return true;

    // Show direct children (locations in this room)
    if (anno.type === 'location' && anno.roomId === this.isolatedRoomId) return true;

    // Show cabinet runs that belong to locations in this room
    if (anno.type === 'cabinet_run') {
        const parentLocation = this.annotations.find(a => a.id === anno.locationId);
        if (parentLocation && parentLocation.roomId === this.isolatedRoomId) return true;
    }

    // Show cabinets that belong to runs in this room
    if (anno.type === 'cabinet') {
        const parentRun = this.annotations.find(a => a.id === anno.cabinetRunId);
        if (parentRun) {
            const parentLocation = this.annotations.find(a => a.id === parentRun.locationId);
            if (parentLocation && parentLocation.roomId === this.isolatedRoomId) return true;
        }
    }

    return false;
}
```

### Location Level Code
```javascript
else if (this.isolationLevel === 'location') {
    // Show parent room
    if (anno.id === this.isolatedRoomId) return true;

    // Show the isolated location itself
    if (anno.id === this.isolatedLocationId) return true;

    // Show direct children (cabinet runs in this location)
    if (anno.type === 'cabinet_run' && anno.locationId === this.isolatedLocationId) return true;

    // Show cabinets that belong to runs in this location
    if (anno.type === 'cabinet') {
        const parentRun = this.annotations.find(a => a.id === anno.cabinetRunId);
        if (parentRun && parentRun.locationId === this.isolatedLocationId) return true;
    }

    return false;
}
```

### Cabinet Run Level Code
```javascript
else if (this.isolationLevel === 'cabinet_run') {
    // Show parent location
    if (anno.id === this.isolatedLocationId) return true;

    // Show parent room
    if (anno.id === this.isolatedRoomId) return true;

    // Show the isolated cabinet run itself
    if (anno.id === this.isolatedCabinetRunId) return true;

    // Show direct children (cabinets in this run)
    if (anno.type === 'cabinet' && anno.cabinetRunId === this.isolatedCabinetRunId) return true;

    return false;
}
```

## User Experience

The isolation mode provides an Illustrator-style layer isolation experience:

1. **Visual Feedback:**
   - Dark overlay (SVG mask) covers the entire PDF
   - Mask cutouts create "windows" showing only the isolated hierarchy
   - Smooth zoom to fit the isolated content

2. **Navigation:**
   - Double-click any level to isolate it
   - Exit isolation mode by clicking "Exit Isolation" button or clicking outside

3. **Context Preservation:**
   - Parent levels remain visible for context
   - Child levels show the full detail
   - Easy to "drill down" through the hierarchy

## Benefits

- **Focus:** Hide unrelated annotations to reduce visual clutter
- **Context:** Keep parent levels visible for spatial reference
- **Flexibility:** Jump to any level in the hierarchy
- **Intuitive:** Works like familiar design tools (Illustrator, Figma)

## Testing

Comprehensive hierarchy testing is available in `test-isolation-debug.mjs` which validates:
- Room level isolation
- Location level isolation
- Cabinet run level isolation
- Mask cutout creation for each level
- Visibility logic for parent/child relationships

## Related Documentation

- `ISOLATION_MODE_BUG_FIX.md` - Details of the room boundary visibility fix
- `pdf-annotation-viewer.blade.php:2387-2446` - Visibility logic implementation
- `pdf-annotation-viewer.blade.php:2449-2564` - Isolation mode entry logic
