import { chromium } from '@playwright/test';

(async () => {
const browser = await chromium.launch({ headless: false, slowMo: 500 });
const page = await browser.newPage();

try {
    console.log('🔐 Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('✓ Logged in');

    console.log('\n📂 Navigating to PDF review page...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On PDF review page');

    console.log('\n⏳ Waiting for page to fully load...');
    await page.waitForTimeout(3000);
    console.log('✓ Page loaded');

    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST: Hierarchical Path Selection');
    console.log('═══════════════════════════════════════════════════\n');

    // Get tree structure
    console.log('📊 Getting tree structure...');
    const treeStructure = await page.evaluate(() => {
        const treeData = window.treeData || [];

        const structure = [];
        for (const room of treeData) {
            const roomInfo = {
                id: room.id,
                name: room.name,
                type: 'room',
                locations: []
            };

            if (room.children && room.children.length > 0) {
                for (const location of room.children) {
                    const locationInfo = {
                        id: location.id,
                        name: location.name,
                        type: 'location',
                        cabinetRuns: []
                    };

                    if (location.children && location.children.length > 0) {
                        for (const run of location.children) {
                            locationInfo.cabinetRuns.push({
                                id: run.id,
                                name: run.name,
                                type: 'cabinet_run'
                            });
                        }
                    }

                    roomInfo.locations.push(locationInfo);
                }
            }

            structure.push(roomInfo);
        }

        return structure;
    });

    console.log(`Found ${treeStructure.length} rooms in tree`);

    // Test 1: Click on a room and verify only room is in path
    if (treeStructure.length > 0) {
        const room = treeStructure[0];
        console.log(`\n\n🧪 TEST 1: Click on Room "${room.name}"`);
        console.log('Expected path: [roomId]');

        // Expand the room first
        await page.evaluate((roomId) => {
            const expandButton = document.querySelector(`[data-room-id="${roomId}"] button`);
            if (expandButton) expandButton.click();
        }, room.id);
        await page.waitForTimeout(500);

        // Click the room
        await page.evaluate((roomId) => {
            const roomElement = document.querySelector(`[data-room-id="${roomId}"]`);
            if (roomElement) {
                roomElement.click();
            } else {
                // Try finding by text content
                const allRooms = Array.from(document.querySelectorAll('.tree-node > div'));
                const roomDiv = allRooms.find(el => el.textContent.includes('🏠'));
                if (roomDiv) roomDiv.click();
            }
        }, room.id);

        await page.waitForTimeout(1000);

        // Check selectedPath in Alpine
        const pathAfterRoomClick = await page.evaluate(() => {
            return window.alpineData?.selectedPath || [];
        });

        console.log(`Actual path: [${pathAfterRoomClick.join(', ')}]`);
        console.log(`Path length: ${pathAfterRoomClick.length}`);

        if (pathAfterRoomClick.length === 1 && pathAfterRoomClick[0] === room.id) {
            console.log('✅ PASS: Room click - correct path');
        } else {
            console.log('❌ FAIL: Room click - incorrect path');
        }

        // Check visual highlighting
        const roomHighlighted = await page.evaluate(() => {
            const roomElements = Array.from(document.querySelectorAll('.tree-node > div'));
            const roomDiv = roomElements.find(el => el.textContent.includes('🏠'));
            return roomDiv?.classList.contains('bg-blue-100') || roomDiv?.classList.contains('bg-blue-900');
        });

        console.log(`Room highlighted: ${roomHighlighted ? '✅' : '❌'}`);

        // Test 2: Click on a location and verify room + location in path
        if (room.locations.length > 0) {
            const location = room.locations[0];
            console.log(`\n\n🧪 TEST 2: Click on Location "${location.name}"`);
            console.log(`Expected path: [${room.id}, ${location.id}]`);

            // Expand location
            await page.evaluate((locationId) => {
                const allElements = Array.from(document.querySelectorAll('.tree-node > div'));
                const locationDiv = allElements.find(el => el.textContent.includes('📍'));
                if (locationDiv) locationDiv.click();
            }, location.id);

            await page.waitForTimeout(1000);

            // Check selectedPath
            const pathAfterLocationClick = await page.evaluate(() => {
                return window.alpineData?.selectedPath || [];
            });

            console.log(`Actual path: [${pathAfterLocationClick.join(', ')}]`);
            console.log(`Path length: ${pathAfterLocationClick.length}`);

            if (pathAfterLocationClick.length === 2 &&
                pathAfterLocationClick[0] === room.id &&
                pathAfterLocationClick[1] === location.id) {
                console.log('✅ PASS: Location click - correct path');
            } else {
                console.log('❌ FAIL: Location click - incorrect path');
            }

            // Check visual highlighting (both room and location should be highlighted)
            const highlightedNodes = await page.evaluate(() => {
                const highlighted = [];
                const allNodes = Array.from(document.querySelectorAll('.tree-node > div'));

                for (const node of allNodes) {
                    if (node.classList.contains('bg-blue-100') ||
                        node.classList.contains('bg-blue-900') ||
                        node.classList.contains('bg-indigo-100') ||
                        node.classList.contains('bg-indigo-900')) {

                        if (node.textContent.includes('🏠')) {
                            highlighted.push('room');
                        } else if (node.textContent.includes('📍')) {
                            highlighted.push('location');
                        }
                    }
                }

                return highlighted;
            });

            console.log(`Highlighted nodes: ${highlightedNodes.join(', ')}`);

            if (highlightedNodes.includes('room') && highlightedNodes.includes('location')) {
                console.log('✅ PASS: Both room and location are highlighted');
            } else {
                console.log('❌ FAIL: Not all nodes in path are highlighted');
            }

            // Test 3: Click on cabinet run and verify full path
            if (location.cabinetRuns.length > 0) {
                const run = location.cabinetRuns[0];
                console.log(`\n\n🧪 TEST 3: Click on Cabinet Run "${run.name}"`);
                console.log(`Expected path: [${room.id}, ${location.id}, ${run.id}]`);

                // Click cabinet run
                await page.evaluate(() => {
                    const allElements = Array.from(document.querySelectorAll('.tree-node > div'));
                    const runDiv = allElements.find(el => el.textContent.includes('📦'));
                    if (runDiv) runDiv.click();
                });

                await page.waitForTimeout(1000);

                // Check selectedPath
                const pathAfterRunClick = await page.evaluate(() => {
                    return window.alpineData?.selectedPath || [];
                });

                console.log(`Actual path: [${pathAfterRunClick.join(', ')}]`);
                console.log(`Path length: ${pathAfterRunClick.length}`);

                if (pathAfterRunClick.length === 3 &&
                    pathAfterRunClick[0] === room.id &&
                    pathAfterRunClick[1] === location.id &&
                    pathAfterRunClick[2] === run.id) {
                    console.log('✅ PASS: Cabinet run click - correct path');
                } else {
                    console.log('❌ FAIL: Cabinet run click - incorrect path');
                }

                // Check visual highlighting (room, location, and run should all be highlighted)
                const highlightedNodesAfterRun = await page.evaluate(() => {
                    const highlighted = [];
                    const allNodes = Array.from(document.querySelectorAll('.tree-node > div'));

                    for (const node of allNodes) {
                        if (node.classList.contains('bg-blue-100') ||
                            node.classList.contains('bg-blue-900') ||
                            node.classList.contains('bg-indigo-100') ||
                            node.classList.contains('bg-indigo-900')) {

                            if (node.textContent.includes('🏠')) {
                                highlighted.push('room');
                            } else if (node.textContent.includes('📍')) {
                                highlighted.push('location');
                            } else if (node.textContent.includes('📦')) {
                                highlighted.push('cabinet_run');
                            }
                        }
                    }

                    return highlighted;
                });

                console.log(`Highlighted nodes: ${highlightedNodesAfterRun.join(', ')}`);

                if (highlightedNodesAfterRun.includes('room') &&
                    highlightedNodesAfterRun.includes('location') &&
                    highlightedNodesAfterRun.includes('cabinet_run')) {
                    console.log('✅ PASS: All nodes in path are highlighted');
                } else {
                    console.log('❌ FAIL: Not all nodes in path are highlighted');
                }
            }
        }
    }

    // Take final screenshot
    await page.screenshot({ path: 'hierarchical-path-selection-test.png', fullPage: true });
    console.log('\n📸 Screenshot saved: hierarchical-path-selection-test.png');

    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST COMPLETE');
    console.log('═══════════════════════════════════════════════════\n');

    console.log('⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('\n❌ Error:', error);
    await page.screenshot({ path: 'test-hierarchical-path-error.png', fullPage: true });
    console.log('📸 Error screenshot saved: test-hierarchical-path-error.png');
} finally {
    await browser.close();
}
})();
