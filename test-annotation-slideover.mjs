import { chromium } from '@playwright/test';

(async () => {
    console.log('🧪 Testing annotation slideover functionality...\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000
    });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Capture console messages
    const messages = [];
    page.on('console', msg => {
        messages.push(`${msg.type()}: ${msg.text()}`);
        if (msg.type() === 'error') {
            console.log(`   ❌ ${msg.text()}`);
        }
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

        // Navigate to annotation page
        console.log('📝 Step 2: Opening annotation page...');
        await page.goto('http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1');
        await page.waitForTimeout(5000);
        console.log('✅ Page loaded\n');

        // Check if Livewire is loaded
        console.log('📝 Step 3: Checking Livewire...');
        const livewireExists = await page.evaluate(() => {
            return typeof window.Livewire !== 'undefined';
        });
        console.log(`   Livewire loaded: ${livewireExists ? '✅' : '❌'}`);

        // Check if annotation editor component exists
        const editorExists = await page.locator('[wire\\:id]').count();
        console.log(`   Livewire components found: ${editorExists}`);

        // Try to draw an annotation
        console.log('\n📝 Step 4: Drawing an annotation...');

        // Click "Draw Location" button
        const drawButton = page.locator('button:has-text("Draw Location")');
        await drawButton.click();
        await page.waitForTimeout(1000);
        console.log('✅ Clicked Draw Location button');

        // Check if canvas exists
        const canvasExists = await page.locator('canvas[id="pdf-canvas"]').count();
        console.log(`   Canvas found: ${canvasExists > 0 ? '✅' : '❌'}`);

        // Draw a rectangle on the canvas
        const canvas = page.locator('canvas[id="pdf-canvas"]');
        const box = await canvas.boundingBox();

        if (box) {
            const startX = box.x + 100;
            const startY = box.y + 100;
            const endX = box.x + 300;
            const endY = box.y + 200;

            await page.mouse.move(startX, startY);
            await page.mouse.down();
            await page.mouse.move(endX, endY);
            await page.mouse.up();

            console.log('✅ Drew rectangle on canvas');
            await page.waitForTimeout(2000);
        }

        // Check if slideover appears
        console.log('\n📝 Step 5: Checking for slideover...');
        await page.waitForTimeout(1000);

        const slideoverVisible = await page.locator('[wire\\:click="cancel"]').isVisible().catch(() => false);
        console.log(`   Slideover visible: ${slideoverVisible ? '✅' : '❌'}`);

        if (slideoverVisible) {
            console.log('\n📝 Step 6: Testing slideover form...');

            // Take screenshot of slideover
            await page.screenshot({ path: 'annotation-slideover-open.png', fullPage: false });
            console.log('✅ Screenshot: annotation-slideover-open.png');

            // Check form fields
            const labelField = await page.locator('input[wire\\:model="data.label"]').count();
            const notesField = await page.locator('textarea[wire\\:model="data.notes"]').count();
            const roomField = await page.locator('select[wire\\:model="data.room_id"]').count();

            console.log(`   Label field: ${labelField > 0 ? '✅' : '❌'}`);
            console.log(`   Notes field: ${notesField > 0 ? '✅' : '❌'}`);
            console.log(`   Room field: ${roomField > 0 ? '✅' : '❌'}`);

            // Try to fill the form
            console.log('\n📝 Step 7: Filling form...');

            if (labelField > 0) {
                await page.locator('input[wire\\:model="data.label"]').fill('Test Location');
                console.log('✅ Filled label');
            }

            if (notesField > 0) {
                await page.locator('textarea[wire\\:model="data.notes"]').fill('Test notes');
                console.log('✅ Filled notes');
            }

            await page.waitForTimeout(1000);

            // Try to save
            console.log('\n📝 Step 8: Attempting to save...');
            const saveButton = page.locator('button:has-text("Save")');
            const saveExists = await saveButton.count();

            if (saveExists > 0) {
                await saveButton.click();
                console.log('✅ Clicked Save button');
                await page.waitForTimeout(2000);

                // Check for errors
                const errorMessages = messages.filter(m => m.includes('error'));
                if (errorMessages.length > 0) {
                    console.log('\n❌ Errors found:');
                    errorMessages.forEach(msg => console.log(`   ${msg}`));
                } else {
                    console.log('✅ No errors');
                }
            } else {
                console.log('❌ Save button not found');
            }
        } else {
            console.log('❌ Slideover did not appear after drawing');

            // Check Alpine data
            const alpineData = await page.evaluate(() => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]');
                if (el && el._x_dataStack) {
                    return {
                        drawMode: el._x_dataStack[0].drawMode,
                        annotations: el._x_dataStack[0].annotations?.length || 0
                    };
                }
                return null;
            });

            if (alpineData) {
                console.log('\n   Alpine state:', alpineData);
            }
        }

        // Check for any console errors
        console.log('\n📝 Step 9: Checking console messages...');
        const errors = messages.filter(m => m.startsWith('error:'));
        if (errors.length > 0) {
            console.log('❌ Console errors found:');
            errors.forEach(msg => console.log(`   ${msg}`));
        } else {
            console.log('✅ No console errors');
        }

        console.log('\n✅ TEST COMPLETE!');
        console.log('Browser will stay open for 10 seconds...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'annotation-slideover-error.png', fullPage: true });
    } finally {
        await browser.close();
        console.log('\n✅ Browser closed');
    }
})();
