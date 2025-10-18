#!/usr/bin/env node

/**
 * Test V2 Annotate Leak Issue
 */

import { chromium } from '@playwright/test';

async function testV2Leak() {
    console.log('🔍 Testing V2 Annotate Leak Issue...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000
    });

    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });

    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');

        await page.waitForTimeout(3000);
        console.log('✅ Logged in\n');

        // Step 2: Navigate to PDF review page
        console.log('📄 Step 2: Navigating to PDF review page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/pdf-review?pdf=1', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        await page.waitForTimeout(2000);
        console.log('✅ PDF review page loaded\n');

        // Step 3: Take screenshot of initial state
        console.log('📸 Step 3: Taking screenshot of initial state...');
        await page.screenshot({
            path: 'test-screenshots/v2-leak-initial.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved: test-screenshots/v2-leak-initial.png\n');

        // Step 4: Look for Annotate button
        console.log('🔍 Step 4: Looking for Annotate button...');

        // Wait for thumbnails to load
        await page.waitForSelector('canvas[id^="thumbnail-canvas"]', { timeout: 10000 });
        console.log('✅ Thumbnails loaded\n');

        // Find V2 badge or Annotate button
        const annotateButton = await page.locator('button:has-text("Annotate")').first();

        if (await annotateButton.count() > 0) {
            console.log('✅ Found Annotate button\n');

            // Step 5: Click Annotate button
            console.log('🖱️  Step 5: Clicking Annotate button...');
            await annotateButton.click();

            await page.waitForTimeout(2000);
            console.log('✅ Annotate button clicked\n');

            // Step 6: Take screenshot after clicking
            console.log('📸 Step 6: Taking screenshot after clicking Annotate...');
            await page.screenshot({
                path: 'test-screenshots/v2-leak-after-click.png',
                fullPage: true
            });
            console.log('✅ Screenshot saved: test-screenshots/v2-leak-after-click.png\n');

            // Step 7: Check for memory/performance issues
            console.log('🔍 Step 7: Checking for JavaScript errors...');

            const logs = [];
            page.on('console', msg => {
                if (msg.type() === 'error' || msg.type() === 'warning') {
                    logs.push(`${msg.type().toUpperCase()}: ${msg.text()}`);
                }
            });

            await page.waitForTimeout(3000);

            if (logs.length > 0) {
                console.log('⚠️  JavaScript errors/warnings found:');
                logs.forEach(log => console.log(`   ${log}`));
            } else {
                console.log('✅ No JavaScript errors detected\n');
            }

            // Step 8: Check for modal visibility
            console.log('🔍 Step 8: Checking modal state...');

            const modal = await page.locator('[x-data*="annotationSystemV2"]').count();
            const pdfCanvas = await page.locator('canvas[x-ref="pdfCanvas"]').count();
            const drawCanvas = await page.locator('canvas[x-ref="drawCanvas"]').count();

            console.log(`   - V2 modal present: ${modal > 0 ? '✅' : '❌'}`);
            console.log(`   - PDF canvas present: ${pdfCanvas > 0 ? '✅' : '❌'}`);
            console.log(`   - Draw canvas present: ${drawCanvas > 0 ? '✅' : '❌'}\n`);

            // Step 9: Visual inspection pause
            console.log('👀 Step 9: Pausing for visual inspection...');
            console.log('   Please check the browser for any "leak" issues');
            console.log('   Press Ctrl+C to exit when done\n');

            await page.waitForTimeout(30000); // 30 seconds

        } else {
            console.log('❌ No Annotate button found\n');
            await page.screenshot({
                path: 'test-screenshots/v2-leak-no-button.png',
                fullPage: true
            });
        }

    } catch (error) {
        console.error('❌ Error:', error.message);

        await page.screenshot({
            path: 'test-screenshots/v2-leak-error.png',
            fullPage: true
        });
        console.log('📸 Error screenshot saved\n');
    } finally {
        console.log('🧹 Cleaning up...');
        await browser.close();
        console.log('✅ Done\n');
    }
}

testV2Leak().catch(console.error);
