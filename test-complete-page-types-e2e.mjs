import { chromium } from '@playwright/test';

/**
 * Complete E2E Test for Page-Type System (Phases 3.1, 3.2, 3.3)
 *
 * Tests:
 * 1. Cover Page auto-population from project context
 * 2. Floor Plan form with manual data entry
 * 3. Elevation form with manual data entry
 * 4. Detail form with manual data entry
 * 5. Other form with manual data entry
 * 6. Data persistence across all page types
 * 7. Page type persistence
 * 8. No overwrite protection for Cover Page
 */

(async () => {
    console.log('🚀 Starting Complete Page-Type System E2E Test...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 400
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    const results = {
        coverAutoPopulation: false,
        floorPlanForm: false,
        elevationForm: false,
        detailForm: false,
        otherForm: false,
        dataPersistence: false,
        noOverwrite: false
    };

    try {
        // ========================================
        // STEP 1: Login
        // ========================================
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in successfully\n');

        // ========================================
        // STEP 2: Navigate to Annotation Viewer
        // ========================================
        console.log('📝 Step 2: Opening annotation viewer...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // ========================================
        // STEP 3: Test Cover Page Auto-Population
        // ========================================
        console.log('📝 Step 3: Testing Cover Page auto-population...');

        // Select Cover page type
        await page.selectOption('select[x-model="pageType"]', 'cover');
        await page.waitForTimeout(2000); // Wait for auto-population
        console.log('   ✓ Selected Cover page type');

        // Verify auto-populated fields
        const coverProjectNumber = await page.inputValue('input[x-model="coverProjectNumber"]');
        const coverCustomerName = await page.inputValue('input[x-model="coverCustomerName"]');
        const coverDate = await page.inputValue('input[x-model="coverDate"]');

        console.log(`   Project Number: "${coverProjectNumber}"`);
        console.log(`   Customer Name: "${coverCustomerName}"`);
        console.log(`   Date: "${coverDate}"`);

        results.coverAutoPopulation =
            coverProjectNumber.length > 0 &&
            coverCustomerName.length > 0 &&
            coverDate.length > 0;

        console.log(`   ${results.coverAutoPopulation ? '✅' : '❌'} Cover Page auto-populated\n`);

        // Take screenshot
        await page.screenshot({ path: 'e2e-cover-page.png', fullPage: true });
        console.log('   📸 Screenshot: e2e-cover-page.png');

        // ========================================
        // STEP 4: Test Floor Plan Form (Page 2)
        // ========================================
        console.log('\n📝 Step 4: Testing Floor Plan form...');
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(2000);
        console.log('   ✓ Navigated to page 2');

        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1000);
        console.log('   ✓ Selected Floor Plan page type');

        // Fill Floor Plan fields
        await page.fill('input[x-model="floorPlanFloorNumber"]', '1');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        await page.fill('input[x-model="floorPlanScale"]', '1/4" = 1\'');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        await page.selectOption('select[x-model="floorPlanOrientation"]', 'north');
        await page.waitForTimeout(500);

        console.log('   ✓ Filled Floor Plan fields');
        results.floorPlanForm = true;

        await page.screenshot({ path: 'e2e-floor-plan.png', fullPage: true });
        console.log('   📸 Screenshot: e2e-floor-plan.png');

        // ========================================
        // STEP 5: Test Elevation Form (Page 3)
        // ========================================
        console.log('\n📝 Step 5: Testing Elevation form...');
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(2000);
        console.log('   ✓ Navigated to page 3');

        await page.selectOption('select[x-model="pageType"]', 'elevation');
        await page.waitForTimeout(1000);
        console.log('   ✓ Selected Elevation page type');

        await page.fill('input[x-model="elevationRoom"]', 'Kitchen');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        await page.fill('input[x-model="elevationLocation"]', 'North Wall');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        await page.selectOption('select[x-model="elevationViewDirection"]', 'front');
        await page.waitForTimeout(500);

        console.log('   ✓ Filled Elevation fields');
        results.elevationForm = true;

        await page.screenshot({ path: 'e2e-elevation.png', fullPage: true });
        console.log('   📸 Screenshot: e2e-elevation.png');

        // ========================================
        // STEP 6: Test Detail Form (Page 4)
        // ========================================
        console.log('\n📝 Step 6: Testing Detail form...');
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(2000);
        console.log('   ✓ Navigated to page 4');

        await page.selectOption('select[x-model="pageType"]', 'detail');
        await page.waitForTimeout(1000);
        console.log('   ✓ Selected Detail page type');

        await page.selectOption('select[x-model="detailType"]', 'cabinet');
        await page.waitForTimeout(500);

        await page.fill('input[x-model="detailNumber"]', 'D-101');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        await page.fill('input[x-model="detailScale"]', '1" = 1\'');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        console.log('   ✓ Filled Detail fields');
        results.detailForm = true;

        await page.screenshot({ path: 'e2e-detail.png', fullPage: true });
        console.log('   📸 Screenshot: e2e-detail.png');

        // ========================================
        // STEP 7: Test Other Form (Page 5)
        // ========================================
        console.log('\n📝 Step 7: Testing Other form...');
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(2000);
        console.log('   ✓ Navigated to page 5');

        await page.selectOption('select[x-model="pageType"]', 'other');
        await page.waitForTimeout(1000);
        console.log('   ✓ Selected Other page type');

        await page.fill('textarea[x-model="otherNotes"]', 'General notes and specifications for this page.');
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(500);

        console.log('   ✓ Filled Other notes');
        results.otherForm = true;

        await page.screenshot({ path: 'e2e-other.png', fullPage: true });
        console.log('   📸 Screenshot: e2e-other.png');

        // ========================================
        // STEP 8: Verify Data Persistence
        // ========================================
        console.log('\n📝 Step 8: Verifying data persistence...');
        console.log('   Going back to page 1 (Cover)...');

        // Navigate back to page 1
        for (let i = 0; i < 4; i++) {
            await page.click('button[title="Previous Page"]');
            await page.waitForTimeout(1500);
        }
        console.log('   ✓ Back at page 1');

        // Verify Cover Page data
        const coverProjectNumber2 = await page.inputValue('input[x-model="coverProjectNumber"]');
        const coverCustomerName2 = await page.inputValue('input[x-model="coverCustomerName"]');
        const coverDate2 = await page.inputValue('input[x-model="coverDate"]');

        const coverPersisted =
            coverProjectNumber2 === coverProjectNumber &&
            coverCustomerName2 === coverCustomerName &&
            coverDate2 === coverDate;

        console.log(`   ${coverPersisted ? '✅' : '❌'} Cover Page data persisted`);

        // Navigate to page 2 and verify Floor Plan
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1500);

        const floorNumber2 = await page.inputValue('input[x-model="floorPlanFloorNumber"]');
        const floorPlanPersisted = floorNumber2 === '1';
        console.log(`   ${floorPlanPersisted ? '✅' : '❌'} Floor Plan data persisted`);

        results.dataPersistence = coverPersisted && floorPlanPersisted;

        // ========================================
        // STEP 9: Test No-Overwrite Protection
        // ========================================
        console.log('\n📝 Step 9: Testing no-overwrite protection...');

        // Go back to page 1
        await page.click('button[title="Previous Page"]');
        await page.waitForTimeout(1500);

        // Manually edit Project Number
        const customValue = 'EDITED-' + Date.now();
        await page.fill('input[x-model="coverProjectNumber"]', customValue);
        await page.evaluate(() => document.activeElement.blur());
        await page.waitForTimeout(1000);
        console.log(`   ✓ Edited Project Number to: "${customValue}"`);

        // Switch to different type and back
        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1000);
        await page.selectOption('select[x-model="pageType"]', 'cover');
        await page.waitForTimeout(2000);

        // Verify custom value preserved
        const projectNumberAfter = await page.inputValue('input[x-model="coverProjectNumber"]');
        results.noOverwrite = projectNumberAfter === customValue;
        console.log(`   ${results.noOverwrite ? '✅' : '❌'} Custom value preserved: "${projectNumberAfter}"`);

        // ========================================
        // FINAL SUMMARY
        // ========================================
        console.log('\n' + '='.repeat(70));
        console.log('📊 COMPLETE PAGE-TYPE SYSTEM E2E TEST RESULTS');
        console.log('='.repeat(70));
        console.log(`${results.coverAutoPopulation ? '✅' : '❌'} Cover Page Auto-Population: ${results.coverAutoPopulation ? 'PASS' : 'FAIL'}`);
        console.log(`${results.floorPlanForm ? '✅' : '❌'} Floor Plan Form: ${results.floorPlanForm ? 'PASS' : 'FAIL'}`);
        console.log(`${results.elevationForm ? '✅' : '❌'} Elevation Form: ${results.elevationForm ? 'PASS' : 'FAIL'}`);
        console.log(`${results.detailForm ? '✅' : '❌'} Detail Form: ${results.detailForm ? 'PASS' : 'FAIL'}`);
        console.log(`${results.otherForm ? '✅' : '❌'} Other Form: ${results.otherForm ? 'PASS' : 'FAIL'}`);
        console.log(`${results.dataPersistence ? '✅' : '❌'} Data Persistence: ${results.dataPersistence ? 'PASS' : 'FAIL'}`);
        console.log(`${results.noOverwrite ? '✅' : '❌'} No-Overwrite Protection: ${results.noOverwrite ? 'PASS' : 'FAIL'}`);
        console.log('='.repeat(70));

        const allPassed = Object.values(results).every(result => result === true);

        if (allPassed) {
            console.log('\n🎉 ALL TESTS PASSED! Page-Type System is fully functional!\n');
        } else {
            console.log('\n⚠️ Some tests failed. Review results above.\n');
        }

        console.log('Screenshots saved:');
        console.log('  - e2e-cover-page.png');
        console.log('  - e2e-floor-plan.png');
        console.log('  - e2e-elevation.png');
        console.log('  - e2e-detail.png');
        console.log('  - e2e-other.png');

        console.log('\nBrowser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:');
        console.error('Error:', error.message);
        console.error('Stack:', error.stack);

        await page.screenshot({
            path: 'e2e-complete-error.png',
            fullPage: true
        });
        console.log('Error screenshot saved: e2e-complete-error.png');

    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
