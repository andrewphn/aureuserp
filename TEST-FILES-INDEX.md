# Drawer Hardware Spec Test - Files Index

## Test Reports

### 1. DRAWER-HARDWARE-SPEC-TEST-FINAL-REPORT.md
**Primary comprehensive test report**
- Detailed test execution results
- Critical findings and URL corrections
- Technical observations
- Manual testing checklist
- Complete route reference
- Recommendations and next steps
- **USE THIS:** For complete test documentation

### 2. DRAWER-HARDWARE-TEST-SUMMARY.md  
**Quick reference summary**
- Executive summary of results
- Quick next steps
- File locations
- Automation status
- **USE THIS:** For quick status updates

### 3. TEST-FILES-INDEX.md
**This file** - Index of all test artifacts

---

## Test Scripts

### Recommended Scripts

#### test-drawer-corrected.mjs ✅ RECOMMENDED
- **Status:** Working with correct URLs
- **Purpose:** Automated navigation to Inventory → Products → Attributes
- **URLs:** Uses correct `/admin/inventory/` paths
- **Run:** `node test-drawer-corrected.mjs`

### Alternative Scripts

#### drawer-hardware-test-simple.mjs
- **Status:** Running in background (PID: 95179)
- **Purpose:** Periodic screenshot capture for documentation
- **Feature:** Takes screenshot every 15 seconds
- **Run:** `node drawer-hardware-test-simple.mjs`

#### test-drawer-hardware-spec.mjs
- **Status:** Has URL errors (uses wrong paths)
- **Purpose:** Original automated test attempt
- **Issue:** Uses `/admin/products/` instead of `/admin/inventory/`
- **Use:** Reference only - DO NOT RUN

---

## Screenshots Directories

### test-screenshots-drawer-corrected/ ✅ CURRENT
**Latest test run with corrected URLs**

Files:
- `01-login.png` - Login page
- `02-dashboard.png` - Dashboard after auth
- `ERROR.png` - Actually shows successful Attributes page load (20 attributes visible)

**Location:** `/Users/andrewphan/tcsadmin/aureuserp/test-screenshots-drawer-corrected/`

### test-screenshots-drawer-hardware/
**First test run - has 404 errors due to incorrect URLs**

Files:
- `01-login-page.png` through `05-manual-test.png`
- Shows 404 errors on products/attributes pages
- Useful for documenting the URL discovery process

**Location:** `/Users/andrewphan/tcsadmin/aureuserp/test-screenshots-drawer-hardware/`

---

## Verified Correct URLs

```
✅ Products:           /admin/inventory/products
✅ Attributes:         /admin/inventory/configurations/product-attributes
✅ Attribute Create:   /admin/inventory/configurations/product-attributes/create
✅ Product Categories: /admin/inventory/configurations/product-categories
✅ Projects:           /admin/projects/projects
```

## Incorrect URLs (Do Not Use)

```
❌ /admin/products/products
❌ /admin/products/attributes
```

---

## How to Use These Files

### For Quick Status Check
1. Read: `DRAWER-HARDWARE-TEST-SUMMARY.md`
2. View screenshots in: `test-screenshots-drawer-corrected/`

### For Complete Test Review
1. Read: `DRAWER-HARDWARE-SPEC-TEST-FINAL-REPORT.md`
2. Review manual testing checklist
3. Execute manual steps listed in report

### For Running New Tests
1. Use script: `test-drawer-corrected.mjs`
2. Update URLs if routes change
3. Save screenshots to new directory

### For Continuing Manual Testing
1. Navigate to: http://aureuserp.test/admin/inventory/configurations/product-attributes/create
2. Create attributes: Slide Length, Total Width Clearance
3. Create product: Blum LEGRABOX with specs
4. Test Spec Builder auto-calculation
5. Document results in test report

---

## Test Environment

- **Application URL:** http://aureuserp.test/admin
- **Login:** info@tcswoodwork.com / Lola2024!
- **Browser:** Chromium (Playwright 1.55.1)
- **Framework:** FilamentPHP v3
- **Test Date:** December 24, 2025

---

## Background Processes

Two test browsers currently running for documentation:

```bash
# Check running tests
ps aux | grep "node.*drawer"

# View test 1 output
tail -f drawer-test-output.log

# View test 2 output
tail -f drawer-test-corrected.log

# Stop tests
killall node
```

---

## Next Steps

1. Review `DRAWER-HARDWARE-TEST-SUMMARY.md` for quick overview
2. Follow manual testing checklist in `DRAWER-HARDWARE-SPEC-TEST-FINAL-REPORT.md`
3. Create required attributes and products
4. Test Spec Builder auto-calculation feature
5. Document results

---

**All files located in:**
`/Users/andrewphan/tcsadmin/aureuserp/`

**Open directory:**
```bash
cd /Users/andrewphan/tcsadmin/aureuserp/
open .
```
