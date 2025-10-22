import { chromium } from '@playwright/test';

/**
 * Debug test to check if page type selector is in the DOM
 */

(async () => {
    console.log('🔍 Debugging page type selector visibility...\n');

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
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in\n');

        // Navigate to annotation viewer
        console.log('📝 Step 2: Opening V2 annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/6?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // Wait for Alpine to initialize
        await page.waitForTimeout(2000);

        // Check if page type selector exists in DOM
        console.log('📝 Step 3: Checking for page type selector...');
        const selectorExists = await page.locator('select[x-model="pageType"]').count();
        console.log(`   Selector count: ${selectorExists}`);

        if (selectorExists === 0) {
            console.log('❌ Page type selector NOT FOUND in DOM!\n');
        } else {
            console.log('✅ Page type selector found in DOM\n');

            // Check if it's visible
            const isVisible = await page.locator('select[x-model="pageType"]').isVisible();
            console.log(`   Is visible: ${isVisible}`);

            // Get computed styles
            const styles = await page.locator('select[x-model="pageType"]').evaluate(el => {
                const computed = window.getComputedStyle(el);
                return {
                    display: computed.display,
                    visibility: computed.visibility,
                    opacity: computed.opacity,
                    width: computed.width,
                    height: computed.height
                };
            });
            console.log('   Computed styles:', styles);
        }

        // Take screenshot of toolbar area
        console.log('\n📝 Step 4: Taking screenshots...');
        await page.screenshot({ path: 'debug-full-page.png', fullPage: false });
        console.log('✅ Full page: debug-full-page.png');

        // Take zoomed screenshot of pagination area
        const paginationBox = await page.locator('.flex.items-center.gap-2.border-r').first().boundingBox();
        if (paginationBox) {
            await page.screenshot({
                path: 'debug-pagination-area.png',
                clip: {
                    x: paginationBox.x - 20,
                    y: paginationBox.y - 20,
                    width: paginationBox.width + 40,
                    height: paginationBox.height + 40
                }
            });
            console.log('✅ Pagination area: debug-pagination-area.png');
        }

        console.log('\n' + '='.repeat(60));
        console.log('✅ Debug Complete!');
        console.log('='.repeat(60));

        console.log('\nBrowser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:', error.message);
        await page.screenshot({ path: 'debug-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
