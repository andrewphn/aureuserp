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

    console.log('\n📂 Navigating to project documents...');
    await page.goto('http://aureuserp.test/admin/projects/projects/9');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    console.log('\n📄 Clicking Documents tab...');
    await page.click('button[role="tab"]:has-text("Documents")');
    await page.waitForTimeout(1000);

    console.log('\n🔗 Clicking Review & Price link...');
    const reviewLink = page.locator('a:has-text("Review & Price")').first();
    await reviewLink.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    console.log('\n✏️ Clicking Annotate link for Page 3...');
    // Page 3 should be the 3rd link (index 2)
    const annotateLink = page.locator('a:has-text("✏️ Annotate")').nth(2);
    await annotateLink.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On annotation page for Page 3');

    // Wait for Alpine.js to load
    await page.waitForSelector('[x-data]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('\n🔍 Checking existing annotations on Page 3...');
    const existingAnnotations = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return alpine?.annotations?.map(a => ({
            id: a.id,
            label: a.label,
            type: a.type,
            parentId: a.parentId,
            roomLocationId: a.roomLocationId
        })) || [];
    });

    console.log(`\n📊 Found ${existingAnnotations.length} annotations:`);
    existingAnnotations.forEach(a => {
        console.log(`  ${a.label} (${a.type}) - ID: ${a.id}, parentId: ${a.parentId ?? 'NULL'}, roomLocationId: ${a.roomLocationId ?? 'NULL'}`);
    });

    // Find Fridge Wall annotation
    const fridgeWall = existingAnnotations.find(a => a.label.includes('Fridge') || a.label.includes('fridge'));
    if (!fridgeWall) {
        console.log('\n❌ Fridge Wall annotation not found on this page');
        console.log('Available annotations:', existingAnnotations.map(a => a.label).join(', '));
        throw new Error('Fridge Wall not found - test cannot continue');
    }

    console.log(`\n✅ Found Fridge Wall: ID ${fridgeWall.id}, roomLocationId: ${fridgeWall.roomLocationId}`);

    console.log('\n🌳 Opening project tree...');
    // Switch to room view to show entity tree
    const roomViewButton = page.locator('button:has-text("🏠 By Room")');
    await roomViewButton.click();
    await page.waitForTimeout(1000);
    console.log('✓ Switched to room view');

    // Expand K1 to show its locations
    console.log('\n📂 Expanding K1...');
    const k1Node = page.locator('.tree-node:has-text("K1")').first();
    const k1ExpandButton = k1Node.locator('button').first();
    await k1ExpandButton.click();
    await page.waitForTimeout(500);

    // Find and click Fridge Wall in the tree
    console.log('\n🎯 Selecting Fridge Wall from tree...');
    const fridgeWallNode = page.locator('.tree-node').filter({ hasText: /Fridge Wall/i }).first();
    await fridgeWallNode.click();
    await page.waitForTimeout(1000);
    console.log('✓ Selected Fridge Wall from tree');

    // Verify active location is set
    const activeLocation = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return {
            activeLocationId: alpine?.activeLocationId,
            activeLocationName: alpine?.activeLocationName,
            isolationMode: alpine?.isolationMode
        };
    });

    console.log(`\n📍 Active context:`);
    console.log(`  activeLocationId: ${activeLocation.activeLocationId}`);
    console.log(`  activeLocationName: ${activeLocation.activeLocationName}`);
    console.log(`  isolationMode: ${activeLocation.isolationMode}`);

    if (activeLocation.activeLocationId !== fridgeWall.roomLocationId) {
        console.log(`\n⚠️  WARNING: Selected location ID (${activeLocation.activeLocationId}) doesn't match Fridge Wall entity ID (${fridgeWall.roomLocationId})`);
    } else {
        console.log(`\n✅ Selected location matches Fridge Wall entity`);
    }

    console.log('\n🖊️ Entering draw mode for cabinet...');
    const cabinetButton = page.locator('button:has-text("🗄️ Cabinet")');
    await cabinetButton.click();
    await page.waitForTimeout(500);

    // Draw a cabinet annotation on the canvas
    console.log('\n✏️ Drawing cabinet annotation...');
    const canvas = page.locator('canvas').first();
    const canvasBox = await canvas.boundingBox();

    if (!canvasBox) {
        throw new Error('Canvas not found');
    }

    // Draw a small rectangle
    const startX = canvasBox.x + 100;
    const startY = canvasBox.y + 100;
    const endX = startX + 80;
    const endY = startY + 60;

    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(endX, endY);
    await page.mouse.up();
    await page.waitForTimeout(1000);
    console.log('✓ Drew cabinet annotation');

    // Check the newly created annotation
    console.log('\n🔍 Checking newly created annotation...');
    const newAnnotations = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return alpine?.annotations?.map(a => ({
            id: a.id,
            label: a.label,
            type: a.type,
            parentId: a.parentId,
            roomLocationId: a.roomLocationId
        })) || [];
    });

    // Find the new annotation (should be the last one with temp_ ID)
    const newAnnotation = newAnnotations.find(a => String(a.id).startsWith('temp_'));

    if (!newAnnotation) {
        console.log('\n❌ New annotation not found');
        console.log('All annotations:', newAnnotations);
        throw new Error('New annotation not created');
    }

    console.log(`\n✅ New annotation created: ${newAnnotation.label}`);
    console.log(`   ID: ${newAnnotation.id}`);
    console.log(`   Type: ${newAnnotation.type}`);
    console.log(`   parentId: ${newAnnotation.parentId ?? 'NULL'}`);
    console.log(`   roomLocationId: ${newAnnotation.roomLocationId ?? 'NULL'}`);

    // CRITICAL CHECK: Does the new annotation have the Fridge Wall annotation as parent?
    if (newAnnotation.parentId === fridgeWall.id) {
        console.log(`\n✅✅✅ SUCCESS! New cabinet annotation correctly has Fridge Wall (${fridgeWall.id}) as parent!`);
    } else if (newAnnotation.parentId === null) {
        console.log(`\n❌ FAILURE! New cabinet annotation has NO parent (expected: ${fridgeWall.id})`);
    } else {
        console.log(`\n❌ FAILURE! New cabinet annotation has WRONG parent (${newAnnotation.parentId}, expected: ${fridgeWall.id})`);
    }

    console.log('\n📸 Taking screenshot...');
    await page.screenshot({ path: 'normal-mode-parent-association.png', fullPage: true });
    console.log('✓ Screenshot saved: normal-mode-parent-association.png');

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('\n❌ Error:', error);
    await page.screenshot({ path: 'normal-mode-parent-error.png', fullPage: true });
} finally {
    await browser.close();
}
