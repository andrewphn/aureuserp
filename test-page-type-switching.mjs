import { chromium } from '@playwright/test';

(async () => {
    console.log('🧪 Testing page type switching between pages...\\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 800
    });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        // Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in\\n');

        // Navigate to annotation page
        console.log('📝 Step 2: Opening annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
        await page.waitForTimeout(4000);
        console.log('✅ Page loaded\\n');

        // Check page type selector on page 1
        console.log('📝 Step 3: Checking page 1 type...');
        const selector = page.locator('select[title="Select page type"]');
        await page.waitForTimeout(1000);

        let currentType = await selector.inputValue();
        console.log(`   Page 1 type: ${currentType || 'none'}`);

        // Set page 1 to "cover"
        console.log('📝 Step 4: Setting page 1 to Cover Page...');
        await selector.selectOption('cover');
        await page.waitForTimeout(2000);
        currentType = await selector.inputValue();
        console.log(`   Page 1 type after change: ${currentType}`);

        // Take screenshot
        await page.screenshot({ path: 'page-type-page1-cover.png', fullPage: false });
        console.log('✅ Screenshot: page-type-page1-cover.png\\n');

        // Navigate to page 2
        console.log('📝 Step 5: Navigating to page 2...');
        const nextBtn = page.locator('button[title="Next Page"]');
        await nextBtn.click();
        await page.waitForTimeout(3000);

        // Check page type on page 2
        currentType = await selector.inputValue();
        console.log(`   Page 2 type: ${currentType || 'none'}`);

        // Set page 2 to "floor_plan"
        console.log('📝 Step 6: Setting page 2 to Floor Plan...');
        await selector.selectOption('floor_plan');
        await page.waitForTimeout(2000);
        currentType = await selector.inputValue();
        console.log(`   Page 2 type after change: ${currentType}`);

        // Take screenshot
        await page.screenshot({ path: 'page-type-page2-floor.png', fullPage: false });
        console.log('✅ Screenshot: page-type-page2-floor.png\\n');

        // Navigate to page 3
        console.log('📝 Step 7: Navigating to page 3...');
        await nextBtn.click();
        await page.waitForTimeout(3000);

        currentType = await selector.inputValue();
        console.log(`   Page 3 type: ${currentType || 'none'}`);

        // Set page 3 to "elevation"
        console.log('📝 Step 8: Setting page 3 to Elevation...');
        await selector.selectOption('elevation');
        await page.waitForTimeout(2000);
        currentType = await selector.inputValue();
        console.log(`   Page 3 type after change: ${currentType}`);

        // Take screenshot
        await page.screenshot({ path: 'page-type-page3-elevation.png', fullPage: false });
        console.log('✅ Screenshot: page-type-page3-elevation.png\\n');

        // Navigate back to page 2
        console.log('📝 Step 9: Going back to page 2...');
        const prevBtn = page.locator('button[title="Previous Page"]');
        await prevBtn.click();
        await page.waitForTimeout(3000);

        currentType = await selector.inputValue();
        console.log(`   Page 2 type (should be floor_plan): ${currentType}`);

        if (currentType === 'floor_plan') {
            console.log('✅ Page type correctly loaded!');
        } else {
            console.log('❌ Page type NOT loaded correctly!');
        }

        // Navigate back to page 1
        console.log('📝 Step 10: Going back to page 1...');
        await prevBtn.click();
        await page.waitForTimeout(3000);

        currentType = await selector.inputValue();
        console.log(`   Page 1 type (should be cover): ${currentType}`);

        if (currentType === 'cover') {
            console.log('✅ Page type correctly loaded!');
        } else {
            console.log('❌ Page type NOT loaded correctly!');
        }

        // Take final screenshot
        await page.screenshot({ path: 'page-type-back-to-page1.png', fullPage: false });
        console.log('\\n✅ Screenshot: page-type-back-to-page1.png');

        // Check console for errors
        const messages = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                messages.push(msg.text());
            }
        });

        await page.waitForTimeout(1000);

        if (messages.length > 0) {
            console.log('\\n⚠️ Console errors:');
            messages.forEach(msg => console.log(`   ${msg}`));
        } else {
            console.log('\\n✅ No console errors');
        }

        console.log('\\n✅ TEST COMPLETE!');
        console.log('Browser will stay open for 30 seconds...');
        await page.waitForTimeout(30000);

    } catch (error) {
        console.error('\\n❌ Error:', error.message);
        await page.screenshot({ path: 'page-type-test-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\\n✅ Browser closed');
    }
})();
