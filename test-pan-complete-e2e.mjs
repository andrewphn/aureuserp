import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Complete Pan/Drag E2E Test...\n');

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

        // Set up console listener
        const allMessages = [];
        page.on('console', msg => {
            allMessages.push(msg.text());
        });

        // Check initial state (100% zoom)
        console.log('\n📊 At 100% zoom:');
        const autoPan100 = await page.evaluate(() => {
            return window.Livewire?.all?.()?.[0]?.shouldAutoPan?.() || false;
        });
        console.log(`   Auto-pan active: ${autoPan100}`);

        // Zoom in to 150%
        console.log('\n📝 Zooming to 150%...');
        const zoomInButton = page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Verify auto-pan is active at 150%
        const autoPan150 = await page.evaluate(() => {
            return window.Livewire?.all?.()?.[0]?.shouldAutoPan?.() || false;
        });
        console.log(`\n📊 At 150% zoom:`);
        console.log(`   Auto-pan active: ${autoPan150}`);

        // Get initial scroll position
        const initialScroll = await page.evaluate(() => {
            const container = document.querySelector('[id^="pdf-container-"]');
            return {
                left: container.scrollLeft,
                top: container.scrollTop
            };
        });
        console.log(`   Initial scroll: (${initialScroll.left}, ${initialScroll.top})`);

        // Perform drag on overlay
        console.log('\n📝 Dragging to pan...');
        const overlay = page.locator('.annotation-overlay').first();

        // Drag from center to bottom-right
        await overlay.hover({ position: { x: 400, y: 300 } });
        await page.waitForTimeout(200);

        await page.mouse.down();
        console.log('   ⬇️  Mouse down');
        await page.waitForTimeout(200);

        await overlay.hover({ position: { x: 300, y: 200 } });
        console.log('   ➡️  Dragging (moving up-left)');
        await page.waitForTimeout(200);

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
            console.log('\n✅ PAN IS WORKING! Container scrolled as expected.');
        } else {
            console.log('\n❌ No scroll change detected');
        }

        // Show relevant console messages
        const panMessages = allMessages.filter(msg =>
            msg.includes('handleMouseDown') ||
            msg.includes('startPan') ||
            msg.includes('handlePan') ||
            msg.includes('endPan')
        );

        if (panMessages.length > 0) {
            console.log('\n📋 Pan console messages:');
            panMessages.forEach(msg => console.log('   ', msg));
        }

        await page.screenshot({ path: 'pan-complete-e2e.png', fullPage: true });
        await page.waitForTimeout(2000);

        console.log('\n✅ Test complete!');

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error(error.stack);
        await page.screenshot({ path: 'pan-e2e-error.png' });
    } finally {
        await browser.close();
    }
})();
