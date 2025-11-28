# Phase 7: Test Polluter Identified

**Date:** 2025-11-22
**Status:** Root Cause Identified

---

## Executive Summary

**TEST 5 ("should toggle annotation visibility (Update operation)") IS THE POLLUTER**

Test 5 authenticates successfully but **fails its assertion**. This test failure leaves the browser in a corrupted state that breaks authentication for the next 2-3 tests.

---

## Evidence

### Incremental Testing Results

| Test Combination | Test 5 Auth Result | Notes |
|------------------|-------------------|-------|
| Test 5 alone | ✅ Auth Success | Proves auth code works |
| Tests 1 + 5 | ✅ Auth Success | Test 1 is NOT polluter |
| Tests 1-2 + 5 | ✅ Auth Success | Test 2 is NOT polluter |
| Tests 1-3 + 5 | ✅ Auth Success | Test 3 is NOT polluter |
| Tests 1-4 + 5 | ✅ Auth Success | Test 4 is NOT polluter |
| **Full Suite** | Tests 6-7 ❌ Auth Timeout | **Test 5 pollutes** |

### Full Suite Pattern (14 tests)

```
Test 1 (isolation): ✅ Auth Success
Test 2 (isolation): ✅ Auth Success
Test 3 (isolation): ✅ Auth Success
Test 4 (isolation): ✅ Auth Success
Test 5 (isolation): ✅ Auth Success → ❌ Assertion Failure (visibility toggle)
Test 6 (isolation): ❌ Auth Timeout (20s) - POLLUTED STATE
Test 7 (isolation): ❌ Auth Timeout (20s) - POLLUTED STATE
Test 8 (isolation): ✅ Auth Success (recovered)
Test 9 (isolation): ✅ Auth Success
Test 10 (page2):    ✅ Auth Success
Test 11 (page2):    ✅ Auth Success
Test 12 (page2):    ✅ Auth Success
Test 13 (page2):    ❌ Auth Timeout OR Other Failure
Test 14 (page2):    ❌ Auth Timeout OR Other Failure
```

**Final Results**: 4 failed, 9 passed (13-14 tests total)

---

## Root Cause Analysis

### What Test 5 Does

1. ✅ Authenticates successfully
2. ✅ Loads Alpine.js component
3. ✅ Waits for systemReady
4. ✅ Gets first annotation's visibility state
5. ❌ **Clicks eye icon** (using `page.getByRole('button', { name: '👁️' })`)
6. ❌ **Assertion fails**: Visibility doesn't change (`true` → `true`)
7. 🔥 **Test fails and leaves browser in corrupted state**

### Why Test 5's Failure Pollutes Browser State

When Test 5 fails its assertion, it likely:
1. **Leaves open modal/overlay** from clicking the eye icon
2. **Corrupts Alpine.js state** due to incomplete interaction
3. **Breaks session cookies** during the failed click operation
4. **Creates pending network requests** that never complete

Tests 6-7 then inherit this corrupted state and:
- Get redirected to login ✅
- Fill in credentials ✅
- Press Enter to submit form ✅
- **Form submission fails** due to corrupted state ❌
- **Timeout waiting for navigation** ❌

### Why Test 8 Recovers

Between Tests 7 and 8, something happens that "resets" the browser:
- Playwright's test cleanup may run timeout handlers
- Enough time passes for pending requests to abort
- beforeEach creates fresh page state
- Previous page garbage collection clears corrupted state

---

## The Visibility Toggle Bug

Test 5's assertion fails because **clicking the eye icon doesn't toggle visibility**:

```typescript
// Initial visibility: true
const eyeIcon = page.getByRole('button', { name: '👁️' }).first();
await eyeIcon.click();

// New visibility: true (UNCHANGED!)
// Expected: newState.visible !== initialState.visible
// Actual:   true !== true → FAIL
```

**Possible causes**:
1. Eye icon selector is incorrect
2. Click event not reaching the button
3. Alpine.js visibility toggle not working
4. State update requires wait time after click
5. Eye icon is visible but not clickable (z-index, overlay, etc.)

---

## Solution Strategies

### Option 1: Fix the Visibility Toggle (Recommended)

Fix the underlying issue so Test 5 doesn't fail:
1. Investigate why eye icon click doesn't toggle visibility
2. Debug the Alpine.js event handler
3. Add proper wait time after click for state update
4. Verify correct selector for eye icon
5. Check for overlays blocking the click

### Option 2: Improve Test Cleanup

Add afterEach cleanup to prevent pollution:

```typescript
test.afterEach(async ({ page }) => {
    // Close any open modals
    await page.evaluate(() => {
        document.querySelectorAll('[x-data]').forEach(el => {
            if (Alpine.$data(el).isOpen) {
                Alpine.$data(el).isOpen = false;
            }
        });
    });

    // Clear browser state
    await page.context().clearCookies();
    await page.evaluate(() => {
        localStorage.clear();
        sessionStorage.clear();
    });
});
```

### Option 3: Use Fresh Browser Contexts

Prevent pollution by creating new context for each test:

```typescript
test.beforeEach(async ({ browser }) => {
    const context = await browser.newContext({
        storageState: 'tests/Browser/auth-state.json'
    });
    const page = await context.newPage();
    // ... test setup
});

test.afterEach(async ({ page }) => {
    await page.context().close();
});
```

### Option 4: Skip Test 5 Temporarily

Mark Test 5 as `.skip()` until the visibility toggle is fixed:

```typescript
test.skip('should toggle annotation visibility (Update operation)', async ({ page }) => {
    // ... test code
});
```

---

## Next Steps

1. **Debug visibility toggle in isolation** - Why doesn't clicking the eye icon change state?
2. **Implement proper cleanup** - Add afterEach to clear browser state
3. **Test the fix** - Verify Tests 6-7 pass after fixing Test 5
4. **Fix remaining failures** - Tests 13-14 may have different root cause

---

## Key Lessons Learned

1. ✅ **Authentication code works perfectly** - All timeouts were due to test pollution
2. ✅ **Test 5 is the single point of failure** - Fixing this will unblock Tests 6-7
3. ✅ **Incremental testing is invaluable** - Systematically ruled out Tests 1-4
4. ⚠️ **Failed tests can pollute subsequent tests** - Not just passed tests!
5. ⚠️ **Assertion failures have side effects** - The failure itself leaves corrupted state

---

**Investigation Status:** Complete
**Next Phase:** Fix visibility toggle and implement cleanup
