import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false });
const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
});
const page = await context.newPage();

// Listen for console messages
page.on('console', msg => console.log(`[BROWSER] ${msg.text()}`));

// Navigate to the annotation page
console.log('📍 Navigating to PDF annotation page (Page 2)...');
await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/2?pdf=1');

// Wait for Alpine to initialize
console.log('⏳ Waiting for Alpine.js to initialize...');
await page.waitForFunction(() => {
    return window.Alpine && document.querySelectorAll('[x-data]').length > 0;
}, { timeout: 10000 });

// Wait for annotations to load
await page.waitForTimeout(3000);

console.log('\n🔍 Checking annotations on page 2...');

// Find the Alpine component
const componentInfo = await page.evaluate(() => {
    const allComponents = document.querySelectorAll('[x-data]');
    console.log(`Found ${allComponents.length} Alpine components`);

    // Debug each component
    const componentsDebug = Array.from(allComponents).map((el, index) => {
        const alpine = window.Alpine?.$data(el);
        return {
            index,
            hasAnnotations: !!alpine?.annotations,
            annotationCount: alpine?.annotations?.length || 0,
            keys: alpine ? Object.keys(alpine).slice(0, 10) : []
        };
    });

    console.log('Components:', componentsDebug);

    const viewerComponent = Array.from(allComponents).find(el => {
        const alpine = window.Alpine?.$data(el);
        return alpine?.annotations && alpine.annotations.length > 0;
    });

    if (!viewerComponent) return { found: false, debug: componentsDebug, componentCount: allComponents.length };

    const alpine = window.Alpine.$data(viewerComponent);

    // Find Kitchen room and its locations
    const kitchenRoom = alpine.annotations.find(a => a.label?.toLowerCase().includes('kitchen') && a.type === 'room');
    const locations = alpine.annotations.filter(a => a.type === 'location');
    const k1 = locations.find(a => a.label === 'K1' || a.label?.toLowerCase().includes('k1'));
    const sinkwall = locations.find(a => a.label === 'Sinkwall' || a.label?.toLowerCase().includes('sink'));

    return {
        found: true,
        viewerIndex: Array.from(allComponents).indexOf(viewerComponent),
        totalAnnotations: alpine.annotations.length,
        kitchen: kitchenRoom ? {
            id: kitchenRoom.id,
            type: kitchenRoom.type,
            label: kitchenRoom.label
        } : null,
        k1: k1 ? {
            id: k1.id,
            type: k1.type,
            label: k1.label,
            roomId: k1.roomId,
            roomName: k1.roomName
        } : null,
        sinkwall: sinkwall ? {
            id: sinkwall.id,
            type: sinkwall.type,
            label: sinkwall.label,
            roomId: sinkwall.roomId,
            roomName: sinkwall.roomName
        } : null,
        allLocations: locations.map(loc => ({
            id: loc.id,
            type: loc.type,
            label: loc.label,
            roomId: loc.roomId
        }))
    };
});

if (!componentInfo.found) {
    console.error('❌ Could not find Alpine component with annotations');
    console.error(`Found ${componentInfo.componentCount} Alpine components:`, componentInfo.debug);
    await page.screenshot({ path: 'location-isolation-error.png', fullPage: true });
    console.log('📸 Error screenshot saved: location-isolation-error.png');
    await browser.close();
    process.exit(1);
}

console.log(`\n📊 Annotation Data:`);
console.log(`Total annotations: ${componentInfo.totalAnnotations}`);
console.log(`\n🏠 Kitchen Room:`, componentInfo.kitchen);
console.log(`\n📍 K1 Location:`, componentInfo.k1);
console.log(`\n📍 Sinkwall Location:`, componentInfo.sinkwall);
console.log(`\n📍 All Locations:`, componentInfo.allLocations);

if (!componentInfo.k1) {
    console.error('\n❌ K1 location not found on page 2!');
    await browser.close();
    process.exit(1);
}

// Verify K1 has correct type
if (componentInfo.k1.type !== 'location') {
    console.error(`\n❌ K1 has incorrect type: "${componentInfo.k1.type}" (should be "location")`);
} else {
    console.log(`\n✅ K1 has correct type: "location"`);
}

// Verify K1 has roomId set
if (!componentInfo.k1.roomId) {
    console.error(`\n⚠️  K1 is missing roomId!`);
} else {
    console.log(`✅ K1 has roomId: ${componentInfo.k1.roomId} (${componentInfo.k1.roomName})`);
}

// Now test location isolation by double-clicking K1
console.log(`\n\n🎯 Testing Location Isolation Mode`);
console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);

