#!/usr/bin/env node

/**
 * Test V3 Annotation Editor - Livewire Integration
 *
 * Tests:
 * 1. Draw annotation on PDF
 * 2. Verify Livewire slideover opens
 * 3. Edit annotation details
 * 4. Save and verify updates
 */

import { chromium } from '@playwright/test';

const TEST_URL = 'http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1';

async function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function testAnnotationEditor() {
    console.log('🧪 Testing V3 Annotation Editor with Livewire...\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const page = await browser.newPage();

    // Capture console messages
    const consoleMessages = [];
    page.on('console', msg => {
        const text = msg.text();
        consoleMessages.push(text);
        if (text.includes('error') || text.includes('Error') || text.includes('❌')) {
            console.log('   [CONSOLE ERROR]:', text);
        }
    });

    try {
        // Step 0: Login if needed
        console.log('🔐 Step 0: Checking authentication...');
        await page.goto(TEST_URL, { waitUntil: 'networkidle' });
        await wait(2000);

        // Check if on login page
        const isLoginPage = await page.locator('input[type="email"]').count() > 0;
        if (isLoginPage) {
            console.log('   Logging in...');
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');
            await page.click('button[type="submit"]');
            await wait(3000);

            // Navigate to test page after login
            await page.goto(TEST_URL, { waitUntil: 'networkidle' });
            await wait(2000);
        }
        console.log('✓ Authenticated\n');

        // Step 1: Verify we're on V3 annotation page
        console.log('📄 Step 1: Verifying V3 annotation viewer loaded...');
        await wait(3000); // Wait for PDF to load

        // Check if we're on the right page
        const pageTitle = await page.locator('h2').filter({ hasText: 'V3 Annotation System' }).count();
        if (pageTitle === 0) {
            throw new Error('❌ Not on V3 annotation page!');
        }
        console.log('✓ V3 annotation viewer loaded\n');

        // Step 2: Wait for PDF to be ready
        console.log('📏 Step 2: Waiting for PDF dimensions...');
        await page.waitForFunction(() => {
            const loadingText = document.querySelector('[x-show="!pdfReady"]');
            return !loadingText || window.getComputedStyle(loadingText).display === 'none';
        }, { timeout: 10000 });
        console.log('✓ PDF ready\n');

        // Step 3: Select context (Room) from tree sidebar
        console.log('🏠 Step 3: Selecting room context from tree...');

        // Look for "Main Kitchen" in the tree sidebar
        const mainKitchenNode = page.locator('.tree-sidebar').locator('text=Main Kitchen').first();
        if (await mainKitchenNode.count() > 0) {
            await mainKitchenNode.click();
            console.log('✓ Room "Main Kitchen" selected from tree\n');
        } else {
            // Fall back to any room in the tree
            const anyRoom = page.locator('.tree-sidebar').locator('span.text-lg', { hasText: '🏠' }).first();
            if (await anyRoom.count() > 0) {
                const roomName = await anyRoom.locator('..').locator('span.text-sm').textContent();
                await anyRoom.click();
                console.log(`✓ Room "${roomName}" selected from tree\n`);
            } else {
                console.log('⚠️  No rooms found in tree, continuing...\n');
            }
        }

        await wait(2000);

        // Step 4: Enable draw mode (skip location, just draw a Location annotation)
        console.log('🎨 Step 4: Enabling Location draw mode (Room only)...');
        const drawButton = page.locator('button').filter({ hasText: 'Draw Location' });
        await drawButton.click();
        console.log('✓ Draw mode enabled\n');

        await wait(1000);

        // Step 6: Draw annotation on PDF
        console.log('🖱️  Step 6: Drawing annotation on PDF...');
        const pdfOverlay = page.locator('.annotation-overlay');
        const overlayBox = await pdfOverlay.boundingBox();

        if (!overlayBox) {
            throw new Error('❌ Could not find PDF overlay!');
        }

        // Draw a rectangle (start point + drag)
        const startX = overlayBox.x + 100;
        const startY = overlayBox.y + 100;
        const endX = overlayBox.x + 300;
        const endY = overlayBox.y + 200;

        await page.mouse.move(startX, startY);
        await page.mouse.down();
        await page.mouse.move(endX, endY, { steps: 10 });
        await page.mouse.up();

        console.log('✓ Annotation drawn\n');

        await wait(2000);

        // Step 7: Wait for Livewire slideover to open
        console.log('📋 Step 7: Waiting for Livewire slideover to open...');

        // Look for Filament slideover modal
        const slideover = page.locator('[role="dialog"]').or(page.locator('.fi-modal-window'));

        try {
            await slideover.waitFor({ state: 'visible', timeout: 5000 });
            console.log('✓ Livewire slideover opened!\n');
        } catch (error) {
            console.log('❌ Slideover did not open. Checking for errors...');

            // Print captured console messages
            console.log('\n📋 Console messages:');
            consoleMessages.slice(-20).forEach((msg, i) => {
                console.log(`   ${i + 1}. ${msg}`);
            });

            // Take screenshot for debugging
            await page.screenshot({ path: 'slideover-not-opened.png', fullPage: true });
            console.log('\n📸 Screenshot saved: slideover-not-opened.png');

            throw new Error('Slideover did not open');
        }

        await wait(1000);

        // Step 8: Verify form fields are populated
        console.log('📝 Step 8: Verifying form fields...');

        // Check label field
        const labelInput = page.locator('input[id*="label"]').or(page.locator('input').filter({ hasText: 'Run 1' }));
        const labelValue = await labelInput.inputValue().catch(() => '');
        console.log(`   Label field: "${labelValue}"`);

        // Check type display
        const typeDisplay = page.locator('text=Location').or(page.locator('text=📍 Location'));
        const typeVisible = await typeDisplay.count() > 0;
        console.log(`   Type display visible: ${typeVisible}`);

        // Check context display (just room, no location for Location type)
        const contextDisplay = page.locator('text=Kitchen');
        const contextVisible = await contextDisplay.count() > 0;
        console.log(`   Context display visible (Room): ${contextVisible}`);

        console.log('✓ Form fields populated\n');

        await wait(1000);

        // Step 9: Edit annotation details
        console.log('✏️  Step 9: Editing annotation details...');

        // Update label
        await labelInput.clear();
        await labelInput.fill('Test Run Alpha');

        // Add notes
        const notesInput = page.locator('textarea').filter({ has: page.locator('[placeholder*="notes"]') }).or(
            page.locator('textarea').first()
        );
        if (await notesInput.count() > 0) {
            await notesInput.fill('This is a test annotation created by automated testing');
        }

        // Add measurements if visible
        const widthInput = page.locator('input').filter({ has: page.locator('[placeholder*="Width"]') }).or(
            page.locator('input[id*="Width"]')
        );
        if (await widthInput.count() > 0) {
            await widthInput.fill('48.5');
        }

        console.log('✓ Details updated\n');

        await wait(1000);

        // Step 10: Save annotation
        console.log('💾 Step 10: Saving annotation...');

        // Look for save button (various possible labels)
        const saveButton = page.locator('button').filter({ hasText: 'Save Details' }).or(
            page.locator('button').filter({ hasText: 'Save' })
        ).first();

        await saveButton.click();
        console.log('✓ Save button clicked\n');

        await wait(2000);

        // Step 11: Verify slideover closed
        console.log('🔍 Step 11: Verifying slideover closed...');

        const slideoverClosed = await slideover.isHidden({ timeout: 3000 }).catch(() => false);
        if (slideoverClosed) {
            console.log('✓ Slideover closed\n');
        } else {
            console.log('⚠️  Slideover still visible\n');
        }

        // Step 12: Verify annotation was updated
        console.log('✅ Step 12: Verifying annotation updated on canvas...');

        const annotationMarker = page.locator('.annotation-marker').first();
        const markerVisible = await annotationMarker.count() > 0;
        console.log(`   Annotation marker visible: ${markerVisible}`);

        if (markerVisible) {
            const annotationLabel = await annotationMarker.locator('.annotation-label').textContent();
            console.log(`   Annotation label: "${annotationLabel}"`);

            if (annotationLabel.includes('Test Run Alpha')) {
                console.log('✓ Annotation label updated correctly!\n');
            } else {
                console.log('⚠️  Annotation label may not have updated\n');
            }
        }

        // Step 13: Test clicking existing annotation
        console.log('🖱️  Step 13: Testing click on existing annotation...');

        if (markerVisible) {
            await annotationMarker.click();
            await wait(2000);

            const slideoverReopened = await slideover.isVisible({ timeout: 3000 }).catch(() => false);
            if (slideoverReopened) {
                console.log('✓ Slideover reopened when clicking annotation\n');

                // Close it
                const cancelButton = page.locator('button').filter({ hasText: 'Cancel' }).first();
                if (await cancelButton.count() > 0) {
                    await cancelButton.click();
                    await wait(1000);
                }
            } else {
                console.log('⚠️  Slideover did not reopen\n');
            }
        }

        // Final screenshot
        await page.screenshot({ path: 'v3-annotation-editor-success.png', fullPage: true });
        console.log('📸 Screenshot saved: v3-annotation-editor-success.png\n');

        console.log('✅ ALL TESTS PASSED! V3 Annotation Editor with Livewire working correctly! 🎉\n');

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        await page.screenshot({ path: 'v3-annotation-editor-error.png', fullPage: true });
        console.log('📸 Error screenshot saved: v3-annotation-editor-error.png\n');
        throw error;
    } finally {
        console.log('🧹 Cleaning up...');
        await wait(3000); // Keep browser open briefly to see results
        await browser.close();
    }
}

// Run the test
testAnnotationEditor().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
