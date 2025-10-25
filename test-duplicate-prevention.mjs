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

    console.log('\n📂 Navigating to annotation page (Page 3)...');
    await page.goto('http://aureuserp.test/admin/projects/pdf-documents/15/annotate-pdf');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On annotation page');

    // Wait for Alpine.js
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

    // Find existing Sink Wall annotation
    console.log('\n🔍 Checking for existing Sink Wall annotation...');
    const existingAnnotations = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return alpine?.annotations?.filter(a =>
            a.label?.toLowerCase().includes('sink')
        ).map(a => ({
            id: a.id,
            label: a.label,
            roomLocationId: a.roomLocationId
        })) || [];
    });

    if (existingAnnotations.length === 0) {
        console.log('⚠️  No existing Sink Wall annotation found - test cannot continue');
        console.log('   This test requires an existing Sink Wall annotation on page 3');
        throw new Error('Test setup incomplete - create Sink Wall annotation first');
    }

    const sinkWall = existingAnnotations[0];
    console.log(`✅ Found existing Sink Wall annotation: ${sinkWall.label} (ID: ${sinkWall.id}, locationId: ${sinkWall.roomLocationId})`);

    // TEST: Try to create duplicate
    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST: Attempt to create duplicate Sink Wall annotation');
    console.log('═══════════════════════════════════════════════════\n');

    // Click Sink Wall in tree (should select it as active location)
    console.log('🎯 Clicking Sink Wall in tree...');
    const sinkWallNode = page.locator('.tree-node').filter({ hasText: /Sink Wall/i }).first();
    await sinkWallNode.click();
    await page.waitForTimeout(1000);

    // Verify selection
    const activeContext = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return {
            activeLocationId: alpine?.activeLocationId,
            activeLocationName: alpine?.activeLocationName
        };
    });

    console.log(`📍 Active location: ${activeContext.activeLocationName} (ID: ${activeContext.activeLocationId})`);

    // Try to enter draw mode for location (should be blocked!)
    console.log('\n🖊️ Attempting to enter draw mode for Location...');
    console.log('   (Should be BLOCKED by duplicate detection)');

    // Listen for notification
    let notificationShown = false;
    page.on('console', msg => {
        if (msg.text().includes('Annotation Already Exists')) {
            notificationShown = true;
            console.log('✅ Notification detected!');
        }
    });

    const locationButton = page.locator('button:has-text("📍 Location")');
    await locationButton.click();
    await page.waitForTimeout(1500);

    // Check draw mode state
    const drawModeState = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return {
            drawMode: alpine?.drawMode,
            annotations: alpine?.annotations?.map(a => ({
                id: a.id,
                label: a.label,
                color: a.color
            }))
        };
    });

    console.log('\n📊 TEST RESULTS:');
    console.log(`  Draw mode enabled: ${drawModeState.drawMode !== null ? '❌ YES (SHOULD BE NULL)' : '✅ NO (CORRECT)'}`);

    // Check if original annotation was highlighted (color changed to red)
    const highlightedAnnotation = drawModeState.annotations.find(a => a.id === sinkWall.id);
    if (highlightedAnnotation) {
        const wasHighlighted = highlightedAnnotation.color === '#ff0000';
        console.log(`  Existing annotation highlighted: ${wasHighlighted ? '✅ YES (CORRECT)' : '⚠️  NO (check timing)'}`);
    }

    // Overall result
    if (drawModeState.drawMode === null) {
        console.log('\n✅✅✅ SUCCESS! Duplicate detection prevented creating duplicate annotation!');
        console.log('   - Draw mode was NOT entered');
        console.log('   - Existing annotation should have been highlighted');
        console.log('   - User should have seen notification');
    } else {
        console.log('\n❌ FAILURE! Draw mode was entered despite duplicate!');
        console.log(`   Current draw mode: ${drawModeState.drawMode}`);
    }

    console.log('\n📸 Taking screenshot...');
    await page.screenshot({ path: 'duplicate-prevention-test.png', fullPage: true });
    console.log('✓ Screenshot saved: duplicate-prevention-test.png');

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('\n❌ Error:', error);
    await page.screenshot({ path: 'duplicate-prevention-error.png', fullPage: true });
} finally {
    await browser.close();
}
