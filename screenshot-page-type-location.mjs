import { chromium } from '@playwright/test';

/**
 * Screenshot: Show Current Page Type Selector Location
 *
 * Takes a clear screenshot showing where the page type selector appears on the annotation page
 */

(async () => {
    console.log('📸 Taking screenshot of page type selector location...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 300
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
        console.log('📝 Step 2: Opening annotation viewer...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // Wait for page to fully render
        await page.waitForTimeout(2000);

        // Take full page screenshot first
        console.log('📝 Step 3: Taking full page screenshot...');
        await page.screenshot({
            path: 'page-type-location-full.png',
            fullPage: false
        });
        console.log('✅ Screenshot saved: page-type-location-full.png\n');

        // Now select a page type to make it visible with color
        console.log('📝 Step 4: Setting page type to Floor Plan for visibility...');
        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1500);

        // Take screenshot with page type selected
        await page.screenshot({
            path: 'page-type-location-selected.png',
            fullPage: false
        });
        console.log('✅ Screenshot saved: page-type-location-selected.png (green Floor Plan selector)\n');

        // Navigate to next page to show it changes
        console.log('📝 Step 5: Navigating to next page...');
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1500);

        await page.screenshot({
            path: 'page-type-location-page2.png',
            fullPage: false
        });
        console.log('✅ Screenshot saved: page-type-location-page2.png (Page 2)\n');

        console.log('\n' + '='.repeat(60));
        console.log('✅ Screenshot Complete!');
        console.log('='.repeat(60));
        console.log('Check these screenshots:');
        console.log('  1. page-type-location-full.png - Initial state');
        console.log('  2. page-type-location-selected.png - Floor Plan selected (green)');
        console.log('  3. page-type-location-page2.png - Page 2 view');
        console.log('='.repeat(60));

        console.log('\nBrowser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Screenshot Failed:', error.message);
        await page.screenshot({ path: 'page-type-location-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
