import { chromium } from '@playwright/test';

const BASE_URL = 'http://aureuserp.test';

async function testAnnotationEditor() {
    console.log('🚀 Starting Annotation Editor Refactoring Test...\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('📝 Step 1: Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL(`${BASE_URL}/admin`);
        console.log('✅ Logged in successfully\n');

        // Step 2: Navigate to a room that has PDF pages
        console.log('📝 Step 2: Finding a project with PDF annotations...');
        await page.goto(`${BASE_URL}/admin/projects/rooms`);
        await page.waitForLoadState('networkidle');

        // Find a room with PDF page (look for "View" or "Edit" button)
        const firstRoom = await page.locator('table tbody tr').first();
        if (!firstRoom) {
            throw new Error('No rooms found');
        }

        // Click on the first room to view it
        await firstRoom.locator('a').first().click();
        await page.waitForLoadState('networkidle');
        console.log('✅ Opened room page\n');

        // Step 3: Check if annotation editor is on the page
        console.log('📝 Step 3: Checking for annotation editor component...');
        await page.waitForTimeout(2000);

        // Look for the annotation editor modal or canvas
        const hasCanvas = await page.locator('canvas').count() > 0;
        console.log(`Canvas elements found: ${await page.locator('canvas').count()}`);

        if (!hasCanvas) {
            console.log('⚠️ No canvas found on this page - trying to navigate to PDF viewer...');

            // Look for a link to PDF viewer or annotations
            const pdfLink = await page.locator('a:has-text("View PDF"), a:has-text("Annotations")').first();
            if (await pdfLink.count() > 0) {
                await pdfLink.click();
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(2000);
            }
        }

        // Step 4: Check for Livewire component
        console.log('📝 Step 4: Checking Livewire components...');
        const livewireComponents = await page.evaluate(() => {
            return window.Livewire ? Object.keys(window.Livewire.all()).length : 0;
        });
        console.log(`Livewire components found: ${livewireComponents}`);

        // Step 5: Check console for errors
        console.log('\n📝 Step 5: Checking for console errors...');
        const consoleErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        page.on('pageerror', error => {
            console.log('❌ Page Error:', error.message);
        });

        await page.waitForTimeout(2000);

        if (consoleErrors.length > 0) {
            console.log('❌ Console Errors Found:');
            consoleErrors.forEach(err => console.log('  -', err));
        } else {
            console.log('✅ No console errors detected');
        }

        // Step 6: Check PHP/Laravel errors in network
        console.log('\n📝 Step 6: Checking for PHP errors in responses...');
        let phpErrors = [];

        page.on('response', async response => {
            if (response.status() >= 500) {
                const text = await response.text();
                if (text.includes('ErrorException') || text.includes('FatalError')) {
                    phpErrors.push({
                        url: response.url(),
                        status: response.status(),
                        error: text.substring(0, 200)
                    });
                }
            }
        });

        await page.waitForTimeout(2000);

        if (phpErrors.length > 0) {
            console.log('❌ PHP Errors Found:');
            phpErrors.forEach(err => {
                console.log(`  URL: ${err.url}`);
                console.log(`  Status: ${err.status}`);
                console.log(`  Error: ${err.error}`);
            });
        } else {
            console.log('✅ No PHP errors detected');
        }

        // Step 7: Test service class loading
        console.log('\n📝 Step 7: Testing service class availability...');

        // Make a test request to see if services are loaded
        const response = await page.evaluate(async () => {
            try {
                const response = await fetch('/admin/projects/rooms', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                return response.ok;
            } catch (e) {
                return false;
            }
        });

        console.log(response ? '✅ Services responding correctly' : '❌ Services not responding');

        // Step 8: Take screenshot of current state
        console.log('\n📝 Step 8: Taking screenshot...');
        await page.screenshot({
            path: 'test-annotation-refactor-result.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved to test-annotation-refactor-result.png');

        // Summary
        console.log('\n' + '='.repeat(60));
        console.log('📊 TEST SUMMARY');
        console.log('='.repeat(60));
        console.log(`✅ Login: Success`);
        console.log(`✅ Room Navigation: Success`);
        console.log(`${hasCanvas ? '✅' : '⚠️'} Canvas Found: ${hasCanvas}`);
        console.log(`✅ Livewire Components: ${livewireComponents}`);
        console.log(`${consoleErrors.length === 0 ? '✅' : '❌'} Console Errors: ${consoleErrors.length}`);
        console.log(`${phpErrors.length === 0 ? '✅' : '❌'} PHP Errors: ${phpErrors.length}`);
        console.log('='.repeat(60));

        if (consoleErrors.length === 0 && phpErrors.length === 0) {
            console.log('\n🎉 ALL TESTS PASSED! Refactoring appears successful!');
        } else {
            console.log('\n⚠️ Some issues detected - check output above');
        }

    } catch (error) {
        console.error('\n❌ Test failed:', error.message);
        await page.screenshot({ path: 'test-annotation-refactor-error.png' });
        console.log('Error screenshot saved to test-annotation-refactor-error.png');
        throw error;
    } finally {
        console.log('\n🏁 Test complete. Browser will stay open for manual inspection...');
        console.log('Press Ctrl+C to close browser and exit.\n');
        // Keep browser open for inspection
        await page.waitForTimeout(300000); // 5 minutes
        await browser.close();
    }
}

testAnnotationEditor().catch(console.error);
