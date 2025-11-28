# PDF Viewer Cache System Documentation

## Overview

The PDF viewer implements a multi-layered caching system to optimize performance and user experience. This document describes all cache-related state fields, their lifecycles, invalidation patterns, and best practices.

## Cache Architecture

### Cache Layers

1. **Readiness Flags** - Boolean flags indicating system initialization states
2. **Data Caches** - Cached data structures (tree, annotations, page mappings)
3. **Performance Caches** - Optimization caches with TTL (_overlayRect, _cachedZoom)
4. **PDF Document Cache** - WeakMap-based PDF.js document storage

## State Field Reference

### Readiness Flags

#### `treeReady` (Boolean)
**Purpose**: Indicates project tree has been loaded and processed
**Set by**: `tree-manager.js:22` - `loadTree()`
**Reset by**: Never (remains true once tree loads)
**Dependencies**: Required for annotation position calculations
**Usage**: Guards against accessing tree data before initialization

**Lifecycle**:
```
Initial: false
  ↓
loadTree() called
  ↓
Tree fetched from API → Processed → Set to true
  ↓
Remains true for session
```

**Example**:
```javascript
// tree-manager.js:22
state.treeReady = true;

// Used in annotation positioning
if (!state.treeReady) {
    console.warn('Tree not ready, skipping annotation positioning');
    return;
}
```

#### `pdfReady` (Boolean)
**Purpose**: Indicates PDF has been preloaded into memory
**Set by**: `pdf-manager.js:35` - `preloadPDF()`
**Reset by**: Never (remains true once PDF loads)
**Dependencies**: Required before rendering pages
**Usage**: Guards against rendering before PDF document exists

**Lifecycle**:
```
Initial: false
  ↓
preloadPDF() called
  ↓
PDF.js loads document → Store in WeakMap → Set to true
  ↓
Remains true for session
```

#### `annotationsReady` (Boolean)
**Purpose**: Indicates current page annotations have been loaded
**Set by**: `annotation-manager.js:51` - `loadAnnotations()`
**Reset by**: `navigation-manager.js:121` - Before loading new page
**Dependencies**: None
**Usage**: Prevents rendering annotations before they're loaded

**Lifecycle**:
```
Initial: false
  ↓
Page navigation occurs
  ↓
Reset to false (navigation-manager.js:121)
  ↓
loadAnnotations() called
  ↓
Annotations fetched → Processed → Set to true
  ↓
Cycle repeats on next navigation
```

#### `systemReady` (Boolean)
**Purpose**: Indicates entire PDF viewer system is initialized
**Set by**: `pdf-viewer-core.js` - After all components initialized
**Reset by**: Never
**Dependencies**: Requires treeReady && pdfReady
**Usage**: Master flag for system availability

**Lifecycle**:
```
Initial: false
  ↓
All subsystems initialize
  ↓
treeReady === true && pdfReady === true
  ↓
Set to true
  ↓
Remains true for session
```

---

### Data Caches

#### `pageMap` (Object)
**Purpose**: Maps page numbers to pdfPageId database IDs
**Type**: `{ [pageNumber: string]: number }`
**Set by**: `state-manager.js:156` - Initial configuration
**Updated by**: Never (static mapping from server)
**Invalidated by**: Never (remains constant for session)

**Structure**:
```javascript
pageMap: {
    "1": 123,  // Page 1 → pdfPageId 123
    "2": 124,  // Page 2 → pdfPageId 124
    "3": 125   // Page 3 → pdfPageId 125
}
```

**Usage Examples**:
```javascript
// navigation-manager.js:142 - Get pdfPageId for page navigation
const newPdfPageId = state.pageMap[pageNum];
if (!newPdfPageId) {
    console.warn(`⚠️ No pdfPageId found for page ${pageNum}`);
    return;
}

// tree-manager.js:451 - Initialize pages from pageMap
Object.keys(state.pageMap).forEach(pageNum => {
    pages.set(parseInt(pageNum), {
        pageNumber: parseInt(pageNum),
        annotations: []
    });
});
```

**Why It Exists**: Server-side page numbers may not match PDF page indices. This mapping ensures correct database lookups for annotations.

#### `tree` (Array)
**Purpose**: Cached project hierarchy structure
**Type**: `Array<TreeNode>`
**Set by**: `tree-manager.js:20` - `loadTree()`
**Updated by**: `tree-manager.js:55-69` - Tree filtering, annotation updates
**Invalidated by**: Never (no manual refresh mechanism)

