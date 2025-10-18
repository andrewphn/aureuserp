#!/usr/bin/env node

/**
 * Visual inspection of V3 grey box issue
 * Opens browser and stays on page for manual inspection
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';

async function main() {
    console.log('🔍 Visual Inspection - Grey Box Issue\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Enable console logging
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('✓') || text.includes('✅') || text.includes('⚠️') || text.includes('❌')) {
            console.log(`  [Browser] ${text}`);
        }
    });

    try {
        // Login
        console.log('📝 Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        if (page.url().includes('/login')) {
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');
            await page.click('button:has-text("Sign in")');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);
        }
        console.log('✓ Logged in\n');

        // Try to find a page with V3 viewer
        // Option 1: Try direct URL to a known project
        console.log('📂 Trying to navigate to project with V3 viewer...\n');

        // Navigate to projects list
        await page.goto(`${BASE_URL}/admin/project/projects`);
        await page.waitForTimeout(2000);

        // Click first project
        const firstRow = page.locator('table tbody tr').first();
        if (await firstRow.isVisible({ timeout: 5000 })) {
            await firstRow.click();
            await page.waitForTimeout(3000);
        }

        // Look for any PDF-related tabs or buttons
        const tabs = await page.locator('[role="tab"], button, a').all();
        for (const tab of tabs) {
            const text = await tab.textContent().catch(() => '');
            if (text && (text.includes('PDF') || text.includes('Review') || text.includes('Annotate'))) {
                console.log(`Found tab: "${text}"`);
            }
        }

        // Take initial screenshot
        await page.screenshot({ path: 'grey-box-page-state.png', fullPage: true });
        console.log('📸 Screenshot saved: grey-box-page-state.png\n');

        // Check if V3 system exists anywhere on page
        const v3Elements = await page.locator('[x-data*="annotationSystemV3"]').all();
        console.log(`Found ${v3Elements.length} V3 annotation system(s)\n`);

        if (v3Elements.length > 0) {
            console.log('✅ V3 System found! Inspecting...\n');

            // Highlight the PDF container area
            await page.evaluate(() => {
                const container = document.querySelector('.pdf-viewer-container');
                if (container) {
                    container.style.outline = '3px solid red';
                }

                const wrapper = document.querySelector('.pdf-content-wrapper');
                if (wrapper) {
                    wrapper.style.outline = '3px solid blue';
                    console.log('Wrapper dimensions:', {
                        width: wrapper.offsetWidth,
                        height: wrapper.offsetHeight,
                        background: window.getComputedStyle(wrapper).background,
                        backgroundColor: window.getComputedStyle(wrapper).backgroundColor
                    });
                }

                const overlay = document.querySelector('.annotation-overlay');
                if (overlay) {
                    overlay.style.outline = '3px solid green';
                    console.log('Overlay dimensions:', {
                        width: overlay.offsetWidth,
                        height: overlay.offsetHeight,
                        background: window.getComputedStyle(overlay).background,
                        backgroundColor: window.getComputedStyle(overlay).backgroundColor,
                        zIndex: window.getComputedStyle(overlay).zIndex
                    });
                }

                const pdfEmbed = document.querySelector('[x-ref="pdfEmbed"]');
                if (pdfEmbed) {
                    console.log('PDF Embed dimensions:', {
                        width: pdfEmbed.offsetWidth,
                        height: pdfEmbed.offsetHeight,
                        background: window.getComputedStyle(pdfEmbed).background,
                        backgroundColor: window.getComputedStyle(pdfEmbed).backgroundColor
                    });

                    const iframe = pdfEmbed.querySelector('iframe');
                    if (iframe) {
                        console.log('PDF iframe found:', {
                            width: iframe.offsetWidth,
                            height: iframe.offsetHeight,
                            src: iframe.src.substring(0, 100)
                        });
                    } else {
                        console.log('⚠️ No iframe found in pdfEmbed');
                    }
                }
            });

            await page.waitForTimeout(1000);

            // Take highlighted screenshot
            await page.screenshot({ path: 'grey-box-highlighted.png', fullPage: false });
            console.log('📸 Screenshot with highlights saved: grey-box-highlighted.png\n');

            // Test scrolling
            console.log('📜 Testing scroll behavior...\n');
            await page.evaluate(() => {
                const container = document.querySelector('[id^="pdf-container-"]');
                if (container) {
                    console.log('Before scroll:', container.scrollTop);
                    container.scrollTop = 500;
                    console.log('After scroll:', container.scrollTop);
                }
            });

            await page.waitForTimeout(1000);
            await page.screenshot({ path: 'grey-box-after-scroll.png', fullPage: false });
            console.log('📸 After scroll screenshot saved: grey-box-after-scroll.png\n');
        }

        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('Browser will stay open for manual inspection');
        console.log('Look for:');
        console.log('  - Red outline: .pdf-viewer-container');
        console.log('  - Blue outline: .pdf-content-wrapper');
        console.log('  - Green outline: .annotation-overlay');
        console.log('Check browser console for dimension logs');
        console.log('Press Ctrl+C to close when done');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        // Keep browser open indefinitely for inspection
        await page.waitForTimeout(300000); // 5 minutes

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'grey-box-error.png', fullPage: true });
        console.log('📸 Error screenshot saved: grey-box-error.png');
    } finally {
        await browser.close();
    }
}

main();
