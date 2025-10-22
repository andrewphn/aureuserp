import { chromium } from '@playwright/test';

/**
 * Find and screenshot the page type selector
 */

(async () => {
    console.log('🔍 Finding page type selector...\n');

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

        // Find the page type selector
        console.log('📝 Step 3: Locating page type selector...');
        const selector = page.locator('select[x-model="pageType"]');
        const count = await selector.count();
        console.log(`   Found ${count} selector(s)\n`);

        if (count > 0) {
            // Get its position
            const box = await selector.boundingBox();
            console.log('   Selector position:', box);

            // Highlight it with a red border
            await selector.evaluate(el => {
                el.style.border = '3px solid red';
                el.style.boxShadow = '0 0 10px red';
            });

            await page.waitForTimeout(500);

            // Take full screenshot
            await page.screenshot({ path: 'found-page-type-full.png', fullPage: false });
            console.log('✅ Screenshot: found-page-type-full.png (selector has red border)\n');

            // Take zoomed screenshot
            if (box) {
                await page.screenshot({
                    path: 'found-page-type-zoom.png',
                    clip: {
                        x: Math.max(0, box.x - 50),
                        y: Math.max(0, box.y - 50),
                        width: Math.min(300, box.width + 100),
                        height: Math.min(200, box.height + 100)
                    }
                });
                console.log('✅ Screenshot: found-page-type-zoom.png (zoomed in)\n');
            }

            // Try to interact with it
            console.log('📝 Step 4: Testing interaction...');
            await selector.selectOption('floor_plan');
            await page.waitForTimeout(1000);
            await page.screenshot({ path: 'found-page-type-selected.png', fullPage: false });
            console.log('✅ Screenshot: found-page-type-selected.png (after selecting Floor Plan)\n');
        }

        console.log('='.repeat(60));
        console.log('✅ Test Complete!');
        console.log('='.repeat(60));

        console.log('\nBrowser will stay open for inspection...');
        console.log('Press Ctrl+C to close');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('\n❌ Test Failed:', error.message);
        await page.screenshot({ path: 'find-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