**Structure**:
```javascript
tree: [
    {
        id: "room-123",
        type: "room",
        name: "Kitchen",
        children: [
            {
                id: "location-456",
                type: "location",
                name: "Island",
                annotations: []  // Updated when annotations change
            }
        ]
    }
]
```

**Update Patterns**:
```javascript
// tree-manager.js:55-69 - Update annotation positions in tree
annotations.forEach(annotation => {
    const treeNode = findNodeById(state.tree, annotation.roomId || annotation.locationId);
    if (treeNode) {
        if (!treeNode.annotations) treeNode.annotations = [];
        treeNode.annotations.push(annotation);
    }
});
```

**Why It Exists**: Avoids repeated API calls for project structure, enables fast tree filtering and annotation positioning.

#### `annotations` (Array)
**Purpose**: Current page's annotation data
**Type**: `Array<Annotation>`
**Set by**: `annotation-manager.js:51` - `loadAnnotations()`
**Updated by**:
- `annotation-manager.js:105` - `saveAnnotation()`
- `annotation-manager.js:180` - `deleteAnnotation()`
**Invalidated by**: `navigation-manager.js:121` - Before page navigation

**Structure**:
```javascript
annotations: [
    {
        id: 789,
        pdfPageId: 124,
        roomId: 123,
        locationId: 456,
        type: "cabinet_run",
        coordinates: { x: 100, y: 200, width: 50, height: 30 },
        label: "Base Cabinet - 36\"",
        metadata: { ... }
    }
]
```

**Invalidation Pattern**:
```javascript
// navigation-manager.js:121 - Clear cache before loading new page
state.annotations = [];
state.annotationsReady = false;

// annotation-manager.js:51 - Repopulate cache
const response = await fetch(`/api/pdf/page/${pdfPageId}/annotations`);
state.annotations = await response.json();
state.annotationsReady = true;
```

**Why It Exists**: Prevents redundant API calls when rendering/interacting with annotations on current page.

---

### Performance Caches

#### `_overlayRect` (Object|null)
**Purpose**: Cached overlay dimensions for click coordinate calculations
**Type**: `DOMRect | null`
**Set by**: `state-manager.js:260` - `getOverlayRect()`
**Invalidated by**: Automatically after 100ms (via `_rectCacheMs`)
**TTL**: 100ms

**Structure**:
```javascript
_overlayRect: {
    x: 0,
    y: 0,
    width: 1200,
    height: 800,
    top: 0,
    right: 1200,
    bottom: 800,
    left: 0
}
```

**Caching Logic**:
```javascript
// state-manager.js:260-271
getOverlayRect() {
    const now = Date.now();

    // Return cached value if still valid
    if (this._overlayRect && (now - this._lastRectUpdate) < this._rectCacheMs) {
        return this._overlayRect;
    }

    // Recalculate and cache
    this._overlayRect = this.$refs.overlay.getBoundingClientRect();
    this._lastRectUpdate = now;
    return this._overlayRect;
}
```

**Why It Exists**: `getBoundingClientRect()` forces layout recalculation (expensive). Caching prevents performance degradation during rapid mouse movements.

#### `_cachedZoom` (Number|undefined)
**Purpose**: Cached zoom level to avoid redundant PDF.js viewport calculations
**Type**: `number | undefined`
**Set by**: Zoom operations
**Invalidated by**: Manual zoom changes
**TTL**: Indefinite (until next zoom change)

**Usage**:
```javascript
// Avoid recalculating if zoom hasn't changed
if (this._cachedZoom === newZoom) {
    return; // Skip expensive re-render
}
this._cachedZoom = newZoom;
// Proceed with viewport update...
```

**Why It Exists**: PDF.js viewport calculations are expensive. Caching prevents unnecessary re-renders when zoom doesn't actually change.

---

### PDF Document Cache

#### `pdfDocuments` (WeakMap)
**Purpose**: Cache PDF.js document objects
**Type**: `WeakMap<Object, PDFDocument>`
**Scope**: Module-level (not in state)
**Location**: `pdf-manager.js:13`

