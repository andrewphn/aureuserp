import { chromium } from '@playwright/test';

(async () => {
    console.log('📸 Taking screenshot of page 1...\n');

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

        // Navigate to page 1
        console.log('📝 Step 2: Opening page 1...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // Wait for Alpine to initialize
        await page.waitForTimeout(2000);

        // Take screenshot
        console.log('📝 Step 3: Taking screenshot...');
        await page.screenshot({ path: 'page-1-screenshot.png', fullPage: false });
        console.log('✅ Screenshot: page-1-screenshot.png\n');

        // Check for page type selector
        const selectorCount = await page.locator('select[x-model="pageType"]').count();
        console.log(`   Page type selector count: ${selectorCount}`);

        if (selectorCount > 0) {
            const isVisible = await page.locator('select[x-model="pageType"]').isVisible();
            console.log(`   Is visible: ${isVisible}`);

            // Highlight it
            await page.locator('select[x-model="pageType"]').evaluate(el => {
                el.style.border = '3px solid red';
            });
            await page.waitForTimeout(500);
            await page.screenshot({ path: 'page-1-highlighted.png', fullPage: false });
            console.log('✅ Screenshot: page-1-highlighted.png (with selector highlighted)\n');
        }

        console.log('Browser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'page-1-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
