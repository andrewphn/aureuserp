import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });

    const viewports = [
        { name: 'mobile', width: 375, height: 667 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'desktop', width: 1920, height: 1080 }
    ];

    for (const viewport of viewports) {
        console.log(`\n=== Testing ${viewport.name} (${viewport.width}x${viewport.height}) ===`);

        const context = await browser.newContext({ viewport });
        const page = await context.newPage();

        // Login
        await page.goto('http://aureuserp.test/admin/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Navigate to project
        await page.goto('http://aureuserp.test/admin/project/projects/1/edit');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Capture MINIMIZED state
        const footer = page.locator('[x-data="contextFooterGlobal()"]');
        await footer.screenshot({ path: `footer-${viewport.name}-minimized.png` });
        console.log(`Minimized screenshot saved: footer-${viewport.name}-minimized.png`);

        // Click to EXPAND
        await footer.click();
        await page.waitForTimeout(500);

        // Capture EXPANDED state
        await footer.screenshot({ path: `footer-${viewport.name}-expanded.png` });
        console.log(`Expanded screenshot saved: footer-${viewport.name}-expanded.png`);

        await context.close();
    }

    console.log('\nAll screenshots captured!');
    await browser.close();
})();
