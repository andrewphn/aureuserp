import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing ALL Elements at Click Point...\n');

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

        // Zoom in
        console.log('📝 Zooming to 150%...\n');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Get overlay position
        const overlayBox = await page.locator('.annotation-overlay').first().boundingBox();
        const clickX = overlayBox.x + overlayBox.width / 2;
        const clickY = overlayBox.y + overlayBox.height / 2;

        console.log(`📍 Testing click point: (${Math.round(clickX)}, ${Math.round(clickY)})\n`);

        // Get ALL elements at that point
        const elementsAtPoint = await page.evaluate(({ x, y }) => {
            const elements = document.elementsFromPoint(x, y);
            return elements.map(el => {
                const style = window.getComputedStyle(el);
                const rect = el.getBoundingClientRect();
                return {
                    tag: el.tagName,
                    id: el.id || '(none)',
                    classes: el.className || '(none)',
                    pointerEvents: style.pointerEvents,
                    zIndex: style.zIndex,
                    position: style.position,
                    display: style.display,
                    opacity: style.opacity,
                    width: rect.width,
                    height: rect.height,
                    hasMousedownListener: el.getAttribute('@mousedown') !== null || el.hasAttribute('x-on:mousedown'),
                    xRefs: el.getAttribute('x-ref')
                };
            });
        }, { x: clickX, y: clickY });

        console.log('📊 ALL Elements at click point (top to bottom):');
        elementsAtPoint.forEach((el, i) => {
            console.log(`\n   ${i + 1}. ${el.tag}${el.id !== '(none)' ? '#' + el.id : ''}`);
            console.log(`      Classes: ${el.classes}`);
            console.log(`      Pointer Events: ${el.pointerEvents}`);
            console.log(`      Z-Index: ${el.zIndex}`);
            console.log(`      Position: ${el.position}`);
            console.log(`      Display: ${el.display}`);
            console.log(`      Opacity: ${el.opacity}`);
            console.log(`      Dimensions: ${Math.round(el.width)}×${Math.round(el.height)}`);
            console.log(`      Has @mousedown: ${el.hasMousedownListener}`);
            if (el.xRefs) console.log(`      x-ref: ${el.xRefs}`);
        });

        // Find the overlay in the list
        const overlayIndex = elementsAtPoint.findIndex(el =>
            el.classes.includes('annotation-overlay')
        );

        if (overlayIndex === -1) {
            console.log('\n❌ Overlay NOT found in elements at click point!');
        } else if (overlayIndex === 0) {
            console.log('\n✅ Overlay is the TOPMOST element at click point!');
        } else {
            console.log(`\n⚠️  Overlay is at position ${overlayIndex + 1}, NOT the topmost element!`);
            console.log(`    Elements ABOVE the overlay:`);
            for (let i = 0; i < overlayIndex; i++) {
                const el = elementsAtPoint[i];
                console.log(`      ${i + 1}. ${el.tag}${el.id !== '(none)' ? '#' + el.id : ''} (pointer-events: ${el.pointerEvents}, z-index: ${el.zIndex})`);
            }
        }

        await page.screenshot({ path: 'all-elements-at-point.png' });
        await page.waitForTimeout(2000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        console.error(error.stack);
        await page.screenshot({ path: 'elements-error.png' });
    } finally {
        await browser.close();
    }
})();
