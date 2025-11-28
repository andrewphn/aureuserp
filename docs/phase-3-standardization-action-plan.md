# Phase 3: Manager Standardization Action Plan

## Overview

Based on the interface analysis, this document provides specific, actionable fixes for each manager that needs standardization. Changes are prioritized by impact and complexity.

---

## Priority Levels

- 🔴 **HIGH**: Breaking inconsistencies affecting testability
- 🟡 **MEDIUM**: Minor inconsistencies affecting code clarity
- 🟢 **LOW**: Nice-to-have improvements

---

## Manager-Specific Action Items

### 1. annotation-manager.js - 🟡 MEDIUM Priority

**Issue:** Mixed parameter pattern - uses `refs` without `callbacks`

**Current Signature:**
```javascript
// Line 17
async function loadAnnotations(state, refs) {
    // Uses refs only for coordinate transform helper
}
```

**Recommended Fix:**
```javascript
// Option A: Include callbacks for consistency (RECOMMENDED)
async function loadAnnotations(state, refs, callbacks) {
    // Now follows standard Pattern B
}

// Option B: Remove refs if not essential
async function loadAnnotations(state, callbacks) {
    // Pass refs through callbacks if needed
    const refs = callbacks.getRefs ? callbacks.getRefs() : null;
}
```

**Decision:** Use Option A - `refs` is legitimately needed for coordinate calculations

**Files to Update:**
- `annotation-manager.js:17` - Update function signature
- `pdf-viewer-core.js` - Update callsite to pass callbacks object

**Breaking Change:** No - callbacks parameter will default to empty object `{}`

---

### 2. tree-manager.js - 🟡 MEDIUM Priority

**Issue:** Optional parameters add cognitive load

**Current Signature:**
```javascript
// Line 40
async function refreshTree(state, refs = null, callbacks = null) {
    // Optional parameters checked with if statements
    if (callbacks.$nextTick) {
        await callbacks.$nextTick();
    }
}
```

**Recommended Fix:**
```javascript
// Make it clear which parameters are required
async function refreshTree(state, callbacks) {
    // refs accessed via callbacks if needed
    const refs = callbacks.getRefs ? callbacks.getRefs() : null;

    if (refs && callbacks.syncOverlayToCanvas) {
        callbacks.syncOverlayToCanvas(refs);
    }
}
```

**Decision:** Simplify to state + callbacks pattern (Pattern A)

**Files to Update:**
- `tree-manager.js:40` - Update function signature
- Remove null checks for refs parameter
- Access refs through callbacks object instead

**Breaking Change:** No - existing calls without callbacks will need update but minimal

---

### 3. zoom-manager.js - 🟢 LOW Priority (Already Good)

**Status:** ✅ No changes needed

**Current Pattern:**
```javascript
async function zoomIn(state, refs, callbacks) { ... }
async function zoomOut(state, refs, callbacks) { ... }
async function setZoom(level, state, refs, callbacks) { ... }
```

**Analysis:** Consistently uses Pattern B (state, refs, callbacks) which is appropriate since zoom manager needs DOM measurements.

---

### 4. isolation-mode-manager.js - 🟢 LOW Priority (Already Good)

**Status:** ✅ No changes needed

**Current Pattern:**
```javascript
async function enterIsolationMode(annotation, state, callbacks) { ... }
export function isAnnotationVisibleInIsolation(anno, state) { ... }
```

**Analysis:** Correctly uses entity-first pattern (Pattern C) for operations on specific annotations.

---

### 5. navigation-manager.js - 🟢 LOW Priority (Already Good)

**Status:** ✅ No changes needed

**Current Pattern:**
```javascript
async function nextPage(state, callbacks) { ... }
async function previousPage(state, callbacks) { ... }
async function goToPage(pageNum, state, callbacks) { ... }
```

**Analysis:** Clean, consistent Pattern A (state + callbacks).

---

### 6. undo-redo-manager.js - 🟢 LOW Priority (Already Good)

**Status:** ✅ No changes needed

**Current Pattern:**
```javascript
export function pushToHistory(state, action) { ... }
export function undo(state) { ... }
export function redo(state) { ... }
export function canUndo(state) { ... }
```

**Analysis:** Clean Pattern D (state-only) for history operations. No DOM or async callbacks needed.

---

### 7. state-manager.js - 🟢 LOW Priority (Already Good)

