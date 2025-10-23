import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Checking Overlay Classes...\n');

    const browser = await chromium.launch({ headless: false });
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
        await page.waitForTimeout(3000);

        // Navigate
        console.log('📝 Navigating to annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
        await page.waitForTimeout(5000);

        // Check overlay at 100%
        console.log('\n📝 At 100% zoom:');
        const overlay = await page.locator('.annotation-overlay').first();
        let overlayClass = await overlay.getAttribute('class');
        console.log(`   Classes: ${overlayClass}`);
        console.log(`   Has pointer-events-auto: ${overlayClass.includes('pointer-events-auto')}`);
        console.log(`   Has pointer-events-none: ${overlayClass.includes('pointer-events-none')}`);

        // Zoom in
        console.log('\n📝 Zooming to 150%...');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Check overlay at 150%
        console.log('\n📝 At 150% zoom:');
        overlayClass = await overlay.getAttribute('class');
        console.log(`   Classes: ${overlayClass}`);
        console.log(`   Has pointer-events-auto: ${overlayClass.includes('pointer-events-auto')}`);
        console.log(`   Has pointer-events-none: ${overlayClass.includes('pointer-events-none')}`);
        console.log(`   Has cursor-grab: ${overlayClass.includes('cursor-grab')}`);

        // Check computed style
        const computedStyle = await overlay.evaluate(el => {
            const style = window.getComputedStyle(el);
            return {
                pointerEvents: style.pointerEvents,
                cursor: style.cursor,
                zIndex: style.zIndex
            };
        });
        console.log('\n📊 Computed Style:', computedStyle);

        await page.screenshot({ path: 'overlay-class-check.png' });
        await page.waitForTimeout(2000);
    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'overlay-error.png' });
    } finally {
        await browser.close();
    }
})();
