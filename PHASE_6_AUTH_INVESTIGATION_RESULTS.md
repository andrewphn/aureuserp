# Phase 6: Authentication Investigation Results

**Date:** 2025-11-22
**Status:** Major Breakthrough - Root Cause Identified

## Executive Summary

The authentication failures in the test suite are **NOT caused by the authentication code**. When tests are run in isolation, they authenticate successfully. The failures are caused by test interaction issues where earlier tests corrupt the browser state for subsequent tests.

---

## Key Finding

### Test 5 Results Comparison

**When Run in Full Suite:**
```
Test 5: "should toggle annotation visibility (Update operation)"
❌ FAILS with authentication timeout
Error: TimeoutError: page.waitForURL: Timeout 20000ms exceeded
Status: Cannot authenticate, stuck on login page
```

**When Run in Isolation:**
```
Test 5: "should toggle annotation visibility (Update operation)"
✅ AUTHENTICATES SUCCESSFULLY
✅ Authenticated, now at: http://aureuserp.test/admin/project/projects/9/annotate-v2/2?pdf=1
❌ FAILS with different error (visibility toggle assertion)
Error: expect(newState.visible).not.toBe(initialState.visible)
```

### What This Proves

1. **Authentication code works correctly** ✅
2. **Promise.all + waitForURL + Enter key press works** ✅
3. **The failures are due to test pollution/interaction** ❌
4. **Some earlier test is corrupting browser/session state** ❌

---

## Failure Pattern Analysis

### Full Suite Run (14 tests):

| Test # | Test Name | Auth Result | Overall Result |
|--------|-----------|-------------|----------------|
| 1-4 | Isolation mode, Read operations | ✅ Auth Success | ✅ Pass |
| 5-7 | Update/Edit/Filter operations | ❌ Auth Timeout | ❌ Fail |
| 8-9 | Undo/Zoom operations | ✅ Auth Success | ✅ Pass |
| 10-12 | Page 2 interactions | ✅ Auth Success | ✅ Pass |
| 13-14 | Page 2 Update operations | ❌ Auth Timeout | ❌ Fail |

**Pattern:** Tests that involve certain operations (Update, Edit, Filter, Interact) fail authentication when run after other tests, but pass when run alone.

---

## Evidence

### Isolated Test Run

```bash
npx playwright test test-isolation-and-crud --grep="should toggle annotation visibility \(Update operation\)" --workers=1
```

**Result:**
```
Running 1 test using 1 worker

🔄 Starting test setup...
📍 Current URL: http://aureuserp.test/admin/login
⚠️ Redirected to login, authenticating...
✅ Authenticated, now at: http://aureuserp.test/admin/project/projects/9/annotate-v2/2?pdf=1
⏳ Waiting for page to stabilize...
✅ Test setup complete
✏️ Testing UPDATE operation - visibility toggle...
📍 Initial visibility: true
📍 New visibility: true

❌ Error: expect(newState.visible).not.toBe(initialState.visible)
Expected: not true
```

**Key observation:** Authentication completed successfully in 6.3 seconds.

---

## Root Cause Hypothesis

### Primary Suspect: Browser State Pollution

One or more of tests 1-4 is leaving the browser in a state that causes subsequent login attempts to fail. Possible causes:

1. **Session Cookie Corruption**
   - Earlier test might be clearing/invalidating cookies
   - Session storage might be getting corrupted

2. **Browser Context Pollution**
   - Local storage issues
   - Service worker state
   - Cache corruption

3. **Page Navigation State**
   - Earlier test might be navigating to a page that breaks subsequent navigation
   - Redirect loop or URL state issue

4. **Global JavaScript State**
   - Alpine.js or Livewire state corruption
   - Event listeners not being cleaned up

### Why Tests 8-9 and 10-12 Pass

After tests 5-7 fail, something happens that "fixes" the browser state:
- Maybe test timeout cleanup
- Maybe page reload between test files
- Maybe beforeEach eventually succeeds after enough time passes

---

## Next Steps - UPDATED WITH BREAKTHROUGH FINDING

### ✅ Tests 1-4 Are NOT the Polluters!

Incremental testing results (chromium):

```bash
# Test 1 + Test 5
✅ Both authenticated successfully

# Tests 1-2 + Test 5
✅ All 3 authenticated successfully

# Tests 1-3 + Test 5
✅ All 4 authenticated successfully

# Tests 1-4 + Test 5
✅ ALL 5 AUTHENTICATED SUCCESSFULLY
```

**Conclusion**: The pollution is NOT from `test-isolation-and-crud.spec.ts` tests 1-4.

### New Hypothesis: test-page2-interactions.spec.ts is the Culprit

The pollution likely comes from:
1. One or more tests in `test-page2-interactions.spec.ts`
2. Interaction when BOTH files run together
3. File execution order (page2 runs before isolation-and-crud)

### Next Investigation Steps

```bash
# Test page2 file alone
npx playwright test test-page2-interactions --project=chromium --workers=1

# Test page2 + isolation test 5
npx playwright test test-page2-interactions test-isolation-and-crud --grep="should toggle annotation visibility \(Update" --project=chromium --workers=1

# Identify which page2 test pollutes
# (Similar incremental approach as before)
```

### Option 2: Improve Test Isolation

Add cleanup to beforeEach/afterEach:

```typescript
test.beforeEach(async ({ page }) => {
    // Clear all browser state before each test
    await page.context().clearCookies();
    await page.context().clearPermissions();
    await page.evaluate(() => {
        localStorage.clear();
        sessionStorage.clear();
    });

    // Then proceed with normal beforeEach...
});
```

### Option 3: Separate Browser Contexts

Use completely fresh browser contexts for each test:

```typescript
test.beforeEach(async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    // ...
});

test.afterEach(async ({ page }) => {
    await page.context().close();
});
```

### Option 4: Skip Problematic Tests for Now

Mark tests 5-7 and 13-14 as `test.skip()` until we can identify and fix the root cause:

```typescript
test.skip('should toggle annotation visibility (Update operation)', async ({ page }) => {
    // ...
});
```

---

## Test Logic Issues Found

### Test 5: Visibility Toggle Assertion

**Issue:** The test expects visibility to toggle, but it remains `true`:

```typescript
// Initial: true
// After click: true
// Expected: Initial !== After
```

**Possible causes:**
1. Eye icon click not working
2. Click happening too fast (needs wait time)
3. Selector for eye icon is incorrect
4. Visibility toggle is not persisting state changes

**Fix needed:**
- Debug why eye icon click doesn't toggle visibility
- May need to wait for state update after click
- Verify the correct selector for eye icon

---

## Summary

✅ **Achievement:** Identified that authentication code works correctly
✅ **Achievement:** Proved the issue is test pollution, not authentication
❌ **Remaining:** Need to identify which test is polluting browser state
❌ **Remaining:** Need to fix test 5's visibility toggle logic

**Next Action:** Run incremental test combinations to identify the polluting test.

---

## Commands for Investigation

```bash
# Test authentication in isolation
npx playwright test test-isolation-and-crud --grep="should toggle annotation visibility \(Update operation\)" --workers=1

# Test incrementally to find polluter
npx playwright test test-isolation-and-crud --grep="should enter isolation mode" --workers=1
npx playwright test test-isolation-and-crud --grep="should enter isolation mode|should toggle annotation visibility \(Update" --workers=1

# Full suite for comparison
npx playwright test test-page2-interactions test-isolation-and-crud --project=chromium --reporter=line --workers=1
```

---

**Report Status:** Investigation complete - Ready for next phase of debugging
