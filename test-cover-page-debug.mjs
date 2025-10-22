import { chromium } from '@playwright/test';

/**
 * Debug Test for Phase 3.3: Cover Page Auto-Population
 *
 * This test checks browser console logs to debug why auto-population might not be triggering
 */

(async () => {
    console.log('🔍 Starting Cover Page Auto-Population Debug Test...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Capture console messages
    const consoleLogs = [];
    page.on('console', msg => {
        const text = msg.text();
        consoleLogs.push(text);
        console.log(`[BROWSER] ${text}`);
    });

    // Capture page errors
    page.on('pageerror', error => {
        console.error(`[PAGE ERROR] ${error.message}`);
    });

    try {
        // Login
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in\n');

        // Navigate to annotation viewer
        console.log('📝 Step 2: Opening annotation viewer...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded\n');

        // Check projectId is available
        console.log('📝 Step 3: Checking projectId in Alpine component...');
        const projectId = await page.evaluate(() => {
            const component = Alpine.$data(document.querySelector('[x-data]'));
            return component.projectId;
        });
        console.log(`   Project ID: ${projectId}\n`);

        // Switch to Cover page type
        console.log('📝 Step 4: Switching to Cover page type...');
        console.log('   Console logs before switch:');

        await page.selectOption('select[x-model="pageType"]', 'cover');
        await page.waitForTimeout(3000); // Wait for auto-population

        console.log('\n   Console logs after switch:');
        console.log('   Looking for "Auto-populating" or "auto-populated" messages...\n');

        // Check field values
        console.log('📝 Step 5: Checking field values...');
        const fields = await page.evaluate(() => {
            const component = Alpine.$data(document.querySelector('[x-data]'));
            return {
                coverProjectNumber: component.coverProjectNumber,
                coverCustomerName: component.coverCustomerName,
                coverAddress: component.coverAddress,
                coverDate: component.coverDate,
                pageType: component.pageType
            };
        });

        console.log('   Field values:');
        console.log(`   - pageType: "${fields.pageType}"`);
        console.log(`   - coverProjectNumber: "${fields.coverProjectNumber}"`);
        console.log(`   - coverCustomerName: "${fields.coverCustomerName}"`);
        console.log(`   - coverAddress: "${fields.coverAddress}"`);
        console.log(`   - coverDate: "${fields.coverDate}"`);

        // Check API endpoint
        console.log('\n📝 Step 6: Testing API endpoint directly...');
        const apiResponse = await page.evaluate(async (projId) => {
            try {
                const response = await fetch(`/api/projects/${projId}`);
                const data = await response.json();
                return { success: true, data };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }, projectId);

        if (apiResponse.success) {
            console.log('   ✅ API endpoint working');
            console.log(`   Project Number: "${apiResponse.data.project_number}"`);
            console.log(`   Customer Name: "${apiResponse.data.customer_name}"`);
            console.log(`   Project Address: "${apiResponse.data.project_address}"`);
        } else {
            console.log(`   ❌ API error: ${apiResponse.error}`);
        }

        // Take screenshot
        await page.screenshot({
            path: 'cover-page-debug.png',
            fullPage: true
        });
        console.log('\n📸 Screenshot saved: cover-page-debug.png');

        console.log('\n🔍 Debug test completed. Check console logs above for issues.');
        console.log('Browser will close in 10 seconds...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ Test Failed:', error.message);
        await page.screenshot({ path: 'cover-page-debug-error.png', fullPage: true });

    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