**Status:** ✅ No changes needed

**Current Pattern:**
```javascript
export function createInitialState(config) { ... }
export function getColorForType(type) { ... }
export function getViewTypeLabel(viewType, orientation = null) { ... }
```

**Analysis:** Pure utility functions. Perfect as-is.

---

## Standardization Rules to Document

### Rule 1: Parameter Order
```
✅ Correct: (entity, state, refs, callbacks)
❌ Wrong:   (state, entity, refs, callbacks)
```

### Rule 2: Required vs Optional
```
✅ Correct: Always include state as first param (or after entity)
✅ Correct: Make callbacks required, use {} default
❌ Wrong:   Optional state parameter
```

### Rule 3: Refs Access
```
✅ Correct: Include refs parameter if DOM access is needed
✅ Correct: Pass refs through callbacks.$refs if occasional use
❌ Wrong:   Mix refs parameter with callbacks.getRefs()
```

---

## Implementation Steps

### Step 1: Update annotation-manager.js
```bash
# File: annotation-manager.js
# Lines to change: 17, and any internal refs usage
```

**Changes:**
```diff
- async function loadAnnotations(state, refs) {
+ async function loadAnnotations(state, refs, callbacks = {}) {
```

**Callsite Updates:**
```javascript
// In pdf-viewer-core.js
await loadAnnotations(this, this.$refs, {
    // Add any callbacks if needed in future
});
```

---

### Step 2: Update tree-manager.js
```bash
# File: tree-manager.js
# Lines to change: 40-67
```

**Changes:**
```diff
- async function refreshTree(state, refs = null, callbacks = null) {
+ async function refreshTree(state, callbacks = {}) {
+     const refs = callbacks.$refs || null;

-     if (refs && callbacks) {
+     if (refs) {
          // Tree refresh logic
      }
  }
```

**Callsite Updates:**
```javascript
// In pdf-viewer-core.js
await refreshTree(this, {
    $refs: this.$refs,
    $nextTick: this.$nextTick,
    syncOverlayToCanvas: this.syncOverlayToCanvas
});
```

---

### Step 3: Add JSDoc for All Updated Functions

**Template:**
```javascript
/**
 * Function description
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs object
 * @param {Object} callbacks - Callback functions
 * @param {Function} [callbacks.callbackName] - Optional callback description
 * @returns {Promise<void>}
 */
async function functionName(state, refs, callbacks = {}) {
    // Implementation
}
```

---

### Step 4: Update Tests

Update test files to use new signatures:

```javascript
// tests/Browser/helpers/manager-helpers.ts

// Update invokeManager method to handle new signatures
async invokeManager(managerName, methodName, ...args) {
    const result = await this.page.evaluate(
        ({ manager, method, args, stateOverride, refsOverride, callbacksOverride }) => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const state = stateOverride || Alpine.$data(el);
            const refs = refsOverride || state.$refs;
            const callbacks = callbacksOverride || {};

            return window.PdfViewerManagers[manager][method](state, refs, callbacks, ...args);
        },
        { manager: managerName, method: methodName, args }
    );
    return result;
}
```

---

## Validation Checklist

After implementing changes, verify:

- [ ] All manager functions follow one of the 4 standard patterns
- [ ] JSDoc comments updated for changed signatures
- [ ] No breaking changes to public API (Alpine component methods)
- [ ] Test helper utilities updated
- [ ] Integration tests pass
- [ ] No console errors in browser

---

## Rollback Plan

If issues discovered:

1. Git revert specific manager files
2. Restore original function signatures
3. Document issue in GitHub issue tracker
4. Re-evaluate approach

---

## Timeline Estimate

- annotation-manager.js: 30 minutes
- tree-manager.js: 45 minutes
- JSDoc updates: 2 hours
- Test updates: 1 hour
- Validation: 1 hour

**Total: ~5 hours**

---

## Success Metrics

Phase 3 complete when:

✅ All managers use one of 4 documented patterns
✅ No optional parameters for state/refs/callbacks
✅ JSDoc complete for all exported functions
✅ Test suite passes with updated signatures
✅ Documentation updated in phase-3-manager-interface-analysis.md

---

**Document Version:** 1.0
**Created:** 2025-11-21
**Phase:** 3 - Manager Interface Standardization
**Status:** Action Plan Ready 📋
