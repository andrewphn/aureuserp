import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing What Captures Click Events...\n');

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
        console.log('📝 Zooming to 150%...');
        const zoomInButton = await page.locator('button[title*="Zoom In"]').first();
        await zoomInButton.click();
        await page.waitForTimeout(500);
        await zoomInButton.click();
        await page.waitForTimeout(1000);

        // Inject click listener on document to see what gets clicked
        console.log('\n📊 Injecting click listener on document...');
        await page.evaluate(() => {
            window.clickedElements = [];
            document.addEventListener('click', (e) => {
                const target = e.target;
                window.clickedElements.push({
                    tag: target.tagName,
                    id: target.id,
                    classes: target.className,
                    pointerEvents: window.getComputedStyle(target).pointerEvents,
                    zIndex: window.getComputedStyle(target).zIndex,
                    path: e.composedPath().map(el => {
                        if (el.tagName) {
                            return `${el.tagName}${el.id ? '#' + el.id : ''}${el.className ? '.' + el.className.split(' ').join('.') : ''}`;
                        }
                        return el.toString();
                    }).slice(0, 10)
                });
            }, true); // Use capture phase
        });

        // Click on the overlay area
        console.log('\n📝 Clicking on overlay area...');
        const overlay = await page.locator('.annotation-overlay').first();
        const box = await overlay.boundingBox();

        const clickX = box.x + box.width / 2;
        const clickY = box.y + box.height / 2;

        console.log(`   Click at (${Math.round(clickX)}, ${Math.round(clickY)})`);
        await page.mouse.click(clickX, clickY);
        await page.waitForTimeout(500);

        // Get clicked elements
        const clickedInfo = await page.evaluate(() => window.clickedElements);

        console.log('\n📋 Elements that received click:');
        if (clickedInfo && clickedInfo.length > 0) {
            clickedInfo.forEach((info, i) => {
                console.log(`\n   Event ${i + 1}:`);
                console.log(`   Tag: ${info.tag}`);
                console.log(`   ID: ${info.id || '(none)'}`);
                console.log(`   Classes: ${info.classes || '(none)'}`);
                console.log(`   Pointer Events: ${info.pointerEvents}`);
                console.log(`   Z-Index: ${info.zIndex}`);
                console.log(`   Event Path:`);
                info.path.forEach((el, j) => {
                    console.log(`     ${j}: ${el}`);
                });
            });
        } else {
            console.log('   (none captured)');
        }

        // Also check what element is at that point
        console.log('\n📊 Element at click point:');
        const elementAtPoint = await page.evaluate(({ x, y }) => {
            const el = document.elementFromPoint(x, y);
            if (el) {
                const style = window.getComputedStyle(el);
                return {
                    tag: el.tagName,
                    id: el.id,
                    classes: el.className,
                    pointerEvents: style.pointerEvents,
                    zIndex: style.zIndex,
                    cursor: style.cursor
                };
            }
            return null;
        }, { x: clickX, y: clickY });

        if (elementAtPoint) {
            console.log(`   Tag: ${elementAtPoint.tag}`);
            console.log(`   ID: ${elementAtPoint.id || '(none)'}`);
            console.log(`   Classes: ${elementAtPoint.classes || '(none)'}`);
            console.log(`   Pointer Events: ${elementAtPoint.pointerEvents}`);
            console.log(`   Z-Index: ${elementAtPoint.zIndex}`);
            console.log(`   Cursor: ${elementAtPoint.cursor}`);
        } else {
            console.log('   (no element found)');
        }

        await page.screenshot({ path: 'test-click-capture.png' });
        await page.waitForTimeout(2000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'test-click-error.png' });
    } finally {
        await browser.close();
    }
})();
