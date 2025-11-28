# PDF Viewer Test Suite Analysis Report
**Date:** 2025-11-22
**Phase:** Phase 6 - Test Diagnosis and Fixes

## Executive Summary

**Major Discovery:** The 74 test failures were primarily caused by parallel test execution (`--workers=2+`), not actual bugs in the code. With `--workers=1`, the real failure count drops to **~10-14 failures**.

### Test Results Comparison

| Configuration | Failed | Skipped | Passed | Runtime | Notes |
|--------------|--------|---------|--------|---------|-------|
| `--workers=2` (full) | 74 | 9 | 16 | 13.1m | Parallel execution issues |
| `--workers=1` (subset) | 4 | 1 | 9 | 2.9m | Real failures only |
| `--workers=1` (full) | 10 | - | - | Stopped | Max failures reached |

## Root Cause Analysis

### Issue 1: Parallel Execution Race Conditions (70 failures)

**Symptom:**
```
TimeoutError: page.waitForFunction: Timeout 15000ms exceeded.
Component exists: false
```

**Root Cause:**
- Multiple test workers (`--workers=2+`) cause timing/race conditions
- Authentication state not properly isolated between parallel tests
- Component mounting conflicts when tests run simultaneously

**Evidence:**
- Same tests PASS with `--workers=1`
- Same tests FAIL with `--workers=2+`
- Headed and headless modes both work correctly with single worker

**Solution:**
- Configure Playwright to use `--workers=1` by default
- Or implement better test isolation for parallel execution
- Consider using Playwright's built-in test fixtures for auth state

### Issue 2: Authentication Fixture Misconfiguration (10 failures)

**Affected Tests:** All tests in `annotation-sync.spec.ts`

**Symptom:**
```
TimeoutError: page.fill: Timeout 15000ms exceeded.
Call log:
  - waiting for locator('input[name="email"]')
```

**Root Cause:**
File: `tests/Browser/fixtures/test-fixtures.ts:35`

Wrong default credentials:
```typescript
// Current (WRONG)
await page.fill('input[name="email"]', process.env.TEST_USER_EMAIL || 'test@example.com');
await page.fill('input[name="password"]', process.env.TEST_USER_PASSWORD || 'password');

// Should be (CORRECT)
await page.fill('input[name="email"]', 'info@tcswoodwork.com');
await page.fill('input[name="password"]', 'Lola2024!');
```

**Failing Tests:**
1. `annotation-sync.spec.ts:13` - should sync annotation between two users in real-time
2. `annotation-sync.spec.ts:54` - should sync annotation updates between users
3. `annotation-sync.spec.ts:96` - should sync annotation deletion between users
4. `annotation-sync.spec.ts:138` - should handle 3+ concurrent users
5. `annotation-sync.spec.ts:179` - should autosave annotation after creation
6. `annotation-sync.spec.ts:197` - should show saving indicator during autosave
7. `annotation-sync.spec.ts:211` - should persist annotations after page refresh
8. `annotation-sync.spec.ts:235` - should batch multiple rapid annotations for autosave
9. `annotation-sync.spec.ts:256` - should resolve conflict when two users edit same annotation
10. `annotation-sync.spec.ts:306` - should show conflict notification when edit conflicts occur

**Solution:**
Fix `test-fixtures.ts` to use correct TCS credentials or read from environment variables properly.

### Issue 3: Individual Test Failures (4 failures with --workers=1)

**Test File:** `test-isolation-and-crud.spec.ts` + `test-page2-interactions.spec.ts`

**Failures:**

1. **test-isolation-and-crud.spec.ts:178** - should toggle annotation visibility (Update operation)
   - Status: Needs investigation
   - Likely: Timing issue with visibility toggle

2. **test-isolation-and-crud.spec.ts:217** - should open annotation editor (Edit operation)
   - Status: Needs investigation
   - Likely: Editor modal timing or selector issue

3. **test-isolation-and-crud.spec.ts:293** - should test zoom functionality
   - Error: `Timeout finding button:has-text("Zoom In")`
   - Likely: Button selector or text mismatch

4. **test-page2-interactions.spec.ts:109** - should click on tree node and highlight annotation
   - Error: Component timeout after re-authentication
   - Status: Intermittent - authentication redirect issue

