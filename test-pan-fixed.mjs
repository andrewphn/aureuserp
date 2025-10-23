import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Pan Fix with Pointer Events...\n');

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

        // Zoom in to activate auto-pan
        console.log('\n📝 Zooming to 150%...');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Check PDF embed pointer-events
        console.log('\n📊 Checking PDF embed pointer-events:');
        const pdfEmbedStyle = await page.evaluate(() => {
            const pdfEmbed = document.querySelector('[x-ref="pdfEmbed"]');
            const computedStyle = window.getComputedStyle(pdfEmbed);
            return {
                inlineStyle: pdfEmbed.getAttribute('style'),
                computedPointerEvents: computedStyle.pointerEvents,
                zIndex: computedStyle.zIndex
            };
        });
        console.log('   Inline style:', pdfEmbedStyle.inlineStyle);
        console.log('   Computed pointer-events:', pdfEmbedStyle.computedPointerEvents);
        console.log('   Computed z-index:', pdfEmbedStyle.zIndex);

        // Check overlay pointer-events
        console.log('\n📊 Checking overlay pointer-events:');
        const overlayStyle = await page.evaluate(() => {
            const overlay = document.querySelector('.annotation-overlay');
            const computedStyle = window.getComputedStyle(overlay);
            return {
                classes: overlay.className,
                computedPointerEvents: computedStyle.pointerEvents,
                computedCursor: computedStyle.cursor,
                zIndex: computedStyle.zIndex
            };
        });
        console.log('   Classes:', overlayStyle.classes);
        console.log('   Computed pointer-events:', overlayStyle.computedPointerEvents);
        console.log('   Computed cursor:', overlayStyle.computedCursor);
        console.log('   Computed z-index:', overlayStyle.zIndex);

        // Set up console message listener for pan events
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

        // Get component state
        console.log('\n📊 Component State:');
        const state = await page.evaluate(() => {
            return window.$wire?.$get?.() || {};
        });
        console.log(JSON.stringify(state, null, 2));

        // Attempt to drag
        console.log('\n📝 Attempting to drag on overlay...');
        const overlay = await page.locator('.annotation-overlay').first();
        const box = await overlay.boundingBox();

        const startX = box.x + box.width / 2;
        const startY = box.y + box.height / 2;
        const endX = startX + 100;
        const endY = startY + 100;

        console.log(`   Drag from (${Math.round(startX)}, ${Math.round(startY)}) to (${Math.round(endX)}, ${Math.round(endY)})`);

        await page.mouse.move(startX, startY);
        console.log('   ⬇️  Mouse down');
        await page.mouse.down();
        await page.waitForTimeout(100);

        console.log('   ➡️  Dragging...');
        await page.mouse.move(endX, endY, { steps: 10 });
        await page.waitForTimeout(100);

        console.log('   ⬆️  Mouse up');
        await page.mouse.up();
        await page.waitForTimeout(500);

        // Check results
        console.log('\n📋 Pan console messages:');
        if (panMessages.length > 0) {
            panMessages.forEach(msg => console.log('   ', msg));
            console.log('\n✅ PAN METHODS WERE CALLED!');
        } else {
            console.log('   (none)');
            console.log('\n❌ Pan methods WERE NOT called');
        }

        // Check scroll position change
        const scrollChanged = await page.evaluate(() => {
            const container = document.querySelector('[id^="pdf-container-"]');
            return {
                scrollLeft: container.scrollLeft,
                scrollTop: container.scrollTop
            };
        });
        console.log('\n📊 Scroll position:', scrollChanged);

        if (scrollChanged.scrollLeft > 0 || scrollChanged.scrollTop > 0) {
            console.log('✅ Container scroll position changed - PAN IS WORKING!');
        }

        await page.screenshot({ path: 'test-pan-fixed.png' });
        await page.waitForTimeout(2000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'test-pan-error.png' });
    } finally {
        await browser.close();
    }
})();
