# Manual E2E Test Guide - Annotation & Zoom

## What Was Fixed:
1. ✅ Removed CSS transform scale conflict
2. ✅ Fixed annotation overlay to always cover canvas (w-full h-full)  
3. ✅ Added delay after zoom for canvas size update
4. ✅ Made canvas responsive at 100% zoom, overflow at higher zoom
5. ✅ Added modals component for createOptionForm

## Test Steps:

### 1. Login & Navigate
- Login: `info@tcswoodwork.com` / `Lola2024!`
- Go to Projects → Click any project
- Click "Annotate PDF" tab

### 2. Test Initial State
- ✅ PDF should fill the window width at 100%
- ✅ Zoom display should show "100%"

### 3. Test Zoom Functionality
- Click "+" button → Should zoom to 125%
- Click "+" again → Should zoom to 150%
- ✅ PDF should get larger and allow scrolling
- Click "-" button → Should zoom back to 125%
- Click "Reset" → Should return to 100%

### 4. Test Drawing Annotations
- Click "Draw Room Boundary" button (house icon)
- ✅ Button should be enabled (no room pre-selection needed)
- Draw rectangle on PDF
- ✅ Slideover should open immediately

### 5. Test Room Creation
- In slideover, click "Room" dropdown
- ✅ Click "+" to create new room
- ✅ Modal should appear with "Name" and "Room Type" fields
- Enter "Test Kitchen" and select room type
- Click "Create"
- ✅ Room should be added to dropdown and selected

### 6. Test Annotation Save
- Fill "Label" field
- Click "Save Changes"
- ✅ Slideover should close
- ✅ Annotation rectangle should appear on PDF
- ✅ Tree should refresh showing new room/annotation

### 7. Test Annotation + Zoom Alignment
- With annotation visible, click "+" to zoom in
- ✅ Annotation should stay aligned with PDF
- ✅ Annotation should scale proportionally
- Zoom in more
- ✅ Annotation still aligned
- Click "Reset"
- ✅ Annotation returns to original position

### 8. Test Tree Updates
- Every annotation save/delete should refresh the tree
- Check browser console for "🌳 Tree refreshed" messages

## Expected Results:
✅ All zoom levels work smoothly
✅ Annotations draw at any zoom level
✅ Annotations stay perfectly aligned during zoom
✅ Room creation modal works
✅ Tree updates after every change
✅ PDF fits window at 100%, overflows at higher zoom

## If Issues Found:
1. Check browser console for errors
2. Check Network tab for failed API calls
3. Take screenshots and report specific step that failed
