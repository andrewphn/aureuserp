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

    console.log('\n📂 Navigating directly to annotation page (Page 2)...');
    // Direct URL to annotation page for Page 2 (ID 14)
    await page.goto('http://aureuserp.test/admin/projects/pdf-documents/14/annotate-pdf');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On annotation page');

    // Wait for annotations to load
    await page.waitForSelector('[x-data]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('\n🔄 Switching to page view...');
    // Find and click the page view button
    const pageViewButton = page.locator('button:has-text("📄 By Page")');
    await pageViewButton.click();
    await page.waitForTimeout(1000);
    console.log('✓ Switched to page view');

    console.log('\n🌳 Checking hierarchical tree structure...');

    // Check if Page 2 section exists
    const page2Header = page.locator('text="Page 2"').first();
    const isPage2Visible = await page2Header.isVisible();
    console.log(`  Page 2 header visible: ${isPage2Visible}`);

    // Expand Page 2 if collapsed
    const page2ExpandButton = page.locator('.tree-node:has-text("Page 2") button').first();
    const isPage2Expanded = await page2ExpandButton.locator('span:has-text("▼")').isVisible();

    if (!isPage2Expanded) {
        console.log('  📂 Expanding Page 2...');
        await page2ExpandButton.click();
        await page.waitForTimeout(500);
    }

    // Check if K1 is displayed as a root node
    const k1Node = page.locator('.tree-node:has-text("K1")').first();
    const isK1Visible = await k1Node.isVisible();
    console.log(`  🏠 K1 node visible: ${isK1Visible}`);

    // Check if K1 has expand button and children badge
    const k1HasExpandButton = await k1Node.locator('button').first().isVisible();
    const k1ChildrenBadge = await k1Node.locator('.badge').textContent().catch(() => null);
    console.log(`  K1 has expand button: ${k1HasExpandButton}`);
    console.log(`  K1 children count: ${k1ChildrenBadge || 'No badge'}`);

    // Check if Sink Wall is initially hidden (K1 collapsed)
    const sinkWallNode = page.locator('.tree-node:has-text("Sink Wall")').first();
    let isSinkWallVisible = await sinkWallNode.isVisible().catch(() => false);
    console.log(`  📍 Sink Wall initially visible: ${isSinkWallVisible}`);

    if (k1HasExpandButton) {
        console.log('\n📂 Expanding K1...');
        const k1ExpandButton = k1Node.locator('button').first();
        await k1ExpandButton.click();
        await page.waitForTimeout(500);

        // Check if Sink Wall is now visible as a child
        isSinkWallVisible = await sinkWallNode.isVisible();
        console.log(`  ✅ Sink Wall visible after expanding K1: ${isSinkWallVisible}`);

        // Check if Sink Wall is indented (child of K1)
        const sinkWallParent = await sinkWallNode.evaluate(el => {
            // Check if it's inside a div with ml-6 class (indented)
            let parent = el.parentElement;
            while (parent && !parent.classList.contains('tree-node')) {
                if (parent.classList.contains('ml-6')) {
                    return 'indented';
                }
                parent = parent.parentElement;
            }
            return 'not indented';
        });
        console.log(`  Sink Wall indentation: ${sinkWallParent}`);

        console.log('\n📂 Collapsing K1...');
        await k1ExpandButton.click();
        await page.waitForTimeout(500);

        isSinkWallVisible = await sinkWallNode.isVisible().catch(() => false);
        console.log(`  Sink Wall visible after collapsing K1: ${isSinkWallVisible}`);
    }

    // Get Alpine.js tree data
    console.log('\n🔍 Checking Alpine.js data structure...');
    const treeData = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const pageGroups = alpine?.getPageGroupedAnnotations?.() || [];

        const page2 = pageGroups.find(p => p.pageNumber === 2);
        if (!page2) return { error: 'Page 2 not found' };

        const mapAnnotation = (anno) => ({
            id: anno.id,
            label: anno.label,
            type: anno.type,
            hasChildren: !!(anno.children && anno.children.length > 0),
            childrenCount: anno.children?.length || 0,
            children: anno.children?.map(mapAnnotation) || []
        });

        return {
            pageNumber: page2.pageNumber,
            annotationCount: page2.annotations.length,
            annotations: page2.annotations.map(mapAnnotation)
        };
    });

    console.log('\n📊 Page 2 tree structure:');
    console.log(JSON.stringify(treeData, null, 2));

    console.log('\n📸 Taking screenshots...');
    await page.screenshot({ path: 'page-view-hierarchy-collapsed.png', fullPage: true });
    console.log('✓ Screenshot saved: page-view-hierarchy-collapsed.png');

    // Expand K1 again for expanded screenshot
    if (k1HasExpandButton) {
        await k1Node.locator('button').first().click();
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'page-view-hierarchy-expanded.png', fullPage: true });
        console.log('✓ Screenshot saved: page-view-hierarchy-expanded.png');
    }

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('❌ Error:', error);
    await page.screenshot({ path: 'page-view-hierarchy-error.png', fullPage: true });
} finally {
    await browser.close();
}
