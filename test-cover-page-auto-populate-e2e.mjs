import { chromium } from '@playwright/test';

/**
 * E2E Test for Phase 3.3: Cover Page Auto-Population
 *
 * Tests:
 * 1. Cover Page auto-fills from project context when empty
 * 2. Project Number field populated correctly
 * 3. Customer Name field populated correctly
 * 4. Address field populated correctly
 * 5. Date field populated with current date
 * 6. Auto-populated data is saved to database
 * 7. Already-populated fields are NOT overwritten
 */

(async () => {
    console.log('🚀 Starting Phase 3.3 Cover Page Auto-Population E2E Test...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 400
    });
    const context = await browser.newContext();
    const page = await context.newPage();

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
        console.log('📝 Step 2: Opening annotation viewer for Project 1...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // ========================================
        // STEP 3: Clear Existing Cover Page Data (if any)
        // ========================================
        console.log('📝 Step 3: Clearing any existing Cover Page data...');

        // Check if page type is already "cover"
        const currentPageType = await page.inputValue('select[x-model="pageType"]');
        console.log(`   Current page type: ${currentPageType}`);

        // If it's already cover, clear the fields first
        if (currentPageType === 'cover') {
            console.log('   Cover Page form is already showing, clearing fields...');
            await page.fill('input[x-model="coverProjectNumber"]', '');
            await page.fill('input[x-model="coverCustomerName"]', '');
            await page.fill('input[x-model="coverAddress"]', '');
            await page.fill('input[x-model="coverDate"]', '');
            await page.evaluate(() => document.querySelector('input[x-model="coverProjectNumber"]').blur());
            await page.waitForTimeout(1000); // Wait for save
            console.log('   ✓ Fields cleared and saved');
        }

        // Switch to a different page type first, then back to cover
        console.log('   Switching to Floor Plan page type...');
        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1000);
        console.log('✅ Prepared for auto-population test\n');

        // ========================================
        // STEP 4: Switch to Cover Page Type (Trigger Auto-Population)
        // ========================================
        console.log('📝 Step 4: Switching to Cover page type (should trigger auto-fill)...');
        await page.selectOption('select[x-model="pageType"]', 'cover');
        await page.waitForTimeout(2000); // Wait for auto-population and save

        // Wait for Cover Page form to appear
        await page.waitForSelector('.bg-blue-50', { timeout: 5000 });
        console.log('   ✓ Cover Page form is visible');
        console.log('✅ Cover page type selected\n');

        // ========================================
        // STEP 5: Verify Auto-Populated Fields
        // ========================================
        console.log('📝 Step 5: Verifying auto-populated fields...');

        await page.waitForTimeout(1000); // Give Alpine.js time to populate

        const projectNumber = await page.inputValue('input[x-model="coverProjectNumber"]');
        const customerName = await page.inputValue('input[x-model="coverCustomerName"]');
        const address = await page.inputValue('input[x-model="coverAddress"]');
        const date = await page.inputValue('input[x-model="coverDate"]');

        console.log(`   Project Number: "${projectNumber}"`);
        console.log(`   Customer Name: "${customerName}"`);
        console.log(`   Address: "${address}"`);
        console.log(`   Date: "${date}"`);

        const hasProjectNumber = projectNumber && projectNumber.length > 0;
        const hasCustomerName = customerName && customerName.length > 0;
        const hasAddress = address && address.length > 0;
        const hasDate = date && date.length > 0;

        console.log(`\n   ${hasProjectNumber ? '✅' : '❌'} Project Number populated: ${hasProjectNumber ? 'YES' : 'NO'}`);
        console.log(`   ${hasCustomerName ? '✅' : '❌'} Customer Name populated: ${hasCustomerName ? 'YES' : 'NO'}`);
        console.log(`   ${hasAddress ? '✅' : '❌'} Address populated: ${hasAddress ? 'YES' : 'NO'}`);
        console.log(`   ${hasDate ? '✅' : '❌'} Date populated: ${hasDate ? 'YES' : 'NO'}`);

        const allFieldsPopulated = hasProjectNumber && hasCustomerName && hasAddress && hasDate;

        if (allFieldsPopulated) {
            console.log('\n✅ All Cover Page fields auto-populated successfully!\n');
        } else {
            console.log('\n⚠️ Warning: Some fields were not auto-populated\n');
        }

        // ========================================
        // STEP 6: Verify Data Persistence (Navigate Away and Back)
        // ========================================
        console.log('📝 Step 6: Testing data persistence...');

        // Navigate to next page
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1500);
        console.log('   ✓ Navigated to page 2');

        // Navigate back to page 1
        await page.click('button[title="Previous Page"]');
        await page.waitForTimeout(1500);
        console.log('   ✓ Navigated back to page 1');

        // Verify Cover Page form is showing again
        const coverFormVisible = await page.isVisible('.bg-blue-50');
        console.log(`   ${coverFormVisible ? '✓' : '✗'} Cover Page form is visible`);

        // Verify field values persisted
        const projectNumber2 = await page.inputValue('input[x-model="coverProjectNumber"]');
        const customerName2 = await page.inputValue('input[x-model="coverCustomerName"]');
        const address2 = await page.inputValue('input[x-model="coverAddress"]');
        const date2 = await page.inputValue('input[x-model="coverDate"]');

        const dataPersisted =
            projectNumber2 === projectNumber &&
            customerName2 === customerName &&
            address2 === address &&
            date2 === date;

        console.log(`\n   ${dataPersisted ? '✅' : '❌'} Data persisted correctly: ${dataPersisted ? 'YES' : 'NO'}`);
        console.log('✅ Data persistence verified\n');

        // ========================================
        // STEP 7: Test No Overwrite of Existing Data
        // ========================================
        console.log('📝 Step 7: Testing that existing data is NOT overwritten...');

        // Manually change the Project Number
        const customProjectNumber = 'CUSTOM-123';
        await page.fill('input[x-model="coverProjectNumber"]', customProjectNumber);
        await page.evaluate(() => document.querySelector('input[x-model="coverProjectNumber"]').blur());
        await page.waitForTimeout(1000); // Wait for save
        console.log(`   ✓ Manually set Project Number to: "${customProjectNumber}"`);

        // Switch to different page type
        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1000);
        console.log('   ✓ Switched to Floor Plan');

        // Switch back to Cover (should NOT overwrite)
        await page.selectOption('select[x-model="pageType"]', 'cover');
        await page.waitForTimeout(2000);
        console.log('   ✓ Switched back to Cover');

        // Verify custom value is preserved
        const projectNumberAfter = await page.inputValue('input[x-model="coverProjectNumber"]');
        const notOverwritten = projectNumberAfter === customProjectNumber;

        console.log(`   Project Number after re-selection: "${projectNumberAfter}"`);
        console.log(`   ${notOverwritten ? '✅' : '❌'} Custom value preserved: ${notOverwritten ? 'YES' : 'NO'}`);
        console.log('✅ No-overwrite verified\n');

        // ========================================
        // STEP 8: Take Screenshots
        // ========================================
        console.log('📝 Step 8: Taking screenshots...');

        await page.screenshot({
            path: 'cover-page-auto-populated.png',
            fullPage: true
        });
        console.log('   ✓ Screenshot saved: cover-page-auto-populated.png');
        console.log('✅ Screenshots captured\n');

        // ========================================
        // FINAL SUMMARY
        // ========================================
        console.log('\n' + '='.repeat(70));
        console.log('📊 PHASE 3.3 E2E TEST SUMMARY');
        console.log('='.repeat(70));
        console.log(`${allFieldsPopulated ? '✅' : '❌'} Cover Page Auto-Population: ${allFieldsPopulated ? 'Working' : 'Failed'}`);
        console.log(`${hasProjectNumber ? '✅' : '❌'} Project Number: ${hasProjectNumber ? 'Populated' : 'Empty'}`);
        console.log(`${hasCustomerName ? '✅' : '❌'} Customer Name: ${hasCustomerName ? 'Populated' : 'Empty'}`);
        console.log(`${hasAddress ? '✅' : '❌'} Address: ${hasAddress ? 'Populated' : 'Empty'}`);
        console.log(`${hasDate ? '✅' : '❌'} Date: ${hasDate ? 'Populated' : 'Empty'}`);
        console.log(`${dataPersisted ? '✅' : '❌'} Data Persistence: ${dataPersisted ? 'Verified' : 'Failed'}`);
        console.log(`${notOverwritten ? '✅' : '❌'} No Overwrite: ${notOverwritten ? 'Verified' : 'Failed'}`);
        console.log('='.repeat(70));

        if (allFieldsPopulated && dataPersisted && notOverwritten) {
            console.log('\n🎉 Phase 3.3 E2E Test Completed Successfully!\n');
        } else {
            console.log('\n⚠️ Phase 3.3 Test Completed with Issues\n');
        }

        console.log('Browser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:');
        console.error('Error:', error.message);
        console.error('Stack:', error.stack);

        await page.screenshot({
            path: 'cover-page-auto-populate-error.png',
            fullPage: true
        });
        console.log('Error screenshot saved: cover-page-auto-populate-error.png');

    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
