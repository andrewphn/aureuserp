#!/usr/bin/env node

/**
 * Direct navigation to V3 PDF viewer to debug grey box issue
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';

async function main() {
    console.log('🔍 Direct V3 Grey Box Test\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Collect console logs
    page.on('console', msg => {
        const text = msg.text();
        console.log(`  [Browser] ${text}`);
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

        // Navigate directly to projects list
        console.log('📂 Navigating to projects...');
        await page.goto(`${BASE_URL}/admin/project/projects`);
        await page.waitForTimeout(2000);

        // Click the first project card (25 Friendship Lane)
        console.log('🎯 Opening first project...');
        const projectCard = await page.locator('div:has-text("25 Friendship Lane")').first();
        if (await projectCard.isVisible({ timeout: 5000 })) {
            await projectCard.click();
            await page.waitForTimeout(3000);
        } else {
            // Fallback: click first table row
            const firstRow = await page.locator('table tbody tr').first();
            await firstRow.click();
            await page.waitForTimeout(3000);
        }

        console.log('Current URL:', page.url());
        await page.screenshot({ path: 'grey-box-project-page.png', fullPage: true });
        console.log('📸 Screenshot saved: grey-box-project-page.png\n');

        // Look for tabs or buttons to access PDF viewer
        console.log('🔍 Looking for PDF viewer access...');

        // Try to find "Review PDF" or similar button
        const reviewButton = await page.locator('button:has-text("Review"), a:has-text("Review"), button:has-text("PDF"), a:has-text("PDF")').first();
        if (await reviewButton.isVisible({ timeout: 3000 })) {
            console.log('Found Review/PDF button, clicking...');
            await reviewButton.click();
            await page.waitForTimeout(5000);
        } else {
            // Try to find tab with "Review" or "PDF"
            const tabs = await page.locator('[role="tab"]').all();
            for (const tab of tabs) {
                const text = await tab.textContent().catch(() => '');
                if (text && (text.includes('Review') || text.includes('PDF') || text.includes('Annotate'))) {
                    console.log(`Found tab: "${text}", clicking...`);
                    await tab.click();
                    await page.waitForTimeout(3000);
                    break;
                }
            }
        }

        console.log('Current URL after navigation:', page.url());
        await page.screenshot({ path: 'grey-box-after-navigation.png', fullPage: true });
        console.log('📸 Screenshot saved: grey-box-after-navigation.png\n');

        // Check for V3 system
        const v3System = await page.locator('[x-data*="annotationSystemV3"]').first();
        const hasV3 = await v3System.isVisible().catch(() => false);

        if (hasV3) {
            console.log('✅ V3 Annotation System found!\n');

            // Highlight elements to identify grey box
            console.log('🎨 Highlighting elements...\n');
            await page.evaluate(() => {
                // Container
                const container = document.querySelector('.pdf-viewer-container');
                if (container) {
                    container.style.outline = '5px solid red';
                    console.log('✓ Container (red outline):', {
                        width: container.offsetWidth,
                        height: container.offsetHeight,
                        bg: window.getComputedStyle(container).backgroundColor
                    });
                }

                // Wrapper
                const wrapper = document.querySelector('.pdf-content-wrapper');
                if (wrapper) {
                    wrapper.style.outline = '5px solid blue';
                    console.log('✓ Wrapper (blue outline):', {
                        width: wrapper.offsetWidth,
                        height: wrapper.offsetHeight,
                        bg: window.getComputedStyle(wrapper).backgroundColor
                    });
                } else {
                    console.log('⚠️ .pdf-content-wrapper not found');
                }

                // Overlay
                const overlay = document.querySelector('.annotation-overlay');
                if (overlay) {
                    overlay.style.outline = '5px solid green';
                    console.log('✓ Overlay (green outline):', {
                        width: overlay.offsetWidth,
                        height: overlay.offsetHeight,
                        bg: window.getComputedStyle(overlay).backgroundColor,
                        zIndex: window.getComputedStyle(overlay).zIndex
                    });
                } else {
                    console.log('⚠️ .annotation-overlay not found');
                }

                // PDF Container
                const pdfContainer = document.querySelector('[id^="pdf-container-"]');
                if (pdfContainer) {
                    pdfContainer.style.outline = '5px solid yellow';
                    console.log('✓ PDF Container (yellow outline):', {
                        width: pdfContainer.offsetWidth,
                        height: pdfContainer.offsetHeight,
                        bg: window.getComputedStyle(pdfContainer).backgroundColor
                    });
                }

                // PDF Embed
                const pdfEmbed = document.querySelector('[x-ref="pdfEmbed"]');
                if (pdfEmbed) {
                    pdfEmbed.style.outline = '5px solid purple';
                    console.log('✓ PDF Embed (purple outline):', {
                        width: pdfEmbed.offsetWidth,
                        height: pdfEmbed.offsetHeight,
                        bg: window.getComputedStyle(pdfEmbed).backgroundColor
                    });

                    const iframe = pdfEmbed.querySelector('iframe');
                    if (iframe) {
                        console.log('✓ PDF iframe found:', {
                            width: iframe.offsetWidth,
                            height: iframe.offsetHeight,
                            src: iframe.src.substring(0, 80) + '...'
                        });
                    } else {
                        console.log('⚠️ No iframe in pdfEmbed');
                    }
                }
            });

            await page.waitForTimeout(1000);
            await page.screenshot({ path: 'grey-box-highlighted.png', fullPage: false });
            console.log('📸 Highlighted screenshot saved: grey-box-highlighted.png\n');

            // Test scrolling
            console.log('📜 Testing scroll...');
            await page.evaluate(() => {
                const container = document.querySelector('[id^="pdf-container-"]');
                if (container) {
                    console.log('Before scroll:', container.scrollTop);
                    container.scrollTop = 300;
                    console.log('After scroll:', container.scrollTop);
                }
            });

            await page.waitForTimeout(1000);
            await page.screenshot({ path: 'grey-box-scrolled.png', fullPage: false });
            console.log('📸 Scrolled screenshot saved: grey-box-scrolled.png\n');

        } else {
            console.log('⚠️ V3 system not found on this page');
            console.log('Page might be using V1 or V2 viewer\n');

            // Check what's actually on the page
            const html = await page.content();
            if (html.includes('annotationSystemV3')) {
                console.log('V3 code exists in HTML but not initialized');
            } else if (html.includes('PSPDFKit') || html.includes('nutrient')) {
                console.log('Page is using V1 viewer (PSPDFKit/Nutrient)');
            } else {
                console.log('Unknown viewer type');
            }
        }

        console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('Browser will stay open for manual inspection');
        console.log('Look for colored outlines:');
        console.log('  🔴 Red: .pdf-viewer-container');
        console.log('  🔵 Blue: .pdf-content-wrapper');
        console.log('  🟢 Green: .annotation-overlay');
        console.log('  🟡 Yellow: #pdf-container-*');
        console.log('  🟣 Purple: [x-ref="pdfEmbed"]');
        console.log('Check which element shows the grey background!');
        console.log('Press Ctrl+C to close when done');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        // Keep browser open for inspection
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
