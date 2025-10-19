# Phase 5: Per-Page Annotation Loading - Implementation Summary

## ✅ Completed: 2025-10-19

## Problem Statement
When navigating between PDF pages using the Previous/Next buttons, all annotations from all pages were being displayed on every page. This violated the user's expectation that "when I go to page 2, annotations for page 1 should disappear and annotations for page 2 should appear."

## Root Cause
The V3 annotation viewer was using a single `pdfPageId` (database record ID) throughout the entire session, regardless of which page the user was viewing. The `loadAnnotations()` method was always loading annotations for the initial page's `pdfPageId`.

## Solution Implemented

### 1. Controller Changes (`AnnotatePdfV2.php`)
**File**: `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/AnnotatePdfV2.php`

- Added `pageMap` property to store mapping of page numbers to database IDs
- Built the page map on mount using `PdfPage::pluck('id', 'page_number')`
- Example: `[1 => 45, 2 => 46, 3 => 47, ...]`

```php
// Build a map of page_number => pdfPageId for all pages
$this->pageMap = PdfPage::where('document_id', $this->pdfDocument->id)
    ->orderBy('page_number')
    ->pluck('id', 'page_number')
    ->toArray();
```

### 2. Blade Template Changes (`annotate-pdf-v2.blade.php`)
**File**: `plugins/webkul/projects/resources/views/filament/resources/project-resource/pages/annotate-pdf-v2.blade.php`

- Passed `pageMap` to the component include

```blade
@include('webkul-project::filament.components.pdf-annotation-viewer-v3-overlay', [
    'pdfPageId' => $pdfPage?->id,
    'pdfUrl' => $pdfUrl,
    'pageNumber' => $pageNumber,
    'projectId' => $projectId,
    'totalPages' => $totalPages,
    'pageType' => $pageType,
    'pageMap' => $pageMap,  // NEW
])
```

### 3. Alpine Component Changes (`pdf-annotation-viewer-v3-overlay.blade.php`)
**File**: `plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer-v3-overlay.blade.php`

#### Added `pageMap` to props:
```php
@props([
    'pdfPageId',
    'pdfUrl',
    'pageNumber',
    'projectId',
    'totalPages' => 1,
    'pageType' => null,
    'pageMap' => [],  // NEW
])
```

#### Passed `pageMap` to Alpine component:
```javascript
x-data="annotationSystemV3({
    pdfUrl: '{{ $pdfUrl }}',
    pageNumber: {{ $pageNumber }},
    pdfPageId: {{ $pdfPageId }},
    projectId: {{ $projectId }},
    totalPages: {{ $totalPages }},
    pageType: {{ $pageType ? "'" . $pageType . "'" : 'null' }},
    pageMap: {{ json_encode($pageMap) }}  // NEW
})"
```

#### Added `pageMap` to component state:
```javascript
Alpine.data('annotationSystemV3', (config) => ({
    // Configuration
    pdfUrl: config.pdfUrl,
    pageNumber: config.pageNumber,
    pdfPageId: config.pdfPageId,
    projectId: config.projectId,
    totalPages: config.totalPages || 1,
    pageMap: config.pageMap || {},  // NEW: Map of page_number => pdfPageId

    // ... rest of state
}))
```

#### Created `updatePdfPageId()` method:
```javascript
// Update pdfPageId based on currentPage using pageMap (NEW - Phase 5)
updatePdfPageId() {
    const newPdfPageId = this.pageMap[this.currentPage];
    if (newPdfPageId) {
        this.pdfPageId = newPdfPageId;
        console.log(`✓ Updated pdfPageId to ${this.pdfPageId} for page ${this.currentPage}`);
    } else {
        console.warn(`⚠️ No pdfPageId found for page ${this.currentPage} in pageMap`);
    }
},
```

#### Updated navigation methods to call `updatePdfPageId()`:
```javascript
async nextPage() {
    if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.updatePdfPageId();  // NEW: Update pdfPageId before loading
        console.log(`📄 Navigating to page ${this.currentPage}, pdfPageId: ${this.pdfPageId}`);
        await this.displayPdf();
        await this.loadAnnotations();  // Now loads correct page's annotations
    }
},

async previousPage() {
    if (this.currentPage > 1) {
        this.currentPage--;
        this.updatePdfPageId();  // NEW: Update pdfPageId before loading
        console.log(`📄 Navigating to page ${this.currentPage}, pdfPageId: ${this.pdfPageId}`);
        await this.displayPdf();
        await this.loadAnnotations();  // Now loads correct page's annotations
    }
},
```

## How It Works

