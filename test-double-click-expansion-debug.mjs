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

    console.log('✓ Logged in (URL:', page.url(), ')');

    console.log('\n📂 Navigating to project page...');
    await page.goto('http://aureuserp.test/admin/projects/projects/9');
    await page.waitForLoadState('networkidle');
    console.log('✓ On project page');

    console.log('\n📄 Clicking Documents tab...');
    await page.click('button[role="tab"]:has-text("Documents")');
    await page.waitForTimeout(1000);
    console.log('✓ Documents tab active');

    console.log('\n🔗 Clicking Review & Price link...');
    const reviewLink = page.locator('a:has-text("Review & Price")').first();
    await reviewLink.click();
    await page.waitForLoadState('networkidle');
    console.log('✓ On PDF documents page');

    console.log('\n✏️ Clicking Annotate link for Page 2...');
    const annotateLink = page.locator('a:has-text("✏️ Annotate")').nth(1);
    await annotateLink.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('✓ On annotation page');

    // Wait for annotations to load
    await page.waitForSelector('[x-data]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('\n🌳 Current tree state:');
    const treeState = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return {
            expandedNodes: alpine?.expandedNodes || [],
            tree: alpine?.tree || []
        };
    });
    console.log('  Expanded nodes:', treeState.expandedNodes);
    console.log('  Tree rooms:', treeState.tree.map(r => ({ id: r.id, name: r.name })));

    console.log('\n🖱️ Double-clicking K1 tree node...');

    // Set up console listener BEFORE double-clicking
    const consoleMessages = [];
    page.on('console', msg => {
        const text = msg.text();
        if (text.includes('Debug expansion') || text.includes('expandedNodes') || text.includes('Added')) {
            consoleMessages.push(text);
            console.log('  📝', text);
        }
    });

    // Find and double-click the K1 tree node
    const k1TreeNode = page.locator('.tree-node:has-text("K1")').first();
    await k1TreeNode.dblclick();
    await page.waitForTimeout(1000);

    console.log('\n📊 Console messages:');
    consoleMessages.forEach(msg => console.log('  ', msg));

    console.log('\n🌳 Tree state after double-click:');
    const afterState = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        return {
            expandedNodes: alpine?.expandedNodes || [],
            isolatedRoomId: alpine?.isolatedRoomId,
            isolationMode: alpine?.isolationMode
        };
    });
    console.log('  Isolation mode:', afterState.isolationMode);
    console.log('  Isolated room ID:', afterState.isolatedRoomId);
    console.log('  Expanded nodes:', afterState.expandedNodes);

    console.log('\n🔍 Checking if K1 node is visually expanded...');
    const isExpanded = await page.locator('.tree-node:has-text("K1")').first().locator('span:has-text("▼")').isVisible();
    console.log('  K1 shows ▼ (expanded):', isExpanded);

    const isCollapsed = await page.locator('.tree-node:has-text("K1")').first().locator('span:has-text("▶")').isVisible();
    console.log('  K1 shows ▶ (collapsed):', isCollapsed);

    console.log('\n📸 Taking screenshot...');
    await page.screenshot({ path: 'double-click-expansion-debug.png', fullPage: true });
    console.log('✓ Screenshot saved');

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('❌ Error:', error);
    await page.screenshot({ path: 'double-click-expansion-error.png', fullPage: true });
} finally {
    await browser.close();
}
