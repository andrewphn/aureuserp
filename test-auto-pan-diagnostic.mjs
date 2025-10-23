import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Auto-Pan Diagnostic Test\n');

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

        // Check initial state
        console.log('\n--- INITIAL STATE (100% zoom) ---');

        let alpineData = await page.evaluate(() => {
            const component = Alpine.$data(document.querySelector('[x-data]'));
            return {
                zoomLevel: component.zoomLevel,
                drawMode: component.drawMode,
                editorModalOpen: component.editorModalOpen,
                isResizing: component.isResizing,
                isMoving: component.isMoving,
                panMode: component.panMode,
                shouldAutoPan: component.shouldAutoPan()
            };
        });

        console.log('Alpine Data:', JSON.stringify(alpineData, null, 2));

        // Zoom in
        console.log('\n--- ZOOMING IN ---');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Check state after zoom
        console.log('\n--- AFTER ZOOM (150% zoom) ---');

        alpineData = await page.evaluate(() => {
            const component = Alpine.$data(document.querySelector('[x-data]'));
            return {
                zoomLevel: component.zoomLevel,
                drawMode: component.drawMode,
                editorModalOpen: component.editorModalOpen,
                isResizing: component.isResizing,
                isMoving: component.isMoving,
                panMode: component.panMode,
                shouldAutoPan: component.shouldAutoPan()
            };
        });

        console.log('Alpine Data:', JSON.stringify(alpineData, null, 2));

        // Check button state
        const panButton = await page.locator('button[title*="Pan"]').first();
        const panButtonClass = await panButton.getAttribute('class');
        console.log('\nPan Button Classes:', panButtonClass);

        // Check if button is highlighted
        if (panButtonClass.includes('bg-primary-600')) {
            console.log('✅ Button IS highlighted (correct if shouldAutoPan is true)');
        } else {
            console.log('❌ Button is NOT highlighted (incorrect if shouldAutoPan is true)');
        }

        // Check overlay cursor
        const overlay = await page.locator('.annotation-overlay').first();
        const overlayClass = await overlay.getAttribute('class');
        console.log('\nOverlay Classes:', overlayClass);

        if (overlayClass.includes('cursor-grab')) {
            console.log('✅ Cursor is grab');
        } else {
            console.log('❌ Cursor is NOT grab');
        }

        await page.screenshot({ path: 'auto-pan-diagnostic.png', fullPage: false });
        console.log('\nScreenshot saved: auto-pan-diagnostic.png');

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error(error.stack);
    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
    }
})();
