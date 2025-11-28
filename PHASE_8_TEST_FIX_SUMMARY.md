# Phase 8: Test 5 Fix & Pollution Discovery

## Final Test Results: 10 passed, 4 failed (2.6 minutes)

### Summary
Fixed Test 5's visibility toggle functionality, but discovered that Test 5's **success** is actually polluting subsequent tests by leaving annotations in a hidden state.

## Test 5 Fix Implementation

### Problem 1: Selector Not Finding Eye Icon
**Root Cause**: The button structure has the emoji in a `<span>` child element:
```blade
<button @click.stop="...">
    <span x-show="...">👁️</span>
</button>
```

**Fix**: Changed selector from:
```typescript
// BEFORE - doesn't work
page.getByRole('button', { name: '👁️' })

// AFTER - works correctly
page.locator('button:has-text("👁️")')
```

### Problem 2: Checking Wrong Visibility Property
**Root Cause**: The visibility system uses a `hiddenAnnotations` array, not individual `.visible` properties.

**Fix**: Changed visibility check from:
```typescript
// BEFORE - checks non-existent property
const anno = data.annotations.find(a => a.id === id);
return { visible: anno.visible !== false };

// AFTER - checks hiddenAnnotations array
return { visible: !data.hiddenAnnotations.includes(id) };
```

### Result
Test 5 now **PASSES** with correct visibility toggle:
```
📍 Initial visibility: true
📍 New visibility: false
✅ Test 5 PASSED
```

## The Pollution Discovery

### Test Failure Pattern
```
✅ Tests 1-5: PASS
❌ Tests 6-7: FAIL (auth timeout)
✅ Tests 8-9: PASS
✅ Tests 10-12: PASS
❌ Tests 13-14: FAIL (auth timeout)
```

### Failed Tests (all auth timeouts)
- Test 6: "should open annotation editor (Edit operation)"
- Test 7: "should test filter functionality"
- Test 13: "should interact with page 2 annotations"
- Test 14: "should toggle annotation visibility" (in test-page2-interactions)

### Root Cause Analysis
**Test 5's SUCCESS is the polluter:**

1. Test 5 clicks eye icon → hides annotation (visibility: `true` → `false`) ✅
2. Test 5 assertion passes ✅
3. **BUT Test 5 doesn't restore visibility state** ❌
4. Browser left with hidden annotations
5. Tests 6-7 encounter corrupted state during beforeEach setup
6. Authentication works, but hidden annotations cause component issues
7. Tests timeout waiting for URL navigation that never completes

### Why Tests 8-9 Still Pass
Tests 8-9 (undo/redo and zoom) don't depend on annotation visibility state, so they pass even with hidden annotations.

### Why Tests 10-12 Pass
Tests 10-12 are from test-page2-interactions.spec.ts. The fresh browser context for the new spec file resets the visibility state, so these tests pass.

### Why Tests 13-14 Fail
Tests 13-14 in test-page2-interactions also try to interact with annotations during setup. The hidden state from Test 5 (carried over from test-isolation-and-crud spec) causes the same auth timeout.

## Solution Required

Test 5 needs cleanup code to restore visibility state:

```typescript
test('should toggle annotation visibility (Update operation)', async ({ page }) => {
    // ... existing test code ...

    // NEW: Restore visibility at end of test
    if (eyeVisible && newState.visible !== initialState.visible) {
        await eyeIcon.click(); // Click again to restore original state
        await page.waitForTimeout(500);
    }
});
```

## Files Modified
- `/Users/andrewphan/tcsadmin/aureuserp/tests/Browser/test-isolation-and-crud.spec.ts` (lines 198-238)
  - Fixed selector: `button:has-text("👁️")`
  - Fixed visibility check: `!data.hiddenAnnotations.includes(id)`

- `/Users/andrewphan/tcsadmin/aureuserp/tests/Browser/test-page2-interactions.spec.ts` (lines 198-232)
  - Fixed selector (same change)

## Next Steps
1. Add cleanup code to Test 5 to restore visibility
2. Verify all 14 tests pass
3. Document complete fix in Phase 9
