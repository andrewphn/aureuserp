import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Collect console messages
    const consoleMessages = [];
    page.on('console', msg => {
        const text = msg.text();
        consoleMessages.push(text);
        console.log(`[CONSOLE] ${text}`);
    });

    console.log('Logging in first...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');

    // Fill in login credentials
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');

    // Wait for redirect after login
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('Navigating to project edit page...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/edit');

    // Wait for page to load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('\n=== Initial Console Messages ===');
    const entityStoreMessages = consoleMessages.filter(m => m.includes('[EntityStore'));
    console.log(`Total EntityStore messages: ${entityStoreMessages.length}`);
    entityStoreMessages.slice(0, 10).forEach(m => console.log(m));

    // Check for loop indicators
    const loopIndicators = consoleMessages.filter(m =>
        m.includes('[EntityStore Commit]') ||
        m.includes('Syncing') ||
        m.includes('Updated project')
    );
    console.log(`\nLoop-related messages: ${loopIndicators.length}`);

    // Look for the branch field - Filament uses wire:model
    console.log('\n=== Looking for branch field ===');

    // Wait a bit for Livewire to initialize
    await page.waitForTimeout(2000);

    // Clear console messages before interaction
    consoleMessages.length = 0;

    console.log('\n=== Looking for branch dropdown ===');
    try {
        // Take initial screenshot for debugging
        await page.screenshot({ path: 'initial-page.png', fullPage: true });

        // Strategy 1: Look for any select field with "branch" in data attributes
        let selectButton = page.locator('[data-field-wrapper-id*="branch"] button').first();

        if (!await selectButton.isVisible({ timeout: 2000 })) {
            // Strategy 2: Find by label text and traverse to button
            console.log('Trying strategy 2: label-based search...');
            selectButton = page.locator('label').filter({ hasText: 'Branch' }).locator('..').locator('button').first();
        }

        if (!await selectButton.isVisible({ timeout: 2000 })) {
            // Strategy 3: Look for the select by wire:model
            console.log('Trying strategy 3: wire:model search...');
            selectButton = page.locator('[wire\\:model*="branch"]').locator('button').first();
        }

        if (!await selectButton.isVisible({ timeout: 2000 })) {
            // Strategy 4: Just find all combobox buttons and filter by visible text
            console.log('Trying strategy 4: searching all comboboxes...');
            const allButtons = page.locator('button[role="combobox"]');
            const count = await allButtons.count();
            console.log(`Found ${count} combobox buttons total`);

            // Log the HTML structure for debugging
            const html = await page.locator('form').first().innerHTML();
            console.log('\n=== Form HTML structure (first 3000 chars) ===');
            console.log(html.substring(0, 3000));

            throw new Error('Could not locate branch field with any strategy');
        }

        console.log('✓ Branch select button found, clicking...');
        await selectButton.click();
        await page.waitForTimeout(1500);

        // Take screenshot of dropdown
        await page.screenshot({ path: 'dropdown-open.png' });

        // Look for Trottier option - try multiple selectors
        let trottierOption = page.locator('[role="option"]').filter({ hasText: 'Trottier' });

        if (!await trottierOption.isVisible({ timeout: 2000 })) {
            trottierOption = page.locator('li').filter({ hasText: 'Trottier' });
        }

        if (await trottierOption.isVisible({ timeout: 3000 })) {
            console.log('✓ Found Trottier option, selecting...');
            await trottierOption.click();
            await page.waitForTimeout(3000);

            // Check console for loops after selection
            console.log('\n=== Console after branch selection ===');
            const afterMessages = consoleMessages.filter(m => m.includes('[EntityStore'));
            console.log(`EntityStore messages after selection: ${afterMessages.length}`);

            if (afterMessages.length > 0) {
                console.log('\nShowing all EntityStore messages:');
                afterMessages.forEach(m => console.log(`  ${m}`));
            }

            // Check if value persisted
            const buttonText = await selectButton.textContent();
            console.log(`\n✓ Branch button text after selection: ${buttonText.trim()}`);

            // Take final screenshot
            await page.screenshot({ path: 'after-selection.png' });

            if (afterMessages.length > 10) {
                console.log('\n❌ WARNING: Detected potential loop (>10 EntityStore messages)');
            } else if (afterMessages.some(m => m.includes('Skipped - on edit page'))) {
                console.log('\n✓ SUCCESS: EntityStore correctly skipping edit page!');
            } else {
                console.log('\n✓ SUCCESS: No loop detected!');
            }
        } else {
            console.log('✗ Trottier option not visible in dropdown');
            await page.screenshot({ path: 'branch-dropdown-debug.png' });
        }
    } catch (e) {
        console.log(`Error: ${e.message}`);
        await page.screenshot({ path: 'error-debug.png', fullPage: true });

        // Log all visible text to help debug
        const bodyText = await page.locator('body').textContent();
        console.log('\n=== Page contains "Branch": ' + bodyText.includes('Branch'));
    }

    console.log('\n=== Test Complete ===');
    console.log('Keeping browser open for 10 seconds for manual inspection...');
    await page.waitForTimeout(10000);

    await browser.close();
})();
