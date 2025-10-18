#!/usr/bin/env node

/**
 * Direct URL navigation to V3 PDF viewer to debug grey box
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;

async function main() {
    console.log('🔍 V3 Grey Box Debug - Direct URL Navigation\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Collect console logs
    page.on('console', msg => {
        console.log(`  [Browser] ${msg.text()}`);
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

        // Navigate DIRECTLY to V3 viewer
        const v3Url = `${BASE_URL}/admin/project/projects/${PROJECT_ID}/annotate-v2?pdf=${PDF_ID}`;
        console.log('📂 Navigating to V3 viewer:', v3Url);
        await page.goto(v3Url);
        await page.waitForTimeout(5000); // Wait for PDF to load

        console.log('Current URL:', page.url());

        // Check for V3 system
        const v3System = await page.locator('[x-data*="annotationSystemV3"]').first();
        const hasV3 = await v3System.isVisible().catch(() => false);

        if (hasV3) {
            console.log('✅ V3 Annotation System found!\n');

            // Highlight all key elements
            console.log('🎨 Highlighting elements...\n');
            await page.evaluate(() => {
                const elements = [
                    { selector: '.pdf-viewer-container', color: 'red', name: 'Container' },
                    { selector: '.pdf-content-wrapper', color: 'blue', name: 'Wrapper' },
                    { selector: '.annotation-overlay', color: 'green', name: 'Overlay' },
                    { selector: '[id^="pdf-container-"]', color: 'yellow', name: 'PDF Container' },
                    { selector: '[x-ref="pdfEmbed"]', color: 'purple', name: 'PDF Embed' }
                ];

                elements.forEach(({ selector, color, name }) => {
                    const el = document.querySelector(selector);
                    if (el) {
                        el.style.outline = `5px solid ${color}`;
                        const computed = window.getComputedStyle(el);
                        console.log(`✓ ${name} (${color}):`, {
                            width: el.offsetWidth,
                            height: el.offsetHeight,
                            bg: computed.backgroundColor,
                            position: computed.position,
                            zIndex: computed.zIndex
                        });
                    } else {
                        console.log(`⚠️ ${name} (${selector}) not found`);
                    }
                });

                // Check iframe
                const pdfEmbed = document.querySelector('[x-ref="pdfEmbed"]');
                if (pdfEmbed) {
                    const iframe = pdfEmbed.querySelector('iframe');
                    if (iframe) {
                        console.log('✓ PDF iframe found:', {
                            width: iframe.offsetWidth,
                            height: iframe.offsetHeight,
                            src: iframe.src.substring(0, 60) + '...'
                        });
                    } else {
                        console.log('⚠️ No iframe in pdfEmbed');
                    }
                }
            });

            await page.waitForTimeout(1000);

            // Take screenshots
            await page.screenshot({ path: 'v3-grey-box-initial.png', fullPage: false });
            console.log('\n📸 Initial screenshot: v3-grey-box-initial.png');

            // Test scroll
            console.log('\n📜 Testing scroll...');
            await page.evaluate(() => {
                const container = document.querySelector('[id^="pdf-container-"]');
                if (container) {
                    console.log('Scroll before:', container.scrollTop);
                    container.scrollTop = 400;
                    setTimeout(() => {
                        console.log('Scroll after:', container.scrollTop);
                    }, 500);
                }
            });

            await page.waitForTimeout(1500);
            await page.screenshot({ path: 'v3-grey-box-scrolled.png', fullPage: false });
            console.log('📸 After scroll screenshot: v3-grey-box-scrolled.png');

            console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('Browser open for manual inspection');
            console.log('Element outlines:');
            console.log('  🔴 Red    = .pdf-viewer-container');
            console.log('  🔵 Blue   = .pdf-content-wrapper');
            console.log('  🟢 Green  = .annotation-overlay');
            console.log('  🟡 Yellow = #pdf-container-*');
            console.log('  🟣 Purple = [x-ref="pdfEmbed"]');
            console.log('\nWhich element has the grey background?');
            console.log('Check browser DevTools to inspect styles!');
            console.log('\nPress Ctrl+C to close');
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

            // Keep open
            await page.waitForTimeout(300000);

        } else {
            console.log('⚠️ V3 system not found!');
            await page.screenshot({ path: 'v3-not-found.png', fullPage: true });
            console.log('📸 Screenshot: v3-not-found.png');
        }

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'v3-error.png', fullPage: true });
        console.log('📸 Error screenshot: v3-error.png');
    } finally {
        await browser.close();
    }
}

main();
