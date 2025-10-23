import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Testing Double-Click Zoom...\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Listen for console messages
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('🔍') || text.includes('👁️') || text.includes('✓')) {
            console.log(`[BROWSER] ${text}`);
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

        // Navigate to annotation page
        console.log('📝 Navigating to annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
        await page.waitForTimeout(5000);

        // Get initial zoom level
        const initialZoom = await page.evaluate(() => {
            const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
            return alpine?.currentZoom || null;
        });
        console.log(`\n📊 Initial zoom: ${initialZoom ? (initialZoom * 100).toFixed(0) + '%' : 'N/A'}`);

        // Find and double-click the first annotation
        console.log('\n📝 Looking for annotations...');
        const annotations = await page.locator('.annotation-box').all();

        if (annotations.length === 0) {
            console.log('❌ No annotations found on page');
            await page.screenshot({ path: 'no-annotations.png' });
            return;
        }

        console.log(`✓ Found ${annotations.length} annotation(s)`);

        const firstAnnotation = annotations[0];
        const annotationBox = await firstAnnotation.boundingBox();

        if (annotationBox) {
            console.log(`\n📍 Double-clicking annotation at (${Math.round(annotationBox.x)}, ${Math.round(annotationBox.y)})`);

            // Double-click the annotation
            await firstAnnotation.dblclick();
            await page.waitForTimeout(2000);

            // Get zoom after double-click
            const finalZoom = await page.evaluate(() => {
                const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
                return alpine?.currentZoom || null;
            });

            console.log(`\n📊 Final zoom: ${finalZoom ? (finalZoom * 100).toFixed(0) + '%' : 'N/A'}`);

            // Check if isolation mode is active
            const isolationActive = await page.evaluate(() => {
                const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
                return alpine?.isolationMode || false;
            });

            console.log(`📊 Isolation mode: ${isolationActive ? 'ACTIVE ✓' : 'INACTIVE ✗'}`);

            // Count visible vs hidden annotations
            const visibleCount = await page.locator('.annotation-box:visible').count();
            const totalCount = await page.locator('.annotation-box').count();

            console.log(`📊 Annotations visible: ${visibleCount} / ${totalCount}`);

            await page.screenshot({ path: 'double-click-result.png', fullPage: true });

            if (finalZoom && finalZoom !== initialZoom) {
                console.log('\n✅ SUCCESS! Zoom changed from', (initialZoom * 100).toFixed(0) + '%', 'to', (finalZoom * 100).toFixed(0) + '%');
            } else if (finalZoom === initialZoom) {
                console.log('\n❌ FAILED: Zoom did not change');
            } else {
                console.log('\n⚠️  Could not determine zoom levels');
            }

            if (isolationActive && visibleCount < totalCount) {
                console.log('✅ SUCCESS! Isolation mode is working - hiding other annotations');
            } else if (!isolationActive) {
                console.log('❌ FAILED: Isolation mode not activated');
            }
        }

        await page.waitForTimeout(3000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'double-click-error.png' });
    } finally {
        await browser.close();
    }
})();
