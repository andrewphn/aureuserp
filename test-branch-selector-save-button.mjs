import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    // Capture console logs
    page.on('console', msg => {
        console.log('[BROWSER]', msg.text());
    });

    console.log('=== Login ===');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('\n=== Navigate to Project Edit Page ===');
    await page.goto('http://aureuserp.test/admin/project/projects/1/edit');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('\n=== Initial Save Button State ===');
    let saveButton = await page.evaluate(() => {
        const btn = Array.from(document.querySelectorAll('button')).find(b =>
            b.textContent.includes('Save changes')
        );
        return btn ? {
            disabled: btn.disabled,
            opacity: window.getComputedStyle(btn).opacity,
            pointerEvents: window.getComputedStyle(btn).pointerEvents,
            classList: Array.from(btn.classList)
        } : null;
    });
    console.log('Initial Save Button:', JSON.stringify(saveButton, null, 2));

    console.log('\n=== Finding Branch Field ===');

    // Find all form fields to see what we have
    const allFields = await page.evaluate(() => {
        const labels = Array.from(document.querySelectorAll('label'));
        return labels.map(label => ({
            text: label.textContent.trim(),
            for: label.getAttribute('for'),
            hasInput: !!label.querySelector('input, select, textarea'),
            siblingInputs: label.parentElement ?
                Array.from(label.parentElement.querySelectorAll('input, select, textarea')).length : 0
        }));
    });
    console.log('All form field labels:', JSON.stringify(allFields.filter(f => f.text.includes('Branch') || f.text.includes('Company')), null, 2));

    // Try to find the branch field using various selectors
    const branchFieldInfo = await page.evaluate(() => {
        // Look for the label
        const branchLabel = Array.from(document.querySelectorAll('label')).find(l =>
            l.textContent.trim().includes('Branch')
        );

        if (!branchLabel) return { found: false, reason: 'Label not found' };

        // Find the container
        const container = branchLabel.closest('[wire\\:key]') || branchLabel.closest('.fi-fo-field-wrp');
        if (!container) return { found: false, reason: 'Container not found', hasLabel: true };

        // Find select element (native or Filament)
        const nativeSelect = container.querySelector('select');
        const filamentSelect = container.querySelector('[x-data*="select"]') || container.querySelector('.fi-select');
        const inputHidden = container.querySelector('input[type="hidden"]');

        return {
            found: true,
            hasNativeSelect: !!nativeSelect,
            hasFilamentSelect: !!filamentSelect,
            hasHiddenInput: !!inputHidden,
            nativeSelectId: nativeSelect?.id,
            hiddenInputId: inputHidden?.id,
            containerClass: container.className,
            selectValue: nativeSelect?.value || inputHidden?.value
        };
    });

    console.log('Branch field info:', JSON.stringify(branchFieldInfo, null, 2));

    if (branchFieldInfo.found && branchFieldInfo.hasNativeSelect) {
        console.log('\n=== Testing Branch Selector ===');

        const branchSelect = page.locator(`#${branchFieldInfo.nativeSelectId}`);

        console.log('\n=== Current Branch Value ===');
        const currentValue = await branchSelect.inputValue();
        console.log('Current branch value:', currentValue);

        console.log('\n=== Getting Branch Options ===');
        const options = await page.evaluate((selectId) => {
            const select = document.getElementById(selectId);
            if (!select) return [];
            return Array.from(select.options).map(opt => ({
                value: opt.value,
                text: opt.text
            }));
        }, branchFieldInfo.nativeSelectId);
        console.log('Available branch options:', JSON.stringify(options, null, 2));

        if (options.length > 1) {
            // Select a different branch
            const newBranch = options.find(opt => opt.value !== currentValue && opt.value !== '');
            if (newBranch) {
                console.log(`\n=== Changing Branch to: ${newBranch.text} (${newBranch.value}) ===`);
                await branchSelect.selectOption(newBranch.value);

                // Trigger blur event to match live(onBlur: true)
                await branchSelect.blur();
                await page.waitForTimeout(2000);

                console.log('\n=== Save Button State After Branch Change ===');
                saveButton = await page.evaluate(() => {
                    const btn = Array.from(document.querySelectorAll('button')).find(b =>
                        b.textContent.includes('Save changes')
                    );
                    return btn ? {
                        disabled: btn.disabled,
                        opacity: window.getComputedStyle(btn).opacity,
                        pointerEvents: window.getComputedStyle(btn).pointerEvents,
                        classList: Array.from(btn.classList)
                    } : null;
                });
                console.log('After Branch Change:', JSON.stringify(saveButton, null, 2));

                console.log('\n=== Checking Livewire State ===');
                const livewireState = await page.evaluate(() => {
                    try {
                        const component = window.Livewire?.all()?.[0];
                        if (!component) return { error: 'No Livewire component found' };

                        return {
                            hasComponent: true,
                            dataKeys: Object.keys(component.$wire?.__instance?.canonical || {}),
                            effectsKeys: Object.keys(component.$wire?.__instance?.effects || {})
                        };
                    } catch (e) {
                        return { error: e.message };
                    }
                });
                console.log('Livewire State:', JSON.stringify(livewireState, null, 2));
            } else {
                console.log('No different branch option available to select');
            }
        } else {
            console.log('Not enough branch options to test (found ' + options.length + ')');
        }
    } else {
        console.log('Branch selector not usable:', branchFieldInfo);
    }

    console.log('\nKeeping browser open for inspection (30 seconds)...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
