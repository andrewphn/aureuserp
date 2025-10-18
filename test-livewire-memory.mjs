import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    // Collect console messages
    page.on('console', msg => console.log(`[BROWSER] ${msg.text()}`));

    console.log('=== Login ===');
    await page.goto('http://aureuserp.test/admin/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('\n=== Navigate to Project View Page ===');
    await page.goto('http://aureuserp.test/admin/project/projects/1');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('\n=== Check Livewire Memory (serverMemo) ===');
    const livewireData = await page.evaluate(() => {
        // Get all Livewire components on the page
        const components = window.Livewire?.all() || [];

        return components.map(component => {
            const snapshot = component.snapshot || component.$wire?.__instance?.snapshot;
            return {
                name: snapshot?.memo?.name || 'unknown',
                id: snapshot?.memo?.id || 'unknown',
                hasData: !!snapshot?.data,
                dataKeys: snapshot?.data ? Object.keys(snapshot.data) : [],
                dataCount: snapshot?.data ? Object.keys(snapshot.data).length : 0
            };
        });
    });

    console.log('\n📊 Livewire Components Found:', livewireData.length);
    livewireData.forEach((comp, idx) => {
        console.log(`\n[${idx + 1}] Component: ${comp.name}`);
        console.log(`    ID: ${comp.id}`);
        console.log(`    Has Data: ${comp.hasData}`);
        console.log(`    Data Keys Count: ${comp.dataCount}`);
        if (comp.dataCount > 0 && comp.dataCount < 20) {
            console.log(`    Data Keys: ${comp.dataKeys.join(', ')}`);
        }
    });

    console.log('\n=== Now check Edit Page ===');
    await page.goto('http://aureuserp.test/admin/project/projects/1/edit');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    const editLivewireData = await page.evaluate(() => {
        const components = window.Livewire?.all() || [];

        return components.map(component => {
            const snapshot = component.snapshot || component.$wire?.__instance?.snapshot;
            return {
                name: snapshot?.memo?.name || 'unknown',
                id: snapshot?.memo?.id || 'unknown',
                hasData: !!snapshot?.data,
                dataKeys: snapshot?.data ? Object.keys(snapshot.data) : [],
                dataCount: snapshot?.data ? Object.keys(snapshot.data).length : 0,
                sampleData: snapshot?.data ? {
                    name: snapshot.data.name,
                    project_number: snapshot.data.project_number,
                    partner_id: snapshot.data.partner_id
                } : null
            };
        });
    });

    console.log('\n📊 Livewire Components on Edit Page:', editLivewireData.length);
    editLivewireData.forEach((comp, idx) => {
        console.log(`\n[${idx + 1}] Component: ${comp.name}`);
        console.log(`    ID: ${comp.id}`);
        console.log(`    Has Data: ${comp.hasData}`);
        console.log(`    Data Keys Count: ${comp.dataCount}`);
        if (comp.dataCount > 0 && comp.dataCount < 30) {
            console.log(`    Data Keys: ${comp.dataKeys.slice(0, 10).join(', ')}`);
        }
        if (comp.sampleData) {
            console.log(`    Sample Data:`, comp.sampleData);
        }
    });

    console.log('\n=== When Livewire Memory Gets Populated ===');
    console.log('1. On EDIT pages: Livewire serverMemo populated on mount() with form data');
    console.log('2. On VIEW pages: Livewire serverMemo mostly empty (no form to populate)');
    console.log('3. On field change: Livewire syncs updated field to serverMemo');
    console.log('4. On save: Livewire sends serverMemo.data to server');

    console.log('\n\nKeeping browser open for 30 seconds...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