// Find K1's overlay element and double-click it
const k1Clicked = await page.evaluate((k1Id, viewerIndex) => {
    const allComponents = document.querySelectorAll('[x-data]');
    const viewerEl = allComponents[viewerIndex];

    // Find K1's annotation overlay
    const k1Overlay = viewerEl.querySelector(`[data-annotation-id="${k1Id}"]`);
    if (!k1Overlay) {
        return { success: false, error: 'K1 overlay element not found' };
    }

    // Double-click to trigger isolation
    k1Overlay.dispatchEvent(new MouseEvent('dblclick', { bubbles: true }));

    return { success: true };
}, componentInfo.k1.id, componentInfo.viewerIndex);

if (!k1Clicked.success) {
    console.error(`❌ ${k1Clicked.error}`);
    await browser.close();
    process.exit(1);
}

console.log('✓ Double-clicked K1 annotation');

// Wait for isolation mode to activate
await page.waitForTimeout(1000);

// Check isolation state
const isolationState = await page.evaluate((viewerIndex) => {
    const allComponents = document.querySelectorAll('[x-data]');
    const viewerEl = allComponents[viewerIndex];
    const alpine = window.Alpine.$data(viewerEl);

    return {
        isolationMode: alpine.isolationMode,
        isolationLevel: alpine.isolationLevel,
        isolatedRoomId: alpine.isolatedRoomId,
        isolatedRoomName: alpine.isolatedRoomName,
        isolatedLocationId: alpine.isolatedLocationId,
        isolatedLocationName: alpine.isolatedLocationName,
        hiddenAnnotations: alpine.hiddenAnnotations,
        visibleCount: alpine.annotations.filter(a => alpine.isAnnotationVisibleInIsolation(a)).length,
        visibleAnnotations: alpine.annotations
            .filter(a => alpine.isAnnotationVisibleInIsolation(a))
            .map(a => ({ id: a.id, type: a.type, label: a.label }))
    };
}, componentInfo.viewerIndex);

console.log(`\n📊 Isolation State:`);
console.log(`Isolation Mode: ${isolationState.isolationMode}`);
console.log(`Isolation Level: ${isolationState.isolationLevel}`);
console.log(`Isolated Room: ${isolationState.isolatedRoomName} (ID: ${isolationState.isolatedRoomId})`);
console.log(`Isolated Location: ${isolationState.isolatedLocationName} (ID: ${isolationState.isolatedLocationId})`);
console.log(`Visible Annotations: ${isolationState.visibleCount}`);
console.log(`Hidden Annotations: ${isolationState.hiddenAnnotations.length}`);

console.log(`\n👁️  Visible Annotations:`);
isolationState.visibleAnnotations.forEach(anno => {
    console.log(`  - ${anno.type}: ${anno.label} (ID: ${anno.id})`);
});

// Verify isolation level
console.log(`\n\n✅ Validation Results:`);
console.log(`━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`);

let passed = true;

if (!isolationState.isolationMode) {
    console.error('❌ Isolation mode is NOT active');
    passed = false;
} else {
    console.log('✅ Isolation mode is active');
}

if (isolationState.isolationLevel !== 'location') {
    console.error(`❌ Wrong isolation level: "${isolationState.isolationLevel}" (expected: "location")`);
    passed = false;
} else {
    console.log('✅ Isolation level is "location"');
}

if (isolationState.isolatedLocationId !== componentInfo.k1.id) {
    console.error(`❌ Wrong location isolated: ${isolationState.isolatedLocationId} (expected: ${componentInfo.k1.id})`);
    passed = false;
} else {
    console.log(`✅ Correct location isolated: K1 (${componentInfo.k1.id})`);
}

// Check that Kitchen room is visible (parent context)
const kitchenVisible = isolationState.visibleAnnotations.some(a => a.id === componentInfo.kitchen?.id);
if (!kitchenVisible && componentInfo.kitchen) {
    console.error('❌ Kitchen room (parent) is NOT visible');
    passed = false;
} else if (componentInfo.kitchen) {
    console.log('✅ Kitchen room (parent) is visible');
}

// Check that K1 is visible
const k1Visible = isolationState.visibleAnnotations.some(a => a.id === componentInfo.k1.id);
if (!k1Visible) {
    console.error('❌ K1 location is NOT visible');
    passed = false;
} else {
    console.log('✅ K1 location is visible');
}

// Check that Sinkwall is HIDDEN
const sinkwallVisible = isolationState.visibleAnnotations.some(a => a.id === componentInfo.sinkwall?.id);
if (sinkwallVisible && componentInfo.sinkwall) {
    console.error('❌ Sinkwall location is visible (should be HIDDEN)');
    passed = false;
} else if (componentInfo.sinkwall) {
    console.log('✅ Sinkwall location is hidden');
}

console.log(`\n\n${passed ? '✅ SUCCESS' : '❌ FAILURE'}: Location isolation ${passed ? 'works correctly' : 'has issues'}!`);

await page.screenshot({ path: 'location-isolation-test.png', fullPage: true });
console.log('\n📸 Screenshot saved: location-isolation-test.png');

await browser.close();
process.exit(passed ? 0 : 1);
