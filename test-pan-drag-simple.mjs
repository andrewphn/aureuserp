import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Simple Pan/Drag Test...\n');

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

        // Zoom in to 150%
        console.log('📝 Zooming to 150%...');
        const zoomInButton = page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Get initial scroll position
        const initialScroll = await page.evaluate(() => {
            const container = document.querySelector('[id^="pdf-container-"]');
            return {
                left: container.scrollLeft,
                top: container.scrollTop
            };
        });
        console.log(`\n📊 Initial scroll: (${initialScroll.left}, ${initialScroll.top})`);

        // Perform drag using mouse actions on the overlay
        console.log('\n📝 Dragging to pan...');
        const overlay = page.locator('.annotation-overlay').first();

        // Use force: true to bypass visibility checks since we know it exists
        const box = await page.evaluate(() => {
            const el = document.querySelector('.annotation-overlay');
            const rect = el.getBoundingClientRect();
            return {
                x: rect.x,
                y: rect.y,
                width: rect.width,
                height: rect.height
            };
        });

        const startX = box.x + 400;
        const startY = box.y + 300;
        const endX = box.x + 300;
        const endY = box.y + 200;

        console.log(`   Drag from (${Math.round(startX)}, ${Math.round(startY)}) to (${Math.round(endX)}, ${Math.round(endY)})`);

        await page.mouse.move(startX, startY);
        await page.waitForTimeout(100);

        await page.mouse.down();
        console.log('   ⬇️  Mouse down');
        await page.waitForTimeout(100);

        await page.mouse.move(endX, endY, { steps: 10 });
        console.log('   ➡️  Dragging...');
        await page.waitForTimeout(100);

        await page.mouse.up();
        console.log('   ⬆️  Mouse up');
        await page.waitForTimeout(500);

        // Get final scroll position
        const finalScroll = await page.evaluate(() => {
            const container = document.querySelector('[id^="pdf-container-"]');
            return {
                left: container.scrollLeft,
                top: container.scrollTop
            };
        });

        console.log(`\n📊 Final scroll: (${finalScroll.left}, ${finalScroll.top})`);

        // Calculate change
        const scrollChangeX = finalScroll.left - initialScroll.left;
        const scrollChangeY = finalScroll.top - initialScroll.top;

        console.log(`\n📏 Scroll change:`);
        console.log(`   Horizontal: ${scrollChangeX}px`);
        console.log(`   Vertical: ${scrollChangeY}px`);

        // Verify pan worked
        if (scrollChangeX !== 0 || scrollChangeY !== 0) {
            console.log('\n✅ SUCCESS! Pan is working - container scrolled as expected.');
        } else {
            console.log('\n❌ FAILED: No scroll change detected');
        }

        await page.screenshot({ path: 'pan-drag-simple.png', fullPage: true });
        await page.waitForTimeout(2000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'pan-drag-error.png' });
    } finally {
        await browser.close();
    }
})();
