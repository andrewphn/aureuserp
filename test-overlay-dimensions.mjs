import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Overlay Dimensions...\n');

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

        // Get overlay dimensions and position
        const overlayInfo = await page.evaluate(() => {
            const overlay = document.querySelector('.annotation-overlay');
            if (!overlay) return { error: 'Overlay not found' };

            const rect = overlay.getBoundingClientRect();
            const style = window.getComputedStyle(overlay);

            return {
                exists: true,
                rect: {
                    x: rect.x,
                    y: rect.y,
                    width: rect.width,
                    height: rect.height,
                    top: rect.top,
                    left: rect.left,
                    bottom: rect.bottom,
                    right: rect.right
                },
                computed: {
                    display: style.display,
                    visibility: style.visibility,
                    opacity: style.opacity,
                    pointerEvents: style.pointerEvents,
                    zIndex: style.zIndex,
                    position: style.position,
                    width: style.width,
                    height: style.height,
                    top: style.top,
                    left: style.left
                },
                parent: {
                    tag: overlay.parentElement?.tagName,
                    id: overlay.parentElement?.id,
                    classes: overlay.parentElement?.className
                }
            };
        });

        console.log('📊 Overlay Information:');
        if (overlayInfo.error) {
            console.log('   ❌', overlayInfo.error);
        } else {
            console.log('\n   Bounding Rect:');
            console.log('   - x:', overlayInfo.rect.x);
            console.log('   - y:', overlayInfo.rect.y);
            console.log('   - width:', overlayInfo.rect.width);
            console.log('   - height:', overlayInfo.rect.height);
            console.log('   - top:', overlayInfo.rect.top);
            console.log('   - left:', overlayInfo.rect.left);

            console.log('\n   Computed Styles:');
            console.log('   - display:', overlayInfo.computed.display);
            console.log('   - visibility:', overlayInfo.computed.visibility);
            console.log('   - opacity:', overlayInfo.computed.opacity);
            console.log('   - pointer-events:', overlayInfo.computed.pointerEvents);
            console.log('   - z-index:', overlayInfo.computed.zIndex);
            console.log('   - position:', overlayInfo.computed.position);
            console.log('   - width:', overlayInfo.computed.width);
            console.log('   - height:', overlayInfo.computed.height);
            console.log('   - top:', overlayInfo.computed.top);
            console.log('   - left:', overlayInfo.computed.left);

            console.log('\n   Parent Element:');
            console.log('   - tag:', overlayInfo.parent.tag);
            console.log('   - id:', overlayInfo.parent.id);
            console.log('   - classes:', overlayInfo.parent.classes);

            if (overlayInfo.rect.width === 0 || overlayInfo.rect.height === 0) {
                console.log('\n❌ PROBLEM: Overlay has zero dimensions!');
            } else {
                console.log('\n✅ Overlay has non-zero dimensions');
            }
        }

        await page.screenshot({ path: 'overlay-dimensions.png' });
        await page.waitForTimeout(2000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'overlay-dimensions-error.png' });
    } finally {
        await browser.close();
    }
})();
