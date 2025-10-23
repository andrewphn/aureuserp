# Z-Index Hierarchy Documentation

## Overview

This document defines the z-index stacking order for all UI layers in the PDF annotation system to prevent overlapping issues.

## The Problem

Previously, the context bar (z-40) appeared above the annotation editor slideover (z-35), causing buttons and controls to overlap the annotation editor panel.

## The Fix

Updated annotation editor z-index values to ensure proper layering:

**File**: `plugins/webkul/projects/resources/views/livewire/annotation-editor.blade.php`
- Line 6: Backdrop changed from `z-30` to `z-50`
- Line 13: Slideover panel changed from `z-index: 35` to `z-index: 55`

## Complete Z-Index Hierarchy

Listed from bottom to top (lowest to highest):

### Layer 1-10: Base PDF and Annotations
- **z-1**: Hidden PDF embed layer
  - Location: `pdf-annotation-viewer.blade.php:673`

- **z-10**: Annotation overlays, resize handles, labels
  - Location: `pdf-annotation-viewer.blade.php:722, 770, 791, 812, 833`
  - Purpose: Annotation boxes, room boundaries, locations, cabinet runs, cabinets

### Layer 15: Isolation Mode UI
- **z-15**: Isolation breadcrumb navigation
  - Location: `pdf-annotation-viewer.blade.php:383`
  - Purpose: Shows current isolation context (Room → Location → Cabinet Run)

### Layer 40: Top Navigation
- **z-40**: Context bar (sticky top bar)
  - Location: `pdf-annotation-viewer.blade.php:32`
  - Purpose: Zoom controls, pagination, drawing tools, view type selectors, save/clear buttons

### Layer 50-55: Annotation Editor ✅ FIXED
- **z-50**: Annotation editor backdrop
  - Location: `annotation-editor.blade.php:6`
  - Purpose: Dark overlay when editing annotations

- **z-55**: Annotation editor slideover panel
  - Location: `annotation-editor.blade.php:13`
  - Purpose: Slide-in form for editing annotation properties

### Layer 60-70: Modal Dialogs
- **z-60**: PDF thumbnail preview modal
  - Location: `pdf-page-thumbnail-serverside.blade.php:93`
  - Purpose: Full-screen PDF page preview

- **z-70**: Filament action modals (create option forms)
  - Location: `annotation-editor.blade.php:95`
  - Purpose: Modals for creating new room/location/cabinet options

### Layer 9999: Always-On-Top Elements
- **z-9999**: Context menus and PDF viewer modals
  - Location: `pdf-annotation-viewer.blade.php:621`
  - Location: `pdf-page-thumbnail-pdfjs.blade.php:127, 1329`
  - Purpose: Right-click context menus, full-screen image viewers

## Visual Hierarchy

```
┌─────────────────────────────────────┐
│  Context Menus (z-9999)             │  ← Always on top
├─────────────────────────────────────┤
│  Filament Modals (z-70)             │  ← Create option forms
├─────────────────────────────────────┤
│  PDF Thumbnails (z-60)              │  ← Preview modals
├─────────────────────────────────────┤
│  Annotation Editor (z-55)           │  ✅ Above context bar
│  Editor Backdrop (z-50)             │
├─────────────────────────────────────┤
│  Context Bar (z-40)                 │  ← Top sticky bar
├─────────────────────────────────────┤
│  Isolation Breadcrumb (z-15)        │  ← Room > Location
├─────────────────────────────────────┤
│  Annotations (z-10)                 │  ← Boxes, boundaries
│  PDF Overlay (z-1)                  │
└─────────────────────────────────────┘
```

## Testing

To verify the fix works correctly:

1. Navigate to a PDF annotation page
2. Click an annotation's Edit button
3. Verify the annotation editor slideover appears **above** the top context bar
4. Verify context bar buttons are NOT clickable through the editor backdrop
5. Verify Filament modals (create room option) appear **above** the editor

## Files Modified

- `plugins/webkul/projects/resources/views/livewire/annotation-editor.blade.php` (lines 6, 13)

## Related Issues

- User report: "some buttons are popping in front of annotation editor"
- Root cause: Context bar (z-40) > Annotation editor (z-35) = buttons overlap editor
- Solution: Increased editor z-index to z-50/z-55 to place it above context bar

## Date

Fixed: October 23, 2025
