#!/usr/bin/env node

/**
 * Detailed V2 Annotate Test - Looking for "Leak" Issues
 */

import { chromium } from '@playwright/test';

async function testV2Annotate() {
    console.log('🔍 Detailed V2 Annotate Test - Looking for Leaks...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // Login
        console.log('📝 Logging in...');
        await page.goto('http://aureuserp.test/admin/login', {
            waitUntil: 'networkidle',
            timeout: 60000
        });

        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        console.log('✅ Logged in\n');

        // Navigate to PDF review
        console.log('📄 Navigating to PDF review...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/pdf-review?pdf=1', {
            waitUntil: 'networkidle',
            timeout: 60000
        });
        await page.waitForTimeout(3000);
        console.log('✅ PDF review loaded\n');

        // Screenshot before clicking
        await page.screenshot({
            path: 'test-screenshots/before-annotate.png',
            fullPage: true
        });
        console.log('📸 Screenshot: before-annotate.png\n');

        // Find and click first Annotate button
        console.log('🖱️  Looking for Annotate button...');

        const annotateButton = await page.locator('button:has-text("Annotate")').first();

        if (await annotateButton.count() === 0) {
            console.log('❌ No Annotate button found!\n');
            await browser.close();
            return;
        }

        // Check if V2 badge is present
        const v2Badge = await page.locator('span:has-text("V2")').first();
        const hasV2Badge = await v2Badge.count() > 0;

        console.log(`   V2 Badge present: ${hasV2Badge ? '✅ YES' : '❌ NO'}`);

        if (!hasV2Badge) {
            console.log('   ⚠️  V2 system may not be enabled. Run: php artisan config:clear\n');
        }

        console.log('   Clicking Annotate button...\n');
        await annotateButton.click();

        // Wait for modal to appear
        await page.waitForTimeout(2000);

        // Screenshot after clicking
        await page.screenshot({
            path: 'test-screenshots/after-annotate.png',
            fullPage: true
        });
        console.log('📸 Screenshot: after-annotate.png\n');

        // Check what modal opened
        console.log('🔍 Checking modal state...\n');

        // Check for V2 canvas viewer
        const v2Modal = await page.locator('[x-data*="annotationSystemV2"]').count();
        const pdfCanvas = await page.locator('canvas[x-ref="pdfCanvas"]').count();
        const drawCanvas = await page.locator('canvas[x-ref="drawCanvas"]').count();

        // Check for V1 Nutrient viewer
        const v1Container = await page.locator('#pspdfkit-container').count();

        console.log(`   V2 Canvas Modal: ${v2Modal > 0 ? '✅' : '❌'}`);
        console.log(`   PDF Canvas: ${pdfCanvas > 0 ? '✅' : '❌'}`);
        console.log(`   Draw Canvas: ${drawCanvas > 0 ? '✅' : '❌'}`);
        console.log(`   V1 Nutrient Container: ${v1Container > 0 ? '⚠️  YES (should be NO)' : '✅'}`);
        console.log();

        // Check for any visual overflow/leak issues
        console.log('🔍 Checking for visual issues...\n');

        // Check modal positioning
        if (v2Modal > 0) {
            const modal = page.locator('[x-show="showAnnotationV2Modal"]').first();
            const box = await modal.boundingBox();

            if (box) {
                console.log(`   Modal dimensions: ${box.width}x${box.height}`);
                console.log(`   Modal position: (${box.x}, ${box.y})`);

                if (box.x < 0 || box.y < 0) {
                    console.log('   ⚠️  WARNING: Modal is positioned off-screen (negative coordinates)');
                }

                if (box.width > 1920 || box.height > 1080) {
                    console.log('   ⚠️  WARNING: Modal exceeds viewport dimensions');
                }
            }
        }

        // Check for console errors
        const errors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        await page.waitForTimeout(2000);

        if (errors.length > 0) {
            console.log('\n🐛 JavaScript Errors:');
            errors.forEach(err => console.log(`   ${err}`));
        } else {
            console.log('✅ No JavaScript errors\n');
        }

        // Screenshot with browser devtools overlay
        await page.screenshot({
            path: 'test-screenshots/modal-detailed.png',
            fullPage: false
        });
        console.log('📸 Screenshot: modal-detailed.png\n');

        console.log('✅ Test complete! Check screenshots for visual issues.\n');
        console.log('   Keeping browser open for 30 seconds for inspection...\n');

        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('❌ Error:', error.message);
        await page.screenshot({
            path: 'test-screenshots/error.png',
            fullPage: true
        });
    } finally {
        await browser.close();
        console.log('🧹 Browser closed\n');
    }
}

testV2Annotate().catch(console.error);
