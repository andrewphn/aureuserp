import { chromium } from 'playwright';

console.log('🔍 Testing Isolation Mode Scroll Synchronization...\n');

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
});
const page = await context.newPage();

// Listen for console messages
page.on('console', msg => {
    const text = msg.text();
    if (text.includes('🔍') || text.includes('👁️') || text.includes('✓') || text.includes('✋')) {
        console.log(`[BROWSER] ${text}`);
    }
});

try {
    // Step 1: Try to navigate directly (may already be logged in)
    console.log('📝 Checking authentication...');
    await page.goto('http://aureuserp.test/admin');
    await page.waitForTimeout(2000);

    // If we see login form, log in
    const emailField = await page.locator('input[type="email"]').count();
    if (emailField > 0) {
        console.log('📝 Logging in...');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
    } else {
        console.log('✓ Already authenticated');
    }

    // Step 2: Navigate to annotation page (page 2 has annotations)
    console.log('📝 Navigating to annotation page 2...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/2?pdf=1');
    await page.waitForTimeout(5000);

    // Step 3: Get initial state
    console.log('\n📊 === INITIAL STATE (Before Isolation) ===');
    const initialState = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const pdfContainer = document.getElementById('pdf-container-main');
        const canvas = document.querySelector('canvas');

        return {
            timestamp: Date.now(),
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            scrollPosition: {
                x: pdfContainer?.scrollLeft || 0,
                y: pdfContainer?.scrollTop || 0
            },
            canvasRect: canvas ? {
                x: canvas.getBoundingClientRect().x,
                y: canvas.getBoundingClientRect().y,
                width: canvas.getBoundingClientRect().width,
                height: canvas.getBoundingClientRect().height
            } : null,
            annotationCount: alpine?.annotations?.length || 0,
            annotations: alpine?.annotations?.slice(0, 3).map(a => ({
                id: a.id,
                label: a.label,
                pdfX: a.pdfX,
                pdfY: a.pdfY,
                pdfWidth: a.pdfWidth,
                pdfHeight: a.pdfHeight,
                screenX: a.screenX,
                screenY: a.screenY,
                screenWidth: a.screenWidth,
                screenHeight: a.screenHeight
            })) || [],
            isolationMode: alpine?.isolationMode || false,
            zoomLevel: alpine?.zoomLevel || 1
        };
    });

    console.log('Viewport:', initialState.viewport);
    console.log('Scroll Position:', initialState.scrollPosition);
    console.log('Canvas Rect:', initialState.canvasRect);
    console.log('Total Annotations:', initialState.annotationCount);
    console.log('Isolation Mode:', initialState.isolationMode);
    console.log('Zoom Level:', initialState.zoomLevel);
    console.log('\nFirst 3 Annotations:');
    initialState.annotations.forEach((anno, i) => {
        console.log(`  ${i + 1}. ${anno.label}`);
        console.log(`     PDF coords: (${anno.pdfX.toFixed(1)}, ${anno.pdfY.toFixed(1)}) ${anno.pdfWidth.toFixed(1)}×${anno.pdfHeight.toFixed(1)}`);
        console.log(`     Screen coords: (${anno.screenX?.toFixed(1) || 'N/A'}, ${anno.screenY?.toFixed(1) || 'N/A'}) ${anno.screenWidth?.toFixed(1) || 'N/A'}×${anno.screenHeight?.toFixed(1) || 'N/A'}`);
    });

    await page.screenshot({ path: 'isolation-before.png', fullPage: true });

    // Step 4: Find and double-click first annotation
    console.log('\n📝 Looking for annotation to double-click...');
    const annotations = await page.locator('.annotation-marker').all();

    if (annotations.length === 0) {
        console.log('❌ No annotations found on page');
        await browser.close();
        process.exit(1);
    }

    console.log(`✓ Found ${annotations.length} annotation(s)`);
    const firstAnnotation = annotations[0];
    const annotationBox = await firstAnnotation.boundingBox();

    console.log(`\n📍 Annotation box position: (${Math.round(annotationBox.x)}, ${Math.round(annotationBox.y)}) ${Math.round(annotationBox.width)}×${Math.round(annotationBox.height)}`);
    console.log('🖱️  Double-clicking annotation...');

    await firstAnnotation.dblclick();
    await page.waitForTimeout(2000);

    // Step 5: Get state after entering isolation mode
    console.log('\n📊 === AFTER ENTERING ISOLATION MODE ===');
    const isolationState = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const pdfContainer = document.getElementById('pdf-container-main');
        const canvas = document.querySelector('canvas');
        const maskRects = document.getElementById('maskRects');

        // Get mask cutout positions
        const cutouts = Array.from(maskRects?.querySelectorAll('rect') || []).map(rect => ({
            x: parseFloat(rect.getAttribute('x')),
            y: parseFloat(rect.getAttribute('y')),
            width: parseFloat(rect.getAttribute('width')),
            height: parseFloat(rect.getAttribute('height'))
        }));

        // Get visible annotations
        const visibleAnnotations = alpine?.annotations?.filter(a =>
            !alpine.hiddenAnnotations.includes(a.id)
        ).map(a => ({
            id: a.id,
            label: a.label,
            screenX: a.screenX,
            screenY: a.screenY,
            screenWidth: a.screenWidth,
            screenHeight: a.screenHeight
        })) || [];

        return {
            timestamp: Date.now(),
            scrollPosition: {
                x: pdfContainer?.scrollLeft || 0,
                y: pdfContainer?.scrollTop || 0
            },
            canvasRect: canvas ? {
                x: canvas.getBoundingClientRect().x,
                y: canvas.getBoundingClientRect().y,
                width: canvas.getBoundingClientRect().width,
                height: canvas.getBoundingClientRect().height
            } : null,
            isolationMode: alpine?.isolationMode || false,
            zoomLevel: alpine?.zoomLevel || 1,
            hiddenCount: alpine?.hiddenAnnotations?.length || 0,
            visibleCount: visibleAnnotations.length,
            visibleAnnotations: visibleAnnotations,
            maskCutouts: cutouts,
            cacheInfo: {
                lastRectUpdate: alpine?._lastRectUpdate || 0,
                rectCacheMs: alpine?._rectCacheMs || 0
            }
        };
    });

    console.log('Isolation Mode Active:', isolationState.isolationMode);
    console.log('Zoom Level:', isolationState.zoomLevel);
    console.log('Scroll Position:', isolationState.scrollPosition);
    console.log('Canvas Rect:', isolationState.canvasRect);
    console.log('Visible Annotations:', isolationState.visibleCount);
    console.log('Hidden Annotations:', isolationState.hiddenCount);
    console.log('SVG Mask Cutouts:', isolationState.maskCutouts.length);
    console.log('Cache Info:', isolationState.cacheInfo);

    console.log('\nVisible Annotation Positions:');
    isolationState.visibleAnnotations.forEach((anno, i) => {
        console.log(`  ${i + 1}. ${anno.label} at (${anno.screenX?.toFixed(1)}, ${anno.screenY?.toFixed(1)}) ${anno.screenWidth?.toFixed(1)}×${anno.screenHeight?.toFixed(1)}`);
    });

    console.log('\nSVG Mask Cutout Positions:');
    isolationState.maskCutouts.forEach((cutout, i) => {
        console.log(`  ${i + 1}. Cutout at (${cutout.x.toFixed(1)}, ${cutout.y.toFixed(1)}) ${cutout.width.toFixed(1)}×${cutout.height.toFixed(1)}`);
    });

    // Compare first annotation to first cutout
    if (isolationState.visibleAnnotations.length > 0 && isolationState.maskCutouts.length > 0) {
        const anno = isolationState.visibleAnnotations[0];
        const cutout = isolationState.maskCutouts[0];
        const offsetX = Math.abs((anno.screenX || 0) - cutout.x - 15); // Cutout has -15px offset
        const offsetY = Math.abs((anno.screenY || 0) - cutout.y - 15);
        console.log(`\n📏 Initial Alignment Check:`);
        console.log(`   Annotation: (${anno.screenX?.toFixed(1)}, ${anno.screenY?.toFixed(1)})`);
        console.log(`   Cutout (adjusted): (${(cutout.x + 15).toFixed(1)}, ${(cutout.y + 15).toFixed(1)})`);
        console.log(`   Offset: ΔX=${offsetX.toFixed(1)}px, ΔY=${offsetY.toFixed(1)}px`);
        console.log(`   Status: ${offsetX < 5 && offsetY < 5 ? '✅ ALIGNED' : '⚠️  MISALIGNED'}`);
    }

    await page.screenshot({ path: 'isolation-active.png', fullPage: true });

    // Step 6: Scroll the PDF container
    console.log('\n📝 Scrolling PDF container...');
    await page.evaluate(() => {
        const pdfContainer = document.getElementById('pdf-container-main');
        console.log('🔄 Scrolling by (200px, 150px)...');
        pdfContainer.scrollBy(200, 150);
    });

    await page.waitForTimeout(1000); // Wait for scroll handler

    // Step 7: Get state after scrolling
    console.log('\n📊 === AFTER SCROLLING ===');
    const afterScrollState = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const pdfContainer = document.getElementById('pdf-container-main');
        const canvas = document.querySelector('canvas');
        const maskRects = document.getElementById('maskRects');

        // Get mask cutout positions
        const cutouts = Array.from(maskRects?.querySelectorAll('rect') || []).map(rect => ({
            x: parseFloat(rect.getAttribute('x')),
            y: parseFloat(rect.getAttribute('y')),
            width: parseFloat(rect.getAttribute('width')),
            height: parseFloat(rect.getAttribute('height'))
        }));

        // Get visible annotations
        const visibleAnnotations = alpine?.annotations?.filter(a =>
            !alpine.hiddenAnnotations.includes(a.id)
        ).map(a => ({
            id: a.id,
            label: a.label,
            screenX: a.screenX,
            screenY: a.screenY,
            screenWidth: a.screenWidth,
            screenHeight: a.screenHeight
        })) || [];

        return {
            timestamp: Date.now(),
            scrollPosition: {
                x: pdfContainer?.scrollLeft || 0,
                y: pdfContainer?.scrollTop || 0
            },
            canvasRect: canvas ? {
                x: canvas.getBoundingClientRect().x,
                y: canvas.getBoundingClientRect().y,
                width: canvas.getBoundingClientRect().width,
                height: canvas.getBoundingClientRect().height
            } : null,
            visibleAnnotations: visibleAnnotations,
            maskCutouts: cutouts,
            cacheInfo: {
                lastRectUpdate: alpine?._lastRectUpdate || 0,
                rectCacheMs: alpine?._rectCacheMs || 0
            }
        };
    });

    console.log('New Scroll Position:', afterScrollState.scrollPosition);
    console.log('Canvas Rect:', afterScrollState.canvasRect);
    console.log('Cache Info:', afterScrollState.cacheInfo);

    console.log('\nUpdated Annotation Positions:');
    afterScrollState.visibleAnnotations.forEach((anno, i) => {
        console.log(`  ${i + 1}. ${anno.label} at (${anno.screenX?.toFixed(1)}, ${anno.screenY?.toFixed(1)}) ${anno.screenWidth?.toFixed(1)}×${anno.screenHeight?.toFixed(1)}`);
    });

    console.log('\nUpdated SVG Mask Cutout Positions:');
    afterScrollState.maskCutouts.forEach((cutout, i) => {
        console.log(`  ${i + 1}. Cutout at (${cutout.x.toFixed(1)}, ${cutout.y.toFixed(1)}) ${cutout.width.toFixed(1)}×${cutout.height.toFixed(1)}`);
    });

    // Final alignment check
    if (afterScrollState.visibleAnnotations.length > 0 && afterScrollState.maskCutouts.length > 0) {
        const anno = afterScrollState.visibleAnnotations[0];
        const cutout = afterScrollState.maskCutouts[0];
        const offsetX = Math.abs((anno.screenX || 0) - cutout.x - 15);
        const offsetY = Math.abs((anno.screenY || 0) - cutout.y - 15);

        console.log(`\n📏 Final Alignment Check (After Scroll):`);
        console.log(`   Annotation: (${anno.screenX?.toFixed(1)}, ${anno.screenY?.toFixed(1)})`);
        console.log(`   Cutout (adjusted): (${(cutout.x + 15).toFixed(1)}, ${(cutout.y + 15).toFixed(1)})`);
        console.log(`   Offset: ΔX=${offsetX.toFixed(1)}px, ΔY=${offsetY.toFixed(1)}px`);

        const tolerance = 20; // Allow 20px tolerance
        if (offsetX < tolerance && offsetY < tolerance) {
            console.log(`   Status: ✅ SUCCESS - Mask is synchronized with annotation!`);
        } else {
            console.log(`   Status: ❌ FAILURE - Mask is out of sync by ${offsetX.toFixed(1)}px, ${offsetY.toFixed(1)}px`);
        }
    }

    await page.screenshot({ path: 'isolation-after-scroll.png', fullPage: true });

    console.log('\n📸 Screenshots saved:');
    console.log('   - isolation-before.png');
    console.log('   - isolation-active.png');
    console.log('   - isolation-after-scroll.png');

    await page.waitForTimeout(3000);

} catch (error) {
    console.error('\n❌ Error:', error.message);
    await page.screenshot({ path: 'isolation-error.png' });
} finally {
    await browser.close();
}
