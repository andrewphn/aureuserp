#!/usr/bin/env node

/**
 * V2 Canvas Viewer Test Script
 * Tests the new canvas-based annotation viewer workflow
 */

import { chromium } from '@playwright/test';

async function testV2CanvasViewer() {
    console.log('🚀 Starting V2 Canvas Viewer Test...\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    // Handle dialogs automatically (dismiss alerts/confirms)
    page.on('dialog', async dialog => {
        console.log(`⚠️  Dialog detected: ${dialog.type()} - "${dialog.message()}"`);
        await dialog.dismiss();
    });

    try {
        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login', {
            waitUntil: 'networkidle',
            timeout: 120000
        });

        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/**', { timeout: 60000 });
        console.log('✅ Logged in successfully\n');

        // Step 2: Navigate to Projects
        console.log('📁 Step 2: Navigating to projects list...');
        await page.goto('http://aureuserp.test/admin/project/projects', {
            waitUntil: 'networkidle',
            timeout: 120000
        });
        console.log('✅ Projects page loaded\n');

        // Step 3: Find and click first project with V2 badge
        console.log('🔍 Step 3: Finding project with V2 annotation system...');

        // Wait for project cards to load
        await page.waitForSelector('table tbody tr', { timeout: 30000 });

        // Click first project to open edit page
        await page.click('table tbody tr:first-child a');
        await page.waitForLoadState('networkidle');
        console.log('✅ Project opened\n');

        // Step 4: Look for PDF review section
        console.log('📄 Step 4: Looking for PDF thumbnails...');

        // Wait for thumbnails to appear
        await page.waitForSelector('canvas[id^="thumbnail-canvas"]', { timeout: 30000 });
        console.log('✅ PDF thumbnails loaded\n');

        // Step 5: Find V2 badge
        console.log('🔎 Step 5: Looking for V2 badge...');
        const v2Badge = await page.locator('span.bg-purple-600:has-text("V2")').first();

        if (await v2Badge.count() > 0) {
            console.log('✅ Found V2 badge!\n');

            // Take screenshot of V2 badge
            await page.screenshot({
                path: 'test-screenshots/v2-badge-found.png',
                fullPage: false
            });
            console.log('📸 Screenshot saved: test-screenshots/v2-badge-found.png\n');

            // Step 6: Click Annotate button next to V2 badge
            console.log('🖱️  Step 6: Clicking Annotate button...');
            const annotateButton = v2Badge.locator('..').locator('button:has-text("Annotate")');
            await annotateButton.click();

            // Wait for modal to open
            await page.waitForTimeout(1000);
            console.log('✅ Annotate button clicked\n');

            // Step 7: Verify V2 modal opened
            console.log('🔍 Step 7: Verifying V2 modal opened...');

            // Check for canvas elements (PDF canvas and draw canvas)
            const pdfCanvas = await page.locator('canvas[x-ref="pdfCanvas"]').count();
            const drawCanvas = await page.locator('canvas[x-ref="drawCanvas"]').count();

            console.log(`   - PDF Canvas found: ${pdfCanvas > 0 ? '✅' : '❌'}`);
            console.log(`   - Draw Canvas found: ${drawCanvas > 0 ? '✅' : '❌'}`);

            // Check for context bar
            const contextBar = await page.locator('text=Select Room & Location First').count();
            console.log(`   - Context bar found: ${contextBar > 0 ? '✅' : '❌'}\n`);

            // Step 8: Check for Alpine errors in console
            console.log('🐛 Step 8: Checking for JavaScript errors...');
            const consoleLogs = [];
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    consoleLogs.push(msg.text());
                }
            });

            // Wait a bit for any errors to appear
            await page.waitForTimeout(2000);

            if (consoleLogs.length > 0) {
                console.log('❌ JavaScript errors found:');
                consoleLogs.forEach(log => console.log(`   - ${log}`));
            } else {
                console.log('✅ No JavaScript errors detected\n');
            }

            // Take final screenshot
            await page.screenshot({
                path: 'test-screenshots/v2-canvas-modal-opened.png',
                fullPage: true
            });
            console.log('📸 Screenshot saved: test-screenshots/v2-canvas-modal-opened.png\n');

            // Step 9: Test context selection
            console.log('🏠 Step 9: Testing room selection...');

            // Click room autocomplete input
            const roomInput = await page.locator('input[placeholder*="room"]').first();
            if (await roomInput.count() > 0) {
                await roomInput.click();
                await page.waitForTimeout(500);

                // Check if dropdown appears
                const dropdown = await page.locator('[role="listbox"]').count();
                console.log(`   - Room dropdown appeared: ${dropdown > 0 ? '✅' : '❌'}\n`);
            } else {
                console.log('   ⚠️  Room input not found\n');
            }

            console.log('✅ V2 Canvas Viewer Test Complete!\n');

        } else {
            console.log('❌ No V2 badge found on this project\n');
            await page.screenshot({
                path: 'test-screenshots/no-v2-badge.png',
                fullPage: true
            });
        }

    } catch (error) {
        console.error('❌ Test failed:', error.message);

        // Take error screenshot
        await page.screenshot({
            path: 'test-screenshots/test-error.png',
            fullPage: true
        });
        console.log('📸 Error screenshot saved: test-screenshots/test-error.png\n');
    } finally {
        console.log('🧹 Cleaning up...');
        await browser.close();
        console.log('✅ Browser closed\n');
    }
}

// Run the test
testV2CanvasViewer().catch(console.error);
