import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const page = await browser.newPage();

console.log('🔍 Checking for invalid parent relationships in annotation hierarchy...\n');

try {
    // Navigate to the annotation page
    await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
    await page.waitForTimeout(5000); // Wait for Alpine and annotations to load

    // Get Alpine data
    const result = await page.evaluate(() => {
        // Find the PDF annotation viewer component
        const viewerEl = document.querySelector('#pdf-annotation-viewer');
        if (!viewerEl || !viewerEl.__x) {
            throw new Error('Alpine component not found or not initialized');
        }

        const viewer = viewerEl.__x.$data;
        const annotations = viewer.annotations || [];
        const invalid = [];

        annotations.forEach(anno => {
            if (!anno.parentId) return; // Skip root annotations

            // Find parent annotation
            const parent = annotations.find(a => a.id === anno.parentId);

            if (!parent) {
                invalid.push({
                    id: anno.id,
                    type: anno.type,
                    label: anno.label || 'unnamed',
                    parentId: anno.parentId,
                    issue: 'Parent annotation does not exist (deleted)'
                });
                return;
            }

            // Check hierarchy rules
            let expectedParentType = null;
            if (anno.type === 'location') expectedParentType = 'room';
            if (anno.type === 'cabinet_run') expectedParentType = 'location';
            if (anno.type === 'cabinet') expectedParentType = 'cabinet_run';

            if (expectedParentType && parent.type !== expectedParentType) {
                invalid.push({
                    id: anno.id,
                    type: anno.type,
                    label: anno.label || 'unnamed',
                    parentId: anno.parentId,
                    parentType: parent.type,
                    expectedParentType: expectedParentType,
                    issue: `Invalid parent type: ${parent.type} (expected ${expectedParentType})`
                });
            }
        });

        return {
            totalAnnotations: annotations.length,
            invalidCount: invalid.length,
            invalid: invalid
        };
    });

    console.log(`📊 Total annotations: ${result.totalAnnotations}`);
    console.log(`❌ Invalid parent relationships: ${result.invalidCount}\n`);

    if (result.invalidCount > 0) {
        console.log('=== INVALID ANNOTATIONS ===\n');
        result.invalid.forEach(item => {
            console.log(`❌ ID ${item.id} "${item.label}" (${item.type})`);
            console.log(`   Parent ID: ${item.parentId}`);
            console.log(`   Issue: ${item.issue}\n`);
        });
    } else {
        console.log('✅ All parent relationships are valid!\n');
    }

} catch (error) {
    console.error('Error:', error.message);
}

await browser.close();
