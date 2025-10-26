import { chromium } from 'playwright';

/**
 * Test the simplified annotation editor
 *
 * Tests:
 * 1. Page loads without errors
 * 2. Annotation editor modal opens
 * 3. Single focused "Annotation" tab exists
 * 4. Read-only entity summary displays
 * 5. "Edit Details →" links present
 * 6. "Save & Next" button exists
 * 7. No nested tabs present
 */

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    try {
        console.log('🚀 Starting annotation editor test...\n');

        // Step 1: Login
        console.log('Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/**');
        console.log('✅ Logged in successfully\n');

        // Step 2: Navigate to annotation page
        console.log('Step 2: Navigating to PDF annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        console.log('✅ Page loaded\n');

        // Take screenshot of initial page
        await page.screenshot({ path: 'test-annotation-page-loaded.png', fullPage: true });
        console.log('📸 Screenshot saved: test-annotation-page-loaded.png\n');

        // Step 3: Check for JavaScript errors
        const errors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        // Step 4: Look for existing annotations and click one
        console.log('Step 3: Looking for annotations to edit...');

        // Wait for PDF canvas to render
        await page.waitForSelector('canvas', { timeout: 10000 });
        console.log('✅ PDF canvas loaded\n');

        // Look for annotation rectangles (SVG or canvas-based)
        const annotationExists = await page.locator('[data-annotation-id]').count() > 0;

        if (annotationExists) {
            console.log('Step 4: Clicking on first annotation...');
            await page.locator('[data-annotation-id]').first().click();
            await page.waitForTimeout(1000);
        } else {
            console.log('⚠️  No existing annotations found, cannot test editor modal\n');
            console.log('Please create an annotation first to test the editor.\n');
            await browser.close();
            return;
        }

        // Step 5: Check if annotation editor modal opened
        console.log('Step 5: Checking if annotation editor modal opened...');
        const modalVisible = await page.locator('text=Edit Annotation').isVisible({ timeout: 5000 });

        if (!modalVisible) {
            console.log('❌ Annotation editor modal did NOT open\n');
            await page.screenshot({ path: 'test-annotation-modal-not-opened.png', fullPage: true });
            await browser.close();
            return;
        }
        console.log('✅ Annotation editor modal opened\n');

        // Take screenshot of opened modal
        await page.screenshot({ path: 'test-annotation-editor-opened.png', fullPage: true });
        console.log('📸 Screenshot saved: test-annotation-editor-opened.png\n');

        // Step 6: Verify single focused "Annotation" tab (no Entity Details tab)
        console.log('Step 6: Checking for simplified tab structure...');

        const annotationTabExists = await page.locator('text=Annotation').count() > 0;
        const entityDetailsTabExists = await page.locator('text=Entity Details').count() > 0;

        if (annotationTabExists && !entityDetailsTabExists) {
            console.log('✅ Single "Annotation" tab present (no nested Entity Details tab)\n');
        } else if (entityDetailsTabExists) {
            console.log('❌ FAILED: "Entity Details" tab still exists (should be removed)\n');
        } else {
            console.log('⚠️  No tabs found\n');
        }

        // Step 7: Check for read-only entity summary
        console.log('Step 7: Checking for read-only entity summary...');
        const entitySummaryExists = await page.locator('text=Linked Entity').count() > 0;

        if (entitySummaryExists) {
            console.log('✅ "Linked Entity" summary section present\n');
        } else {
            console.log('⚠️  No "Linked Entity" summary found (might not be linked to entity)\n');
        }

        // Step 8: Check for "Edit Details →" links
        console.log('Step 8: Checking for "Edit Details →" links...');
        const editDetailsLinkExists = await page.locator('text=/Edit Details/').count() > 0;

        if (editDetailsLinkExists) {
            console.log('✅ "Edit Details →" link present\n');
        } else {
            console.log('ℹ️  No "Edit Details →" link (annotation might not be linked to entity)\n');
        }

        // Step 9: Check for "Save & Next" button
        console.log('Step 9: Checking for "Save & Next" button...');
        const saveAndNextExists = await page.locator('text=Save & Next').count() > 0;

        if (saveAndNextExists) {
            console.log('✅ "Save & Next" button present\n');
        } else {
            console.log('❌ FAILED: "Save & Next" button NOT found\n');
        }

        // Step 10: Verify NO nested tabs exist
        console.log('Step 10: Verifying NO nested tabs (Room Info, Contents, etc.)...');
        const nestedTabsExist = await page.locator('text=/Room Info|Location Info|Run Info|Cabinet Info|Contents|Context/').count() > 0;

        if (!nestedTabsExist) {
            console.log('✅ No nested tabs found (correctly simplified)\n');
        } else {
            console.log('❌ FAILED: Nested tabs still exist (should be removed)\n');
        }

        // Step 11: Check console errors
        console.log('Step 11: Checking for JavaScript errors...');
        if (errors.length === 0) {
            console.log('✅ No JavaScript errors detected\n');
        } else {
            console.log('❌ JavaScript errors found:');
            errors.forEach(err => console.log('   -', err));
            console.log('');
        }

        // Final screenshot
        await page.screenshot({ path: 'test-annotation-editor-final.png', fullPage: true });
        console.log('📸 Final screenshot saved: test-annotation-editor-final.png\n');

        console.log('='.repeat(60));
        console.log('✅ TEST COMPLETE\n');
        console.log('Summary:');
        console.log(`  - Page loaded: ✅`);
        console.log(`  - Modal opened: ${modalVisible ? '✅' : '❌'}`);
        console.log(`  - Single tab structure: ${annotationTabExists && !entityDetailsTabExists ? '✅' : '❌'}`);
        console.log(`  - Entity summary: ${entitySummaryExists ? '✅' : 'N/A'}`);
        console.log(`  - Edit Details link: ${editDetailsLinkExists ? '✅' : 'N/A'}`);
        console.log(`  - Save & Next button: ${saveAndNextExists ? '✅' : '❌'}`);
        console.log(`  - No nested tabs: ${!nestedTabsExist ? '✅' : '❌'}`);
        console.log(`  - No JS errors: ${errors.length === 0 ? '✅' : '❌'}`);
        console.log('='.repeat(60));

    } catch (error) {
        console.error('❌ Test failed with error:', error.message);
        await page.screenshot({ path: 'test-annotation-editor-error.png', fullPage: true });
        console.log('📸 Error screenshot saved: test-annotation-editor-error.png');
    } finally {
        await browser.close();
    }
})();
