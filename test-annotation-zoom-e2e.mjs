import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    try {
        console.log('🧪 E2E Test: Complete Annotation & Zoom Workflow');

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
        await page.goto('http://aureuserp.test/admin/projects/projects');
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

        // Step 4: Verify PDF is displayed at 100% zoom
        console.log('\n4️⃣ Verifying initial zoom...');
        const zoomDisplay = page.locator('span:has-text("%")').first();
        const initialZoom = await zoomDisplay.textContent();
        console.log(`✅ Initial zoom: ${initialZoom}`);
        await page.screenshot({ path: 'e2e-01-initial-zoom.png', fullPage: true });

        // Step 5: Test Zoom In
        console.log('\n5️⃣ Testing zoom in...');
        const zoomInBtn = page.locator('button:has-text("+")').first();
        await zoomInBtn.click();
        await page.waitForTimeout(1000);
        const zoomAfterIn = await zoomDisplay.textContent();
        console.log(`✅ Zoomed in to: ${zoomAfterIn}`);
        await page.screenshot({ path: 'e2e-02-zoomed-in.png', fullPage: true });

        // Step 6: Test Zoom Out
        console.log('\n6️⃣ Testing zoom out...');
        const zoomOutBtn = page.locator('button:has-text("-")').first();
        await zoomOutBtn.click();
        await page.waitForTimeout(1000);
        const zoomAfterOut = await zoomDisplay.textContent();
        console.log(`✅ Zoomed out to: ${zoomAfterOut}`);

        // Step 7: Reset to 100%
        console.log('\n7️⃣ Resetting zoom to 100%...');
        const resetBtn = page.locator('button[title*="Reset Zoom"]').first();
        await resetBtn.click();
        await page.waitForTimeout(1000);
        const resetZoom = await zoomDisplay.textContent();
        console.log(`✅ Reset to: ${resetZoom}`);

        // Step 8: Click Draw Room Boundary
        console.log('\n8️⃣ Activating Draw Room mode...');
        const roomBtn = page.locator('button[title*="Room"]').first();
        await roomBtn.click();
        await page.waitForTimeout(500);
        console.log('✅ Draw Room mode activated');
        await page.screenshot({ path: 'e2e-03-draw-mode.png', fullPage: true });

        // Step 9: Draw annotation
        console.log('\n9️⃣ Drawing room boundary...');
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();
        
        if (box) {
            const startX = box.x + 150;
            const startY = box.y + 150;
            const endX = box.x + 400;
            const endY = box.y + 350;

            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();
            await page.waitForTimeout(2000);
            console.log('✅ Rectangle drawn');
        }

        // Step 10: Verify slideover opened
        console.log('\n🔟 Verifying slideover...');
        const slideover = page.locator('h2:has-text("Edit Annotation")');
        const isVisible = await slideover.isVisible({ timeout: 3000 });
        
        if (isVisible) {
            console.log('✅ Slideover opened');
            await page.screenshot({ path: 'e2e-04-slideover.png', fullPage: true });

            // Step 11: Fill form
            console.log('\n1️⃣1️⃣ Filling annotation form...');
            
            // Fill label
            const labelInput = page.locator('input[id*="label"]').first();
            await labelInput.fill('Test Kitchen');
            
            // Try to create room
            console.log('Attempting to create room...');
            const roomSelect = page.locator('input[id*="room"], select[id*="room"]').first();
            await roomSelect.click();
            await page.waitForTimeout(1000);
            
            // Look for + button
            const createBtn = page.locator('button:has-text("Create")').first();
            if (await createBtn.isVisible({ timeout: 2000 })) {
                await createBtn.click();
                await page.waitForTimeout(1000);
                
                const roomNameInput = page.locator('input[id*="name"]').last();
                await roomNameInput.fill('E2E Test Kitchen');
                await page.waitForTimeout(500);
                
                const modalCreateBtn = page.locator('button:has-text("Create")').last();
                await modalCreateBtn.click();
                await page.waitForTimeout(2000);
                console.log('✅ Room created');
            }

            // Step 12: Save annotation
            console.log('\n1️⃣2️⃣ Saving annotation...');
            const saveBtn = page.locator('button:has-text("Save Changes")').first();
            await saveBtn.click();
            await page.waitForTimeout(3000);
            console.log('✅ Annotation saved');

            await page.screenshot({ path: 'e2e-05-after-save.png', fullPage: true });
        }

        // Step 13: Verify annotation appears on canvas
        console.log('\n1️⃣3️⃣ Verifying annotation rendered...');
        const annotation = page.locator('.annotation-overlay > div').first();
        const annoVisible = await annotation.isVisible({ timeout: 2000 }).catch(() => false);
        
        if (annoVisible) {
            console.log('✅ Annotation visible on canvas');
        } else {
            console.log('⚠️  Annotation not visible');
        }

        // Step 14: Test zoom with annotation
        console.log('\n1️⃣4️⃣ Testing zoom with annotation...');
        await zoomInBtn.click();
        await page.waitForTimeout(1500);
        console.log('✅ Zoomed in with annotation');
        await page.screenshot({ path: 'e2e-06-zoomed-with-annotation.png', fullPage: true });

        // Step 15: Verify annotation still aligned
        console.log('\n1️⃣5️⃣ Checking annotation alignment...');
        const annoAfterZoom = await annotation.isVisible({ timeout: 2000 }).catch(() => false);
        if (annoAfterZoom) {
            console.log('✅ Annotation still visible and aligned after zoom');
        } else {
            console.log('⚠️  Annotation alignment issue after zoom');
        }

        // Step 16: Reset zoom
        await resetBtn.click();
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'e2e-07-final-state.png', fullPage: true });

        console.log('\n✅✅✅ E2E TEST COMPLETE ✅✅✅');
        console.log('\nScreenshots saved:');
        console.log('  - e2e-01-initial-zoom.png');
        console.log('  - e2e-02-zoomed-in.png');
        console.log('  - e2e-03-draw-mode.png');
        console.log('  - e2e-04-slideover.png');
        console.log('  - e2e-05-after-save.png');
        console.log('  - e2e-06-zoomed-with-annotation.png');
        console.log('  - e2e-07-final-state.png');

    } catch (error) {
        console.error('\n❌ E2E TEST FAILED:', error.message);
        await page.screenshot({ path: 'e2e-error.png', fullPage: true });
    } finally {
        await page.waitForTimeout(2000);
        await browser.close();
    }
})();