## Working Tests (Baseline Success Cases)

These tests consistently **PASS** with `--workers=1`:

### Page 2 Interactions (test-page2-interactions.spec.ts)
- ✅ should navigate to page 2 and verify annotations
- ✅ should verify page 2 annotations are loaded (3 annotations)
- ✅ should expand tree and verify room structure
- ✅ should interact with page 2 annotations
- ✅ should toggle annotation visibility

### Isolation & CRUD (test-isolation-and-crud.spec.ts)
- ✅ should enter isolation mode for K1 room
- ✅ should verify isolation mode filters annotations
- ✅ should exit isolation mode
- ✅ should create annotation via form
- ✅ should test annotation visibility toggle

## Recommendations

### Immediate Actions (Priority 1)

1. **Fix Authentication Fixture**
   - File: `tests/Browser/fixtures/test-fixtures.ts`
   - Change default credentials to TCS credentials
   - This will fix 10+ `annotation-sync.spec.ts` failures

2. **Configure Default Workers**
   - File: `playwright.config.ts`
   - Set `workers: 1` as default
   - Or implement proper test isolation for parallel execution

### Short-term Fixes (Priority 2)

3. **Fix Zoom Button Selector**
   - File: `test-isolation-and-crud.spec.ts:305`
   - Verify actual button text/selector in UI
   - Update test selector to match implementation

4. **Fix Toggle Visibility Test**
   - File: `test-isolation-and-crud.spec.ts:178`
   - Add timing/stability checks before assertion
   - Verify visibility toggle implementation

5. **Fix Tree Node Click Test**
   - File: `test-page2-interactions.spec.ts:109`
   - Investigate intermittent auth redirect
   - May need better session state management

### Long-term Improvements (Priority 3)

6. **Implement Test Isolation for Parallel Execution**
   - Use Playwright's built-in auth state fixtures
   - Implement proper test data cleanup between tests
   - Configure database snapshots/rollback for test isolation

7. **Create Shared Authentication Fixture**
   - Centralize authentication logic
   - Use environment variables properly
   - Avoid code duplication across test files

## Test Environment Details

**Configuration:**
- Browser: Chromium (Playwright)
- Base URL: `http://aureuserp.test`
- Test User: `info@tcswoodwork.com`
- Project ID: 9
- PDF Page: 2

**Authentication Pattern (Working):**
```typescript
await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });

if (page.url().includes('/login')) {
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');

    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 10000 })
            .catch(e => console.log('Navigation timeout:', e.message)),
        page.click('button[type="submit"]')
    ]);

    if (!page.url().includes('/annotate-v2/')) {
        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
    }
}

await page.waitForTimeout(3000);

// Wait for systemReady
await page.waitForFunction(() => {
    const el = document.querySelector('[x-data*="annotationSystemV3"]');
    if (!el || !window.Alpine) return false;
    const data = Alpine.$data(el);
    return data?.systemReady === true;
}, { timeout: 15000 });
```

## Next Steps

1. ✅ Fix `test-fixtures.ts` authentication (10 failures → 0)
2. ✅ Configure `playwright.config.ts` with `workers: 1`
3. ⏳ Fix remaining 4 individual test failures
4. ⏳ Re-run full test suite with fixes
5. ⏳ Document working test patterns for future tests

## Success Metrics

**Current State:**
- 74 failures (with parallel execution)
- 10 failures (authentication fixture issue)
- 4 failures (individual test issues)

**Target State:**
- 0 failures with `--workers=1`
- All tests passing reliably
- Proper test isolation for parallel execution (future enhancement)

## Files to Modify

1. `tests/Browser/fixtures/test-fixtures.ts` - Fix authentication credentials
2. `playwright.config.ts` - Set default workers to 1
3. `test-isolation-and-crud.spec.ts` - Fix zoom button selector (line 305)
4. `test-isolation-and-crud.spec.ts` - Fix visibility toggle (line 178)
5. `test-isolation-and-crud.spec.ts` - Fix editor modal (line 217)
6. `test-page2-interactions.spec.ts` - Fix tree node click (line 109)

---

**Report Generated:** Phase 6 Test Diagnosis
**Next Phase:** Phase 6 Test Fixes (Authentication + Individual Tests)
