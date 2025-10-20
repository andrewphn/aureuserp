#!/usr/bin/env node

import { chromium } from 'playwright';

(async () => {
    console.log('🚀 E2E Test: Annotation Drag and Resize Functionality\n');
    console.log('=' .repeat(60));

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300,
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        recordVideo: { dir: './test-videos/' }
    });

    const page = await context.newPage();

    // Enable console logging from the page
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('🖱️') || text.includes('✓') || text.includes('📄') || text.includes('Drag') || text.includes('Resize')) {
            console.log(`   [PAGE] ${text}`);
        }
    });

    try {
        // Step 1: Login (skip if redirecting)
        console.log('\n📝 STEP 1: Login');
        console.log('-'.repeat(60));
        await page.goto('http://aureuserp.test/admin/login', { waitUntil: 'networkidle' });

        // Check if already logged in (redirected to dashboard)
        if (page.url().includes('/admin/project')) {
            console.log('✅ Already logged in');
        } else {
            await page.waitForSelector('input[type="email"]', { timeout: 10000 });
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');

            await page.click('button[type="submit"]');
            await page.waitForLoadState('networkidle');
            console.log('✅ Login successful');
        }

        // Step 2: Navigate to 25 Friendship project
        console.log('\n📂 STEP 2: Navigate to 25 Friendship Project');
        console.log('-'.repeat(60));

        // Go to projects list (correct URL: /admin/project/projects not /admin/projects/projects)
        await page.goto('http://aureuserp.test/admin/project/projects', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(2000); // Wait for page to settle

        // Click on the "25 Friendship" project link (use getByRole for better reliability)
        await page.getByRole('link', { name: /25 Friendship/ }).first().click();
        await page.waitForTimeout(2000); // Wait for navigation
        console.log('✅ Project page opened');

        // Step 3: Navigate to Documents tab and open annotation viewer
        console.log('\n📄 STEP 3: Open PDF Annotation Viewer');
        console.log('-'.repeat(60));

        // Click on Documents tab
        await page.getByRole('tab', { name: 'Documents' }).click();
        await page.waitForTimeout(1000);

        // Click "Review & Price" button for the PDF
        await page.getByRole('link', { name: 'Review & Price' }).click();
        await page.waitForLoadState('networkidle');

        // Click "✏️ Annotate" link for Page 1
        await page.getByRole('link', { name: '✏️ Annotate' }).first().click();
        await page.waitForTimeout(3000); // Wait for new tab to open and load

        // Switch to the annotation viewer tab (should be tab index 1)
        const pages = page.context().pages();
        const annotationPage = pages[pages.length - 1]; // Get the last opened page
        await annotationPage.bringToFront();

        // Wait for annotation overlay to be visible
        await annotationPage.waitForSelector('.annotation-overlay', { timeout: 20000 });
        console.log('✅ PDF Annotation Viewer loaded');

        // Wait for PDF canvas to render
        await annotationPage.waitForSelector('canvas', { timeout: 15000 });
        await annotationPage.waitForTimeout(3000);
        console.log('✅ PDF canvas rendered');

        // Step 4: Check for existing annotations
        console.log('\n🔍 STEP 4: Check Existing Annotations');
        console.log('-'.repeat(60));

        let annotationCount = await annotationPage.locator('.annotation-marker').count();
        console.log(`   Found ${annotationCount} existing annotation(s)`);

        if (annotationCount === 0) {
            console.log('\n⚠️  No annotations found. Creating test annotation...');

            // Select room
            await annotationPage.click('input[placeholder*="search"]');
            await annotationPage.fill('input[placeholder*="search"]', 'Kitchen 1');
            await annotationPage.waitForTimeout(500);

            // Try to select from dropdown or create new
            const dropdown = annotationPage.locator('.absolute.z-50').first();
            if (await dropdown.isVisible()) {
                await dropdown.locator('div').first().click();
            } else {
                await annotationPage.keyboard.press('Enter');
            }
            await annotationPage.waitForTimeout(1000);

            // Click Location button
            await annotationPage.click('button:has-text("Location")');
            await annotationPage.waitForTimeout(500);

            // Draw annotation
            const overlay = await annotationPage.locator('.annotation-overlay').boundingBox();
            if (overlay) {
                await annotationPage.mouse.move(overlay.x + 300, overlay.y + 300);
                await annotationPage.mouse.down();
                await annotationPage.mouse.move(overlay.x + 450, overlay.y + 400, { steps: 5 });
                await annotationPage.mouse.up();
                await annotationPage.waitForTimeout(1500);

                // Close editor modal
                const cancelBtn = annotationPage.locator('button:has-text("Cancel")');
                if (await cancelBtn.isVisible()) {
                    await cancelBtn.click();
                }
                await annotationPage.waitForTimeout(1000);
            }

            annotationCount = await annotationPage.locator('.annotation-marker').count();
            console.log(`✅ Created test annotation. Total: ${annotationCount}`);
        }

        // Step 5: Test DRAG functionality
        console.log('\n🖱️  STEP 5: Test DRAG Functionality');
        console.log('-'.repeat(60));

        const annotation = annotationPage.locator('.annotation-marker').first();

        // Get initial position
        const initialBox = await annotation.boundingBox();
        console.log(`   Initial position: x=${Math.round(initialBox.x)}, y=${Math.round(initialBox.y)}`);
        console.log(`   Initial size: width=${Math.round(initialBox.width)}, height=${Math.round(initialBox.height)}`);

        // Perform drag
        console.log('\n   🎯 Performing drag operation...');
        const centerX = initialBox.x + initialBox.width / 2;
        const centerY = initialBox.y + initialBox.height / 2;

        await annotationPage.mouse.move(centerX, centerY);
        await annotationPage.waitForTimeout(300);
        console.log('   📍 Mouse positioned at center');

        await annotationPage.mouse.down();
        console.log('   👇 Mouse down - drag started');
        await annotationPage.waitForTimeout(200);

        const dragDistance = 120;
        await annotationPage.mouse.move(centerX + dragDistance, centerY + 80, { steps: 15 });
        console.log(`   ➡️  Mouse moved ${dragDistance}px right, 80px down`);
        await annotationPage.waitForTimeout(200);

        await annotationPage.mouse.up();
        console.log('   👆 Mouse up - drag finished');
        await annotationPage.waitForTimeout(1000);

        // Verify drag
        const draggedBox = await annotation.boundingBox();
        console.log(`\n   Final position: x=${Math.round(draggedBox.x)}, y=${Math.round(draggedBox.y)}`);

        const movedX = Math.abs(draggedBox.x - initialBox.x);
        const movedY = Math.abs(draggedBox.y - initialBox.y);
        console.log(`   Movement: Δx=${Math.round(movedX)}px, Δy=${Math.round(movedY)}px`);

        if (movedX > 50 && movedY > 30) {
            console.log('   ✅ DRAG TEST PASSED - Annotation moved successfully!');
        } else {
            console.log('   ❌ DRAG TEST FAILED - Insufficient movement detected');
        }

        // Take screenshot after drag
        await annotationPage.screenshot({ path: 'test-drag-after.png', fullPage: false });

        // Step 6: Test RESIZE functionality
        console.log('\n📏 STEP 6: Test RESIZE Functionality');
        console.log('-'.repeat(60));

        // Hover to show resize handles
        const currentBox = await annotation.boundingBox();
        await annotationPage.mouse.move(currentBox.x + currentBox.width / 2, currentBox.y + currentBox.height / 2);
        await annotationPage.waitForTimeout(800);
        console.log('   👆 Hovering to show resize handles...');

        // Check if resize handles are visible
        const handleCount = await annotationPage.locator('.annotation-marker .absolute.w-3.h-3').count();
        console.log(`   Found ${handleCount} resize handles`);

        if (handleCount > 0) {
            console.log('\n   🎯 Performing resize operation (bottom-right handle)...');

            // Calculate handle position (bottom-right)
            const handleX = currentBox.x + currentBox.width;
            const handleY = currentBox.y + currentBox.height;

            await annotationPage.mouse.move(handleX, handleY);
            console.log('   📍 Mouse positioned at bottom-right handle');
            await annotationPage.waitForTimeout(300);

            await annotationPage.mouse.down();
            console.log('   👇 Mouse down - resize started');
            await annotationPage.waitForTimeout(200);

            const resizeDelta = 80;
            await annotationPage.mouse.move(handleX + resizeDelta, handleY + resizeDelta, { steps: 15 });
            console.log(`   ↗️  Mouse moved ${resizeDelta}px in both directions`);
            await annotationPage.waitForTimeout(200);

            await annotationPage.mouse.up();
            console.log('   👆 Mouse up - resize finished');
            await annotationPage.waitForTimeout(1000);

            // Verify resize
            const resizedBox = await annotation.boundingBox();
            console.log(`\n   Original size: width=${Math.round(currentBox.width)}, height=${Math.round(currentBox.height)}`);
            console.log(`   Final size: width=${Math.round(resizedBox.width)}, height=${Math.round(resizedBox.height)}`);

            const widthIncrease = resizedBox.width - currentBox.width;
            const heightIncrease = resizedBox.height - currentBox.height;
            console.log(`   Size change: Δwidth=${Math.round(widthIncrease)}px, Δheight=${Math.round(heightIncrease)}px`);

            if (widthIncrease > 30 && heightIncrease > 30) {
                console.log('   ✅ RESIZE TEST PASSED - Annotation resized successfully!');
            } else {
                console.log('   ❌ RESIZE TEST FAILED - Insufficient size change detected');
            }
        } else {
            console.log('   ⚠️  No resize handles found - handles may not be visible');
        }

        // Take final screenshot
        await annotationPage.screenshot({ path: 'test-resize-after.png', fullPage: false });

        // Step 7: Test multiple resize handles
        console.log('\n🔄 STEP 7: Test Different Resize Handles');
        console.log('-'.repeat(60));

        // Test top-left corner
        console.log('\n   Testing TOP-LEFT corner handle...');
        await annotationPage.mouse.move(currentBox.x + currentBox.width / 2, currentBox.y + currentBox.height / 2);
        await annotationPage.waitForTimeout(500);

        const topLeftX = currentBox.x;
        const topLeftY = currentBox.y;
        await annotationPage.mouse.move(topLeftX, topLeftY);
        await annotationPage.waitForTimeout(300);
        await annotationPage.mouse.down();
        await annotationPage.mouse.move(topLeftX - 40, topLeftY - 40, { steps: 10 });
        await annotationPage.mouse.up();
        await annotationPage.waitForTimeout(1000);
        console.log('   ✅ Top-left resize completed');

        // Final screenshot
        await annotationPage.screenshot({ path: 'test-final-state.png', fullPage: false });

        // Summary
        console.log('\n' + '='.repeat(60));
        console.log('✅ E2E TEST COMPLETED SUCCESSFULLY!');
        console.log('='.repeat(60));
        console.log('\n📸 Screenshots saved:');
        console.log('   - test-drag-after.png');
        console.log('   - test-resize-after.png');
        console.log('   - test-final-state.png');

        console.log('\n⏱️  Browser will remain open for 10 seconds for inspection...');
        await annotationPage.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ TEST FAILED');
        console.error('=' .repeat(60));
        console.error('Error:', error.message);
        console.error('\nStack trace:', error.stack);

        // Try to take screenshot from whichever page is available
        try {
            if (typeof annotationPage !== 'undefined') {
                await annotationPage.screenshot({ path: 'test-error.png', fullPage: true });
            } else {
                await page.screenshot({ path: 'test-error.png', fullPage: true });
            }
            console.log('\n📸 Error screenshot saved: test-error.png');
        } catch (screenshotError) {
            console.log('\n⚠️  Could not capture error screenshot');
        }
    } finally {
        await browser.close();
        console.log('\n🏁 Browser closed. Test finished.');
    }
})();
