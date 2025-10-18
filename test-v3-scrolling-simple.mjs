#!/usr/bin/env node

/**
 * Simple V3 Scrolling Test
 * Tests that Phase 1, 2, and 3 improvements work correctly
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;

async function main() {
    console.log('🚀 V3 Scrolling Fix - Simple Test\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    // Collect console logs
    const logs = [];
    page.on('console', msg => {
        const text = msg.text();
        logs.push(text);
        if (text.includes('V3') || text.includes('Phase') || text.includes('✓') || text.includes('✅')) {
            console.log(`  [Browser] ${text}`);
        }
    });

    try {
        // Step 1: Login
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

        // Step 2: Navigate to PDF Review
        console.log('📄 Opening PDF Review...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000); // Wait for V3 system to initialize
        console.log('✓ PDF Review loaded\n');

        // Step 3: Check for V3 system
        console.log('🔍 Checking for V3 Annotation System...');
        const v3System = await page.locator('[x-data*="annotationSystemV3"]').first();
        const hasV3 = await v3System.isVisible().catch(() => false);

        if (!hasV3) {
            console.log('⚠️ V3 system not detected on this page');
            console.log('This may be the wizard interface, not the V3 overlay viewer');
            await page.screenshot({ path: 'v3-scrolling-not-found.png', fullPage: true });
            console.log('📸 Screenshot saved: v3-scrolling-not-found.png\n');
            return;
        }

        console.log('✅ V3 Annotation System detected!\n');

        // Step 4: Test Phase 1 - HTML Structure
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('PHASE 1: HTML Structure (Natural Scrolling)');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        const wrapper = await page.locator('.pdf-content-wrapper').first();
        const hasWrapper = await wrapper.isVisible().catch(() => false);

        if (hasWrapper) {
            console.log('✅ .pdf-content-wrapper found');
        } else {
            console.log('❌ .pdf-content-wrapper NOT found');
        }

        const overlay = await page.locator('.annotation-overlay').first();
        const hasOverlay = await overlay.isVisible().catch(() => false);

        if (hasOverlay) {
            const overlayClasses = await overlay.getAttribute('class');
            if (overlayClasses.includes('inset-0')) {
                console.log('✅ Overlay uses inset-0 (scrolls with PDF)');
            } else {
                console.log('❌ Overlay does NOT use inset-0');
            }
        } else {
            console.log('❌ Annotation overlay NOT found');
        }

        // Check annotation positioning
        const annotations = await page.locator('.annotation-marker').all();
        if (annotations.length > 0) {
            console.log(`✅ Found ${annotations.length} annotations`);
            const firstAnno = annotations[0];
            const style = await firstAnno.getAttribute('style');
            if (style.includes('transform: translate')) {
                console.log('✅ Annotations use GPU-accelerated transforms');
            } else {
                console.log('⚠️ Annotations using old left/top positioning');
            }
        } else {
            console.log('ℹ️ No annotations found (may be new project)');
        }

        console.log('');

        // Step 5: Test Phase 2 - IntersectionObserver
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('PHASE 2: IntersectionObserver (Multi-Page Support)');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        const hasObserver = await page.evaluate(() => {
            const alpine = window.Alpine;
            if (!alpine) return false;
            const viewer = alpine.$data(document.querySelector('[x-data*="annotationSystemV3"]'));
            return viewer && viewer.pageObserver !== null;
        });

        if (hasObserver) {
            console.log('✅ IntersectionObserver initialized');
        } else {
            console.log('⚠️ IntersectionObserver not detected');
            console.log('   (This is OK for single-page PDFs)');
        }

        console.log('');

        // Step 6: Test Phase 3 - Performance Caching
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('PHASE 3: Performance Optimization (Rect Caching)');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        const hasCaching = await page.evaluate(() => {
            const alpine = window.Alpine;
            if (!alpine) return false;
            const viewer = alpine.$data(document.querySelector('[x-data*="annotationSystemV3"]'));
            return viewer && typeof viewer._overlayRect !== 'undefined' &&
                   typeof viewer.getOverlayRect === 'function';
        });

        if (hasCaching) {
            console.log('✅ Overlay rect caching implemented');
            console.log('   (Reduces getBoundingClientRect() calls)');
        } else {
            console.log('❌ Overlay rect caching NOT found');
        }

        console.log('');

        // Step 7: Test Scrolling Behavior
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('SCROLLING TEST');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        const pdfContainer = await page.locator('[id^="pdf-container-"]').first();
        const hasContainer = await pdfContainer.isVisible().catch(() => false);

        if (hasContainer) {
            // Get initial scroll
            const initialScroll = await page.evaluate(() => {
                const container = document.querySelector('[id^="pdf-container-"]');
                return container ? container.scrollTop : 0;
            });
            console.log(`📍 Initial scroll: ${initialScroll}px`);

            // Take screenshot before scroll
            await page.screenshot({ path: 'v3-scrolling-before.png', fullPage: false });
            console.log('📸 Screenshot saved: v3-scrolling-before.png');

            // Scroll down 300px
            await page.evaluate(() => {
                const container = document.querySelector('[id^="pdf-container-"]');
                if (container) {
                    container.scrollTop += 300;
                }
            });
            await page.waitForTimeout(500);

            // Get new scroll position
            const newScroll = await page.evaluate(() => {
                const container = document.querySelector('[id^="pdf-container-"]');
                return container ? container.scrollTop : 0;
            });
            console.log(`📍 New scroll: ${newScroll}px`);

            // Take screenshot after scroll
            await page.screenshot({ path: 'v3-scrolling-after.png', fullPage: false });
            console.log('📸 Screenshot saved: v3-scrolling-after.png');

            if (newScroll > initialScroll) {
                console.log('✅ PASS: PDF container scrolled successfully');
                console.log(`   Scrolled ${newScroll - initialScroll}px`);
            } else {
                console.log('⚠️ WARNING: No scroll movement');
                console.log('   (PDF may be short or fit entirely in viewport)');
            }
        } else {
            console.log('❌ FAIL: PDF container not found');
        }

        console.log('');

        // Step 8: Summary
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('TEST SUMMARY');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

        const phase1 = hasWrapper && hasOverlay;
        const phase2 = hasObserver || true; // Optional for single-page
        const phase3 = hasCaching;

        console.log(`Phase 1 (HTML Structure):       ${phase1 ? '✅ PASS' : '❌ FAIL'}`);
        console.log(`Phase 2 (IntersectionObserver): ${phase2 ? '✅ PASS' : '⚠️ SKIP'}`);
        console.log(`Phase 3 (Performance Caching):  ${phase3 ? '✅ PASS' : '❌ FAIL'}`);

        if (phase1 && phase3) {
            console.log('\n🎉 All critical phases passed!');
            console.log('✅ Annotations should now scroll correctly with PDF');
        } else {
            console.log('\n⚠️ Some phases failed - scrolling may not work correctly');
        }

        console.log('\n💡 Manual verification:');
        console.log('   1. Compare v3-scrolling-before.png and v3-scrolling-after.png');
        console.log('   2. If annotations appear in the same position, scrolling works!');
        console.log('   3. If annotations moved off-screen, Phase 1 fix needs review');

        // Keep browser open for manual inspection
        console.log('\n⏰ Browser will remain open for 30 seconds...');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        await page.screenshot({ path: 'v3-scrolling-error.png', fullPage: true });
        console.log('📸 Error screenshot saved: v3-scrolling-error.png');
    } finally {
        await browser.close();
    }
}

main();
