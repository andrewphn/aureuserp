import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Pan Functionality - Simple Test...\n');

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

        // Check auto-pan status
        const panButton = await page.locator('button[title*="Pan"]').first();
        const panButtonClass = await panButton.getAttribute('class');
        console.log(`Pan button: ${panButtonClass.includes('bg-primary-600') ? '✅ Active' : '❌ Not active'}\n`);

        // Find the PDF container
        const container = await page.evaluate(() => {
            const elem = document.querySelector('[id^="pdf-container-"]');
            if (!elem) return null;
            return {
                id: elem.id,
                scrollLeft: elem.scrollLeft,
                scrollTop: elem.scrollTop,
                scrollWidth: elem.scrollWidth,
                scrollHeight: elem.scrollHeight,
                clientWidth: elem.clientWidth,
                clientHeight: elem.clientHeight
            };
        });

        if (!container) {
            console.log('❌ PDF container not found!');
            throw new Error('PDF container not found');
        }

        console.log('📦 PDF Container:', container);
        console.log(`   Scrollable area: ${container.scrollWidth}×${container.scrollHeight}`);
        console.log(`   Visible area: ${container.clientWidth}×${container.clientHeight}`);
        console.log(`   Can scroll: ${container.scrollWidth > container.clientWidth || container.scrollHeight > container.clientHeight}\n`);

        // Try dragging
        console.log('📝 Attempting to drag...');
        const overlay = await page.locator('.annotation-overlay').first();
        const box = await overlay.boundingBox();

        if (box) {
            const x = box.x + box.width / 2;
            const y = box.y + box.height / 2;

            console.log(`   Start: (${Math.round(x)}, ${Math.round(y)})`);
            await page.mouse.move(x, y);
            await page.waitForTimeout(200);

            await page.mouse.down();
            console.log('   Mouse down');
            await page.waitForTimeout(200);

            const endX = x + 150;
            const endY = y + 150;
            await page.mouse.move(endX, endY, { steps: 10 });
            console.log(`   Dragged to: (${Math.round(endX)}, ${Math.round(endY)})`);
            await page.waitForTimeout(200);

            await page.mouse.up();
            console.log('   Mouse up\n');
            await page.waitForTimeout(500);
        }

        // Check final scroll position
        const finalScroll = await page.evaluate(() => {
            const elem = document.querySelector('[id^="pdf-container-"]');
            return {
                left: elem.scrollLeft,
                top: elem.scrollTop
            };
        });

        console.log(`Initial scroll: (${container.scrollLeft}, ${container.scrollTop})`);
        console.log(`Final scroll:   (${finalScroll.left}, ${finalScroll.top})`);

        const changed = container.scrollLeft !== finalScroll.left || container.scrollTop !== finalScroll.top;

        if (changed) {
            console.log(`\n✅ PAN WORKING! Delta: (${finalScroll.left - container.scrollLeft}, ${finalScroll.top - container.scrollTop})`);
        } else {
            console.log('\n❌ PAN NOT WORKING - scroll position unchanged');
        }

        await page.screenshot({ path: 'pan-test.png' });
        console.log('\nScreenshot: pan-test.png');

        await page.waitForTimeout(2000);
    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'pan-error.png' });
    } finally {
        await browser.close();
    }
})();
