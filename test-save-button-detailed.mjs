import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    // Capture console logs
    page.on('console', msg => {
        if (msg.text().includes('EntityStore') || msg.text().includes('Livewire')) {
            console.log('[BROWSER]', msg.text());
        }
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
            pointerEvents: window.getComputedStyle(btn).pointerEvents
        } : null;
    });
    console.log('Initial:', saveButton);

    console.log('\n=== Making Change to Project Number ===');
    const projectNumberInput = page.locator('input[id*="project_number"]').first();
    await projectNumberInput.fill('TFW-0001-CHANGED');
    await page.waitForTimeout(2000);

    console.log('\n=== After Change Save Button State ===');
    saveButton = await page.evaluate(() => {
        const btn = Array.from(document.querySelectorAll('button')).find(b =>
            b.textContent.includes('Save changes')
        );
        return btn ? {
            disabled: btn.disabled,
            opacity: window.getComputedStyle(btn).opacity,
            pointerEvents: window.getComputedStyle(btn).pointerEvents
        } : null;
    });
    console.log('After Change:', saveButton);

    console.log('\n=== Checking Livewire dirty state ===');
    const dirtyState = await page.evaluate(() => {
        try {
            const component = window.Livewire?.all()?.[0];
            if (!component) return { error: 'No Livewire component found' };

            return {
                componentExists: true,
                snapshot: component.$wire?.__instance?.snapshot || 'N/A',
                effects: component.$wire?.__instance?.effects || 'N/A'
            };
        } catch (e) {
            return { error: e.message };
        }
    });
    console.log('Livewire State:', JSON.stringify(dirtyState, null, 2));

    console.log('\nKeeping browser open for inspection (30 seconds)...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
