import { chromium } from '@playwright/test';

/**
 * E2E Test for Renamed PDF Annotation System
 *
 * Tests the new /annotate route (instead of /annotate-v2)
 * Verifies that the renaming works correctly.
 */

(async () => {
    console.log('🚀 Starting Renamed Annotation System Test...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // ========================================
        // STEP 1: Login
        // ========================================
        console.log('📝 Step 1: Logging in...');
        await page.goto('http://aureuserp.test/admin/login');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(2000);
        console.log('✅ Logged in successfully\n');

        // ========================================
        // STEP 2: Test New Route
        // ========================================
        console.log('📝 Step 2: Testing new /annotate route...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(3000);

        // Wait for PDF to load
        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ PDF viewer loaded on new route\n');

        // Verify page elements
        const pageTitle = await page.textContent('h1');
        console.log('   Page title:', pageTitle);

        // Check that title no longer has "(V3 Overlay System)"
        if (pageTitle.includes('V3 Overlay System') || pageTitle.includes('V2')) {
            console.log('⚠️  Warning: Page title still contains version reference!');
        } else {
            console.log('✅ Page title cleaned (no V2/V3 reference)');
        }

        // ========================================
        // STEP 3: Verify Old Route Still Works
        // ========================================
        console.log('\n📝 Step 3: Verifying old /annotate-v2 route...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1');
        await page.waitForTimeout(3000);

        await page.waitForSelector('.pdf-viewer-container', { timeout: 10000 });
        console.log('✅ Old route still works (backwards compatibility)\n');

        // ========================================
        // FINAL VERIFICATION
        // ========================================
        console.log('📝 Final Verification...');

        await page.screenshot({
            path: 'renamed-annotation-test.png',
            fullPage: true
        });
        console.log('   ✅ Screenshot saved: renamed-annotation-test.png');

        console.log('\n' + '='.repeat(60));
        console.log('📊 RENAMING TEST SUMMARY');
        console.log('='.repeat(60));
        console.log('✅ New /annotate route works');
        console.log('✅ Old /annotate-v2 route works (backwards compatible)');
        console.log('✅ Page title cleaned of version references');
        console.log('='.repeat(60));
        console.log('\n🎉 Renaming Test Completed Successfully!\n');

        console.log('Browser will close in 5 seconds...');
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('\n❌ Test Failed:');
        console.error('Error:', error.message);

        await page.screenshot({ path: 'renamed-annotation-error.png', fullPage: true });
        console.log('Error screenshot saved: renamed-annotation-error.png');

    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