**Pattern**:
```javascript
// pdf-manager.js:13
const pdfDocuments = new WeakMap();

// pdf-manager.js:35 - Store PDF document
export async function preloadPDF(state, refs, callbacks = {}) {
    const pdfDocument = await pdfjsLib.getDocument(pdfUrl).promise;
    pdfDocuments.set(state, pdfDocument);  // Cache by state object
    state.pdfReady = true;
}

// pdf-manager.js:57 - Retrieve cached document
export async function renderPDF(state, refs, callbacks = {}) {
    const pdfDocument = pdfDocuments.get(state);
    if (!pdfDocument) {
        throw new Error('PDF not preloaded');
    }
    // Use cached document...
}
```

**Why WeakMap**:
- **Automatic garbage collection**: When Alpine component is destroyed, state object becomes unreachable → WeakMap entry auto-deleted
- **Memory leak prevention**: No manual cleanup needed
- **Component isolation**: Each viewer instance has its own cache entry

**Why Not in State**:
- PDF.js document objects are NOT serializable
- Alpine.js reactivity would fail on non-serializable objects
- WeakMap prevents Alpine from attempting reactive conversion

---

## Cache Invalidation Patterns

### Pattern 1: Navigation-Based Invalidation

**Trigger**: User navigates to different page
**Invalidated Caches**: `annotations`, `annotationsReady`
**Location**: `navigation-manager.js:121`

```javascript
export async function navigateToPage(state, refs, pageNum) {
    // Clear annotation cache BEFORE loading new page
    state.annotations = [];
    state.annotationsReady = false;

    // Update page number
    state.currentPage = pageNum;

    // Trigger reload (will repopulate cache)
    await loadAnnotations(state, refs);
}
```

**Rationale**: Annotations are page-specific. Stale annotations from previous page would cause rendering errors.

---

### Pattern 2: CRUD-Based Invalidation

**Trigger**: Annotation created, updated, or deleted
**Invalidated Caches**: In-memory `annotations` array
**Location**: `annotation-manager.js:105, 180`

```javascript
// CREATE/UPDATE
export async function saveAnnotation(state, refs, annotationData) {
    const response = await fetch('/api/annotations', {
        method: 'POST',
        body: JSON.stringify(annotationData)
    });

    const savedAnnotation = await response.json();

    // Update cache with new/updated annotation
    const existingIndex = state.annotations.findIndex(a => a.id === savedAnnotation.id);
    if (existingIndex !== -1) {
        state.annotations[existingIndex] = savedAnnotation;  // Update
    } else {
        state.annotations.push(savedAnnotation);  // Create
    }
}

// DELETE
export async function deleteAnnotation(state, refs, annotationId) {
    await fetch(`/api/annotations/${annotationId}`, { method: 'DELETE' });

    // Remove from cache
    state.annotations = state.annotations.filter(a => a.id !== annotationId);
}
```

**Rationale**: Keep cache in sync with database without full reload. Optimistic UI updates.

---

### Pattern 3: Tree-Based Invalidation

**Trigger**: Annotations loaded or updated
**Invalidated Caches**: `tree` node annotations
**Location**: `tree-manager.js:55-69`

```javascript
export function updateTreeWithAnnotations(state) {
    // Clear all annotation references in tree
    clearTreeAnnotations(state.tree);

    // Rebuild annotation positions
    state.annotations.forEach(annotation => {
        const nodeId = annotation.locationId || annotation.roomId;
        const treeNode = findNodeInTree(state.tree, nodeId);

        if (treeNode) {
            if (!treeNode.annotations) treeNode.annotations = [];
            treeNode.annotations.push(annotation);
        }
    });
}
```

**Rationale**: Tree displays annotation counts per room/location. Must stay in sync with current page annotations.

---

### Pattern 4: Time-Based Invalidation

**Trigger**: TTL expires
**Invalidated Caches**: `_overlayRect`
**Location**: `state-manager.js:260`

```javascript
getOverlayRect() {
    const now = Date.now();
    const cacheAge = now - this._lastRectUpdate;

    // Invalidate if cache is stale
    if (cacheAge >= this._rectCacheMs) {
        this._overlayRect = null;  // Force recalculation
    }

    // Recalculate if needed
    if (!this._overlayRect) {
        this._overlayRect = this.$refs.overlay.getBoundingClientRect();
        this._lastRectUpdate = now;
    }

    return this._overlayRect;
}
```

**Rationale**: Overlay dimensions can change (browser resize, zoom). TTL prevents stale coordinates while avoiding excessive recalculations.

---

## Cache Flow Diagrams

### Page Navigation Flow

