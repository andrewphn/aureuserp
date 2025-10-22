import { chromium } from '@playwright/test';

/**
 * Visual Test: Page Type Selector Placement
 *
 * Verifies the page type selector is prominently positioned next to pagination controls
 */

(async () => {
    console.log('🚀 Testing Page Type Selector Placement...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });
    const context = await browser.newContext();
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

        // Test page type selector with different types
        console.log('📝 Step 3: Testing page type selector placement and styling...\n');

        // Cover Page
        console.log('   Testing Cover Page type...');
        await page.selectOption('select[x-model="pageType"]', 'cover');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'page-type-cover-placement.png', fullPage: false });
        console.log('   ✓ Screenshot: page-type-cover-placement.png (blue)');

        // Navigate to next page and test Floor Plan
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1500);
        console.log('   Testing Floor Plan type...');
        await page.selectOption('select[x-model="pageType"]', 'floor_plan');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'page-type-floor-placement.png', fullPage: false });
        console.log('   ✓ Screenshot: page-type-floor-placement.png (green)');

        // Navigate to next page and test Elevation
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1500);
        console.log('   Testing Elevation type...');
        await page.selectOption('select[x-model="pageType"]', 'elevation');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'page-type-elevation-placement.png', fullPage: false });
        console.log('   ✓ Screenshot: page-type-elevation-placement.png (purple)');

        console.log('\n' + '='.repeat(60));
        console.log('✅ Page Type Selector Placement Test Complete!');
        console.log('='.repeat(60));
        console.log('Check screenshots to verify:');
        console.log('  - Page type selector is next to pagination controls');
        console.log('  - Color-coding is prominent and clear');
        console.log('  - Badge shows current page type');
        console.log('  - Layout is clean and accessible');
        console.log('='.repeat(60));

        console.log('\nBrowser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:', error.message);
        await page.screenshot({ path: 'page-type-placement-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