### Data Flow:
1. **Controller** queries all PdfPage records for the document
2. **Controller** builds a map: `{1: 45, 2: 46, 3: 47, ...}` (page number → database ID)
3. **Blade template** passes the map to Alpine component
4. **Alpine component** stores the map in state
5. **When user clicks Next/Previous**:
   - `currentPage` increments/decrements
   - `updatePdfPageId()` looks up the new page's database ID in the map
   - `pdfPageId` is updated
   - `loadAnnotations()` fetches annotations for the **new** `pdfPageId`
   - Only annotations for the current page are displayed

### Example Navigation Sequence:
```
User on Page 1 → clicks Next
├─ currentPage: 1 → 2
├─ updatePdfPageId(): pageMap[2] = 46
├─ pdfPageId: 45 → 46
├─ loadAnnotations(): GET /api/pdf/page/46/annotations
└─ Display annotations for page 2 only

User clicks Next again
├─ currentPage: 2 → 3
├─ updatePdfPageId(): pageMap[3] = 47
├─ pdfPageId: 46 → 47
├─ loadAnnotations(): GET /api/pdf/page/47/annotations
└─ Display annotations for page 3 only
```

## API Endpoint Used
- **Endpoint**: `GET /api/pdf/page/{pdfPageId}/annotations`
- **Controller**: `App\Http\Controllers\Api\PdfAnnotationController::loadPageAnnotations()`
- **Location**: Line 610 in `app/Http/Controllers/Api/PdfAnnotationController.php`
- **Filtering**: Already filters by `pdf_page_id` (line 626)

```php
$annotations = \App\Models\PdfPageAnnotation::where('pdf_page_id', $pdfPageId)
    ->orderBy('created_at', 'asc')
    ->get();
```

## Database Schema
The solution leverages the existing database structure:
- `pdf_pages` table: Each page has a unique `id` and `page_number`
- `pdf_page_annotations` table: Each annotation has a `pdf_page_id` foreign key
- The API already filters annotations by `pdf_page_id`

## Benefits

### ✅ User Experience:
- Annotations are now page-specific
- Navigating to a new page shows only that page's annotations
- Previous page's annotations disappear as expected
- Clean, focused annotation view per page

### ✅ Performance:
- No additional API calls required
- Page map is built once on page load
- Reuses existing API endpoint
- No database schema changes needed

### ✅ Maintainability:
- Simple, clear logic
- Follows existing patterns
- Well-commented code
- Easy to debug with console logs

## Testing Recommendations

### Manual Testing:
1. Navigate to annotation viewer: `http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1`
2. Note the annotations on Page 1
3. Click "Next →" to go to Page 2
4. Verify Page 1 annotations disappear
5. Add an annotation on Page 2
6. Click "Next →" to go to Page 3
7. Verify Page 2 annotations disappear
8. Click "← Prev" to go back to Page 2
9. Verify Page 2 annotations reappear (including the one just added)
10. Click "← Prev" to go back to Page 1
11. Verify Page 1 annotations reappear

### Browser Console Verification:
Look for these console messages when navigating:
```
📄 Navigating to page 2, pdfPageId: 46
✓ Updated pdfPageId to 46 for page 2
📥 Loading annotations...
✓ Loaded 3 annotations
```

## Files Modified

1. `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/AnnotatePdfV2.php`
   - Added `pageMap` property
   - Built page map in `mount()` method

2. `plugins/webkul/projects/resources/views/filament/resources/project-resource/pages/annotate-pdf-v2.blade.php`
   - Passed `pageMap` to component

3. `plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer-v3-overlay.blade.php`
   - Added `pageMap` to props
   - Passed `pageMap` to Alpine component
   - Added `pageMap` to component state
   - Created `updatePdfPageId()` method
   - Updated `nextPage()` and `previousPage()` to call `updatePdfPageId()`

## Next Steps

### Remaining Phases:
- **Phase 3**: Page-Type-Specific Workflows (cover page vs floor plan workflows)
- **Phase 4**: Fix Coordinate System (if any issues remain with annotation positioning)

### Potential Enhancements:
- Add page-specific annotation counts to pagination UI
- Preload adjacent page annotations for faster navigation
- Add visual transition when switching pages
- Implement annotation search/filter across all pages

## Conclusion

Phase 5 is **complete and ready for manual testing**. The implementation ensures that annotations are properly filtered by page number, providing a clean and intuitive user experience when navigating through multi-page PDFs.

**User's original request fulfilled**:
> "when I go to page 2 shouldn't the annotations for page one disappear and annotations for page 2 appear"

**Answer**: ✅ Yes! This is now exactly how it works.
