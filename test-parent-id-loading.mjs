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

    console.log('\n📂 Navigating to project page...');
    await page.goto('http://aureuserp.test/admin/projects/projects/9');
    await page.waitForLoadState('networkidle');
    console.log('✓ On project page');

    console.log('\n📄 Clicking Documents tab...');
    await page.click('button[role="tab"]:has-text("Documents")');
    await page.waitForTimeout(1000);

    console.log('\n🔗 Clicking Review & Price link...');
    const reviewLink = page.locator('a:has-text("Review & Price")').first();
    await reviewLink.click();
    await page.waitForLoadState('networkidle');

    console.log('\n✏️ Clicking Annotate link for Page 2...');
    const annotateLink = page.locator('a:has-text("✏️ Annotate")').nth(1);
    await annotateLink.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On annotation page');

    // Wait for annotations to load
    await page.waitForSelector('[x-data]', { timeout: 10000 });
    await page.waitForTimeout(2000);

    console.log('\n🔍 Checking loaded annotations...');
    const annotationsData = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const annotations = alpine?.annotations || [];

        return annotations.map(a => ({
            id: a.id,
            label: a.label,
            type: a.type,
            parentId: a.parentId,
            roomId: a.roomId
        }));
    });

    console.log(`\n📊 Found ${annotationsData.length} annotations:`);
    annotationsData.forEach(a => {
        console.log(`  ID: ${a.id} | Label: ${a.label} | Type: ${a.type} | ParentID: ${a.parentId ?? 'NULL'} | RoomID: ${a.roomId ?? 'NULL'}`);
    });

    // Check K1 and Sink Wall specifically
    const k1 = annotationsData.find(a => a.label === 'K1');
    const sinkwall = annotationsData.find(a => a.label === 'Sink Wall');

    if (k1) {
        console.log('\n✅ K1 found:');
        console.log(`   ID: ${k1.id}, parentId: ${k1.parentId ?? 'NULL'}`);
    } else {
        console.log('\n❌ K1 NOT found');
    }

    if (sinkwall) {
        console.log('\n✅ Sink Wall found:');
        console.log(`   ID: ${sinkwall.id}, parentId: ${sinkwall.parentId ?? 'NULL'}`);
        if (sinkwall.parentId === k1?.id) {
            console.log('   ✅ parentId correctly points to K1!');
        } else {
            console.log(`   ❌ parentId does NOT point to K1 (expected: ${k1?.id}, got: ${sinkwall.parentId})`);
        }
    } else {
        console.log('\n❌ Sink Wall NOT found');
    }

    console.log('\n⏸️  Pausing for inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('❌ Error:', error);
    await page.screenshot({ path: 'parent-id-loading-error.png', fullPage: true });
} finally {
    await browser.close();
}
