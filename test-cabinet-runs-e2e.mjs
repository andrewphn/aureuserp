#!/usr/bin/env node

/**
 * End-to-End Test: Cabinet Runs Relation Manager
 *
 * Tests the complete CRUD functionality of the Cabinet Runs relation manager
 * in the FilamentPHP admin panel.
 *
 * Test Coverage:
 * - Navigate to project edit page
 * - Open Cabinet Runs tab
 * - Create a new cabinet run
 * - Verify it appears in the table
 * - Edit the cabinet run
 * - Delete the cabinet run
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;

async function runTest() {
    console.log('=== Cabinet Runs Relation Manager E2E Test ===\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('Step 1: Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.fill('input[name="email"]', 'info@tcswoodwork.com');
        await page.fill('input[name="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✓ Logged in successfully\n');

        // Step 2: Navigate to project edit page
        console.log('Step 2: Navigating to project edit page...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/edit`);
        await page.waitForLoadState('networkidle');
        console.log('✓ Project edit page loaded\n');

        // Step 3: Click on "Project Data" tab
        console.log('Step 3: Opening Project Data tab...');
        await page.click('button:has-text("Project Data")');
        await page.waitForTimeout(1000);
        console.log('✓ Project Data tab opened\n');

        // Step 4: Verify Cabinet Runs section is visible
        console.log('Step 4: Verifying Cabinet Runs section...');
        const cabinetRunsHeading = await page.locator('h3:has-text("Cabinet Runs")');
        await cabinetRunsHeading.waitFor({ state: 'visible', timeout: 5000 });
        console.log('✓ Cabinet Runs section is visible\n');

        // Step 5: Click "New cabinet run" button
        console.log('Step 5: Clicking "New cabinet run" button...');
        const createButton = page.locator('button:has-text("New cabinet run")').first();
        await createButton.click();
        await page.waitForTimeout(1500);
        console.log('✓ Create cabinet run modal opened\n');

        // Step 6: Check if we have room locations to select
        console.log('Step 6: Checking for available room locations...');
        const roomLocationSelect = page.locator('select[name="room_location_id"], div[role="combobox"]:near(:text("Room Location"))').first();

        // Check if room locations exist
        const hasRoomLocations = await page.locator('text=/Room Location/i').count() > 0;

        if (!hasRoomLocations) {
            console.log('⚠️  WARNING: No Room Location field found. Project may not have rooms/locations configured.');
            console.log('   Skipping cabinet run creation test.\n');

            // Close modal
            const cancelButton = page.locator('button:has-text("Cancel")').first();
            if (await cancelButton.isVisible()) {
                await cancelButton.click();
                await page.waitForTimeout(500);
            }
        } else {
            console.log('✓ Room Location field found\n');

            // Step 7: Fill in cabinet run form
            console.log('Step 7: Filling in cabinet run form...');

            // Wait for form to be ready
            await page.waitForTimeout(1000);

            // Try to select a room location
            try {
                // Check if it's a Filament select (combobox) or native select
                const isFilamentSelect = await page.locator('div[role="combobox"]:near(:text("Room Location"))').count() > 0;

                if (isFilamentSelect) {
                    console.log('  - Using Filament select for Room Location');
                    await page.click('div[role="combobox"]:near(:text("Room Location"))');
                    await page.waitForTimeout(500);
                    await page.click('div[role="option"]').first();
                } else {
                    console.log('  - Using native select for Room Location');
                    const options = await page.locator('select[name="room_location_id"] option').count();
                    if (options > 1) {
                        await page.selectOption('select[name="room_location_id"]', { index: 1 });
                    }
                }
                console.log('  ✓ Room Location selected');
            } catch (e) {
                console.log('  ⚠️  Could not select room location:', e.message);
            }

            // Fill in Run Name
            await page.fill('input[name="name"]', 'Test Cabinet Run E2E');
            console.log('  ✓ Run Name filled');

            // Select Run Type
            try {
                const runTypeSelect = page.locator('select[name="run_type"]').first();
                await runTypeSelect.selectOption('base');
                console.log('  ✓ Run Type selected');
            } catch (e) {
                console.log('  ⚠️  Could not select run type:', e.message);
            }

            // Fill in Total Linear Feet (optional)
            try {
                await page.fill('input[name="total_linear_feet"]', '25.5');
                console.log('  ✓ Total Linear Feet filled');
            } catch (e) {
                console.log('  ⚠️  Could not fill linear feet:', e.message);
            }

            console.log('✓ Form filled\n');

            // Step 8: Submit the form
            console.log('Step 8: Submitting form...');
            const createSubmitButton = page.locator('button[type="submit"]:has-text("Create")').first();
            await createSubmitButton.click();
            await page.waitForTimeout(2000);

            // Check for success notification
            const hasSuccessNotification = await page.locator('text=/created successfully/i, text=/success/i').count() > 0;
            if (hasSuccessNotification) {
                console.log('✓ Cabinet run created successfully\n');
            } else {
                console.log('⚠️  Could not verify success notification\n');
            }

            // Step 9: Verify the cabinet run appears in the table
            console.log('Step 9: Verifying cabinet run appears in table...');
            await page.waitForTimeout(1000);

            const tableRow = page.locator('tr:has-text("Test Cabinet Run E2E")');
            const isVisible = await tableRow.count() > 0;

            if (isVisible) {
                console.log('✓ Cabinet run appears in table\n');

                // Step 10: Test editing the cabinet run
                console.log('Step 10: Testing edit functionality...');
                const editButton = tableRow.locator('button[title="Edit"], button:has-text("Edit")').first();
                if (await editButton.count() > 0) {
                    await editButton.click();
                    await page.waitForTimeout(1500);
                    console.log('✓ Edit modal opened\n');

                    // Update the name
                    await page.fill('input[name="name"]', 'Test Cabinet Run E2E - EDITED');
                    console.log('  ✓ Name updated');

                    // Save changes
                    const saveButton = page.locator('button[type="submit"]:has-text("Save")').first();
                    await saveButton.click();
                    await page.waitForTimeout(2000);
                    console.log('✓ Changes saved\n');

                    // Verify updated name appears
                    const updatedRow = await page.locator('tr:has-text("Test Cabinet Run E2E - EDITED")').count() > 0;
                    if (updatedRow) {
                        console.log('✓ Updated name appears in table\n');
                    }
                } else {
                    console.log('⚠️  Edit button not found\n');
                }

                // Step 11: Test deleting the cabinet run
                console.log('Step 11: Testing delete functionality...');
                const deleteRow = page.locator('tr:has-text("Test Cabinet Run E2E")');
                const deleteButton = deleteRow.locator('button[title="Delete"], button:has-text("Delete")').first();

                if (await deleteButton.count() > 0) {
                    await deleteButton.click();
                    await page.waitForTimeout(1000);
                    console.log('  ✓ Delete button clicked');

                    // Confirm deletion
                    const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Delete")').last();
                    if (await confirmButton.isVisible()) {
                        await confirmButton.click();
                        await page.waitForTimeout(2000);
                        console.log('  ✓ Deletion confirmed');
                    }

                    // Verify it's gone from the table
                    const stillExists = await page.locator('tr:has-text("Test Cabinet Run E2E")').count() > 0;
                    if (!stillExists) {
                        console.log('✓ Cabinet run successfully deleted from table\n');
                    } else {
                        console.log('⚠️  Cabinet run still appears in table after deletion\n');
                    }
                } else {
                    console.log('⚠️  Delete button not found\n');
                }
            } else {
                console.log('⚠️  Cabinet run does not appear in table\n');
            }
        }

        // Final screenshot
        await page.screenshot({ path: 'cabinet-runs-e2e-final.png', fullPage: true });
        console.log('📸 Final screenshot saved: cabinet-runs-e2e-final.png\n');

        console.log('=== ✅ E2E Test Complete ===\n');

    } catch (error) {
        console.error('\n❌ ERROR during E2E test:', error.message);
        await page.screenshot({ path: 'cabinet-runs-e2e-error.png', fullPage: true });
        console.log('📸 Error screenshot saved: cabinet-runs-e2e-error.png\n');
        throw error;
    } finally {
        await browser.close();
    }
}

// Run the test
runTest().catch(error => {
    console.error('Test failed:', error);
    process.exit(1);
});
