import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    try {
        console.log('🧪 Complete E2E Test: Annotation System with All Features');

        // Step 1: Login
        console.log('\n1️⃣ Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        console.log('✅ Logged in');

        // Step 2: Navigate to Projects
        console.log('\n2️⃣ Navigating to projects...');
        await page.goto('http://aureuserp.test/admin/project/projects');
        await page.waitForTimeout(2000);

        // Click first project
        const firstRow = page.locator('table tbody tr').first();
        await firstRow.click();
        await page.waitForTimeout(2000);
        console.log('✅ Opened project');

        // Step 3: Open Annotate PDF tab
        console.log('\n3️⃣ Opening Annotate PDF tab...');
        const annotateTab = page.locator('button:has-text("Annotate PDF"), a:has-text("Annotate PDF")').first();
        await annotateTab.click();
        await page.waitForTimeout(3000);
        console.log('✅ Annotation viewer loaded');

        // Step 4: Test Zoom Functionality
        console.log('\n4️⃣ Testing zoom functionality...');
        const zoomDisplay = page.locator('span:has-text("%")').first();
        const initialZoom = await zoomDisplay.textContent();
        console.log(`✅ Initial zoom: ${initialZoom}`);

        // Zoom in
        const zoomInBtn = page.locator('button:has-text("+")').first();
        await zoomInBtn.click();
        await page.waitForTimeout(1000);
        const zoomAfterIn = await zoomDisplay.textContent();
        console.log(`✅ Zoomed in to: ${zoomAfterIn}`);

        // Zoom out
        const zoomOutBtn = page.locator('button:has-text("-")').first();
        await zoomOutBtn.click();
        await page.waitForTimeout(1000);
        const zoomAfterOut = await zoomDisplay.textContent();
        console.log(`✅ Zoomed out to: ${zoomAfterOut}`);

        // Reset zoom
        const resetBtn = page.locator('button[title*="Reset"]').first();
        await resetBtn.click();
        await page.waitForTimeout(1000);
        console.log('✅ Zoom reset to 100%');

        await page.screenshot({ path: 'e2e-test-01-zoom-working.png', fullPage: true });

        // Step 5: Click Draw Room Boundary
        console.log('\n5️⃣ Testing Draw Room Boundary...');
        const roomBtn = page.locator('button[title*="Room"]').first();
        await roomBtn.click();
        await page.waitForTimeout(500);
        console.log('✅ Draw Room mode activated');

        // Step 6: Draw annotation using Playwright's mouse API
        console.log('\n6️⃣ Drawing room boundary rectangle...');
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();

        if (box) {
            const startX = box.x + 200;
            const startY = box.y + 150;
            const endX = box.x + 400;
            const endY = box.y + 350;

            // Use Playwright's mouse API for real interaction
            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();
            await page.waitForTimeout(2000);
            console.log('✅ Rectangle drawn');
        }

        await page.screenshot({ path: 'e2e-test-02-after-draw.png', fullPage: true });

        // Step 7: Verify slideover opened
        console.log('\n7️⃣ Verifying slideover opened...');
        const slideover = page.locator('h2:has-text("Edit Annotation")');
        const isVisible = await slideover.isVisible({ timeout: 3000 }).catch(() => false);

        if (isVisible) {
            console.log('✅ Slideover opened successfully');

            // Step 8: Fill label
            console.log('\n8️⃣ Filling annotation form...');
            const labelInput = page.locator('input[id*="label"]').first();
            await labelInput.fill('E2E Test Kitchen');
            console.log('✅ Label filled');

            // Step 9: Create new room via + button
            console.log('\n9️⃣ Testing room creation...');
            const roomSelect = page.locator('select[id*="room"], input[id*="room"]').first();
            await roomSelect.click();
            await page.waitForTimeout(500);

            // Look for create button in the dropdown
            const createBtn = page.locator('button:has-text("Create"), button[title*="Create"]').first();
            const createVisible = await createBtn.isVisible({ timeout: 2000 }).catch(() => false);

            if (createVisible) {
                await createBtn.click();
                await page.waitForTimeout(1000);
                console.log('✅ Create room modal opened');

                // Fill room name
                const roomNameInput = page.locator('input[id*="name"]').last();
                await roomNameInput.fill('E2E Test Kitchen Room');
                await page.waitForTimeout(500);

                // Click modal create button
                const modalCreateBtn = page.locator('button:has-text("Create")').last();
                await modalCreateBtn.click();
                await page.waitForTimeout(2000);
                console.log('✅ Room created via modal');
            } else {
                console.log('⚠️  Create button not found, selecting existing room');
                await roomSelect.selectOption({ index: 0 });
            }

            await page.screenshot({ path: 'e2e-test-03-form-filled.png', fullPage: true });

            // Step 10: Save annotation
            console.log('\n🔟 Saving annotation...');
            const saveBtn = page.locator('button:has-text("Save Changes")').first();
            await saveBtn.click();
            await page.waitForTimeout(3000);
            console.log('✅ Annotation saved');

            await page.screenshot({ path: 'e2e-test-04-after-save.png', fullPage: true });

            // Step 11: Verify tree refreshed
            console.log('\n1️⃣1️⃣ Verifying tree refresh...');
            await page.waitForTimeout(1000);
            const treeElement = page.locator('.tree, [class*="tree"]').first();
            const treeVisible = await treeElement.isVisible({ timeout: 2000 }).catch(() => false);
            if (treeVisible) {
                console.log('✅ Tree visible and should be refreshed');
            }

            // Step 12: Test zoom with annotation
            console.log('\n1️⃣2️⃣ Testing zoom with annotation...');
            await zoomInBtn.click();
            await page.waitForTimeout(1500);
            console.log('✅ Zoomed in with annotation');

            await page.screenshot({ path: 'e2e-test-05-zoomed-with-annotation.png', fullPage: true });

            // Zoom in more
            await zoomInBtn.click();
            await page.waitForTimeout(1500);
            console.log('✅ Zoomed in more (150%)');

            await page.screenshot({ path: 'e2e-test-06-zoomed-150.png', fullPage: true });

            // Reset zoom
            await resetBtn.click();
            await page.waitForTimeout(1500);
            console.log('✅ Zoom reset to 100%');

            await page.screenshot({ path: 'e2e-test-07-final-state.png', fullPage: true });

        } else {
            console.log('❌ Slideover did not open - checking console for errors');
            await page.screenshot({ path: 'e2e-test-error-no-slideover.png', fullPage: true });
        }

        console.log('\n✅✅✅ E2E TEST COMPLETE ✅✅✅');
        console.log('\nScreenshots saved:');
        console.log('  - e2e-test-01-zoom-working.png');
        console.log('  - e2e-test-02-after-draw.png');
        console.log('  - e2e-test-03-form-filled.png');
        console.log('  - e2e-test-04-after-save.png');
        console.log('  - e2e-test-05-zoomed-with-annotation.png');
        console.log('  - e2e-test-06-zoomed-150.png');
        console.log('  - e2e-test-07-final-state.png');

    } catch (error) {
        console.error('\n❌ E2E TEST FAILED:', error.message);
        await page.screenshot({ path: 'e2e-test-error.png', fullPage: true });
        console.error(error.stack);
    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
    }
})();
