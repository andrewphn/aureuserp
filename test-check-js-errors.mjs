import { chromium } from 'playwright';

(async () => {
    console.log('🔍 Checking for JavaScript errors\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Capture console messages and errors
    const consoleMessages = [];
    const pageErrors = [];

    page.on('console', msg => {
        consoleMessages.push(`[${msg.type()}] ${msg.text()}`);
    });

    page.on('pageerror', error => {
        pageErrors.push(error.toString());
    });

    try {
        // Login
        console.log('📝 Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);

        // Navigate to annotation page
        console.log('📝 Navigating to annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1');
        await page.waitForTimeout(5000);

        // Check for errors
        console.log('\n--- PAGE ERRORS ---');
        if (pageErrors.length > 0) {
            pageErrors.forEach(err => console.log(`❌ ${err}`));
        } else {
            console.log('✅ No page errors');
        }

        console.log('\n--- CONSOLE MESSAGES (last 20) ---');
        consoleMessages.slice(-20).forEach(msg => console.log(msg));

        // Try to access Alpine component
        console.log('\n--- CHECKING ALPINE COMPONENT ---');
        const result = await page.evaluate(() => {
            try {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                if (!el) {
                    return { success: false, error: 'Element not found' };
                }

                // Try different ways to access the component
                const component1 = Alpine.$data(el);
                const component2 = el.__x ? el.__x.$data : null;

                const test1Keys = Object.keys(component1 || {});
                const test2Keys = component2 ? Object.keys(component2) : [];

                return {
                    success: true,
                    method1: {
                        totalKeys: test1Keys.length,
                        sampleKeys: test1Keys.slice(0, 10),
                        hasMethod: typeof component1?.shouldAutoPan === 'function'
                    },
                    method2: {
                        totalKeys: test2Keys.length,
                        sampleKeys: test2Keys.slice(0, 10),
                        hasMethod: component2 && typeof component2.shouldAutoPan === 'function'
                    }
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.toString()
                };
            }
        });

        console.log(JSON.stringify(result, null, 2));

        await page.screenshot({ path: 'js-errors-check.png', fullPage: false });

    } catch (error) {
        console.error('❌ Test failed:', error.message);
    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
    }
})();
