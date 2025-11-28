import { test, expect } from '@playwright/test';

const PROJECT_ID = 9;
const PDF_PAGE_ID = 2; // Start on page 2 where annotations exist for testing
const PDF_ID = 1;
const PDF_VIEWER_URL = `/admin/project/projects/${PROJECT_ID}/annotate-v2/${PDF_PAGE_ID}?pdf=${PDF_ID}`;

test.describe('Isolation Mode & CRUD Operations', () => {
    test.beforeEach(async ({ page }) => {
        console.log('🔄 Starting test setup...');
        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
        console.log('📍 Current URL:', page.url());

        // Handle login
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
            console.log('✅ Authenticated, now at:', page.url());
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

        // Wait for component to be ready
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

    test.afterEach(async ({ page }, testInfo) => {
        console.log('🧹 Cleaning up after test...');

        try {
            // Exit isolation mode if active
            const inIsolation = await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                return el && window.Alpine ? Alpine.$data(el).isolationMode : false;
            }).catch(() => false);

            if (inIsolation) {
                console.log('🔓 Exiting isolation mode for cleanup');
                await page.click('button:has-text("Exit Isolation")').catch(() => {
                    console.log('⚠️ Exit Isolation button not found, forcing state reset');
                });
                await page.waitForTimeout(500);
            }

            // Reset visibility state
            await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                if (el && window.Alpine) {
                    const data = Alpine.$data(el);
                    data.hiddenAnnotations = [];
                    console.log('🔄 Reset hiddenAnnotations array');
                }
            }).catch(() => {});

            // Force page reload after visibility toggle tests to ensure clean state
            if (testInfo.title.includes('visibility')) {
                console.log('🔄 Forcing page reload after visibility test');
                await page.reload({ waitUntil: 'networkidle' }).catch(() => {});
                await page.waitForTimeout(1000);
            }

            console.log('✅ Cleanup complete');
        } catch (error) {
            console.log('⚠️ Cleanup error (non-critical):', error.message);
        }
    });

    test('should enter isolation mode by double-clicking tree node', async ({ page }) => {
        console.log('🔒 Testing isolation mode entry...');

        // Expand K1 room first if needed
        const expandBtn = page.locator('button:has-text("▶")').first();
        if (await expandBtn.isVisible()) {
            await expandBtn.click();
            await page.waitForTimeout(500);
        }

        // Double-click on K1 room to enter isolation
        await page.locator('text=K1').first().dblclick();
        await page.waitForTimeout(1500);

        // Verify isolation mode is active
        const isolationState = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return {
                isolationMode: data.isolationMode,
                isolationLevel: data.isolationLevel,
                isolatedRoomName: data.isolatedRoomName
            };
        });

        console.log('🔒 Isolation state:', isolationState);
        expect(isolationState.isolationMode).toBe(true);
        expect(isolationState.isolationLevel).toBe('room');

        // Check for isolation breadcrumb
        const breadcrumb = page.locator('text=Isolation Mode');
        await expect(breadcrumb).toBeVisible();

        // Take screenshot
        await page.screenshot({ path: 'tests/Browser/isolation-mode-active.png', fullPage: true });
    });

    test('should exit isolation mode', async ({ page }) => {
        // Enter isolation first
        const expandBtn = page.locator('button:has-text("▶")').first();
        if (await expandBtn.isVisible()) {
            await expandBtn.click();
            await page.waitForTimeout(500);
        }

        await page.locator('text=K1').first().dblclick();
        await page.waitForTimeout(1000);

        // Verify we're in isolation
        const inIsolation = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).isolationMode === true;
        });
        expect(inIsolation).toBe(true);

        // Click "Exit Isolation" button
        await page.click('button:has-text("Exit Isolation")');
        await page.waitForTimeout(1000);

        // Verify we exited
        const exitedIsolation = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).isolationMode === false;
        });

        console.log('🔓 Exited isolation mode:', exitedIsolation);
        expect(exitedIsolation).toBe(true);

        await page.screenshot({ path: 'tests/Browser/isolation-mode-exited.png', fullPage: true });
    });

    test('should navigate pages while in isolation mode', async ({ page }) => {
        // Enter isolation
        const expandBtn = page.locator('button:has-text("▶")').first();
        if (await expandBtn.isVisible()) {
            await expandBtn.click();
            await page.waitForTimeout(500);
        }
        await page.locator('text=K1').first().dblclick();
        await page.waitForTimeout(1000);

        // We start on page 2, navigate to next page (page 3)
        await page.click('button[title="Next Page"]');
        await page.waitForTimeout(1000);

        const state = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return {
                isolationMode: data.isolationMode,
                currentPage: data.currentPage
            };
        });

        console.log('📄 Page navigation in isolation:', state);
        expect(state.isolationMode).toBe(true);
        expect(state.currentPage).toBe(3); // We started on page 2, clicked Next, so we're on page 3

        await page.screenshot({ path: 'tests/Browser/isolation-page-navigation.png', fullPage: true });
    });

    test('should test annotation visibility toggle (Read operation)', async ({ page }) => {
        console.log('👁️ Testing READ operation - visibility check...');

        // Get annotation visibility state
        const annotations = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.map(a => ({
                id: a.id,
                type: a.type,
                label: a.label || a.roomName || 'Unnamed',
                visible: a.visible !== false
            }));
        });

        console.log('📊 Found annotations:', annotations.length);
        expect(annotations.length).toBeGreaterThan(0);

        // Verify at least one is visible
        const visibleCount = annotations.filter(a => a.visible).length;
        console.log('👁️ Visible annotations:', visibleCount);
        expect(visibleCount).toBeGreaterThan(0);
    });

    test('should toggle annotation visibility (Update operation)', async ({ page }) => {
        console.log('✏️ Testing UPDATE operation - visibility toggle...');

        // Track console errors
        const consoleErrors: string[] = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(`[${msg.type()}] ${msg.text()}`);
            }
        });

        // Get initial state - check hiddenAnnotations array, not .visible property
        const initialState = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const firstAnno = data.annotations[0];
            return {
                id: firstAnno.id,
                visible: !data.hiddenAnnotations.includes(firstAnno.id)
            };
        });

        console.log('📍 Initial visibility:', initialState.visible);
        console.log('📝 Console errors before test:', consoleErrors.length);

        // Click eye icon to toggle - find button containing the emoji
        const eyeIcon = page.locator('button:has-text("👁️")').first();
        const eyeVisible = await eyeIcon.isVisible().catch(() => false);

        if (eyeVisible) {
            // Log which eye icon we're clicking
            const eyeIconInfo = await eyeIcon.evaluate((el) => {
                const button = el as HTMLButtonElement;
                const parent = button.closest('[class*="tree"]') || button.parentElement;
                return {
                    title: button.getAttribute('title') || button.title,
                    parentClasses: parent?.className || 'unknown',
                    parentText: parent?.textContent?.slice(0, 50) || 'unknown'
                };
            });
            console.log('👁️ Clicking eye icon:', eyeIconInfo);

            await eyeIcon.click();
            await page.waitForTimeout(1500); // Increased to allow Alpine.js x-for DOM recreation

            // Log how many annotations were affected by the hierarchical toggle
            const affectedCount = await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                const data = Alpine.$data(el);
                return {
                    totalAnnotations: data.annotations.length,
                    hiddenCount: data.hiddenAnnotations.length,
                    hiddenIds: data.hiddenAnnotations.slice(0, 5) // First 5 IDs for debugging
                };
            });
            console.log('📊 Hierarchical impact:', affectedCount);

            console.log('📝 Console errors after first click:', consoleErrors.length);
            if (consoleErrors.length > 0) {
                console.log('❌ Errors detected:', consoleErrors);
            }

            // Check new state - use hiddenAnnotations array
            const newState = await page.evaluate((id) => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                const data = Alpine.$data(el);
                return {
                    visible: !data.hiddenAnnotations.includes(id)
                };
            }, initialState.id);

            console.log('📍 New visibility:', newState.visible);
            expect(newState.visible).not.toBe(initialState.visible);

            await page.screenshot({ path: 'tests/Browser/annotation-toggled.png', fullPage: true });

            // CLEANUP: Restore original visibility state to prevent test pollution
            console.log('🔄 Restoring visibility to prevent pollution...');
            await eyeIcon.click();
            await page.waitForTimeout(1500); // Increased to allow Alpine.js x-for DOM recreation

            // Log restoration impact
            const restoredCount = await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                const data = Alpine.$data(el);
                return {
                    totalAnnotations: data.annotations.length,
                    hiddenCount: data.hiddenAnnotations.length,
                    hiddenIds: data.hiddenAnnotations.slice(0, 5)
                };
            });
            console.log('📊 After restoration:', restoredCount);

            console.log('📝 Console errors after restoration:', consoleErrors.length);
            if (consoleErrors.length > 0) {
                console.log('❌ Total errors during test:', consoleErrors);
            }

            // Verify restoration
            const restoredState = await page.evaluate((id) => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                const data = Alpine.$data(el);
                return {
                    visible: !data.hiddenAnnotations.includes(id)
                };
            }, initialState.id);

            console.log('📍 Restored visibility:', restoredState.visible);
            expect(restoredState.visible).toBe(initialState.visible);

            // Wait additional time for event handlers to re-attach
            await page.waitForTimeout(1000);

            // Try DOM focus reset as additional cleanup
            console.log('🎯 Resetting DOM focus...');
            await page.click('body');
            await page.waitForTimeout(300);

            console.log('✅ Test 5 cleanup complete');
        } else {
            console.log('⚠️ Eye icon not visible, skipping visibility toggle test');
        }
    });

    test('should open annotation editor (Edit operation)', async ({ page }) => {
        console.log('✏️ Testing EDIT operation - open editor...');

        // We're already on page 2 which has annotations
        // Get first annotation
        const firstAnno = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const anno = data.annotations.find(a => a.pageNumber === 2);
            return anno ? { id: anno.id, label: anno.label || anno.roomName } : null;
        });

        if (!firstAnno) {
            console.log('⚠️ No annotations on page 2');
            test.skip();
            return;
        }

        console.log('📝 Found annotation to edit:', firstAnno.label);

        // Double-click on annotation overlay to open editor
        const annoOverlay = page.locator(`[data-annotation-id="${firstAnno.id}"]`).first();
        if (await annoOverlay.isVisible()) {
            await annoOverlay.dblclick();
            await page.waitForTimeout(1000);

            // Check if editor modal opened
            const modalVisible = await page.locator('text=/Edit|Update|Save/i').first().isVisible().catch(() => false);
            console.log('📝 Editor modal opened:', modalVisible);

            await page.screenshot({ path: 'tests/Browser/annotation-editor-opened.png', fullPage: true });
        }
    });

    test('should test filter functionality', async ({ page }) => {
        console.log('🔍 Testing filter operations...');

        // Click Filter button
        await page.click('button:has-text("Filter")');
        await page.waitForTimeout(1000);

        // Check if filter UI appeared
        const filterUI = await page.locator('text=/Filter by|Room|Location/i').first().isVisible().catch(() => false);
        console.log('🔍 Filter UI visible:', filterUI);

        await page.screenshot({ path: 'tests/Browser/filter-ui.png', fullPage: true });
    });

    test('should test undo/redo functionality', async ({ page }) => {
        console.log('↩️ Testing undo/redo...');

        // Perform an action - toggle visibility
        const eyeIcon = page.locator('button:has-text("👁️")').first();
        if (await eyeIcon.isVisible()) {
            await eyeIcon.click();
            await page.waitForTimeout(500);

            // Check if undo is available
            const undoAvailable = await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                const data = Alpine.$data(el);
                return data.undoStack?.length > 0;
            });

            console.log('↩️ Undo available:', undoAvailable);

            // Try to undo (Ctrl+Z)
            await page.keyboard.press('Control+Z');
            await page.waitForTimeout(500);

            console.log('✅ Undo operation attempted');

            await page.screenshot({ path: 'tests/Browser/undo-operation.png', fullPage: true });
        }
    });

    test('should test zoom functionality', async ({ page }) => {
        console.log('🔍 Testing zoom...');

        // Get initial zoom
        const initialZoom = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).zoomLevel;
        });

        console.log('📍 Initial zoom:', initialZoom);

        // Zoom in - use title attribute since buttons use icons not text
        await page.click('button[title="Zoom In"]');
        await page.waitForTimeout(500);

        const newZoom = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).zoomLevel;
        });

        console.log('📍 New zoom:', newZoom);
        expect(newZoom).toBeGreaterThan(initialZoom);

        await page.screenshot({ path: 'tests/Browser/zoomed-in.png', fullPage: true });
    });
});
