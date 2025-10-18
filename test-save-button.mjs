import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

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

    console.log('\n=== Check Save Button State ===');
    const saveButtonState = await page.evaluate(() => {
        // Find save button
        const saveButtons = Array.from(document.querySelectorAll('button')).filter(btn =>
            btn.textContent.includes('Save') || btn.textContent.includes('changes')
        );

        if (saveButtons.length === 0) return { found: false };

        const saveButton = saveButtons[0];
        return {
            found: true,
            text: saveButton.textContent.trim(),
            disabled: saveButton.disabled,
            classes: saveButton.className,
            ariaDisabled: saveButton.getAttribute('aria-disabled'),
            pointerEvents: window.getComputedStyle(saveButton).pointerEvents,
            opacity: window.getComputedStyle(saveButton).opacity
        };
    });

    console.log('Save Button State:', JSON.stringify(saveButtonState, null, 2));

    console.log('\n=== Check Form State ===');
    const formState = await page.evaluate(() => {
        const livewireComponent = window.Livewire?.all()?.[0];
        if (!livewireComponent) return { found: false };

        return {
            found: true,
            hasUnsavedChanges: livewireComponent.get('unsavedChanges'),
            data: Object.keys(livewireComponent.get('data') || {}).length
        };
    });

    console.log('Form State:', JSON.stringify(formState, null, 2));

    console.log('\n=== Make a Small Change ===');
    // Try to change project number to trigger dirty state
    await page.fill('input[name="data.project_number"]', 'TEST-0001-Changed');
    await page.waitForTimeout(1000);

    console.log('\n=== Check Save Button After Change ===');
    const saveButtonAfterChange = await page.evaluate(() => {
        const saveButtons = Array.from(document.querySelectorAll('button')).filter(btn =>
            btn.textContent.includes('Save') || btn.textContent.includes('changes')
        );

        if (saveButtons.length === 0) return { found: false };

        const saveButton = saveButtons[0];
        return {
            found: true,
            text: saveButton.textContent.trim(),
            disabled: saveButton.disabled,
            classes: saveButton.className
        };
    });

    console.log('Save Button After Change:', JSON.stringify(saveButtonAfterChange, null, 2));

    console.log('\nKeeping browser open for 30 seconds...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
