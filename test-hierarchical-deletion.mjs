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
    await page.goto('http://aureuserp.test/admin/project/projects/9/pdf-review?pdf=1');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On PDF review page');

    // Wait for page content to load
    console.log('\n⏳ Waiting for page to fully load...');
    await page.waitForTimeout(3000);
    console.log('✓ Page loaded');

    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST: Hierarchical Annotation Deletion');
    console.log('═══════════════════════════════════════════════════\n');

    // Get current annotation counts from tree
    console.log('📊 Getting initial annotation counts...');
    const initialCounts = await page.evaluate(() => {
        const treeData = window.treeData || [];

        const countsByType = {
            room: 0,
            location: 0,
            cabinet_run: 0,
            cabinet: 0
        };

        function countNodes(nodes) {
            for (const node of nodes) {
                if (node.type) {
                    countsByType[node.type] = (countsByType[node.type] || 0) + 1;
                }
                if (node.children && node.children.length > 0) {
                    countNodes(node.children);
                }
            }
        }

        countNodes(treeData);
        return countsByType;
    });

    console.log('Initial annotation counts:');
    console.log(`   Rooms: ${initialCounts.room}`);
    console.log(`   Locations: ${initialCounts.location}`);
    console.log(`   Cabinet Runs: ${initialCounts.cabinet_run}`);
    console.log(`   Cabinets: ${initialCounts.cabinet}`);

    // Find a room with children
    console.log('\n🔍 Looking for a room with children to delete...');
    const roomToDelete = await page.evaluate(() => {
        const treeData = window.treeData || [];

        // Find first room that has location children
        for (const room of treeData) {
            if (room.type === 'room' && room.children && room.children.length > 0) {
                // Count total descendants
                let locationCount = 0;
                let cabinetRunCount = 0;
                let cabinetCount = 0;

                for (const location of room.children) {
                    if (location.type === 'location') {
                        locationCount++;

                        if (location.children) {
                            for (const run of location.children) {
                                if (run.type === 'cabinet_run') {
                                    cabinetRunCount++;

                                    if (run.children) {
                                        cabinetCount += run.children.filter(c => c.type === 'cabinet').length;
                                    }
                                }
                            }
                        }
                    }
                }

                return {
                    id: room.id,
                    label: room.label,
                    locationCount,
                    cabinetRunCount,
                    cabinetCount,
                    totalDescendants: locationCount + cabinetRunCount + cabinetCount
                };
            }
        }
        return null;
    });

    if (!roomToDelete) {
        console.log('❌ No room with children found to test deletion');
        console.log('ℹ️  You need to create a room with locations, cabinet runs, and cabinets first');
        await page.screenshot({ path: 'no-room-to-delete.png', fullPage: true });
        await browser.close();
        return;
    }

    console.log(`\n✓ Found room to delete: "${roomToDelete.label}"`);
    console.log(`   Room ID: ${roomToDelete.id}`);
    console.log(`   Total descendants: ${roomToDelete.totalDescendants}`);
    console.log(`   - Locations: ${roomToDelete.locationCount}`);
    console.log(`   - Cabinet Runs: ${roomToDelete.cabinetRunCount}`);
    console.log(`   - Cabinets: ${roomToDelete.cabinetCount}`);

    // Take screenshot before deletion
    await page.screenshot({ path: 'before-deletion.png', fullPage: true });
    console.log('\n📸 Screenshot saved: before-deletion.png');

    // Delete the room
    console.log(`\n🗑️ Deleting room "${roomToDelete.label}"...`);
    const deleteResult = await page.evaluate(async (roomId) => {
        try {
            const response = await fetch(`/api/pdf/page/annotations/${roomId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const data = await response.json();
            return {
                success: data.success,
                message: data.message,
                deletedCount: data.deleted_count,
                error: data.error
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }, roomToDelete.id);

    if (!deleteResult.success) {
        console.log(`❌ Delete failed: ${deleteResult.error}`);
        await page.screenshot({ path: 'delete-failed.png', fullPage: true });
        await browser.close();
        return;
    }

    console.log(`✓ Delete API call successful`);
    console.log(`   ${deleteResult.message}`);
    console.log(`   Annotations deleted: ${deleteResult.deletedCount}`);

    // Wait for refresh
    console.log('\n⏳ Waiting for tree refresh...');
    await page.waitForTimeout(2000);

    // Check if tree was refreshed by getting new counts
    console.log('\n📊 Getting updated annotation counts...');
    const updatedCounts = await page.evaluate(() => {
        const treeData = window.treeData || [];

        const countsByType = {
            room: 0,
            location: 0,
            cabinet_run: 0,
            cabinet: 0
        };

        function countNodes(nodes) {
            for (const node of nodes) {
                if (node.type) {
                    countsByType[node.type] = (countsByType[node.type] || 0) + 1;
                }
                if (node.children && node.children.length > 0) {
                    countNodes(node.children);
                }
            }
        }

        countNodes(treeData);
        return countsByType;
    });

    console.log('Updated annotation counts:');
    console.log(`   Rooms: ${updatedCounts.room} (was ${initialCounts.room})`);
    console.log(`   Locations: ${updatedCounts.location} (was ${initialCounts.location})`);
    console.log(`   Cabinet Runs: ${updatedCounts.cabinet_run} (was ${initialCounts.cabinet_run})`);
    console.log(`   Cabinets: ${updatedCounts.cabinet} (was ${initialCounts.cabinet})`);

    // Calculate what was actually deleted
    const actuallyDeleted = {
        room: initialCounts.room - updatedCounts.room,
        location: initialCounts.location - updatedCounts.location,
        cabinet_run: initialCounts.cabinet_run - updatedCounts.cabinet_run,
        cabinet: initialCounts.cabinet - updatedCounts.cabinet
    };

    console.log('\n🔢 Annotations deleted:');
    console.log(`   Rooms: ${actuallyDeleted.room}`);
    console.log(`   Locations: ${actuallyDeleted.location}`);
    console.log(`   Cabinet Runs: ${actuallyDeleted.cabinet_run}`);
    console.log(`   Cabinets: ${actuallyDeleted.cabinet}`);

    const totalDeleted = actuallyDeleted.room + actuallyDeleted.location + actuallyDeleted.cabinet_run + actuallyDeleted.cabinet;
    console.log(`   Total: ${totalDeleted}`);

    // Take screenshot after deletion
    await page.screenshot({ path: 'after-deletion.png', fullPage: true });
    console.log('\n📸 Screenshot saved: after-deletion.png');

    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST RESULTS');
    console.log('═══════════════════════════════════════════════════\n');

    // Verify deletion counts match
    const expectedTotal = 1 + roomToDelete.totalDescendants; // 1 room + all descendants
    const success = totalDeleted === expectedTotal;

    if (success) {
        console.log('✅ PASS: Hierarchical deletion working correctly');
        console.log(`   Expected to delete: ${expectedTotal} annotations`);
        console.log(`   Actually deleted: ${totalDeleted} annotations`);
    } else {
        console.log('❌ FAIL: Deletion count mismatch');
        console.log(`   Expected to delete: ${expectedTotal} annotations`);
        console.log(`   Actually deleted: ${totalDeleted} annotations`);
        console.log(`   Difference: ${Math.abs(expectedTotal - totalDeleted)}`);
    }

    console.log('\n📁 Screenshots saved:');
    console.log('   - before-deletion.png');
    console.log('   - after-deletion.png');

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('\n❌ Error:', error);
    await page.screenshot({ path: 'test-hierarchical-deletion-error.png', fullPage: true });
    console.log('📸 Error screenshot saved: test-hierarchical-deletion-error.png');
} finally {
    await browser.close();
}
})();
