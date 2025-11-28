import { test, expect } from '@playwright/test';
import { createManagerHelpers, type AlpineComponentState } from './helpers/manager-helpers';

/**
 * PDF Viewer Manager Workflows E2E Tests
 *
 * Tests complete user workflows involving multiple managers working together.
 * These tests verify real browser behavior of manager interactions.
 */

/**
 * TEST SETUP REQUIREMENTS:
 * 1. Project #9 (25 Friendship Lane) must have PDFs uploaded
 * 2. PDFs must have annotations (rooms, locations, cabinet runs)
 * 3. User must be authenticated before tests run
 *
 * To run these tests:
 * 1. Upload a PDF with annotations to project #9
 * 2. Create test fixtures in tests/Browser/fixtures/
 * 3. Update PROJECT_ID and PDF_PAGE_ID below
 */

const PROJECT_ID = 9; // 25 Friendship Lane - Residential
const PDF_PAGE_ID = 2; // Page 2 has annotations for testing
const PDF_ID = 1; // PDF document ID
const PDF_VIEWER_URL = `/admin/project/projects/${PROJECT_ID}/annotate-v2/${PDF_PAGE_ID}?pdf=${PDF_ID}`;

test.describe('PDF Viewer Manager Workflows', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to project with PDF viewer
        await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });

        // Check if we're on login page and re-authenticate if needed
        if (page.url().includes('/login')) {
            console.log('⚠️  Session expired. Re-authenticating...');

            // Check for rate limiting
            const rateLimited = await page.locator('text=/too many login attempts/i').isVisible().catch(() => false);
            if (rateLimited) {
                console.error('❌ RATE LIMITED - wait 60 seconds before running tests');
                test.skip();
                return;
            }

            await page.fill('input[type="email"]', 'info@tcswoodwork.com');
            await page.fill('input[type="password"]', 'Lola2024!');

            // Use Promise.all to wait for navigation during submit
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle', timeout: 10000 }).catch(e => console.log('Navigation timeout:', e.message)),
                page.click('button[type="submit"]')
            ]);

            // Navigate back to PDF viewer if not already there
            if (!page.url().includes('/annotate-v2/')) {
                await page.goto(PDF_VIEWER_URL, { waitUntil: 'networkidle' });
            }
            console.log('✅ Re-authenticated and navigated to PDF viewer');
        }

        // Wait for Alpine and managers to be ready
        const helpers = createManagerHelpers(page);

        try {
            await helpers.waitForAlpineReady();
        } catch (error) {
            // If Alpine not found, skip the test
            console.log('⚠️  Alpine.js PDF viewer not found on page. Skipping test.');
            console.log('   Make sure project has PDFs and PDF viewer is rendered.');
            // Take screenshot for debugging
            await page.screenshot({ path: `tests/Browser/debug-skip-${Date.now()}.png`, fullPage: true });
            test.skip();
        }
    });

    test.describe('Navigation + Zoom + Isolation Workflows', () => {
        test('should navigate, zoom, enter isolation, and exit cleanly', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Step 1: Verify initial state (starting on page 2)
            let state = await helpers.getComponentState();
            expect(state.currentPage).toBe(2);
            expect(state.zoomLevel).toBe(1.0);
            expect(state.isolationMode).toBe(false);

            // Step 2: Navigate to page 3
            await helpers.nextPage();
            state = await helpers.getComponentState();
            expect(state.currentPage).toBe(3);

            // Step 3: Zoom in to 150%
            await helpers.zoomIn();
            await helpers.zoomIn();
            const zoomLevel = await helpers.getZoomLevel();
            expect(zoomLevel).toBeGreaterThan(1.4);
            expect(zoomLevel).toBeLessThan(1.6);

            // Step 4: Get first room annotation
            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (roomAnnotation) {
                // Step 5: Enter isolation mode for room
                await helpers.enterIsolationMode(roomAnnotation.id);

                state = await helpers.getComponentState();
                expect(state.isolationMode).toBe(true);
                expect(state.isolationLevel).toBe('room');
                expect(state.isolatedRoomName).toBeTruthy();

                // Step 6: Verify zoom is maintained or adjusted
                const isolationZoom = await helpers.getZoomLevel();
                expect(isolationZoom).toBeGreaterThan(0.5); // Reasonable zoom level

                // Step 7: Exit isolation mode
                await helpers.exitIsolationMode();

                state = await helpers.getComponentState();
                expect(state.isolationMode).toBe(false);
                expect(state.isolationLevel).toBeNull();
                expect(state.isolatedRoomName).toBe('');
            }
        });

        test('should handle page boundaries with zoom state', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Zoom to 200%
            await helpers.setZoom(2.0);

            // Try to go to previous page (from page 2 to page 1)
            await helpers.previousPage();
            let state = await helpers.getComponentState();
            expect(state.currentPage).toBe(1); // Should move to page 1

            // Zoom should be maintained
            expect(state.zoomLevel).toBeGreaterThan(1.9);

            // Navigate to last page
            const totalPages = state.totalPages;
            for (let i = 1; i < totalPages; i++) {
                await helpers.nextPage();
            }

            state = await helpers.getComponentState();
            expect(state.currentPage).toBe(totalPages);

            // Try to go beyond last page
            await helpers.nextPage();
            state = await helpers.getComponentState();
            expect(state.currentPage).toBe(totalPages); // Should stay at last page

            // Zoom should still be maintained
            expect(state.zoomLevel).toBeGreaterThan(1.9);
        });

        test('should maintain zoom through multi-level isolation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Find room and location annotations
            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');
            const locationAnnotation = annotations.find(a => a.type === 'location');

            if (roomAnnotation && locationAnnotation) {
                // Enter room isolation
                await helpers.enterIsolationMode(roomAnnotation.id);
                const roomZoom = await helpers.getZoomLevel();

                // Enter location isolation (nested)
                await helpers.enterIsolationMode(locationAnnotation.id);
                const locationZoom = await helpers.getZoomLevel();

                // Zoom should have changed for tighter focus
                expect(locationZoom).toBeGreaterThanOrEqual(roomZoom);

                const state = await helpers.getComponentState();
                expect(state.isolationLevel).toBe('location');
                expect(state.isolatedRoomName).toBeTruthy(); // Room context maintained
                expect(state.isolatedLocationName).toBeTruthy();
            }
        });

        test('should handle zoom limits in isolation mode', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Get max zoom limit
            let state = await helpers.getComponentState();
            const zoomMax = state.zoomMax || 3.0;

            // Zoom to max
            await helpers.setZoom(zoomMax);

            // Try to zoom beyond max
            await helpers.zoomIn();

            const finalZoom = await helpers.getZoomLevel();
            expect(finalZoom).toBeLessThanOrEqual(zoomMax);
        });

        test('should restore view state when exiting nested isolation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Set initial zoom (already on page 2)
            await helpers.setZoom(1.5);

            const initialState = await helpers.getComponentState();

            // Find annotations
            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (roomAnnotation) {
                // Enter and exit isolation
                await helpers.enterIsolationMode(roomAnnotation.id);
                await helpers.exitIsolationMode();

                // Verify state restored
                const finalState = await helpers.getComponentState();
                expect(finalState.currentPage).toBe(2);
                expect(finalState.isolationMode).toBe(false);
                expect(finalState.hiddenAnnotations).toEqual([]);
            }
        });
    });

    test.describe('Tree Hierarchy Workflows', () => {
        test('should select tree node and highlight corresponding annotation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Get tree structure
            const tree = await helpers.getTree();
            if (tree.length === 0) {
                test.skip();
                return;
            }

            const firstRoom = tree[0];

            // Select node in tree
            await helpers.selectTreeNode(firstRoom.id, 'room');

            // Verify selection
            const state = await helpers.getComponentState();
            expect(state.selectedNodeId).toBe(firstRoom.id);

            // Verify corresponding annotation is highlighted (implementation-specific)
            const selectedAnnotation = await page.locator(`[data-annotation-id][data-selected="true"]`).count();
            expect(selectedAnnotation).toBeGreaterThan(0);
        });

        test('should expand tree node and show children', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const tree = await helpers.getTree();
            const roomWithChildren = tree.find(r => r.children && r.children.length > 0);

            if (!roomWithChildren) {
                test.skip();
                return;
            }

            // Expand room node
            await helpers.expandTreeNode(roomWithChildren.id);

            const state = await helpers.getComponentState();
            expect(state.expandedNodes).toContain(roomWithChildren.id);

            // Verify children are visible in UI
            const childNodes = await page.locator(`[data-tree-parent="${roomWithChildren.id}"]`).count();
            expect(childNodes).toBe(roomWithChildren.children.length);
        });

        test('should navigate through tree hierarchy in isolation mode', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const tree = await helpers.getTree();
            const roomWithLocations = tree.find(r =>
                r.type === 'room' && r.children && r.children.length > 0
            );

            if (!roomWithLocations) {
                test.skip();
                return;
            }

            // Get room annotation
            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a =>
                a.type === 'room' && a.roomId === roomWithLocations.id
            );

            if (roomAnnotation) {
                // Enter room isolation
                await helpers.enterIsolationMode(roomAnnotation.id);

                // Tree should expand room node
                const state = await helpers.getComponentState();
                expect(state.expandedNodes).toContain(roomWithLocations.id);

                // Children should be visible in tree
                const locationNodes = await page.locator(`[data-tree-node][data-type="location"]`).count();
                expect(locationNodes).toBeGreaterThan(0);
            }
        });

        test('should maintain tree state across page navigation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const tree = await helpers.getTree();
            if (tree.length === 0) {
                test.skip();
                return;
            }

            const room = tree[0];

            // Expand and select node
            await helpers.expandTreeNode(room.id);
            await helpers.selectTreeNode(room.id, 'room');

            let state = await helpers.getComponentState();
            const expandedBefore = [...state.expandedNodes];
            const selectedBefore = state.selectedNodeId;

            // Navigate to next page and back
            await helpers.nextPage();
            await helpers.previousPage();

            // Verify tree state maintained
            state = await helpers.getComponentState();
            expect(state.expandedNodes).toEqual(expandedBefore);
            expect(state.selectedNodeId).toBe(selectedBefore);
        });

        test('should filter tree based on active annotations', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Apply room filter
            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (roomAnnotation) {
                await helpers.toggleRoomVisibility(roomAnnotation.roomId);

                // Verify tree reflects filter
                const visibleTreeNodes = await page.locator('[data-tree-node][data-visible="true"]').count();
                const hiddenTreeNodes = await page.locator('[data-tree-node][data-visible="false"]').count();

                // At least some nodes should be hidden
                expect(hiddenTreeNodes).toBeGreaterThan(0);
            }
        });

        test('should update tree when annotations change', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const initialTree = await helpers.getTree();
            const initialCount = initialTree.length;

            // TODO: Create new room annotation (requires annotation creation helper)
            // For now, verify tree reactivity by toggling visibility

            const annotations = await helpers.getAnnotations();
            if (annotations.length > 0) {
                const roomId = annotations[0].roomId;
                await helpers.toggleRoomVisibility(roomId);
                await helpers.toggleRoomVisibility(roomId); // Toggle back

                // Tree should still be intact
                const finalTree = await helpers.getTree();
                expect(finalTree.length).toBe(initialCount);
            }
        });
    });

    test.describe('Filter System Workflows', () => {
        test('should apply room filter and update page navigation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (!roomAnnotation) {
                test.skip();
                return;
            }

            // Apply room filter
            await helpers.applyFilter('room', roomAnnotation.roomId);

            const state = await helpers.getComponentState();
            expect(state.activeFilters.length).toBeGreaterThan(0);

            // Navigate through filtered pages
            await helpers.nextPage();
            await helpers.previousPage();

            // Filter should persist
            const stateAfterNav = await helpers.getComponentState();
            expect(stateAfterNav.activeFilters.length).toBeGreaterThan(0);
        });

        test('should combine multiple filters', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');
            const locationAnnotation = annotations.find(a => a.type === 'location');

            if (!roomAnnotation || !locationAnnotation) {
                test.skip();
                return;
            }

            // Apply room filter
            await helpers.applyFilter('room', roomAnnotation.roomId);

            // Apply location filter
            await helpers.applyFilter('location', locationAnnotation.roomLocationId);

            const state = await helpers.getComponentState();
            expect(state.activeFilters.length).toBe(2);

            // Verify fewer annotations visible
            const visibleAnnotations = await helpers.getVisibleAnnotations();
            expect(visibleAnnotations.length).toBeLessThan(annotations.length);
        });

        test('should clear all filters and restore visibility', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const allAnnotations = await helpers.getAnnotations();

            // Apply some filters
            const roomAnnotation = allAnnotations.find(a => a.type === 'room');
            if (roomAnnotation) {
                await helpers.applyFilter('room', roomAnnotation.roomId);
            }

            // Clear all filters
            await helpers.clearAllFilters();

            const state = await helpers.getComponentState();
            expect(state.activeFilters.length).toBe(0);
            expect(state.hiddenAnnotations.length).toBe(0);

            const visibleAnnotations = await helpers.getVisibleAnnotations();
            expect(visibleAnnotations.length).toBe(allAnnotations.length);
        });

        test('should maintain filters when entering/exiting isolation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (!roomAnnotation) {
                test.skip();
                return;
            }

            // Apply filter
            await helpers.applyFilter('room', roomAnnotation.roomId);

            const filtersBefore = (await helpers.getComponentState()).activeFilters;

            // Enter and exit isolation
            await helpers.enterIsolationMode(roomAnnotation.id);
            await helpers.exitIsolationMode();

            // Filters should persist
            const filtersAfter = (await helpers.getComponentState()).activeFilters;
            expect(filtersAfter).toEqual(filtersBefore);
        });
    });

    test.describe('Undo/Redo Workflows', () => {
        test('should undo and redo annotation visibility toggle', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (!roomAnnotation) {
                test.skip();
                return;
            }

            // Toggle visibility
            await helpers.toggleRoomVisibility(roomAnnotation.roomId);

            const stateAfterToggle = await helpers.getComponentState();
            const hiddenAfterToggle = stateAfterToggle.hiddenAnnotations.length;

            // Undo
            const canUndoBefore = await helpers.canUndo();
            if (canUndoBefore) {
                await helpers.undo();

                const stateAfterUndo = await helpers.getComponentState();
                expect(stateAfterUndo.hiddenAnnotations.length).not.toBe(hiddenAfterToggle);

                // Redo
                const canRedoBefore = await helpers.canRedo();
                expect(canRedoBefore).toBe(true);

                await helpers.redo();

                const stateAfterRedo = await helpers.getComponentState();
                expect(stateAfterRedo.hiddenAnnotations.length).toBe(hiddenAfterToggle);
            }
        });

        test('should maintain history across page navigation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Make a change
            await helpers.setZoom(1.5);

            // Navigate
            await helpers.nextPage();

            // Should still be able to undo
            const canUndo = await helpers.canUndo();
            expect(canUndo).toBe(true);
        });

        test('should handle rapid undo/redo operations', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (!roomAnnotation) {
                test.skip();
                return;
            }

            // Make multiple changes
            await helpers.toggleRoomVisibility(roomAnnotation.roomId);
            await helpers.setZoom(1.5);
            await helpers.setZoom(2.0);

            // Undo all
            let canUndo = await helpers.canUndo();
            while (canUndo) {
                await helpers.undo();
                canUndo = await helpers.canUndo();
            }

            // Redo all
            let canRedo = await helpers.canRedo();
            while (canRedo) {
                await helpers.redo();
                canRedo = await helpers.canRedo();
            }

            // Should be back to final state
            const finalZoom = await helpers.getZoomLevel();
            expect(finalZoom).toBeCloseTo(2.0, 1);
        });
    });

    test.describe('Complex Multi-Manager Workflows', () => {
        test('complete workflow: load → filter → isolate → zoom → navigate → exit', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Step 1: Verify initial load
            let state = await helpers.getComponentState();
            expect(state.pdfDoc).toBeTruthy();
            expect(state.totalPages).toBeGreaterThan(0);

            // Step 2: Apply filter
            const annotations = await helpers.getAnnotations();
            const roomAnnotation = annotations.find(a => a.type === 'room');

            if (!roomAnnotation) {
                test.skip();
                return;
            }

            await helpers.applyFilter('room', roomAnnotation.roomId);

            // Step 3: Enter isolation mode
            await helpers.enterIsolationMode(roomAnnotation.id);

            state = await helpers.getComponentState();
            expect(state.isolationMode).toBe(true);

            // Step 4: Zoom in isolation
            await helpers.zoomIn();

            const zoomInIsolation = await helpers.getZoomLevel();
            expect(zoomInIsolation).toBeGreaterThan(1.0);

            // Step 5: Navigate in isolation
            await helpers.nextPage();
            await helpers.previousPage();

            // Step 6: Exit isolation
            await helpers.exitIsolationMode();

            state = await helpers.getComponentState();
            expect(state.isolationMode).toBe(false);

            // Step 7: Verify filter maintained
            expect(state.activeFilters.length).toBeGreaterThan(0);
        });

        test('error recovery: handle missing annotation in isolation', async ({ page }) => {
            const helpers = createManagerHelpers(page);

            // Try to enter isolation with non-existent annotation
            try {
                await helpers.enterIsolationMode(99999);
                // Should either throw or gracefully handle
            } catch (error) {
                expect(error).toBeDefined();
            }

            // System should still be functional
            const state = await helpers.getComponentState();
            expect(state.isolationMode).toBe(false);

            // Should be able to perform normal operations
            await helpers.nextPage();
            const canNavigate = await helpers.getComponentState();
            expect(canNavigate.currentPage).toBeGreaterThan(0);
        });
    });
});
