/**
 * Test Global Footer V2 Implementation
 *
 * Tests the new FilamentPHP v4 compliant footer widget
 */

import { chromium } from '@playwright/test';

const BASE_URL = 'http://aureuserp.test';
const CREDENTIALS = {
    email: 'info@tcswoodwork.com',
    password: 'Lola2024!'
};

async function testFooterV2() {
    console.log('🧪 Testing Global Footer V2...\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.fill('input[name="email"]', CREDENTIALS.email);
        await page.fill('input[name="password"]', CREDENTIALS.password);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin**', { timeout: 10000 });
        console.log('✅ Logged in successfully\n');

        // Step 2: Check footer on dashboard
        console.log('📝 Step 2: Checking footer on dashboard...');
        await page.waitForTimeout(2000);

        // Check if V2 footer widget exists
        const footerWidget = await page.locator('[x-data*="contextFooter"]').count();
        console.log(`   Footer widget found: ${footerWidget > 0 ? '✅ YES' : '❌ NO'}`);

        if (footerWidget > 0) {
            console.log('   ✅ V2 Footer is rendering!\n');
        } else {
            console.log('   ❌ V2 Footer NOT found - checking for V1...\n');
            const v1Footer = await page.locator('[x-data*="projectFooterGlobal"]').count();
            console.log(`   V1 Footer found: ${v1Footer > 0 ? 'YES' : 'NO'}`);
        }

        // Step 3: Take screenshot
        console.log('📝 Step 3: Taking screenshot...');
        await page.screenshot({
            path: 'test-footer-v2-dashboard.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved: test-footer-v2-dashboard.png\n');

        // Step 4: Check Alpine component registration
        console.log('📝 Step 4: Checking Alpine component registration...');
        const alpineCheck = await page.evaluate(() => {
            return {
                contextFooter: typeof window.contextFooter === 'function',
                Alpine: typeof window.Alpine !== 'undefined',
                Livewire: typeof window.Livewire !== 'undefined',
                componentRegistry: window.componentRegistry ? Object.keys(window.componentRegistry) : []
            };
        });

        console.log('   Alpine.js available:', alpineCheck.Alpine ? '✅' : '❌');
        console.log('   Livewire available:', alpineCheck.Livewire ? '✅' : '❌');
        console.log('   contextFooter function:', alpineCheck.contextFooter ? '✅' : '❌');
        console.log('   Registered components:', alpineCheck.componentRegistry.join(', ') || 'None');
        console.log('');

        // Step 5: Check console for errors
        console.log('📝 Step 5: Checking for JavaScript errors...');
        const logs = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                logs.push(msg.text());
            }
        });

        await page.waitForTimeout(2000);

        if (logs.length > 0) {
            console.log('❌ JavaScript errors found:');
            logs.forEach(log => console.log('   -', log));
        } else {
            console.log('✅ No JavaScript errors detected\n');
        }

        // Step 6: Navigate to projects and check footer
        console.log('📝 Step 6: Testing footer on projects page...');
        try {
            await page.goto(`${BASE_URL}/admin/project/projects`, { timeout: 10000 });
            await page.waitForTimeout(2000);

            const projectsFooter = await page.locator('[x-data*="contextFooter"]').count();
            console.log(`   Footer on projects page: ${projectsFooter > 0 ? '✅ Found' : '❌ Not found'}\n`);
        } catch (e) {
            console.log('   ⚠️  Could not navigate to projects page:', e.message);
        }

        // Step 7: Check footer visibility and state
        console.log('📝 Step 7: Checking footer state...');
        const footerState = await page.evaluate(() => {
            const footerEl = document.querySelector('[x-data*="contextFooter"]');
            if (!footerEl) return null;

            return {
                visible: footerEl.offsetHeight > 0,
                position: window.getComputedStyle(footerEl).position,
                bottom: window.getComputedStyle(footerEl).bottom,
                zIndex: window.getComputedStyle(footerEl).zIndex,
                width: footerEl.offsetWidth,
                height: footerEl.offsetHeight
            };
        });

        if (footerState) {
            console.log('   Visible:', footerState.visible ? '✅' : '❌');
            console.log('   Position:', footerState.position);
            console.log('   Bottom:', footerState.bottom);
            console.log('   Z-Index:', footerState.zIndex);
            console.log('   Dimensions:', `${footerState.width}x${footerState.height}px`);
        } else {
            console.log('   ❌ Footer element not found in DOM');
        }

        console.log('\n✅ Footer V2 test complete!');
        console.log('📊 Summary:');
        console.log(`   - V2 Footer Widget: ${footerWidget > 0 ? '✅ Found' : '❌ Not found'}`);
        console.log(`   - Alpine Component: ${alpineCheck.contextFooter ? '✅ Loaded' : '❌ Not loaded'}`);
        console.log(`   - JavaScript Errors: ${logs.length === 0 ? '✅ None' : `❌ ${logs.length} errors`}`);
        console.log(`   - Footer Visible: ${footerState?.visible ? '✅ Yes' : '❌ No'}`);

    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
}

// Run the test
testFooterV2().catch(console.error);
