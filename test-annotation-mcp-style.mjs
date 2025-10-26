import { chromium } from 'playwright';

/**
 * MCP-style test: Navigate to annotation page and test editor
 */

(async () => {
    const browser = await chromium.launch({
        headless: false,
        slowMo: 500 // Slow down for visibility
    });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        // Navigate to login
        console.log('📍 Navigating to login page...');
        await page.goto('http://aureuserp.test/admin/login');

        // Fill login form
        console.log('🔐 Logging in...');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/**');
        console.log('✅ Logged in\n');

        // Navigate to annotation page
        console.log('📍 Navigating to annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/9/annotate-v2/1?pdf=1', {
            waitUntil: 'networkidle'
        });
        await page.waitForTimeout(3000);

        // Take screenshot of page
        await page.screenshot({
            path: 'annotation-page-loaded.png',
            fullPage: true
        });
        console.log('📸 Screenshot: annotation-page-loaded.png\n');

        // Check for footer widget
        const footerExists = await page.locator('text=No Project Selected').or(page.locator('text=Project #')).count() > 0;
        console.log(`Footer widget: ${footerExists ? '✅ Loaded' : '❌ Not found'}\n`);

        // Wait for PDF to load
        console.log('⏳ Waiting for PDF canvas...');
        await page.waitForSelector('canvas', { timeout: 10000 });
        console.log('✅ PDF loaded\n');

        // Look for the "Create Annotation" button or existing annotations
        const createButton = page.locator('button:has-text("Create"), button:has-text("Add Annotation")');
        const existingAnnotations = page.locator('[data-annotation-id], rect[data-type]');

        const hasCreateButton = await createButton.count() > 0;
        const hasAnnotations = await existingAnnotations.count() > 0;

        console.log(`Create button: ${hasCreateButton ? '✅ Found' : '❌ Not found'}`);
        console.log(`Existing annotations: ${hasAnnotations ? '✅ Found' : '❌ Not found'}\n`);

        if (hasAnnotations) {
            console.log('🖱️  Clicking existing annotation...');
            await existingAnnotations.first().click();
            await page.waitForTimeout(2000);

            // Check if modal opened
            const modalOpened = await page.locator('text=Edit Annotation').isVisible();

            if (modalOpened) {
                console.log('✅ Annotation editor modal opened!\n');

                await page.screenshot({
                    path: 'annotation-editor-modal.png',
                    fullPage: true
                });
                console.log('📸 Screenshot: annotation-editor-modal.png\n');

                // Check for new simplified features
                const checks = {
                    'Annotation Tab': await page.locator('text=Annotation').count() > 0,
                    'Entity Details Tab (should NOT exist)': await page.locator('text=Entity Details').count() > 0,
                    'Linked Entity Summary': await page.locator('text=Linked Entity').count() > 0,
                    'Edit Details Link': await page.locator('text=/Edit Details/').count() > 0,
                    'Save & Next Button': await page.locator('text=Save & Next').count() > 0,
                    'Save Button': await page.locator('button:has-text("Save Changes")').count() > 0,
                    'Nested Tabs (should NOT exist)': await page.locator('text=/Room Info|Contents|Context/').count() > 0,
                };

                console.log('✨ Simplified Editor Features:');
                console.log('─'.repeat(50));
                for (const [feature, exists] of Object.entries(checks)) {
                    const shouldNotExist = feature.includes('should NOT exist');
                    const icon = shouldNotExist
                        ? (exists ? '❌ BAD' : '✅ GOOD')
                        : (exists ? '✅' : '❌');
                    console.log(`${icon} ${feature}: ${exists ? 'Present' : 'Not found'}`);
                }
                console.log('─'.repeat(50));

            } else {
                console.log('❌ Modal did not open\n');
            }
        } else {
            console.log('ℹ️  No annotations exist yet. Draw a rectangle on the PDF to create one.\n');
        }

        // Check for console errors
        const errors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        await page.waitForTimeout(2000);

        if (errors.length > 0) {
            console.log('\n⚠️  JavaScript Errors:');
            errors.forEach(err => console.log(`   - ${err}`));
        } else {
            console.log('\n✅ No JavaScript errors\n');
        }

        console.log('\n✅ Test complete! Browser will stay open for 10 seconds...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'test-error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
