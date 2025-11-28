import { test } from '@playwright/test';

const PDF_VIEWER_URL = `/admin/project/projects/9/annotate-v2/1?pdf=1`;

test('diagnose annotation loading', async ({ page }) => {
    await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });

    // Handle login
    if (page.url().includes('/login')) {
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
    }

    console.log('📍 Current URL:', page.url());

    // Wait for Alpine.js
    await page.waitForFunction(() => window.hasOwnProperty('Alpine'), { timeout: 10000 });
    console.log('✅ Alpine.js loaded');

    // Wait for component element
    await page.waitForSelector('[x-data*="annotationSystemV3"]', { timeout: 10000 });
    console.log('✅ Component element found');

    // Wait a bit for data to load
    await page.waitForTimeout(3000);

    // Get detailed component state
    const componentState = await page.evaluate(() => {
        const el = document.querySelector('[x-data*="annotationSystemV3"]');
        if (!el) return { error: 'Element not found' };

        try {
            const data = Alpine.$data(el);
            return {
                hasData: !!data,
                systemReady: data?.systemReady,
                pdfDoc: !!data?.pdfDoc,
                totalPages: data?.totalPages,
                currentPage: data?.currentPage,
                annotationsCount: data?.annotations?.length || 0,
                annotationsArray: data?.annotations || [],
                // Get first few annotations if they exist
                sampleAnnotations: (data?.annotations || []).slice(0, 3).map(a => ({
                    id: a.id,
                    type: a.type,
                    label: a.label || a.roomName || 'Unnamed',
                    pageNumber: a.pageNumber,
                    visible: a.visible
                })),
                isolationMode: data?.isolationMode,
                zoomLevel: data?.zoomLevel
            };
        } catch (e) {
            return { error: e.message };
        }
    });

    console.log('📊 Component State:', JSON.stringify(componentState, null, 2));

    // Check if annotations are loading asynchronously
    console.log('⏳ Waiting for annotations to load...');
    await page.waitForTimeout(5000);

    const stateAfterWait = await page.evaluate(() => {
        const el = document.querySelector('[x-data*="annotationSystemV3"]');
        const data = Alpine.$data(el);
        return {
            annotationsCount: data?.annotations?.length || 0,
            systemReady: data?.systemReady,
            firstAnnotation: data?.annotations?.[0] || null
        };
    });

    console.log('📊 State after 5s wait:', JSON.stringify(stateAfterWait, null, 2));

    // Check console for any errors
    const consoleMessages = await page.evaluate(() => {
        return (window as any).testConsoleMessages || [];
    });
    console.log('🖥️ Console messages:', consoleMessages);

    // Take screenshot
    await page.screenshot({ path: 'tests/Browser/annotation-diagnostic.png', fullPage: true });

    console.log('✅ Diagnostic complete');
});
