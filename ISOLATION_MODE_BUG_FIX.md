# Isolation Mode Bug Fix - Room Boundary Visibility

## Summary

Fixed critical bug where double-clicking a room boundary annotation caused the entire PDF to go dark (grey overlay covering everything) instead of showing the isolated room with proper mask cutouts.

## The Problem

**Symptoms:**
- When double-clicking a room boundary annotation to enter isolation mode:
  - The page would zoom to 193% correctly ✅
  - Isolation mode would activate ✅
  - But the dark overlay would cover EVERYTHING ❌
  - No mask cutouts were created ❌
  - The room boundary itself was being hidden ❌

**User Report:**
> "i think you need to niveistighate that somenintg is making it not work, its going grey there might ber a seocnd ting on move that isnt moving something else to make the gap and ventered. it also gets out of popsiton"

## Root Cause

The bug was in the `enterIsolationMode()` function at **line 2506** in `pdf-annotation-viewer.blade.php`:

```javascript
// BEFORE (Buggy):
const roomId = anno.roomId;  // ❌ For room-type annotations, anno.roomId is undefined/null!
```

When double-clicking a **room boundary annotation** (type: "room"):
- The annotation's own ID is `37` (example)
- The annotation IS the room itself, so `anno.roomId` is `null` or references a parent context
- Setting `isolatedRoomId = null` caused the visibility check to fail
- In `isAnnotationVisibleInIsolation()` at line 2389:
  ```javascript
  if (anno.id === this.isolatedRoomId) return true;
  // Becomes: 37 === null → false ❌
  ```
- The room boundary was marked as hidden
- With 0 visible annotations, 0 mask cutouts were created
- Result: Complete grey overlay with no cutouts

## The Fix

Changed line 2511 to use the annotation's own ID when it's a room type:

```javascript
// AFTER (Fixed):
const roomId = anno.type === 'room' ? anno.id : anno.roomId;  // ✅
```

Now when double-clicking a room boundary:
- `isolatedRoomId = 37` (the annotation's ID)
- Visibility check: `37 === 37 → true` ✅
- Room boundary is VISIBLE
- Mask cutout is created for the room
- Dark overlay has a cutout showing the isolated room clearly

## Files Modified

1. **pdf-annotation-viewer.blade.php** (line 2511):
   - Changed `isolatedRoomId` assignment logic
   - Added conditional to use `anno.id` for room-type annotations

## Test Results

### Before Fix:
```
Isolation Mode: true
Hidden Annotations: 1 [37]
Visible Annotations: 0
Mask Cutouts: 0
Status: ❌ FAILURE - Everything goes grey
```

### After Fix:
```
Isolation Mode: true
Hidden Annotations: 0 []
Visible Annotations: 1
Mask Cutouts: 1
Status: ✅ SUCCESS - Room visible with dark overlay cutout
```

## Impact

- **User Experience:** Room isolation mode now works as intended with proper visual feedback
- **Scroll Sync:** The previous scroll sync fix (lines 1182-1187) now has visible annotations to synchronize
- **Hierarchy Support:** Fix applies to all room-type annotations across the application

## Testing

Comprehensive Playwright test created in `test-isolation-debug.mjs` to validate:
- Component identification
- Isolation mode state tracking
- Annotation visibility logic
- Mask cutout creation
- State persistence over time (T=0ms, 100ms, 500ms, 1000ms, 2000ms)

Test confirms:
- ✅ Isolation mode activates correctly
- ✅ Zoom changes to 193%
- ✅ Room boundary remains visible
- ✅ Mask cutout is created
- ✅ State persists correctly

## Related Fixes

This fix works in conjunction with the scroll synchronization fix (lines 1182-1187) which clears the rect cache during scroll to keep mask cutouts aligned with annotations.

## Date

Fixed: October 23, 2025