```
User clicks page 2
  ↓
navigateToPage(2)
  ↓
state.annotations = []           ← INVALIDATE
state.annotationsReady = false   ← INVALIDATE
  ↓
state.currentPage = 2
  ↓
loadAnnotations()
  ↓
fetch(/api/pdf/page/124/annotations)
  ↓
state.annotations = [...]        ← REPOPULATE
state.annotationsReady = true    ← REPOPULATE
  ↓
updateTreeWithAnnotations()
  ↓
tree[...].annotations = [...]    ← UPDATE TREE CACHE
  ↓
renderAnnotations()
```

### Annotation Save Flow

```
User draws annotation
  ↓
saveAnnotation(annotationData)
  ↓
POST /api/annotations
  ↓
savedAnnotation = response.json()
  ↓
state.annotations.push(savedAnnotation)  ← UPDATE CACHE
  ↓
updateTreeWithAnnotations()
  ↓
tree[...].annotations.push(...)          ← UPDATE TREE CACHE
  ↓
renderAnnotations()
```

### System Initialization Flow

```
Alpine component mounted
  ↓
loadTree()
  ↓
state.tree = [...]        ← CACHE TREE
state.treeReady = true    ← READINESS FLAG
  ↓
preloadPDF()
  ↓
pdfDocuments.set(state, pdfDoc)  ← CACHE PDF
state.pdfReady = true            ← READINESS FLAG
  ↓
loadAnnotations()
  ↓
state.annotations = [...]        ← CACHE ANNOTATIONS
state.annotationsReady = true    ← READINESS FLAG
  ↓
state.systemReady = true         ← MASTER READINESS FLAG
```

---

## Best Practices

### 1. Always Check Readiness Flags

```javascript
// ❌ BAD - Accessing cache without checking
export function renderAnnotations(state) {
    state.annotations.forEach(a => render(a));  // Crash if annotations not loaded
}

// ✅ GOOD - Check readiness first
export function renderAnnotations(state) {
    if (!state.annotationsReady) {
        console.warn('Annotations not ready');
        return;
    }
    state.annotations.forEach(a => render(a));
}
```

### 2. Invalidate Before Reload

```javascript
// ❌ BAD - Load without invalidating
export async function navigateToPage(state, pageNum) {
    state.currentPage = pageNum;
    await loadAnnotations(state);  // May show stale data briefly
}

// ✅ GOOD - Invalidate first
export async function navigateToPage(state, pageNum) {
    state.annotations = [];           // Clear stale data
    state.annotationsReady = false;   // Prevent rendering during load
    state.currentPage = pageNum;
    await loadAnnotations(state);     // Load fresh data
}
```

### 3. Update Cache Optimistically

```javascript
// ❌ BAD - Full reload after save
export async function saveAnnotation(state, data) {
    await fetch('/api/annotations', { method: 'POST', body: data });
    await loadAnnotations(state);  // Expensive full reload
}

// ✅ GOOD - Update cache directly
export async function saveAnnotation(state, data) {
    const response = await fetch('/api/annotations', { method: 'POST', body: data });
    const saved = await response.json();
    state.annotations.push(saved);  // Instant UI update
}
```

### 4. Use WeakMap for Non-Serializable Objects

```javascript
// ❌ BAD - Store PDF.js object in state
export async function preloadPDF(state) {
    state.pdfDocument = await pdfjsLib.getDocument(url).promise;  // Alpine error!
}

// ✅ GOOD - Use WeakMap
const pdfDocuments = new WeakMap();

export async function preloadPDF(state) {
    const pdfDoc = await pdfjsLib.getDocument(url).promise;
    pdfDocuments.set(state, pdfDoc);  // No reactivity issues
    state.pdfReady = true;
}
```

### 5. Implement TTL for Expensive Operations

```javascript
// ❌ BAD - Recalculate on every call
export function getOverlayRect(refs) {
    return refs.overlay.getBoundingClientRect();  // Forces layout thrashing
}

// ✅ GOOD - Cache with TTL
let cachedRect = null;
let lastUpdate = 0;
const TTL = 100;  // ms

export function getOverlayRect(refs) {
    const now = Date.now();
    if (!cachedRect || (now - lastUpdate) > TTL) {
        cachedRect = refs.overlay.getBoundingClientRect();
        lastUpdate = now;
    }
    return cachedRect;
}
```

---

## Cache Debugging

### Inspect Cache State

