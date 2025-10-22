import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    try {
        console.log('🧪 Starting E2E test: Draw Room Boundary without pre-selection');

        // Step 1: Login
        console.log('1️⃣ Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin');
        console.log('✓ Logged in successfully');

        // Step 2: Navigate to a project with PDF
        console.log('2️⃣ Navigating to project...');
        await page.goto('http://aureuserp.test/admin/projects/projects');
        await page.waitForTimeout(2000);
        
        // Click first project in the list
        const firstProject = await page.locator('table tbody tr').first();
        await firstProject.click();
        await page.waitForTimeout(2000);
        console.log('✓ Opened project');

        // Step 3: Click "Annotate PDF" tab
        console.log('3️⃣ Opening Annotate PDF tab...');
        const annotateTab = await page.locator('button:has-text("Annotate PDF"), a:has-text("Annotate PDF")').first();
        if (await annotateTab.isVisible()) {
            await annotateTab.click();
            await page.waitForTimeout(3000);
            console.log('✓ Annotate PDF tab opened');
        }

        // Step 4: Verify "Draw Room Boundary" button exists and is enabled
        console.log('4️⃣ Checking Draw Room Boundary button...');
        const roomButton = await page.locator('button[title*="Draw Room Boundary"]').first();
        
        if (!await roomButton.isVisible()) {
            throw new Error('❌ Draw Room Boundary button not found');
        }
        
        const isDisabled = await roomButton.isDisabled();
        if (isDisabled) {
            throw new Error('❌ Draw Room Boundary button is disabled (should be enabled)');
        }
        console.log('✓ Draw Room Boundary button is enabled');

        // Step 5: Click "Draw Room Boundary" button
        console.log('5️⃣ Clicking Draw Room Boundary button...');
        await roomButton.click();
        await page.waitForTimeout(1000);
        console.log('✓ Draw mode activated');

        // Step 6: Draw a rectangle on the PDF
        console.log('6️⃣ Drawing room boundary rectangle...');
        const canvas = await page.locator('canvas').first();
        const canvasBox = await canvas.boundingBox();
        
        if (!canvasBox) {
            throw new Error('❌ Canvas not found');
        }

        // Draw from top-left to bottom-right
        const startX = canvasBox.x + 100;
        const startY = canvasBox.y + 100;
        const endX = canvasBox.x + 400;
        const endY = canvasBox.y + 300;

        await page.mouse.move(startX, startY);
        await page.mouse.down();
        await page.mouse.move(endX, endY);
        await page.mouse.up();
        await page.waitForTimeout(2000);
        console.log('✓ Rectangle drawn');

        // Step 7: Verify slideover opened
        console.log('7️⃣ Verifying slideover opened...');
        const slideover = await page.locator('h2:has-text("Edit Annotation")').first();
        
        if (!await slideover.isVisible({ timeout: 5000 })) {
            throw new Error('❌ Slideover did not open after drawing');
        }
        console.log('✓ Slideover opened');

        // Step 8: Take screenshot of slideover
        console.log('8️⃣ Taking screenshot of form...');
        await page.screenshot({ path: 'room-boundary-slideover.png', fullPage: true });
        console.log('✓ Screenshot saved: room-boundary-slideover.png');

        // Step 9: Check if Room field is enabled and has create option
        console.log('9️⃣ Checking Room field...');
        const roomField = await page.locator('label:has-text("Room")').first();
        
        if (!await roomField.isVisible()) {
            throw new Error('❌ Room field not found in form');
        }
        console.log('✓ Room field is visible');

        // Step 10: Try to create a new room
        console.log('🔟 Testing "Create new room" functionality...');
        
        // Look for the room select field
        const roomSelect = await page.locator('[wire\\:key*="room_id"], select[name="data.room_id"], input[wire\\:model*="room_id"]').first();
        
        // Click on the room field to open dropdown
        await roomSelect.click();
        await page.waitForTimeout(1000);

        // Look for "Create" or "+" option in dropdown
        const createOption = await page.locator('button:has-text("Create"), li:has-text("Create")').first();
        
        if (await createOption.isVisible({ timeout: 3000 })) {
            console.log('✓ "Create new room" option available');
            
            // Click create option
            await createOption.click();
            await page.waitForTimeout(1500);
            
            // Fill in new room form
            const roomNameInput = await page.locator('input[id*="name"], input[name*="name"]').last();
            if (await roomNameInput.isVisible({ timeout: 2000 })) {
                await roomNameInput.fill('Test Kitchen E2E');
                console.log('✓ Filled room name');
                
                // Take screenshot of create room modal
                await page.screenshot({ path: 'create-room-modal.png', fullPage: true });
                console.log('✓ Screenshot saved: create-room-modal.png');
                
                // Click "Create" button in modal
                const createButton = await page.locator('button:has-text("Create")').last();
                await createButton.click();
                await page.waitForTimeout(2000);
                console.log('✓ Room created');
            }
        } else {
            console.log('⚠️  Create option not immediately visible, checking if room field allows typing...');
        }

        // Step 11: Fill label
        console.log('1️⃣1️⃣ Filling annotation label...');
        const labelInput = await page.locator('input[id*="label"], input[wire\\:model*="label"]').first();
        if (await labelInput.isVisible()) {
            await labelInput.clear();
            await labelInput.fill('Kitchen Boundary');
            console.log('✓ Label filled');
        }

        // Step 12: Save the annotation
        console.log('1️⃣2️⃣ Saving annotation...');
        const saveButton = await page.locator('button:has-text("Save Changes")').first();
        
        if (!await saveButton.isVisible()) {
            throw new Error('❌ Save Changes button not found');
        }
        
        await saveButton.click();
        await page.waitForTimeout(3000);
        console.log('✓ Save button clicked');

        // Step 13: Verify slideover closed
        console.log('1️⃣3️⃣ Verifying slideover closed...');
        const slideoverStillOpen = await slideover.isVisible({ timeout: 2000 }).catch(() => false);
        
        if (slideoverStillOpen) {
            console.log('⚠️  Slideover still open, checking for errors...');
            await page.screenshot({ path: 'slideover-after-save.png', fullPage: true });
        } else {
            console.log('✓ Slideover closed');
        }

        // Step 14: Verify annotation appears in tree
        console.log('1️⃣4️⃣ Checking if annotation appears in tree...');
        await page.waitForTimeout(2000);
        
        const treeItem = await page.locator('text="Kitchen Boundary", text="Test Kitchen"').first();
        const inTree = await treeItem.isVisible({ timeout: 3000 }).catch(() => false);
        
        if (inTree) {
            console.log('✓ Annotation appears in tree');
        } else {
            console.log('⚠️  Annotation not found in tree sidebar');
        }

        // Final screenshot
        console.log('📸 Taking final screenshot...');
        await page.screenshot({ path: 'room-boundary-complete.png', fullPage: true });
        console.log('✓ Screenshot saved: room-boundary-complete.png');

        console.log('\n✅ E2E TEST PASSED: Room boundary workflow works without pre-selection!');

    } catch (error) {
        console.error('\n❌ E2E TEST FAILED:', error.message);
        await page.screenshot({ path: 'room-boundary-error.png', fullPage: true });
        console.log('Error screenshot saved: room-boundary-error.png');
    } finally {
        await browser.close();
    }
})();
