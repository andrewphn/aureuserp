import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Pan Functionality...\n');

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

        // Navigate to annotation page
        console.log('📝 Navigating to annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
        await page.waitForTimeout(5000);

        // Zoom in
        console.log('📝 Zooming in...');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        const zoomText = await page.locator('text=/\\d+%/').textContent();
        console.log(`✅ Current zoom: ${zoomText}\n`);

        // Check if auto-pan is active
        const panButton = await page.locator('button[title*="Pan"]').first();
        const panButtonClass = await panButton.getAttribute('class');
        console.log(`Pan button classes: ${panButtonClass}`);

        if (panButtonClass.includes('bg-primary-600')) {
            console.log('✅ Auto-pan is active (button highlighted)\n');
        } else {
            console.log('❌ Auto-pan not active\n');
        }

        // Get initial scroll position
        const container = await page.locator('#pdf-container').first();
        const initialScroll = await container.evaluate(el => ({
            left: el.scrollLeft,
            top: el.scrollTop,
            scrollWidth: el.scrollWidth,
            scrollHeight: el.scrollHeight,
            clientWidth: el.clientWidth,
            clientHeight: el.clientHeight
        }));
        console.log('Initial scroll state:', initialScroll);

        // Try to pan by dragging
        console.log('\n📝 Attempting to pan by dragging...');
        const overlay = await page.locator('.annotation-overlay').first();
        const overlayBox = await overlay.boundingBox();

        if (overlayBox) {
            // Start drag from center
            const startX = overlayBox.x + overlayBox.width / 2;
            const startY = overlayBox.y + overlayBox.height / 2;

            // Drag 200px to the right and down
            const endX = startX + 200;
            const endY = startY + 200;

            console.log(`Dragging from (${startX}, ${startY}) to (${endX}, ${endY})`);

            await page.mouse.move(startX, startY);
            await page.waitForTimeout(100);
            await page.mouse.down();
            await page.waitForTimeout(100);
            await page.mouse.move(endX, endY, { steps: 10 });
            await page.waitForTimeout(100);
            await page.mouse.up();
            await page.waitForTimeout(500);
        }

        // Check final scroll position
        const finalScroll = await container.evaluate(el => ({
            left: el.scrollLeft,
            top: el.scrollTop
        }));
        console.log('Final scroll state:', finalScroll);

        const scrollChanged = initialScroll.left !== finalScroll.left || initialScroll.top !== finalScroll.top;

        if (scrollChanged) {
            console.log('\n✅ PAN IS WORKING! Scroll position changed.');
            console.log(`   Delta X: ${finalScroll.left - initialScroll.left}`);
            console.log(`   Delta Y: ${finalScroll.top - initialScroll.top}`);
        } else {
            console.log('\n❌ PAN NOT WORKING - Scroll position did not change');

            // Debug: Check if overlay is receiving events
            const overlayClass = await overlay.getAttribute('class');
            console.log('\nOverlay classes:', overlayClass);

            const overlayStyle = await overlay.getAttribute('style');
            console.log('Overlay style:', overlayStyle);
        }

        await page.screenshot({ path: 'pan-test-result.png', fullPage: false });
        console.log('\nScreenshot saved: pan-test-result.png');

        await page.waitForTimeout(3000);
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        await page.screenshot({ path: 'pan-test-error.png', fullPage: false });
    } finally {
        await browser.close();
    }
})();
