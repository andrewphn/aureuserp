import { chromium } from '@playwright/test';

/**
 * Visual Test: Page Type Selector Under Page Number
 *
 * Verifies the page type selector now appears directly under the page number
 */

(async () => {
    console.log('🚀 Testing Page Type Selector Under Page Number...\n');

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
        console.log('📝 Step 2: Opening annotation viewer...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // Wait for full render
        await page.waitForTimeout(2000);

        // Take screenshot of default state
        console.log('📝 Step 3: Screenshot - default state...');
        await page.screenshot({ path: 'page-type-under-default.png', fullPage: false });
        console.log('✅ Screenshot: page-type-under-default.png\n');

        // Select Floor Plan type
        console.log('📝 Step 4: Setting to Floor Plan...');
        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'page-type-under-floor.png', fullPage: false });
        console.log('✅ Screenshot: page-type-under-floor.png (green)\n');

        // Navigate to next page
        console.log('📝 Step 5: Navigating to page 2...');
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1500);

        // Select Elevation type
        await page.selectOption('select[x-model="pageType"]', 'elevation');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'page-type-under-elevation.png', fullPage: false });
        console.log('✅ Screenshot: page-type-under-elevation.png (purple)\n');

        console.log('\n' + '='.repeat(60));
        console.log('✅ Test Complete!');
        console.log('='.repeat(60));
        console.log('Check screenshots to verify:');
        console.log('  - Page type selector is UNDER "Page X of Y"');
        console.log('  - Selector is vertically stacked');
        console.log('  - Color-coding is clear and prominent');
        console.log('  - Layout is clean and compact');
        console.log('='.repeat(60));

        console.log('\nBrowser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:', error.message);
        await page.screenshot({ path: 'page-type-under-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
