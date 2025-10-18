#!/usr/bin/env node

/**
 * Test Cover Page Type Dropdown Functionality
 *
 * This test verifies that when "Cover" is selected from the page type dropdown,
 * the cover page fields (Customer, Company, Branch, Address) appear correctly.
 */

import { chromium } from 'playwright';

async function testCoverPageTypeDropdown() {
    console.log('🚀 Starting Cover Page Type Dropdown Test\n');

    const browser = await chromium.launch({
        headless: false,
        timeout: 120000
    });

    try {
        const context = await browser.newContext({
            viewport: { width: 1920, height: 1080 },
            ignoreHTTPSErrors: true
        });

        const page = await context.newPage();
        page.setDefaultTimeout(60000);

        // Navigate to projects page
        console.log('📄 Navigating to projects page...');
        await page.goto('http://aureuserp.test/admin/project/projects', {
            waitUntil: 'networkidle',
            timeout: 120000
        });

        console.log('✅ Page loaded successfully\n');

        // Find and click on a project to view its details
        console.log('🔍 Looking for a project with PDF documents...');
        const projectRow = await page.locator('table tbody tr').first();
        await projectRow.click();

        await page.waitForTimeout(2000);

        // Look for "Review PDF & Price" button/link
        console.log('📋 Looking for Review PDF & Price page...');
        const reviewPdfLink = await page.getByText('Review PDF & Price', { exact: false }).first();
        if (await reviewPdfLink.count() > 0) {
            await reviewPdfLink.click();
            await page.waitForTimeout(3000);
        }

        // Find and click the "Annotate" button on a PDF page
        console.log('🖊️ Looking for Annotate button...');
        const annotateButton = await page.locator('button:has-text("Annotate")').first();

        if (await annotateButton.count() === 0) {
            console.log('⚠️ No Annotate button found. Please ensure there are PDF documents in the project.');
            return;
        }

        await annotateButton.click();
        console.log('✅ Clicked Annotate button\n');

        // Wait for annotation modal to appear
        await page.waitForTimeout(2000);

        // Take screenshot of initial state
        await page.screenshot({
            path: 'test-screenshots/cover-page-initial.png',
            fullPage: false
        });
        console.log('📸 Screenshot saved: cover-page-initial.png\n');

        // Find the page type dropdown
        console.log('🔍 Looking for page type dropdown...');
        const pageTypeDropdown = await page.locator('select').filter({ hasText: 'Select Page Type' }).first();

        if (await pageTypeDropdown.count() === 0) {
            console.log('❌ Page type dropdown not found!');
            return;
        }

        console.log('✅ Found page type dropdown\n');

        // Check if cover page fields are initially hidden
        console.log('🔍 Checking if cover page fields are initially hidden...');
        const coverFieldsContainer = await page.locator('div[x-show="pageType === \'cover\'"]').first();
        const isInitiallyHidden = await coverFieldsContainer.isHidden();

        console.log(`Initial state: Cover fields are ${isInitiallyHidden ? 'HIDDEN ✅' : 'VISIBLE ❌'}\n`);

        // Select "Cover" from the dropdown
        console.log('🎯 Selecting "Cover" from page type dropdown...');
        await pageTypeDropdown.selectOption({ label: 'Cover' });

        // Wait for Alpine.js to react
        await page.waitForTimeout(1000);

        // Take screenshot after selection
        await page.screenshot({
            path: 'test-screenshots/cover-page-selected.png',
            fullPage: false
        });
        console.log('📸 Screenshot saved: cover-page-selected.png\n');

        // Check if cover page fields are now visible
        console.log('🔍 Checking if cover page fields are now visible...');
        const isNowVisible = await coverFieldsContainer.isVisible();

        console.log(`After selection: Cover fields are ${isNowVisible ? 'VISIBLE ✅' : 'HIDDEN ❌'}\n`);

        // Check for specific fields
        if (isNowVisible) {
            console.log('🔍 Verifying specific cover page fields...');

            const customerField = await page.locator('#customer-select').first();
            const companyField = await page.locator('#company-select').first();
            const branchField = await page.locator('#branch-select').first();

            const customerVisible = await customerField.isVisible();
            const companyVisible = await companyField.isVisible();
            const branchVisible = await branchField.isVisible();

            console.log(`  • Customer field: ${customerVisible ? '✅ Visible' : '❌ Hidden'}`);
            console.log(`  • Company field: ${companyVisible ? '✅ Visible' : '❌ Hidden'}`);
            console.log(`  • Branch field: ${branchVisible ? '✅ Visible' : '❌ Hidden'}`);
            console.log('');
        }

        // Test selecting another page type to ensure fields hide again
        console.log('🔄 Testing if fields hide when selecting another page type...');
        await pageTypeDropdown.selectOption({ label: 'Floor Plan' });
        await page.waitForTimeout(1000);

        const isHiddenAfterSwitch = await coverFieldsContainer.isHidden();
        console.log(`After switching to Floor Plan: Cover fields are ${isHiddenAfterSwitch ? 'HIDDEN ✅' : 'VISIBLE ❌'}\n`);

        // Take final screenshot
        await page.screenshot({
            path: 'test-screenshots/cover-page-switched.png',
            fullPage: false
        });
        console.log('📸 Screenshot saved: cover-page-switched.png\n');

        // Summary
        console.log('═══════════════════════════════════════════════════════');
        console.log('📊 TEST SUMMARY');
        console.log('═══════════════════════════════════════════════════════');
        console.log(`Initial State (no selection): ${isInitiallyHidden ? '✅ PASS' : '❌ FAIL'} - Fields should be hidden`);
        console.log(`After selecting "Cover": ${isNowVisible ? '✅ PASS' : '❌ FAIL'} - Fields should be visible`);
        console.log(`After switching to "Floor Plan": ${isHiddenAfterSwitch ? '✅ PASS' : '❌ FAIL'} - Fields should be hidden`);
        console.log('═══════════════════════════════════════════════════════\n');

        const allTestsPassed = isInitiallyHidden && isNowVisible && isHiddenAfterSwitch;

        if (allTestsPassed) {
            console.log('🎉 ALL TESTS PASSED! The cover page type dropdown is working correctly.');
        } else {
            console.log('❌ SOME TESTS FAILED. Please review the Alpine.js scope configuration.');
        }

        // Keep browser open for manual inspection
        console.log('\n⏸️  Browser will remain open for 30 seconds for manual inspection...');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('❌ Test failed with error:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
        console.log('\n✅ Test complete. Browser closed.');
    }
}

// Run the test
testCoverPageTypeDropdown().catch(console.error);
