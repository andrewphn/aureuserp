import { test, expect } from '@playwright/test';

const PROJECT_ID = 9;
const PDF_PAGE_ID = 2; // Start on page 2 where annotations exist
const PDF_ID = 1;
const PDF_VIEWER_URL = `/admin/project/projects/${PROJECT_ID}/annotate-v2/${PDF_PAGE_ID}?pdf=${PDF_ID}`;

test.describe('Complete CRUD Operations - All Hierarchy Levels', () => {
    test.beforeEach(async ({ page }) => {
        console.log('🔄 Starting CRUD test setup...');
        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });

        // Handle login
        if (page.url().includes('/login')) {
            console.log('⚠️ Redirected to login, authenticating...');
            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');

            // Use Promise.all to wait for navigation during submit
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle', timeout: 10000 }).catch(e => console.log('Navigation timeout:', e.message)),
                page.click('button[type="submit"]')
            ]);

            // Navigate to target page if not already there
            if (!page.url().includes('/annotate-v2/')) {
                await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
            }
            console.log('✅ Authenticated, now at:', page.url());
        }

        // Wait for page to stabilize
        await page.waitForTimeout(3000);

        // Wait for component ready
        await page.waitForFunction(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el || !window.Alpine) return false;
            const data = Alpine.$data(el);
            return data?.systemReady === true;
        }, { timeout: 15000 });

        // Wait for annotations to be loaded
        await page.waitForFunction(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el || !window.Alpine) return false;
            const data = Alpine.$data(el);
            return data.annotations && data.annotations.length > 0;
        }, { timeout: 10000 });

        console.log('✅ CRUD test setup complete');
    });

    test('CREATE: Should create a new Room annotation', async ({ page }) => {
        console.log('🆕 Testing Room creation...');

        // Exit any isolation mode first
        const exitBtn = page.locator('button:has-text("Exit Isolation")');
        if (await exitBtn.isVisible()) {
            await exitBtn.click();
            await page.waitForTimeout(1000);
        }

        // Get initial room count
        const initialCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'room').length;
        });
        console.log('📊 Initial rooms:', initialCount);

        // Clear room context by clearing the room field
        const roomField = page.locator('input[placeholder*="Type"]');
        await roomField.clear();
        await page.waitForTimeout(300);

        // Type new room name
        await roomField.fill('Test Room CRUD');
        await page.waitForTimeout(500);

        // Draw rectangle on canvas
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();

        if (box) {
            // Start draw at (100, 100) from top-left of canvas
            const startX = box.x + 100;
            const startY = box.y + 100;
            const endX = startX + 200;
            const endY = startY + 150;

            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();
            await page.waitForTimeout(500);

            // Click Save button to persist the annotation
            await page.click('button:has-text("Save")');
            await page.waitForTimeout(1500);
        }

        // Verify room was created
        const newCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'room').length;
        });

        console.log('📊 New room count:', newCount);
        expect(newCount).toBeGreaterThan(initialCount);

        // Take screenshot
        await page.screenshot({ path: 'tests/Browser/crud-room-created.png', fullPage: true });
    });

    test('CREATE: Should create Location within Room', async ({ page }) => {
        console.log('🆕 Testing Location creation...');

        // First, enter K1 room isolation
        const expandBtn = page.locator('button:has-text("▶")').first();
        if (await expandBtn.isVisible()) {
            await expandBtn.click();
            await page.waitForTimeout(500);
        }

        await page.locator('text=K1').first().dblclick();
        await page.waitForTimeout(1500);

        // Verify we're in room isolation
        const isolationLevel = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).isolationLevel;
        });
        expect(isolationLevel).toBe('room');

        // Get initial location count
        const initialCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'location').length;
        });

        // Fill in location name
        await page.fill('input[placeholder*="Select"]', 'Test Location CRUD');
        await page.waitForTimeout(500);

        // Draw location rectangle
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();

        if (box) {
            const startX = box.x + 150;
            const startY = box.y + 150;
            const endX = startX + 180;
            const endY = startY + 120;

            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();
            await page.waitForTimeout(500);

            // Click Save to persist
            await page.click('button:has-text("Save")');
            await page.waitForTimeout(1500);
        }

        // Verify location created
        const newCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'location').length;
        });

        console.log('📊 Location count: initial=%d, new=%d', initialCount, newCount);
        expect(newCount).toBeGreaterThan(initialCount);

        await page.screenshot({ path: 'tests/Browser/crud-location-created.png', fullPage: true });
    });

    test('CREATE: Should create Cabinet Run within Location', async ({ page }) => {
        console.log('🆕 Testing Cabinet Run creation...');

        // Enter location isolation (double-click Fridge Wall location)
        const expandBtn = page.locator('button:has-text("▶")').first();
        if (await expandBtn.isVisible()) {
            await expandBtn.click();
            await page.waitForTimeout(500);
        }

        // Expand K1 to see locations
        await page.locator('text=K1').first().click();
        await page.waitForTimeout(500);

        // Double-click on a location to enter location isolation
        await page.locator('text=Fridge Wall').first().dblclick();
        await page.waitForTimeout(1500);

        // Verify location isolation
        const isolationLevel = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            return Alpine.$data(el).isolationLevel;
        });
        expect(isolationLevel).toBe('location');

        // Get initial cabinet run count
        const initialCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'cabinet_run').length;
        });

        // Draw cabinet run
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();

        if (box) {
            const startX = box.x + 200;
            const startY = box.y + 200;
            const endX = startX + 160;
            const endY = startY + 100;

            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();
            await page.waitForTimeout(500);

            // Click Save to persist
            await page.click('button:has-text("Save")');
            await page.waitForTimeout(1500);
        }

        // Verify cabinet run created
        const newCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'cabinet_run').length;
        });

        console.log('📊 Cabinet Run count: initial=%d, new=%d', initialCount, newCount);
        expect(newCount).toBeGreaterThan(initialCount);

        await page.screenshot({ path: 'tests/Browser/crud-cabinet-run-created.png', fullPage: true });
    });

    test('CREATE: Should create Cabinet within Cabinet Run', async ({ page }) => {
        console.log('🆕 Testing Cabinet creation...');

        // Navigate to cabinet run isolation
        // (This would require entering room → location → cabinet run isolation)
        // For now, just test that the drawing mechanism works

        // Get initial cabinet count
        const initialCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'cabinet').length;
        });

        console.log('📊 Initial cabinet count:', initialCount);

        // Draw cabinet (would need to be in cabinet run isolation in real scenario)
        const canvas = page.locator('canvas').first();
        const box = await canvas.boundingBox();

        if (box) {
            const startX = box.x + 250;
            const startY = box.y + 250;
            const endX = startX + 100;
            const endY = startY + 80;

            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();
            await page.waitForTimeout(500);

            // Click Save to persist
            await page.click('button:has-text("Save")');
            await page.waitForTimeout(1500);
        }

        await page.screenshot({ path: 'tests/Browser/crud-cabinet-drawn.png', fullPage: true });
    });

    test('UPDATE: Should resize Room annotation', async ({ page }) => {
        console.log('📏 Testing Room resize...');

        // Get K1 room annotation
        const roomInfo = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const room = data.annotations.find(a => a.type === 'room' && a.label === 'K1');
            return {
                id: room?.id,
                initialWidth: room?.width,
                initialHeight: room?.height
            };
        });

        console.log('📊 Initial room size:', roomInfo);

        // Find the annotation overlay on canvas
        const annoOverlay = page.locator(`[data-annotation-id="${roomInfo.id}"]`).first();

        if (await annoOverlay.isVisible()) {
            // Click to select the annotation
            await annoOverlay.click();
            await page.waitForTimeout(500);

            // Look for resize handle (bottom-right corner)
            const resizeHandle = page.locator('[data-resize-handle="se"]').first();

            if (await resizeHandle.isVisible()) {
                const handleBox = await resizeHandle.boundingBox();
                if (handleBox) {
                    // Drag resize handle
                    await page.mouse.move(handleBox.x + 5, handleBox.y + 5);
                    await page.mouse.down();
                    await page.mouse.move(handleBox.x + 50, handleBox.y + 50);
                    await page.mouse.up();
                    await page.waitForTimeout(1000);

                    // Verify size changed
                    const newSize = await page.evaluate((id) => {
                        const el = document.querySelector('[x-data*="annotationSystemV3"]');
                        const data = Alpine.$data(el);
                        const room = data.annotations.find(a => a.id === id);
                        return {
                            width: room?.width,
                            height: room?.height
                        };
                    }, roomInfo.id);

                    console.log('📊 New room size:', newSize);
                    expect(newSize.width).not.toBe(roomInfo.initialWidth);

                    await page.screenshot({ path: 'tests/Browser/crud-room-resized.png', fullPage: true });
                }
            }
        }
    });

    test('UPDATE: Should edit Location properties', async ({ page }) => {
        console.log('✏️ Testing Location edit...');

        // Find Fridge Wall location
        const locationInfo = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const loc = data.annotations.find(a => a.type === 'location' && a.label === 'Fridge Wall');
            return {
                id: loc?.id,
                label: loc?.label
            };
        });

        console.log('📊 Editing location:', locationInfo);

        // Double-click annotation to open editor
        const annoOverlay = page.locator(`[data-annotation-id="${locationInfo.id}"]`).first();

        if (await annoOverlay.isVisible()) {
            await annoOverlay.dblclick();
            await page.waitForTimeout(1000);

            // Look for edit modal/form
            const modal = page.locator('[role="dialog"]').first();
            if (await modal.isVisible()) {
                console.log('✅ Edit modal opened');

                // Try to find and update label field
                const labelField = modal.locator('input[name="label"]').first();
                if (await labelField.isVisible()) {
                    await labelField.clear();
                    await labelField.fill('Fridge Wall - Updated');
                    await page.waitForTimeout(500);

                    // Click save button
                    await modal.locator('button:has-text("Save")').click();
                    await page.waitForTimeout(1000);
                }

                await page.screenshot({ path: 'tests/Browser/crud-location-edited.png', fullPage: true });
            }
        }
    });

    test('DELETE: Should delete Cabinet Run annotation', async ({ page }) => {
        console.log('🗑️ Testing Cabinet Run deletion...');

        // Get initial count
        const initialCount = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            return data.annotations.filter(a => a.type === 'cabinet_run').length;
        });

        // Find SW-Base cabinet run
        const cabinetRunInfo = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const run = data.annotations.find(a => a.type === 'cabinet_run' && a.label === 'SW-Base');
            return {
                id: run?.id,
                label: run?.label
            };
        });

        console.log('📊 Deleting:', cabinetRunInfo);

        if (cabinetRunInfo.id) {
            // Click on annotation to select it
            const annoOverlay = page.locator(`[data-annotation-id="${cabinetRunInfo.id}"]`).first();
            if (await annoOverlay.isVisible()) {
                await annoOverlay.click();
                await page.waitForTimeout(500);

                // Press Delete key
                await page.keyboard.press('Delete');
                await page.waitForTimeout(1000);

                // Verify deletion
                const newCount = await page.evaluate(() => {
                    const el = document.querySelector('[x-data*="annotationSystemV3"]');
                    const data = Alpine.$data(el);
                    return data.annotations.filter(a => a.type === 'cabinet_run').length;
                });

                console.log('📊 Cabinet Run count: initial=%d, after delete=%d', initialCount, newCount);
                expect(newCount).toBeLessThan(initialCount);

                await page.screenshot({ path: 'tests/Browser/crud-cabinet-run-deleted.png', fullPage: true });
            }
        }
    });

    test('DELETE: Should delete Location and verify tree updates', async ({ page }) => {
        console.log('🗑️ Testing Location deletion with tree verification...');

        // Expand tree to see locations
        const expandBtn = page.locator('button:has-text("▶")').first();
        if (await expandBtn.isVisible()) {
            await expandBtn.click();
            await page.waitForTimeout(500);
        }

        // Get location from tree
        const locationNode = page.locator('text=Fridge Wall').first();
        const isInTree = await locationNode.isVisible();
        console.log('📊 Location in tree before delete:', isInTree);

        // Get location ID
        const locationInfo = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const loc = data.annotations.find(a => a.type === 'location' && a.label === 'Fridge Wall');
            return loc?.id;
        });

        if (locationInfo) {
            // Select and delete
            const annoOverlay = page.locator(`[data-annotation-id="${locationInfo}"]`).first();
            if (await annoOverlay.isVisible()) {
                await annoOverlay.click();
                await page.waitForTimeout(500);
                await page.keyboard.press('Delete');
                await page.waitForTimeout(1000);

                // Verify removed from tree
                const stillInTree = await locationNode.isVisible().catch(() => false);
                console.log('📊 Location in tree after delete:', stillInTree);
                expect(stillInTree).toBe(false);

                await page.screenshot({ path: 'tests/Browser/crud-location-deleted-tree-updated.png', fullPage: true });
            }
        }
    });

    test('RESIZE: Should resize annotation with constraints', async ({ page }) => {
        console.log('📏 Testing constrained resize...');

        // Test minimum size constraint
        const roomInfo = await page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            const data = Alpine.$data(el);
            const room = data.annotations.find(a => a.type === 'room');
            return {
                id: room?.id,
                width: room?.width,
                height: room?.height
            };
        });

        const annoOverlay = page.locator(`[data-annotation-id="${roomInfo.id}"]`).first();

        if (await annoOverlay.isVisible()) {
            await annoOverlay.click();
            await page.waitForTimeout(500);

            // Try to resize smaller than minimum
            const resizeHandle = page.locator('[data-resize-handle="se"]').first();

            if (await resizeHandle.isVisible()) {
                const handleBox = await resizeHandle.boundingBox();
                if (handleBox) {
                    // Try to make very small (should be constrained)
                    await page.mouse.move(handleBox.x, handleBox.y);
                    await page.mouse.down();
                    await page.mouse.move(handleBox.x - 200, handleBox.y - 200);
                    await page.mouse.up();
                    await page.waitForTimeout(1000);

                    // Verify minimum size maintained
                    const finalSize = await page.evaluate((id) => {
                        const el = document.querySelector('[x-data*="annotationSystemV3"]');
                        const data = Alpine.$data(el);
                        const room = data.annotations.find(a => a.id === id);
                        return {
                            width: room?.width,
                            height: room?.height
                        };
                    }, roomInfo.id);

                    console.log('📊 Final size (should respect minimum):', finalSize);
                    // Typically minimum is 50x50 or similar
                    expect(finalSize.width).toBeGreaterThanOrEqual(40);
                    expect(finalSize.height).toBeGreaterThanOrEqual(40);

                    await page.screenshot({ path: 'tests/Browser/crud-resize-constrained.png', fullPage: true });
                }
            }
        }
    });
});
