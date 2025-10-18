import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });

    // Test different viewport sizes
    const viewports = [
        { name: 'mobile', width: 375, height: 667 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'laptop', width: 1366, height: 768 },
        { name: 'desktop', width: 1920, height: 1080 },
        { name: 'ultrawide', width: 2560, height: 1440 }
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

        // Capture footer screenshot
        const footer = page.locator('[x-data="contextFooterGlobal()"]');
        await footer.screenshot({ path: `footer-${viewport.name}.png` });

        console.log(`Screenshot saved: footer-${viewport.name}.png`);

        await context.close();
    }

    console.log('\nAll screenshots captured!');
    await browser.close();
})();
