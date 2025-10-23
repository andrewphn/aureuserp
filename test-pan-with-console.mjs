import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Pan with Console Logs...\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Collect console messages
    const consoleMessages = [];
    page.on('console', msg => {
        const text = msg.text();
        consoleMessages.push(text);
        if (text.includes('🖐️') || text.includes('Pan')) {
            console.log(`   [CONSOLE] ${text}`);
        }
    });

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
        console.log('\n📝 Zooming in...');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        const zoomText = await page.locator('text=/\\d+%/').textContent();
        console.log(`✅ Current zoom: ${zoomText}`);

        // Check shouldAutoPan() result
        const shouldPan = await page.evaluate(() => {
            const overlay = document.querySelector('.annotation-overlay');
            if (!overlay) return null;
            const component = Alpine.$data(overlay);
            return {
                shouldAutoPan: component.shouldAutoPan ? component.shouldAutoPan() : null,
                panMode: component.panMode,
                zoomLevel: component.zoomLevel,
                drawMode: component.drawMode,
                editorModalOpen: component.editorModalOpen,
                isResizing: component.isResizing,
                isMoving: component.isMoving
            };
        });
        console.log('\n📊 Component State:', JSON.stringify(shouldPan, null, 2));

        // Try dragging
        console.log('\n📝 Attempting to drag...');
        const overlay = await page.locator('.annotation-overlay').first();
        const box = await overlay.boundingBox();

        if (box) {
            const x = box.x + box.width / 2;
            const y = box.y + box.height / 2;

            console.log(`   Drag from (${Math.round(x)}, ${Math.round(y)}) down 150px right`);

            await page.mouse.move(x, y);
            await page.waitForTimeout(200);
            await page.mouse.down();
            console.log('   ⬇️  Mouse down');
            await page.waitForTimeout(500);

            await page.mouse.move(x + 150, y + 150, { steps: 10 });
            console.log('   ➡️  Dragging...');
            await page.waitForTimeout(500);

            await page.mouse.up();
            console.log('   ⬆️  Mouse up');
            await page.waitForTimeout(1000);
        }

        // Check if pan was called
        const panCalled = consoleMessages.some(msg => msg.includes('🖐️ Pan started'));
        console.log(`\n${panCalled ? '✅' : '❌'} Pan methods ${panCalled ? 'WERE' : 'WERE NOT'} called`);

        console.log('\n📋 All pan-related console messages:');
        const panMessages = consoleMessages.filter(msg => msg.includes('🖐️') || msg.toLowerCase().includes('pan'));
        if (panMessages.length > 0) {
            panMessages.forEach(msg => console.log(`   - ${msg}`));
        } else {
            console.log('   (none)');
        }

        await page.screenshot({ path: 'pan-console-test.png' });

        await page.waitForTimeout(2000);
    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'pan-console-error.png' });
    } finally {
        await browser.close();
    }
})();
