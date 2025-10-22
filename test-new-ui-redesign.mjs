import { chromium } from '@playwright/test';

(async () => {
    console.log('🎨 Testing new UI redesign...\\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
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

        // Navigate directly to annotation page
        console.log('📝 Step 2: Opening annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);
        console.log('✅ Page loaded\\n');

        // Wait for Alpine to initialize
        await page.waitForTimeout(2000);

        // Take full screenshot
        console.log('📝 Step 3: Taking full page screenshot...');
        await page.screenshot({ path: 'ui-redesign-full.png', fullPage: false });
        console.log('✅ Screenshot: ui-redesign-full.png\\n');

        // Test sidebar collapse
        console.log('📝 Step 4: Testing sidebar collapse...');
        const collapseBtn = page.locator('button[title="Collapse sidebar"]');
        if (await collapseBtn.count() > 0) {
            await collapseBtn.click();
            await page.waitForTimeout(500);
            await page.screenshot({ path: 'ui-redesign-collapsed.png', fullPage: false });
            console.log('✅ Screenshot: ui-redesign-collapsed.png (sidebar collapsed)\\n');

            // Test sidebar expand
            console.log('📝 Step 5: Testing sidebar expand...');
            const expandBtn = page.locator('button[title="Show sidebar"]');
            await expandBtn.click();
            await page.waitForTimeout(500);
            await page.screenshot({ path: 'ui-redesign-expanded.png', fullPage: false });
            console.log('✅ Screenshot: ui-redesign-expanded.png (sidebar expanded)\\n');
        }

        // Check for page type selector
        console.log('📝 Step 6: Checking page type selector...');
        const selector = page.locator('select[title="Select page type"]');
        const selectorCount = await selector.count();
        console.log(`   Page type selector count: ${selectorCount}`);

        if (selectorCount > 0) {
            const isVisible = await selector.isVisible();
            console.log(`   Is visible: ${isVisible}`);

            // Highlight it
            await selector.evaluate(el => {
                el.style.border = '3px solid red';
            });
            await page.waitForTimeout(500);
            await page.screenshot({ path: 'ui-redesign-selector-highlighted.png', fullPage: false });
            console.log('✅ Screenshot: ui-redesign-selector-highlighted.png (selector highlighted)\\n');
        }

        // Check console for errors
        const errors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        await page.waitForTimeout(2000);

        if (errors.length > 0) {
            console.log('\\n⚠️ Console errors detected:');
            errors.forEach(err => console.log(`   - ${err}`));
        } else {
            console.log('\\n✅ No console errors detected');
        }

        console.log('\\nBrowser will stay open for 60 seconds...');
        console.log('Press Ctrl+C to close');
        await page.waitForTimeout(60000);

    } catch (error) {
        console.error('\\n❌ Error:', error.message);
        await page.screenshot({ path: 'ui-redesign-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\\n✅ Browser closed');
    }
})();
