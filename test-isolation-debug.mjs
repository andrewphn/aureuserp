import { chromium } from 'playwright';

console.log('🔍 Deep Investigation: Isolation Mode State Bug\n');

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
});
const page = await context.newPage();

// Capture ALL console messages with timestamps
page.on('console', msg => {
    const timestamp = new Date().toISOString().slice(11, 23);
    console.log(`[${timestamp}] [BROWSER] ${msg.text()}`);
});

try {
    // Step 1: Authentication
    console.log('📝 Checking authentication...');
    await page.goto('http://aureuserp.test/admin');
    await page.waitForTimeout(2000);

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

    // Step 2: Navigate to annotation page 2
    console.log('📝 Navigating to annotation page 2...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/2?pdf=1');
    await page.waitForTimeout(5000);

    // Step 3: Identify the correct Alpine component
    console.log('\n🔍 === COMPONENT IDENTIFICATION ===');
    const componentInfo = await page.evaluate(() => {
        const allComponents = document.querySelectorAll('[x-data]');
        const componentList = Array.from(allComponents).map((el, idx) => {
            const alpine = window.Alpine?.$data(el);
            return {
                index: idx,
                tagName: el.tagName,
                id: el.id || 'no-id',
                classes: el.className || 'no-class',
                hasAnnotations: !!alpine?.annotations,
                annotationCount: alpine?.annotations?.length || 0,
                hasIsolationMode: alpine?.isolationMode !== undefined,
                isolationMode: alpine?.isolationMode || false
            };
        });

        // Find the annotation viewer component (has annotations array)
        const viewerComponent = Array.from(allComponents).find(el => {
            const alpine = window.Alpine?.$data(el);
            return alpine?.annotations && alpine.annotations.length > 0;
        });

        return {
            totalComponents: allComponents.length,
            components: componentList,
            viewerFound: !!viewerComponent,
            viewerIndex: viewerComponent ? Array.from(allComponents).indexOf(viewerComponent) : -1,
            viewerId: viewerComponent?.id || 'unknown'
        };
    });

    console.log(`Total Alpine components: ${componentInfo.totalComponents}`);
    componentInfo.components.forEach(comp => {
        console.log(`  [${comp.index}] ${comp.tagName} id="${comp.id}" annotations=${comp.annotationCount} isolation=${comp.isolationMode}`);
    });
    console.log(`\nAnnotation viewer component: index=${componentInfo.viewerIndex} id="${componentInfo.viewerId}"`);

    if (!componentInfo.viewerFound) {
        console.log('❌ No annotation viewer component found!');
        await browser.close();
        process.exit(1);
    }

    // Step 4: Get container information
    console.log('\n🔍 === CONTAINER IDENTIFICATION ===');
    const containerInfo = await page.evaluate(() => {
        const pdfContainer = document.getElementById('pdf-container-main');
        const allContainers = Array.from(document.querySelectorAll('[id*="pdf-container"]'));

        return {
            mainContainerExists: !!pdfContainer,
            mainContainerType: pdfContainer?.tagName || 'not found',
            allPdfContainers: allContainers.map(c => ({
                id: c.id,
                tagName: c.tagName,
                scrollable: c.scrollHeight > c.clientHeight || c.scrollWidth > c.clientWidth
            }))
        };
    });

    console.log('PDF container "pdf-container-main" exists:', containerInfo.mainContainerExists);
    if (containerInfo.allPdfContainers.length > 0) {
        console.log('All PDF-related containers:');
        containerInfo.allPdfContainers.forEach(c => {
            console.log(`  - ${c.id} (${c.tagName}) scrollable=${c.scrollable}`);
        });
    }

    // Step 5: Get initial state using the correct component
    console.log('\n📊 === INITIAL STATE ===');
    const initialState = await page.evaluate((viewerIndex) => {
        const allComponents = document.querySelectorAll('[x-data]');
        const viewerEl = allComponents[viewerIndex];
        const alpine = window.Alpine?.$data(viewerEl);

        return {
            componentId: viewerEl.id,
            isolationMode: alpine.isolationMode,
            zoomLevel: alpine.zoomLevel || alpine.currentZoom || 1,
            annotationCount: alpine.annotations?.length || 0,
            hiddenCount: alpine.hiddenAnnotations?.length || 0,
            firstAnnotation: alpine.annotations?.[0] ? {
                id: alpine.annotations[0].id,
                label: alpine.annotations[0].label,
                type: alpine.annotations[0].type
            } : null
        };
    }, componentInfo.viewerIndex);

    console.log('Component ID:', initialState.componentId);
    console.log('Isolation Mode:', initialState.isolationMode);
    console.log('Zoom Level:', initialState.zoomLevel);
    console.log('Annotations:', initialState.annotationCount);
    console.log('Hidden:', initialState.hiddenCount);
    if (initialState.firstAnnotation) {
        console.log('First annotation:', initialState.firstAnnotation.label, `(${initialState.firstAnnotation.type})`);
    }

    // Step 6: Inject detailed logging into the page
    console.log('\n📝 Injecting detailed logging...');
    await page.evaluate((viewerIndex) => {
        const allComponents = document.querySelectorAll('[x-data]');
        const viewerEl = allComponents[viewerIndex];
        const alpine = window.Alpine?.$data(viewerEl);

        // Store original methods
        const originalEnterIsolation = alpine.enterIsolationMode;
        const originalUpdateMask = alpine.updateIsolationMask;
        const originalZoomToFit = alpine.zoomToFitAnnotation;

        // Wrap enterIsolationMode with detailed logging
        alpine.enterIsolationMode = async function(anno) {
            console.log('🔵 [1] enterIsolationMode() called with:', anno.type, anno.label);
            console.log('🔵 [2] State BEFORE: isolationMode =', this.isolationMode);

            const result = await originalEnterIsolation.call(this, anno);

            console.log('🔵 [3] State AFTER: isolationMode =', this.isolationMode);
            console.log('🔵 [4] hiddenAnnotations =', this.hiddenAnnotations?.length || 0, 'items');
            console.log('🔵 [5] zoomLevel =', this.zoomLevel || this.currentZoom);

            return result;
        };

        // Wrap zoomToFitAnnotation
        alpine.zoomToFitAnnotation = async function(anno) {
            console.log('🟢 [ZOOM] zoomToFitAnnotation() called');
            console.log('🟢 [ZOOM] isolationMode BEFORE zoom =', this.isolationMode);

            const result = await originalZoomToFit.call(this, anno);

            console.log('🟢 [ZOOM] isolationMode AFTER zoom =', this.isolationMode);
            console.log('🟢 [ZOOM] zoomLevel =', this.zoomLevel || this.currentZoom);

            return result;
        };

        // Wrap updateIsolationMask
        alpine.updateIsolationMask = function() {
            console.log('🟡 [MASK] updateIsolationMask() called');
            console.log('🟡 [MASK] isolationMode =', this.isolationMode);
            console.log('🟡 [MASK] hiddenAnnotations =', this.hiddenAnnotations?.length || 0);

            const visibleCount = this.annotations?.filter(a =>
                !this.hiddenAnnotations.includes(a.id)
            ).length || 0;

            console.log('🟡 [MASK] visibleAnnotations =', visibleCount);

            const result = originalUpdateMask.call(this);

            const maskRects = document.getElementById('maskRects');
            const cutoutCount = maskRects?.querySelectorAll('rect').length || 0;
            console.log('🟡 [MASK] Created', cutoutCount, 'mask cutouts');

            return result;
        };

        console.log('✅ Logging injected successfully');
    }, componentInfo.viewerIndex);

    await page.waitForTimeout(500);

    // Step 7: Find and double-click annotation
    console.log('\n🖱️  === DOUBLE-CLICK ACTION ===');
    const annotations = await page.locator('.annotation-marker').all();

    if (annotations.length === 0) {
        console.log('❌ No annotation markers found on page');
        await browser.close();
        process.exit(1);
    }

    console.log(`Found ${annotations.length} annotation marker(s)`);
    const firstAnnotation = annotations[0];
    const box = await firstAnnotation.boundingBox();
    console.log(`Target: (${Math.round(box.x)}, ${Math.round(box.y)}) ${Math.round(box.width)}×${Math.round(box.height)}`);

    console.log('\n⏱️  T=0ms: Double-clicking annotation...');
    await firstAnnotation.dblclick();

    // Check state at multiple intervals
    for (const delay of [100, 500, 1000, 2000]) {
        await page.waitForTimeout(delay === 100 ? 100 : delay - (delay === 500 ? 100 : delay === 1000 ? 500 : 1000));

        const state = await page.evaluate((viewerIndex) => {
            const allComponents = document.querySelectorAll('[x-data]');
            const viewerEl = allComponents[viewerIndex];
            const alpine = window.Alpine?.$data(viewerEl);

            return {
                isolationMode: alpine.isolationMode,
                zoomLevel: alpine.zoomLevel || alpine.currentZoom || 1,
                hiddenCount: alpine.hiddenAnnotations?.length || 0,
                visibleCount: alpine.annotations?.filter(a =>
                    !alpine.hiddenAnnotations.includes(a.id)
                ).length || 0
            };
        }, componentInfo.viewerIndex);

        console.log(`\n⏱️  T=${delay}ms: isolation=${state.isolationMode} zoom=${state.zoomLevel.toFixed(2)} hidden=${state.hiddenCount} visible=${state.visibleCount}`);
    }

    // Final state check
    console.log('\n📊 === FINAL STATE (T=2000ms) ===');
    const finalState = await page.evaluate((viewerIndex) => {
        const allComponents = document.querySelectorAll('[x-data]');
        const viewerEl = allComponents[viewerIndex];
        const alpine = window.Alpine?.$data(viewerEl);
        const maskRects = document.getElementById('maskRects');

        return {
            isolationMode: alpine.isolationMode,
            isolationLevel: alpine.isolationLevel,
            isolatedRoomId: alpine.isolatedRoomId,
            isolatedRoomName: alpine.isolatedRoomName,
            zoomLevel: alpine.zoomLevel || alpine.currentZoom || 1,
            hiddenCount: alpine.hiddenAnnotations?.length || 0,
            visibleCount: alpine.annotations?.filter(a =>
                !alpine.hiddenAnnotations.includes(a.id)
            ).length || 0,
            maskCutoutCount: maskRects?.querySelectorAll('rect').length || 0,
            hiddenAnnotations: alpine.hiddenAnnotations || []
        };
    }, componentInfo.viewerIndex);

    console.log('Isolation Mode:', finalState.isolationMode);
    console.log('Isolation Level:', finalState.isolationLevel || 'none');
    console.log('Isolated Room:', finalState.isolatedRoomName || 'none');
    console.log('Zoom Level:', finalState.zoomLevel);
    console.log('Hidden Annotations:', finalState.hiddenCount, finalState.hiddenAnnotations);
    console.log('Visible Annotations:', finalState.visibleCount);
    console.log('Mask Cutouts:', finalState.maskCutoutCount);

    if (finalState.isolationMode && finalState.maskCutoutCount > 0) {
        console.log('\n✅ SUCCESS: Isolation mode is active and mask cutouts are created!');
    } else if (finalState.isolationMode && finalState.maskCutoutCount === 0) {
        console.log('\n⚠️  PARTIAL: Isolation mode is active but NO mask cutouts!');
    } else {
        console.log('\n❌ FAILURE: Isolation mode is NOT active!');
    }

    await page.screenshot({ path: 'isolation-debug-final.png', fullPage: true });
    console.log('\n📸 Screenshot saved: isolation-debug-final.png');

    await page.waitForTimeout(3000);

} catch (error) {
    console.error('\n❌ Error:', error.message);
    console.error(error.stack);
    await page.screenshot({ path: 'isolation-debug-error.png' });
} finally {
    await browser.close();
}
