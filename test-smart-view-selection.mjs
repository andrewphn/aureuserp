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

    console.log('\n📂 Navigating to PDF review page (correct URL format)...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/pdf-review?pdf=1');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✓ On PDF review page');

    // Wait for page to fully load - look for PDF viewer or annotation buttons
    await page.waitForSelector('canvas, button:has-text("Location"), .pdf-viewer', { timeout: 15000 });
    await page.waitForTimeout(2000);

    console.log('\n🔍 Checking existing annotations in database...');
    const existingAnnotations = await page.evaluate(() => {
        const alpine = window.Alpine?.$data(document.querySelector('[x-data]'));
        const locationAnnotations = alpine?.annotations?.filter(a => a.type === 'location') || [];

        return locationAnnotations.map(a => ({
            id: a.id,
            label: a.label,
            viewType: a.viewType,
            viewOrientation: a.viewOrientation,
            roomLocationId: a.roomLocationId,
            pageNumber: a.pdfPage?.page_number || a.pageNumber || '?'
        }));
    });

    console.log(`\n📊 Found ${existingAnnotations.length} location annotations:`);
    existingAnnotations.forEach(a => {
        const orientation = a.viewOrientation ? ` - ${a.viewOrientation}` : '';
        console.log(`   • ${a.label} (${a.viewType}${orientation}) on Page ${a.pageNumber} [ID: ${a.id}]`);
    });

    // Check if we have any K1 Sink Wall annotations
    const sinkWallAnnotations = existingAnnotations.filter(a =>
        a.label.toLowerCase().includes('sink')
    );

    console.log(`\n🔍 Sink Wall annotations: ${sinkWallAnnotations.length}`);
    if (sinkWallAnnotations.length > 0) {
        console.log('   Existing Sink Wall views:');
        sinkWallAnnotations.forEach(a => {
            const orientation = a.viewOrientation ? ` - ${a.viewOrientation}` : '';
            console.log(`   • ${a.viewType}${orientation} (Page ${a.pageNumber})`);
        });
    }

    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST: Smart View Type Selection');
    console.log('═══════════════════════════════════════════════════\n');

    // Navigate to page 3 to draw new annotation
    console.log('📄 Navigating to page 3...');
    const pageNumberInput = page.locator('input[type="number"]').first();
    await pageNumberInput.fill('3');
    await page.keyboard.press('Enter');
    await page.waitForTimeout(2000);
    console.log('✓ On page 3');

    // Enter draw mode for location
    console.log('\n🖊️ Entering draw mode for Location...');
    const locationButton = page.locator('button:has-text("📍 Location")');
    await locationButton.click();
    await page.waitForTimeout(1000);

    // Draw a location annotation
    console.log('✏️ Drawing location annotation...');
    const canvas = page.locator('canvas').first();
    const canvasBox = await canvas.boundingBox();

    if (!canvasBox) {
        throw new Error('Canvas not found');
    }

    const startX = canvasBox.x + 200;
    const startY = canvasBox.y + 200;
    const endX = startX + 80;
    const endY = startY + 80;

    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(endX, endY);
    await page.mouse.up();
    await page.waitForTimeout(1500);
    console.log('✓ Drew location annotation');

    // Wait for annotation editor to open
    console.log('\n⏳ Waiting for annotation editor...');
    await page.waitForSelector('.fi-modal', { timeout: 5000 });
    await page.waitForTimeout(1000);
    console.log('✓ Annotation editor opened');

    // Fill in parent annotation (K1)
    console.log('\n📝 Filling in form fields...');
    console.log('   Selecting parent annotation: K1');

    // Click the parent annotation select
    const parentSelect = page.locator('label:has-text("Parent Annotation")').locator('..').locator('input[role="combobox"]').first();
    await parentSelect.click();
    await page.waitForTimeout(500);

    // Type to search for K1
    await parentSelect.fill('K1');
    await page.waitForTimeout(1000);

    // Click the K1 option
    const k1Option = page.locator('[role="option"]:has-text("K1")').first();
    await k1Option.click();
    await page.waitForTimeout(1000);
    console.log('   ✓ Selected K1 as parent');

    // Fill in label
    console.log('   Typing label: "Sink Wall Test"');
    const labelInput = page.locator('input[name="label"]').or(page.locator('label:has-text("Label")').locator('..').locator('input')).first();
    await labelInput.fill('Sink Wall Test');
    await page.waitForTimeout(500);
    console.log('   ✓ Label filled');

    // NOW CHECK THE VIEW TYPE DROPDOWN
    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('CHECKING: View Type Dropdown Options');
    console.log('═══════════════════════════════════════════════════\n');

    // Wait for Livewire to update after selecting parent and label
    await page.waitForTimeout(2000);

    // Take screenshot before checking dropdown
    await page.screenshot({ path: 'view-type-before-click.png', fullPage: true });
    console.log('📸 Screenshot saved: view-type-before-click.png');

    // Click the view type dropdown to open it
    console.log('\n🔽 Opening View Type dropdown...');
    const viewTypeSelect = page.locator('label:has-text("View Type")').locator('..').locator('select, button[role="combobox"], input[role="combobox"]').first();

    // Check if it's a native select or custom select
    const tagName = await viewTypeSelect.evaluate(el => el.tagName.toLowerCase());
    console.log(`   View Type element type: ${tagName}`);

    if (tagName === 'select') {
        // Native select - get options
        const options = await page.locator('label:has-text("View Type")').locator('..').locator('select option').allTextContents();
        console.log('\n📋 View Type Options:');
        options.forEach((opt, i) => {
            console.log(`   ${i + 1}. ${opt}`);
            if (opt.includes('✓')) {
                console.log('      ⭐ THIS OPTION HAS A CHECKMARK!');
            }
        });
    } else {
        // Custom select (FilamentPHP) - click to open
        await viewTypeSelect.click();
        await page.waitForTimeout(1000);

        // Take screenshot of opened dropdown
        await page.screenshot({ path: 'view-type-dropdown-open.png', fullPage: true });
        console.log('📸 Screenshot saved: view-type-dropdown-open.png');

        // Get all options from the dropdown
        const options = await page.locator('[role="option"], li[role="option"], .fi-select-option').allTextContents();
        console.log('\n📋 View Type Options:');
        if (options.length > 0) {
            options.forEach((opt, i) => {
                console.log(`   ${i + 1}. ${opt}`);
                if (opt.includes('✓')) {
                    console.log('      ⭐ THIS OPTION HAS A CHECKMARK!');
                }
            });
        } else {
            console.log('   ⚠️  No options found! Dropdown may not have opened correctly.');
        }
    }

    // Check helper text
    console.log('\n📖 Checking helper text...');
    const helperText = await page.locator('label:has-text("View Type")').locator('..').locator('.fi-fo-field-wrp-helper-text, p.text-sm').first().textContent().catch(() => 'Not found');
    console.log(`   Helper text: "${helperText}"`);

    // Select Plan View
    console.log('\n✅ Selecting "Plan View"...');
    if (tagName === 'select') {
        await viewTypeSelect.selectOption({ label: /Plan/ });
    } else {
        const planOption = page.locator('[role="option"]:has-text("Plan"), li:has-text("Plan")').first();
        await planOption.click();
    }
    await page.waitForTimeout(1000);
    console.log('   ✓ Plan View selected');

    // Take final screenshot
    await page.screenshot({ path: 'view-type-selected.png', fullPage: true });
    console.log('📸 Screenshot saved: view-type-selected.png');

    // Try to save
    console.log('\n💾 Attempting to save...');
    const saveButton = page.locator('button:has-text("Save"), button[type="submit"]').last();
    await saveButton.click();
    await page.waitForTimeout(3000);

    // Check for notification
    console.log('\n🔔 Checking for duplicate warning notification...');
    const notification = await page.locator('.fi-no-notification, [role="alert"], .filament-notifications').first().textContent().catch(() => '');

    if (notification.includes('Duplicate') || notification.includes('already exists')) {
        console.log('   ✅ Duplicate warning notification appeared:');
        console.log(`   "${notification}"`);
    } else if (notification) {
        console.log(`   ℹ️  Notification: "${notification}"`);
    } else {
        console.log('   ℹ️  No notification detected');
    }

    // Take final screenshot
    await page.screenshot({ path: 'test-complete.png', fullPage: true });
    console.log('\n📸 Final screenshot saved: test-complete.png');

    console.log('\n\n═══════════════════════════════════════════════════');
    console.log('TEST RESULTS SUMMARY');
    console.log('═══════════════════════════════════════════════════\n');

    console.log('✅ Test completed successfully');
    console.log('📁 Screenshots saved:');
    console.log('   - view-type-before-click.png');
    console.log('   - view-type-dropdown-open.png');
    console.log('   - view-type-selected.png');
    console.log('   - test-complete.png');

    console.log('\n⏸️  Pausing for manual inspection...');
    await page.waitForTimeout(5000);

} catch (error) {
    console.error('\n❌ Error:', error);
    await page.screenshot({ path: 'test-error.png', fullPage: true });
    console.log('📸 Error screenshot saved: test-error.png');
} finally {
    await browser.close();
}
