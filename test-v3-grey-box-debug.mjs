#!/usr/bin/env node

/**
 * Debug V3 Grey Box Issue
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';

async function main() {
    console.log('🔍 Debugging V3 Grey Box Issue\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    // Collect console logs
    const logs = [];
    page.on('console', msg => {
        const text = msg.text();
        logs.push(text);
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

        // Navigate to projects
        console.log('📂 Navigating to first project...');
        await page.goto(`${BASE_URL}/admin/project/projects`);
        await page.waitForTimeout(2000);

        // Click first project
        const firstProject = await page.locator('table tbody tr').first();
        if (await firstProject.isVisible({ timeout: 5000 })) {
            await firstProject.click();
            await page.waitForTimeout(3000);
        }

        // Look for V3 viewer trigger
        console.log('🔍 Looking for V3 annotation viewer...\n');

        // Try to find Review PDF button or similar
        const reviewButton = await page.locator('button:has-text("Review"), a:has-text("Review")').first();
        if (await reviewButton.isVisible({ timeout: 3000 })) {
            console.log('Found Review button, clicking...');
            await reviewButton.click();
            await page.waitForTimeout(5000);
        }

        // Take screenshot of current state
        await page.screenshot({ path: 'debug-grey-box-initial.png', fullPage: true });
        console.log('📸 Screenshot saved: debug-grey-box-initial.png\n');

        // Check for V3 system
        const v3System = await page.locator('[x-data*="annotationSystemV3"]').first();
        const hasV3 = await v3System.isVisible().catch(() => false);

        if (hasV3) {
            console.log('✅ V3 Annotation System detected!\n');

            // Check for grey box elements
            console.log('🔍 Checking for grey box elements...\n');

            // Inspect pdf-content-wrapper
            const wrapper = await page.locator('.pdf-content-wrapper').first();
            if (await wrapper.isVisible().catch(() => false)) {
                const wrapperStyles = await wrapper.evaluate(el => {
                    const computed = window.getComputedStyle(el);
                    return {
                        background: computed.background,
                        backgroundColor: computed.backgroundColor,
                        width: computed.width,
                        height: computed.height,
                        position: computed.position,
                        minHeight: computed.minHeight
                    };
                });
                console.log('pdf-content-wrapper styles:', wrapperStyles);
            }

            // Inspect annotation overlay
            const overlay = await page.locator('.annotation-overlay').first();
            if (await overlay.isVisible().catch(() => false)) {
                const overlayStyles = await overlay.evaluate(el => {
                    const computed = window.getComputedStyle(el);
                    return {
                        background: computed.background,
                        backgroundColor: computed.backgroundColor,
                        width: computed.width,
                        height: computed.height,
                        position: computed.position,
                        inset: computed.inset,
                        zIndex: computed.zIndex
                    };
                });
                console.log('annotation-overlay styles:', overlayStyles);
            }

            // Inspect PDF embed
            const pdfEmbed = await page.locator('[x-ref="pdfEmbed"]').first();
            if (await pdfEmbed.isVisible().catch(() => false)) {
                const embedStyles = await pdfEmbed.evaluate(el => {
                    const computed = window.getComputedStyle(el);
                    return {
                        background: computed.background,
                        backgroundColor: computed.backgroundColor,
                        width: computed.width,
                        height: computed.height
                    };
                });
                console.log('pdfEmbed styles:', embedStyles);

                // Check if iframe exists
                const iframe = await pdfEmbed.evaluate(el => {
                    const iframe = el.querySelector('iframe');
                    if (iframe) {
                        const rect = iframe.getBoundingClientRect();
                        return {
                            exists: true,
                            src: iframe.src.substring(0, 50) + '...',
                            width: rect.width,
                            height: rect.height,
                            top: rect.top,
                            left: rect.left
                        };
                    }
                    return { exists: false };
                });
                console.log('PDF iframe:', iframe);
            }

            // Scroll PDF to test
            console.log('\n📜 Testing scroll...\n');
            const container = await page.locator('[id^="pdf-container-"]').first();
            if (await container.isVisible().catch(() => false)) {
                await page.evaluate(() => {
                    const cont = document.querySelector('[id^="pdf-container-"]');
                    if (cont) {
                        console.log('Before scroll:', cont.scrollTop);
                        cont.scrollTop += 200;
                        console.log('After scroll:', cont.scrollTop);
                    }
                });
                await page.waitForTimeout(1000);

                // Take screenshot after scroll
                await page.screenshot({ path: 'debug-grey-box-scrolled.png', fullPage: false });
                console.log('📸 Screenshot saved: debug-grey-box-scrolled.png\n');
            }

            // Check Alpine data
            const alpineData = await page.evaluate(() => {
                const viewer = window.Alpine?.$data(document.querySelector('[x-data*="annotationSystemV3"]'));
                if (viewer) {
                    return {
                        pdfReady: viewer.pdfReady,
                        scrollX: viewer.scrollX,
                        scrollY: viewer.scrollY,
                        annotationsCount: viewer.annotations.length,
                        zoomLevel: viewer.zoomLevel,
                        pdfIframe: viewer.pdfIframe ? 'exists' : 'null'
                    };
                }
                return null;
            });
            console.log('Alpine state:', alpineData);

        } else {
            console.log('⚠️ V3 system not found on this page\n');
        }

        // Keep browser open for inspection
        console.log('\n⏰ Browser will remain open for 60 seconds for inspection...');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'debug-grey-box-error.png', fullPage: true });
        console.log('📸 Error screenshot saved: debug-grey-box-error.png');
    } finally {
        await browser.close();
    }
}

main();
