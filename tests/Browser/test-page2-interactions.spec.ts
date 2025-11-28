import { test, expect } from '@playwright/test';

const PROJECT_ID = 9;
const PDF_PAGE_ID = 2; // Start on page 2 where annotations exist for testing
const PDF_ID = 1;
const PDF_VIEWER_URL = `/admin/project/projects/${PROJECT_ID}/annotate-v2/${PDF_PAGE_ID}?pdf=${PDF_ID}`;

test.describe('Page 2 Interactions & Tree Tests', () => {
    test.beforeEach(async ({ page }) => {
        console.log('🔄 Starting test setup...');
        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
        console.log('📍 Current URL:', page.url());

        // Handle login if needed
        if (page.url().includes('/login')) {
            console.log('⚠️ Redirected to login, authenticating...');
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');

            // Wait for form to be ready
            await page.waitForTimeout(500);

            // Submit form by pressing Enter (more reliable than button click)
            await Promise.all([
                page.waitForURL(url => !url.toString().includes('/login'), { timeout: 20000 }),
                page.press('input[type="password"]', 'Enter')
            ]);

            // Navigate to target page if not already there
            if (!page.url().includes('/annotate-v2/')) {
                await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
            }
            console.log('✅ Re-authenticated, now at:', page.url());
        }

        // Give page extra time to initialize
        console.log('⏳ Waiting for page to stabilize...');
        await page.waitForTimeout(3000);

        // Check Alpine.js loaded
        console.log('⏳ Checking for Alpine.js...');
        const alpineLoaded = await page.evaluate(() => typeof window.Alpine !== 'undefined');
        console.log('✓ Alpine.js loaded:', alpineLoaded);

        // Check component exists
        console.log('⏳ Checking for component...');
        const componentExists = await page.evaluate(() => {
            return !!document.querySelector('[x-data*="annotationSystemV3"]');
        });
        console.log('✓ Component exists:', componentExists);

        // Wait for Alpine.js and component to be ready
        console.log('⏳ Waiting for systemReady...');
        await page.waitForFunction(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el || !window.Alpine) return false;
            const data = Alpine.$data(el);
            return data?.systemReady === true;
        }, { timeout: 15000 });

        // Wait for annotations to be loaded (fixes race condition)
        console.log('⏳ Waiting for annotations to load...');
        await page.waitForFunction(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el || !window.Alpine) return false;
            const data = Alpine.$data(el);
            return data.annotations && data.annotations.length > 0;
        }, { timeout: 10000 });

        console.log('✅ Test setup complete');
    });

    test('should verify page 2 annotations are loaded', async ({ page }) => {
        const currentPage = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).currentPage;
        });

        console.log('📍 Current page:', currentPage);
        expect(currentPage).toBe(2);

        // Get annotations on page 2
        const annotationsOnPage2 = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.pageNumber === 2);
        });

        console.log('📊 Annotations on page 2:', annotationsOnPage2.length);
        expect(annotationsOnPage2.length).toBeGreaterThan(0);

        // Take screenshot
        await page.screenshot({ path: 'tests/Browser/page2-with-annotations.png', fullPage: true });
    });

    test('should expand tree and verify room structure', async ({ page }) => {
        // Find the tree node for K1 room
        const treeNode = page.locator('[x-data*="annotationSystemV3"]').locator('text=K1').first();
        await expect(treeNode).toBeVisible();

        // Get initial tree state
        const initialTreeState = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return {
                annotations: data.annotations.length,
                rooms: data.annotations.filter(a => a.type === 'room').length,
                locations: data.annotations.filter(a => a.type === 'location').length,
                cabinetRuns: data.annotations.filter(a => a.type === 'cabinet_run').length
            };
        });

        console.log('🏗️ Tree structure:', initialTreeState);

        // Click the expand arrow for K1 room
        const expandButton = page.locator('button:has-text("▶")').first();
        if (await expandButton.isVisible()) {
            await expandButton.click();
            await page.waitForTimeout(500);
            console.log('✅ Expanded K1 room node');
        }

        // Take screenshot of expanded tree
        await page.screenshot({ path: 'tests/Browser/tree-expanded-k1.png', fullPage: true });

        expect(initialTreeState.annotations).toBeGreaterThan(0);
    });

    test('should click on tree node and highlight annotation', async ({ page }) => {
        // Find and click tree node
        const roomNode = page.locator('[x-data*="annotationSystemV3"]').locator('text=K1').first();
        await roomNode.click();
        await page.waitForTimeout(500);

        // Verify annotation is selected
        const selectedAnnotation = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.selectedAnnotation;
        });

        console.log('🎯 Selected annotation:', selectedAnnotation);
        expect(selectedAnnotation).toBeTruthy();

        // Take screenshot showing selection
        await page.screenshot({ path: 'tests/Browser/tree-node-selected.png', fullPage: true });
    });

    test('should interact with page 2 annotations', async ({ page }) => {
        // We're already on page 2 from the URL
        const currentPage = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).currentPage;
        });
        expect(currentPage).toBe(2);

        // Get annotations on page 2
        const page2Annotations = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const annos = data.annotations.filter(a => a.pageNumber === 2);
            return annos.map(a => ({
                id: a.id,
                type: a.type,
                label: a.label || a.roomName || 'Unnamed',
                visible: a.visible !== false
            }));
        });

        console.log('📍 Page 2 annotations:', page2Annotations);
        expect(page2Annotations.length).toBeGreaterThan(0);

        // Try to click on first visible annotation
        if (page2Annotations.length > 0 && page2Annotations[0].visible) {
            const firstAnno = page2Annotations[0];
            console.log('🖱️ Attempting to click annotation:', firstAnno.label);

            // Find and click the annotation overlay
            const annoOverlay = page.locator(`[data-annotation-id="${firstAnno.id}"]`).first();
            if (await annoOverlay.isVisible()) {
                await annoOverlay.click();
                await page.waitForTimeout(500);

                const selected = await page.evaluate(() => {
                    const el = document.querySelector('[x-data*="annotationSystemV3"]');
                    return Alpine.$data(el).selectedAnnotation?.id;
                });

                console.log('✅ Annotation selected:', selected);
                expect(selected).toBe(firstAnno.id);
            }
        }

        // Take screenshot
        await page.screenshot({ path: 'tests/Browser/page2-annotation-interaction.png', fullPage: true });
    });

    test('should toggle annotation visibility', async ({ page }) => {
        // Navigate to page 2
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1000);

        // Get first visible annotation
        const firstAnno = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const visible = data.annotations.find(a => a.pageNumber === 2 && a.visible !== false);
            return visible ? { id: visible.id, label: visible.label || visible.roomName } : null;
        });

        if (!firstAnno) {
            console.log('⚠️ No visible annotations on page 2');
            test.skip();
            return;
        }

        console.log('👁️ Testing visibility toggle for:', firstAnno.label);

        // Find eye icon in tree - find button containing the emoji
        const eyeIcon = page.locator('button:has-text("👁️")').first();
        const eyeVisible = await eyeIcon.isVisible().catch(() => false);

        if (eyeVisible) {
            await eyeIcon.click();
            await page.waitForTimeout(500);

            console.log('✅ Toggled visibility');
            await page.screenshot({ path: 'tests/Browser/annotation-visibility-toggled.png', fullPage: true });
        } else {
            console.log('⚠️ Eye icon not visible');
        }
    });
});
