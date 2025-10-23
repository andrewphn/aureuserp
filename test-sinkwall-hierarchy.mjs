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

// Wait for page load
await page.waitForLoadState('networkidle');
await page.waitForTimeout(2000);

console.log('\n🔍 Checking annotation hierarchy from Livewire component...\n');

// Query the Livewire component for annotation data
const annotationsData = await page.evaluate(async () => {
    // Find the Livewire component
    const livewireEl = document.querySelector('[wire\\:id]');
    if (!livewireEl) {
        return { error: 'No Livewire component found' };
    }

    // Get the Alpine data
    const alpineEl = document.querySelector('[x-data*="annotations"]');
    if (!alpineEl || !window.Alpine) {
        return { error: 'No Alpine component found' };
    }

    const alpine = window.Alpine.$data(alpineEl);
    if (!alpine || !alpine.annotations) {
        return { error: 'No annotations in Alpine data' };
    }

    // Find Kitchen, K1, and Sinkwall
    const kitchen = alpine.annotations.find(a => a.label?.toLowerCase().includes('kitchen') && a.type === 'room');
    const k1 = alpine.annotations.find(a => a.label === 'K1' || a.label?.toLowerCase() === 'k1');
    const sinkwall = alpine.annotations.find(a => a.label?.toLowerCase().includes('sink'));

    return {
        success: true,
        totalAnnotations: alpine.annotations.length,
        kitchen: kitchen ? {
            id: kitchen.id,
            type: kitchen.type,
            label: kitchen.label,
            roomId: kitchen.roomId
        } : null,
        k1: k1 ? {
            id: k1.id,
            type: k1.type,
            label: k1.label,
            roomId: k1.roomId,
            roomName: k1.roomName,
            locationId: k1.locationId,
            locationName: k1.locationName
        } : null,
        sinkwall: sinkwall ? {
            id: sinkwall.id,
            type: sinkwall.type,
            label: sinkwall.label,
            roomId: sinkwall.roomId,
            roomName: sinkwall.roomName,
            locationId: sinkwall.locationId,
            locationName: sinkwall.locationName,
            cabinetRunId: sinkwall.cabinetRunId,
            cabinetRunName: sinkwall.cabinetRunName
        } : null
    };
});

if (annotationsData.error) {
    console.error(`❌ ${annotationsData.error}`);
    await page.screenshot({ path: 'sinkwall-hierarchy-error.png', fullPage: true });
    console.log('📸 Screenshot saved: sinkwall-hierarchy-error.png');
    await browser.close();
    process.exit(1);
}

console.log(`📊 Total annotations on page 2: ${annotationsData.totalAnnotations}\n`);

console.log('🏠 Kitchen Room:');
if (annotationsData.kitchen) {
    console.log(`   ID: ${annotationsData.kitchen.id}`);
    console.log(`   Type: ${annotationsData.kitchen.type}`);
    console.log(`   Label: ${annotationsData.kitchen.label}`);
} else {
    console.log('   ❌ NOT FOUND');
}

console.log('\n📍 K1 Location:');
if (annotationsData.k1) {
    console.log(`   ID: ${annotationsData.k1.id}`);
    console.log(`   Type: ${annotationsData.k1.type}`);
    console.log(`   Label: ${annotationsData.k1.label}`);
    console.log(`   Room ID: ${annotationsData.k1.roomId}`);
    console.log(`   Room Name: ${annotationsData.k1.roomName}`);

    if (annotationsData.k1.type === 'location') {
        console.log('   ✅ Type is correct (location)');
    } else {
        console.log(`   ❌ Type is WRONG (expected: "location", got: "${annotationsData.k1.type}")`);
    }
} else {
    console.log('   ❌ NOT FOUND');
}

console.log('\n🗄️  Sinkwall:');
if (annotationsData.sinkwall) {
    console.log(`   ID: ${annotationsData.sinkwall.id}`);
    console.log(`   Type: ${annotationsData.sinkwall.type}`);
    console.log(`   Label: ${annotationsData.sinkwall.label}`);
    console.log(`   Room ID: ${annotationsData.sinkwall.roomId}`);
    console.log(`   Room Name: ${annotationsData.sinkwall.roomName}`);
    console.log(`   Location ID: ${annotationsData.sinkwall.locationId}`);
    console.log(`   Location Name: ${annotationsData.sinkwall.locationName}`);

    if (annotationsData.sinkwall.type === 'cabinet_run') {
        console.log('   ✅ Type is cabinet_run (correct)');

        if (annotationsData.sinkwall.locationId === annotationsData.k1?.id) {
            console.log(`   ✅ Belongs to K1 (locationId matches K1's ID)`);
        } else {
            console.log(`   ❌ Does NOT belong to K1 (locationId: ${annotationsData.sinkwall.locationId}, K1 ID: ${annotationsData.k1?.id})`);
        }
    } else {
        console.log(`   ⚠️  Type is "${annotationsData.sinkwall.type}" (expected: "cabinet_run")`);
    }
} else {
    console.log('   ❌ NOT FOUND');
}

// Summary
console.log('\n' + '='.repeat(60));
console.log('HIERARCHY VALIDATION');
console.log('='.repeat(60));

let allCorrect = true;

if (!annotationsData.kitchen) {
    console.log('❌ Kitchen room not found');
    allCorrect = false;
} else {
    console.log('✅ Kitchen room exists');
}

if (!annotationsData.k1) {
    console.log('❌ K1 location not found');
    allCorrect = false;
} else if (annotationsData.k1.type !== 'location') {
    console.log(`❌ K1 has wrong type: "${annotationsData.k1.type}" (should be "location")`);
    allCorrect = false;
} else {
    console.log('✅ K1 is a location');
}

if (!annotationsData.sinkwall) {
    console.log('❌ Sinkwall not found');
    allCorrect = false;
} else if (annotationsData.sinkwall.type !== 'cabinet_run') {
    console.log(`❌ Sinkwall has wrong type: "${annotationsData.sinkwall.type}" (should be "cabinet_run")`);
    allCorrect = false;
} else if (annotationsData.sinkwall.locationId !== annotationsData.k1?.id) {
    console.log(`❌ Sinkwall does not belong to K1 (locationId mismatch)`);
    allCorrect = false;
} else {
    console.log('✅ Sinkwall is a cabinet run inside K1');
}

console.log('\n' + (allCorrect ? '✅ SUCCESS: Hierarchy is correct!' : '❌ FAILURE: Hierarchy issues detected'));

await page.screenshot({ path: 'sinkwall-hierarchy-test.png', fullPage: true });
console.log('\n📸 Screenshot saved: sinkwall-hierarchy-test.png');

await browser.close();
process.exit(allCorrect ? 0 : 1);