```javascript
// In browser console
$alpine.raw(document.querySelector('[x-data]'))

// Output:
{
    pageMap: { "1": 123, "2": 124 },
    tree: [...],
    annotations: [...],
    treeReady: true,
    pdfReady: true,
    annotationsReady: true,
    _overlayRect: { x: 0, y: 0, ... },
    _cachedZoom: 1.5
}
```

### Check WeakMap Cache

```javascript
// In pdf-manager.js, add temporary logging
export function debugPDFCache(state) {
    const hasPDF = pdfDocuments.has(state);
    console.log('PDF cached:', hasPDF);
    return hasPDF;
}
```

### Monitor Cache Invalidations

```javascript
// Add logging to navigation-manager.js:121
console.log('🗑️ Invalidating annotations cache');
state.annotations = [];
state.annotationsReady = false;
```

---

## Performance Considerations

### Cache Hit Rates

**High Hit Rate (Good)**:
- `pageMap`: 100% (never changes)
- `tree`: ~95% (only loads once)
- `_overlayRect`: ~90% (100ms TTL covers most interactions)

**Medium Hit Rate (Expected)**:
- `annotations`: ~60% (invalidated on every page navigation)

**Low Hit Rate (By Design)**:
- `_cachedZoom`: Variable (depends on user zoom frequency)

### Memory Usage

**Approximate Sizes**:
- `pageMap`: ~1KB (100 pages × 10 bytes)
- `tree`: ~50KB (complex project with 1000 nodes)
- `annotations`: ~10KB (100 annotations × 100 bytes)
- `_overlayRect`: ~100 bytes
- `pdfDocuments` WeakMap: ~5MB per PDF (managed by PDF.js)

**Total**: ~5-10MB per viewer instance (acceptable for modern browsers)

### Cache Cleanup

**Automatic Cleanup**:
- `pdfDocuments` WeakMap: Auto-cleaned when component destroyed
- All state caches: Cleared when Alpine component unmounted

**Manual Cleanup** (not currently implemented):
```javascript
// Could add cleanup hook
export function cleanupCaches(state) {
    state.annotations = [];
    state.tree = [];
    state._overlayRect = null;
    state._cachedZoom = undefined;
    // WeakMap auto-cleans when state is GC'd
}
```

---

## Future Enhancements

### 1. LRU Cache for Historical Pages

Currently, only current page annotations are cached. Could implement LRU cache:

```javascript
const annotationCache = new Map();  // pageId → annotations
const MAX_CACHED_PAGES = 10;

export function cacheAnnotations(pageId, annotations) {
    if (annotationCache.size >= MAX_CACHED_PAGES) {
        const oldestKey = annotationCache.keys().next().value;
        annotationCache.delete(oldestKey);
    }
    annotationCache.set(pageId, annotations);
}
```

### 2. Service Worker for Offline Caching

Enable offline access to PDFs and annotations:

```javascript
// service-worker.js
self.addEventListener('fetch', event => {
    if (event.request.url.includes('/api/pdf/')) {
        event.respondWith(
            caches.match(event.request)
                .then(cached => cached || fetch(event.request))
        );
    }
});
```

### 3. IndexedDB for Large Datasets

For projects with 1000+ annotations, use IndexedDB:

```javascript
const db = await openDB('pdf-annotations', 1);
await db.put('annotations', annotationsArray, pageId);
const cached = await db.get('annotations', pageId);
```

### 4. Cache Preloading

Preload adjacent pages for faster navigation:

```javascript
export async function preloadAdjacentPages(state) {
    const currentPage = state.currentPage;
    const nextPages = [currentPage - 1, currentPage + 1];

    nextPages.forEach(async pageNum => {
        if (state.pageMap[pageNum]) {
            const pdfPageId = state.pageMap[pageNum];
            const annotations = await fetch(`/api/pdf/page/${pdfPageId}/annotations`);
            annotationCache.set(pdfPageId, annotations);
        }
    });
}
```

---

## Summary

The PDF viewer cache system balances performance, memory usage, and data freshness through:

1. **Readiness Flags**: Prevent accessing uninitialized data
2. **Data Caches**: Reduce API calls for static/semi-static data
3. **Performance Caches**: Optimize expensive DOM operations
4. **WeakMap Pattern**: Safe caching of non-serializable objects

**Key Principles**:
- ✅ Invalidate before reload
- ✅ Update cache optimistically
- ✅ Check readiness flags
- ✅ Use WeakMap for complex objects
- ✅ Implement TTL for DOM measurements

This architecture provides fast, responsive PDF annotation while maintaining data consistency and preventing memory leaks.
