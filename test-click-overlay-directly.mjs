import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Direct Overlay Click...\n');

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

        // Set up console listener for pan messages
        const panMessages = [];
        page.on('console', msg => {
            const text = msg.text();
            if (text.includes('handleMouseDown') ||
                text.includes('startPan') ||
                text.includes('handlePan') ||
                text.includes('endPan')) {
                panMessages.push(text);
            }
        });

        // Zoom in
        console.log('📝 Zooming to 150%...\n');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Click directly on the overlay element using Playwright's locator
        console.log('📝 Clicking directly on overlay element...');
        const overlay = page.locator('.annotation-overlay').first();

        // Verify overlay is visible
        const isVisible = await overlay.isVisible();
        console.log(`   Overlay visible: ${isVisible}`);

        if (isVisible) {
            // Click on the center of the overlay
            await overlay.click({ position: { x: 100, y: 100 } });
            console.log('   ✅ Clicked overlay at relative position (100, 100)');

            await page.waitForTimeout(500);

            // Check if pan methods were called
            console.log('\n📋 Pan console messages:');
            if (panMessages.length > 0) {
                panMessages.forEach(msg => console.log('   ', msg));
                console.log('\n✅ PAN METHODS WERE CALLED!');
            } else {
                console.log('   (none)');
                console.log('\n❌ Pan methods WERE NOT called');
            }
        } else {
            console.log('❌ Overlay is not visible');
        }

        await page.screenshot({ path: 'test-direct-click.png' });
        await page.waitForTimeout(2000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'test-direct-click-error.png' });
    } finally {
        await browser.close();
    }
})();
