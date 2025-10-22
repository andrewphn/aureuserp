import { chromium } from '@playwright/test';

/**
 * Test consolidated viewer with page type selector
 */

(async () => {
    console.log('🚀 Testing consolidated viewer...\n');

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

        // Navigate to the V2 route (the one you were on)
        console.log('📝 Step 2: Opening V2 annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/6?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // Wait for full render
        await page.waitForTimeout(2000);

        // Take screenshot
        console.log('📝 Step 3: Taking screenshot...');
        await page.screenshot({ path: 'consolidated-viewer.png', fullPage: false });
        console.log('✅ Screenshot saved: consolidated-viewer.png\n');

        console.log('\n' + '='.repeat(60));
        console.log('✅ Test Complete!');
        console.log('='.repeat(60));
        console.log('The page type selector should now be visible');
        console.log('under the "Page 6 of 8" pagination!');
        console.log('='.repeat(60));

        console.log('\nBrowser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:', error.message);
        await page.screenshot({ path: 'consolidated-viewer-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
