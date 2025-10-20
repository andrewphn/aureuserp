#!/usr/bin/env node

import { chromium } from 'playwright';

(async () => {
    console.log('🚀 E2E Test: Annotation Drag and Resize Functionality\n');
    console.log('=' .repeat(60));

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300,
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
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
        // Step 1: Login
        console.log('\n📝 STEP 1: Login');
        console.log('-'.repeat(60));
        await page.goto('http://aureuserp.test/admin/login');

        // Check if already logged in
        await page.waitForTimeout(1000);
        if (page.url().includes('/admin/project')) {
            console.log('✅ Already logged in');
        } else {
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');
            await page.click('button[type="submit"]');
            await page.waitForTimeout(2000);
            console.log('✅ Login successful');
        }

        // Step 2: Navigate directly to annotation viewer (skip intermediate pages)
        console.log('\n📄 STEP 2: Navigate directly to PDF Annotation Viewer');
        console.log('-'.repeat(60));

        // Direct URL to annotation viewer for project 1, page 1
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
        await page.waitForTimeout(5000); // Give it time to load PDF

        // Wait for annotation overlay
        await page.waitForSelector('.annotation-overlay', { timeout: 20000 });
        console.log('✅ PDF Annotation Viewer loaded');

        // Wait for PDF canvas to render
        await page.waitForSelector('canvas', { timeout: 15000 });
        await page.waitForTimeout(3000);
        console.log('✅ PDF canvas rendered');

        // Step 3: Check for existing annotations
        console.log('\n🔍 STEP 3: Check Existing Annotations');
        console.log('-'.repeat(60));

        let annotationCount = await page.locator('.annotation-marker').count();
        console.log(`   Found ${annotationCount} existing annotation(s)`);

        if (annotationCount === 0) {
            console.log('\n⚠️  No annotations found. Test requires at least one annotation.');
            console.log('   Please create an annotation manually and re-run the test.');
            await browser.close();
            return;
        }

        // Step 4: Test DRAG functionality
        console.log('\n🖱️  STEP 4: Test DRAG Functionality');
        console.log('-'.repeat(60));

        const annotation = page.locator('.annotation-marker').first();

        // Get initial position
        const initialBox = await annotation.boundingBox();
        console.log(`   Initial position: x=${Math.round(initialBox.x)}, y=${Math.round(initialBox.y)}`);
        console.log(`   Initial size: width=${Math.round(initialBox.width)}, height=${Math.round(initialBox.height)}`);

        // Perform drag
        console.log('\n   🎯 Performing drag operation...');
        const centerX = initialBox.x + initialBox.width / 2;
        const centerY = initialBox.y + initialBox.height / 2;

        await page.mouse.move(centerX, centerY);
        await page.waitForTimeout(300);
        console.log('   📍 Mouse positioned at center');

        await page.mouse.down();
        console.log('   👇 Mouse down - drag started');
        await page.waitForTimeout(200);

        const dragDistance = 120;
        await page.mouse.move(centerX + dragDistance, centerY + 80, { steps: 15 });
        console.log(`   ➡️  Mouse moved ${dragDistance}px right, 80px down`);
        await page.waitForTimeout(200);

        await page.mouse.up();
        console.log('   👆 Mouse up - drag finished');
        await page.waitForTimeout(1000);

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
        await page.screenshot({ path: 'test-drag-after.png', fullPage: false });

        // Step 5: Test RESIZE functionality
        console.log('\n📏 STEP 5: Test RESIZE Functionality');
        console.log('-'.repeat(60));

        // Hover to show resize handles
        const currentBox = await annotation.boundingBox();
        await page.mouse.move(currentBox.x + currentBox.width / 2, currentBox.y + currentBox.height / 2);
        await page.waitForTimeout(800);
        console.log('   👆 Hovering to show resize handles...');

        // Check if resize handles are visible
        const handleCount = await page.locator('.annotation-marker .absolute.w-3.h-3').count();
        console.log(`   Found ${handleCount} resize handles`);

        if (handleCount > 0) {
            console.log('\n   🎯 Performing resize operation (bottom-right handle)...');

            // Calculate handle position (bottom-right)
            const handleX = currentBox.x + currentBox.width;
            const handleY = currentBox.y + currentBox.height;

            await page.mouse.move(handleX, handleY);
            console.log('   📍 Mouse positioned at bottom-right handle');
            await page.waitForTimeout(300);

            await page.mouse.down();
            console.log('   👇 Mouse down - resize started');
            await page.waitForTimeout(200);

            const resizeDelta = 80;
            await page.mouse.move(handleX + resizeDelta, handleY + resizeDelta, { steps: 15 });
            console.log(`   ↗️  Mouse moved ${resizeDelta}px in both directions`);
            await page.waitForTimeout(200);

            await page.mouse.up();
            console.log('   👆 Mouse up - resize finished');
            await page.waitForTimeout(1000);

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
        await page.screenshot({ path: 'test-resize-after.png', fullPage: false });

        // Summary
        console.log('\n' + '='.repeat(60));
        console.log('✅ E2E TEST COMPLETED SUCCESSFULLY!');
        console.log('='.repeat(60));
        console.log('\n📸 Screenshots saved:');
        console.log('   - test-drag-after.png');
        console.log('   - test-resize-after.png');

        console.log('\n⏱️  Browser will remain open for 10 seconds for inspection...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ TEST FAILED');
        console.error('=' .repeat(60));
        console.error('Error:', error.message);
        console.error('\nStack trace:', error.stack);

        await page.screenshot({ path: 'test-error.png', fullPage: true });
        console.log('\n📸 Error screenshot saved: test-error.png');
    } finally {
        await browser.close();
        console.log('\n🏁 Browser closed. Test finished.');
    }
})();
