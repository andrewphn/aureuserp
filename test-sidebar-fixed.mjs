import { chromium } from '@playwright/test';

(async () => {
    console.log('✅ Testing fixed sidebar...\\n');

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
        console.log('📝 Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in\\n');

        // Navigate to annotation page
        console.log('📝 Opening annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(5000);
        console.log('✅ Page loaded\\n');

        // Check Alpine data
        const alpineData = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (el && el._x_dataStack) {
                return {
                    sidebarCollapsed: el._x_dataStack[0].sidebarCollapsed,
                    currentPage: el._x_dataStack[0].currentPage,
                    pageType: el._x_dataStack[0].pageType
                };
            }
            return null;
        });

        console.log('Alpine state:', alpineData);

        // Check sidebar
        const sidebarVisible = await page.locator('.w-\\[280px\\]').isVisible();
        console.log(`Sidebar visible: ${sidebarVisible}`);

        // Take screenshot
        await page.screenshot({ path: 'sidebar-fixed.png', fullPage: false });
        console.log('\\n✅ Screenshot: sidebar-fixed.png');

        console.log('\\nBrowser will stay open for 30 seconds...');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('\\n❌ Error:', error.message);
        await page.screenshot({ path: 'sidebar-fixed-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\\n✅ Browser closed');
    }
})();
