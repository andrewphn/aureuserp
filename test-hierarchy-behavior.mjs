import { chromium } from '@playwright/test';

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

    console.log('\n📂 Navigating to annotation page (Page 3 - K1 elevations)...');
    // Page 3 should have K1 location elevations
    await page.goto('http://aureuserp.test/admin/projects/pdf-documents/15/annotate-pdf');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On annotation page');

    // Wait for Alpine.js to load
    await page.waitForSelector('[x-data]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    // Switch to room view
    console.log('\n🏠 Switching to room view...');
    const roomViewButton = page.locator('button:has-text("🏠 By Room")');
    await roomViewButton.click();
    await page.waitForTimeout(1000);

    // Expand K1
    console.log('\n📂 Expanding K1...');
    const k1Node = page.locator('.tree-node:has-text("K1")').first();
    const k1ExpandButton = k1Node.locator('button').first();
    await k1ExpandButton.click();
    await page.waitForTimeout(500);

    // TEST 1: Click location (Sink Wall), then draw location
    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST 1: Click location → draw location (should be SIBLINGS)');
    console.log('═══════════════════════════════════════════════════\n');

    // Find and click Sink Wall
    console.log('🎯 Clicking Sink Wall location...');
    const sinkWallNode = page.locator('.tree-node').filter({ hasText: /Sink Wall/i }).first();
    await sinkWallNode.click();
    await page.waitForTimeout(1000);

    // Check active context
    const contextAfterLocationClick = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return {
            activeRoomId: alpine?.activeRoomId,
            activeLocationId: alpine?.activeLocationId,
            activeRoomName: alpine?.activeRoomName,
            activeLocationName: alpine?.activeLocationName
        };
    });

    console.log('📍 Active context after clicking Sink Wall:');
    console.log(`  activeRoomId: ${contextAfterLocationClick.activeRoomId}`);
    console.log(`  activeRoomName: ${contextAfterLocationClick.activeRoomName}`);
    console.log(`  activeLocationId: ${contextAfterLocationClick.activeLocationId}`);
    console.log(`  activeLocationName: ${contextAfterLocationClick.activeLocationName}`);

    // Enter draw mode for location
    console.log('\n🖊️ Entering draw mode for NEW location...');
    const locationButton = page.locator('button:has-text("📍 Location")');
    await locationButton.click();
    await page.waitForTimeout(500);

    // Draw a location annotation
    console.log('✏️ Drawing new location annotation...');
    const canvas = page.locator('canvas').first();
    const canvasBox = await canvas.boundingBox();

    if (!canvasBox) {
        throw new Error('Canvas not found');
    }

    const startX = canvasBox.x + 150;
    const startY = canvasBox.y + 150;
    const endX = startX + 60;
    const endY = startY + 60;

    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(endX, endY);
    await page.mouse.up();
    await page.waitForTimeout(1000);
    console.log('✓ Drew new location annotation');

    // Check the newly created annotation
    const newLocationAnnotation = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const tempAnnotation = alpine?.annotations?.find(a => String(a.id).startsWith('temp_'));
        if (!tempAnnotation) return null;

        return {
            id: tempAnnotation.id,
            label: tempAnnotation.label,
            type: tempAnnotation.type,
            parentId: tempAnnotation.parentId,
            roomId: tempAnnotation.roomId,
            roomLocationId: tempAnnotation.roomLocationId
        };
    });

    console.log('\n📊 New location annotation:');
    console.log(`  ID: ${newLocationAnnotation?.id}`);
    console.log(`  Label: ${newLocationAnnotation?.label}`);
    console.log(`  Type: ${newLocationAnnotation?.type}`);
    console.log(`  parentId: ${newLocationAnnotation?.parentId ?? 'NULL'}`);
    console.log(`  roomId: ${newLocationAnnotation?.roomId ?? 'NULL'}`);

    // Find Sink Wall annotation on this page
    const sinkWallAnnotation = await page.evaluate((locationId) => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const sinkWall = alpine?.annotations?.find(a =>
            a.type === 'location' &&
            a.roomLocationId === locationId &&
            !String(a.id).startsWith('temp_')
        );
        return sinkWall ? {
            id: sinkWall.id,
            label: sinkWall.label,
            parentId: sinkWall.parentId,
            roomId: sinkWall.roomId
        } : null;
    }, contextAfterLocationClick.activeLocationId);

    console.log('\n📊 Sink Wall annotation on this page:');
    console.log(`  ID: ${sinkWallAnnotation?.id}`);
    console.log(`  Label: ${sinkWallAnnotation?.label}`);
    console.log(`  parentId: ${sinkWallAnnotation?.parentId ?? 'NULL'}`);
    console.log(`  roomId: ${sinkWallAnnotation?.roomId ?? 'NULL'}`);

    // CHECK: Are they siblings?
    console.log('\n✅ TEST 1 RESULT:');
    if (newLocationAnnotation?.parentId === sinkWallAnnotation?.parentId) {
        console.log('✅✅✅ SUCCESS! New location and Sink Wall are SIBLINGS (same parent)');
        console.log(`   Both have parentId: ${newLocationAnnotation?.parentId}`);
    } else if (newLocationAnnotation?.parentId === sinkWallAnnotation?.id) {
        console.log('❌ FAILURE! New location is CHILD of Sink Wall');
        console.log(`   New location parentId: ${newLocationAnnotation?.parentId}`);
        console.log(`   Sink Wall ID: ${sinkWallAnnotation?.id}`);
        console.log('   Should be siblings, not parent-child');
    } else {
        console.log('⚠️  UNEXPECTED! Parent relationships unclear');
        console.log(`   New location parentId: ${newLocationAnnotation?.parentId}`);
        console.log(`   Sink Wall parentId: ${sinkWallAnnotation?.parentId}`);
    }

    // TEST 2: Click cabinet run, then draw cabinet
    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST 2: Click cabinet run → draw cabinet (should be PARENT-CHILD)');
    console.log('═══════════════════════════════════════════════════\n');

    // First need to check if there's a cabinet run on this page
    const cabinetRunExists = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const cabinetRun = alpine?.annotations?.find(a => a.type === 'cabinet_run');
        return cabinetRun ? {
            id: cabinetRun.id,
            label: cabinetRun.label,
            cabinetRunId: cabinetRun.cabinetRunId
        } : null;
    });

    if (!cabinetRunExists) {
        console.log('⚠️  No cabinet run on this page to test with');
        console.log('   Test 2 skipped - would need to create a cabinet run first');
    } else {
        console.log(`🎯 Found cabinet run: ${cabinetRunExists.label} (ID: ${cabinetRunExists.id})`);

        // Expand Sink Wall to see cabinet runs
        console.log('\n📂 Looking for cabinet run in tree...');
        const sinkWallExpandButton = sinkWallNode.locator('button').first();
        await sinkWallExpandButton.click();
        await page.waitForTimeout(500);

        // Find and click cabinet run node
        const cabinetRunNode = page.locator('.tree-node').filter({
            hasText: new RegExp(cabinetRunExists.label, 'i')
        }).first();

        await cabinetRunNode.click();
        await page.waitForTimeout(1000);

        // Enter draw mode for cabinet
        console.log('\n🖊️ Entering draw mode for cabinet...');
        const cabinetButton = page.locator('button:has-text("🗄️ Cabinet")');
        await cabinetButton.click();
        await page.waitForTimeout(500);

        // Draw a cabinet annotation
        console.log('✏️ Drawing cabinet annotation...');
        const startX2 = canvasBox.x + 250;
        const startY2 = canvasBox.y + 150;
        const endX2 = startX2 + 50;
        const endY2 = startY2 + 50;

        await page.mouse.move(startX2, startY2);
        await page.mouse.down();
        await page.mouse.move(endX2, endY2);
        await page.mouse.up();
        await page.waitForTimeout(1000);
        console.log('✓ Drew cabinet annotation');

        // Check the newly created cabinet annotation
        const newCabinetAnnotation = await page.evaluate(() => {
            const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
            const tempAnnotation = alpine?.annotations?.find(a =>
                String(a.id).startsWith('temp_') &&
                a.type === 'cabinet'
            );
            if (!tempAnnotation) return null;

            return {
                id: tempAnnotation.id,
                label: tempAnnotation.label,
                type: tempAnnotation.type,
                parentId: tempAnnotation.parentId,
                cabinetRunId: tempAnnotation.cabinetRunId
            };
        });

        console.log('\n📊 New cabinet annotation:');
        console.log(`  ID: ${newCabinetAnnotation?.id}`);
        console.log(`  Label: ${newCabinetAnnotation?.label}`);
        console.log(`  Type: ${newCabinetAnnotation?.type}`);
        console.log(`  parentId: ${newCabinetAnnotation?.parentId ?? 'NULL'}`);

        // CHECK: Is it a child of the cabinet run?
        console.log('\n✅ TEST 2 RESULT:');
        if (newCabinetAnnotation?.parentId === cabinetRunExists.id) {
            console.log('✅✅✅ SUCCESS! New cabinet is CHILD of cabinet run');
            console.log(`   Cabinet parentId: ${newCabinetAnnotation?.parentId}`);
            console.log(`   Cabinet run ID: ${cabinetRunExists.id}`);
        } else {
            console.log('❌ FAILURE! New cabinet is NOT child of cabinet run');
            console.log(`   Cabinet parentId: ${newCabinetAnnotation?.parentId}`);
            console.log(`   Cabinet run ID: ${cabinetRunExists.id}`);
        }
    }

    console.log('\n📸 Taking screenshot...');
    await page.screenshot({ path: 'hierarchy-behavior-test.png', fullPage: true });
    console.log('✓ Screenshot saved: hierarchy-behavior-test.png');

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('\n❌ Error:', error);
    await page.screenshot({ path: 'hierarchy-behavior-error.png', fullPage: true });
} finally {
    await browser.close();
}
