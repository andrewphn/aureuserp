#!/usr/bin/env node

import { chromium } from 'playwright';

(async () => {
    console.log('🚀 Testing Annotation Drag and Resize Functionality...\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[name="email"]', 'info@tcswoodwork.com');
        await page.fill('input[name="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin', { timeout: 10000 });
        console.log('✅ Logged in successfully\n');

        // Step 2: Navigate to Projects
        console.log('📂 Step 2: Navigating to Projects...');
        await page.goto('http://aureuserp.test/admin/projects/projects');
        await page.waitForLoadState('networkidle');
        console.log('✅ Projects page loaded\n');

        // Step 3: Find and click on "25 Friendship" project
        console.log('🔍 Step 3: Looking for "25 Friendship" project...');
        const projectRow = await page.locator('tr').filter({ hasText: '25 Friendship' }).first();
        if (await projectRow.count() === 0) {
            throw new Error('Could not find "25 Friendship" project');
        }

        // Click the view button (eye icon) in the actions column
        await projectRow.locator('button[title="View"]').first().click();
        await page.waitForTimeout(2000);
        console.log('✅ Opened project view\n');

        // Step 4: Open PDF Annotation Viewer
        console.log('📄 Step 4: Opening PDF Annotation Viewer...');

        // Look for the "Review & Price PDF" tab or button
        const reviewButton = page.locator('text=Review').or(page.locator('text=PDF')).or(page.locator('text=Annotation')).first();
        if (await reviewButton.count() > 0) {
            await reviewButton.click();
            await page.waitForTimeout(2000);
        }

        // Wait for the annotation viewer to be visible
        await page.waitForSelector('.annotation-overlay', { timeout: 15000 });
        console.log('✅ PDF Annotation Viewer opened\n');

        // Step 5: Wait for PDF to load
        console.log('⏳ Step 5: Waiting for PDF to load...');
        await page.waitForSelector('canvas', { timeout: 15000 });
        await page.waitForTimeout(2000);
        console.log('✅ PDF loaded\n');

        // Step 6: Check if annotations exist
        console.log('🔍 Step 6: Checking for existing annotations...');
        const annotationCount = await page.locator('.annotation-marker').count();
        console.log(`   Found ${annotationCount} existing annotations\n`);

        if (annotationCount === 0) {
            console.log('⚠️  No annotations found. Creating a test annotation first...\n');

            // Select a room first
            console.log('📍 Selecting a room...');
            await page.click('input[placeholder="Type to search or create..."]');
            await page.fill('input[placeholder="Type to search or create..."]', 'Test Kitchen');
            await page.waitForTimeout(500);
            await page.keyboard.press('Enter');
            await page.waitForTimeout(500);

            // Click Location draw button
            console.log('🎨 Clicking Location draw button...');
            await page.click('button:has-text("Location")');
            await page.waitForTimeout(500);

            // Draw an annotation
            console.log('✏️  Drawing a test annotation...');
            const overlay = await page.locator('.annotation-overlay').boundingBox();
            if (overlay) {
                const startX = overlay.x + 200;
                const startY = overlay.y + 200;
                const endX = startX + 150;
                const endY = startY + 100;

                await page.mouse.move(startX, startY);
                await page.mouse.down();
                await page.mouse.move(endX, endY);
                await page.mouse.up();
                await page.waitForTimeout(1000);

                // Close the edit modal if it appears
                const cancelButton = page.locator('button:has-text("Cancel")');
                if (await cancelButton.count() > 0) {
                    await cancelButton.click();
                    await page.waitForTimeout(500);
                }
            }
        }

        // Step 7: Test Drag Functionality
        console.log('🖱️  Step 7: Testing DRAG functionality...');
        const firstAnnotation = page.locator('.annotation-marker').first();

        if (await firstAnnotation.count() > 0) {
            const beforeBox = await firstAnnotation.boundingBox();
            console.log(`   Annotation position BEFORE drag: x=${Math.round(beforeBox.x)}, y=${Math.round(beforeBox.y)}`);

            // Drag the annotation
            await page.mouse.move(beforeBox.x + beforeBox.width / 2, beforeBox.y + beforeBox.height / 2);
            await page.mouse.down();
            await page.mouse.move(beforeBox.x + beforeBox.width / 2 + 100, beforeBox.y + beforeBox.height / 2 + 50, { steps: 10 });
            await page.mouse.up();
            await page.waitForTimeout(1000);

            const afterBox = await firstAnnotation.boundingBox();
            console.log(`   Annotation position AFTER drag: x=${Math.round(afterBox.x)}, y=${Math.round(afterBox.y)}`);

            const movedX = Math.abs(afterBox.x - beforeBox.x) > 50;
            const movedY = Math.abs(afterBox.y - beforeBox.y) > 20;

            if (movedX && movedY) {
                console.log('✅ DRAG works! Annotation moved successfully\n');
            } else {
                console.log('❌ DRAG failed - annotation did not move as expected\n');
            }
        }

        // Step 8: Test Resize Functionality
        console.log('📏 Step 8: Testing RESIZE functionality...');

        // Hover over the annotation to show resize handles
        const annotation = page.locator('.annotation-marker').first();
        if (await annotation.count() > 0) {
            const box = await annotation.boundingBox();

            // Hover to show handles
            await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
            await page.waitForTimeout(500);

            console.log(`   Annotation size BEFORE resize: width=${Math.round(box.width)}, height=${Math.round(box.height)}`);

            // Try to grab the bottom-right resize handle
            const handleX = box.x + box.width;
            const handleY = box.y + box.height;

            await page.mouse.move(handleX, handleY);
            await page.mouse.down();
            await page.mouse.move(handleX + 50, handleY + 30, { steps: 10 });
            await page.mouse.up();
            await page.waitForTimeout(1000);

            const afterResizeBox = await annotation.boundingBox();
            console.log(`   Annotation size AFTER resize: width=${Math.round(afterResizeBox.width)}, height=${Math.round(afterResizeBox.height)}`);

            const widthIncreased = afterResizeBox.width > box.width + 20;
            const heightIncreased = afterResizeBox.height > box.height + 10;

            if (widthIncreased && heightIncreased) {
                console.log('✅ RESIZE works! Annotation resized successfully\n');
            } else {
                console.log('❌ RESIZE failed - annotation did not resize as expected\n');
            }
        }

        // Step 9: Take screenshots
        console.log('📸 Step 9: Taking screenshots...');
        await page.screenshot({ path: 'annotation-drag-resize-test.png', fullPage: true });
        console.log('✅ Screenshot saved: annotation-drag-resize-test.png\n');

        console.log('✅ All tests completed!\n');
        console.log('Browser will stay open for 10 seconds so you can inspect...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        await page.screenshot({ path: 'annotation-drag-resize-error.png', fullPage: true });
        console.log('Error screenshot saved: annotation-drag-resize-error.png');
    } finally {
        await browser.close();
        console.log('✅ Browser closed');
    }
})();
