import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });

    // Test different viewport sizes
    const viewports = [
        { name: 'mobile', width: 375, height: 667 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'desktop', width: 1440, height: 900 },
    ];

    for (const viewport of viewports) {
        console.log(`\n=== Testing ${viewport.name} (${viewport.width}x${viewport.height}) ===`);

        const context = await browser.newContext({ viewport });
        const page = await context.newPage();

        // Login
        await page.goto('http://aureuserp.test/admin/login', { waitUntil: 'domcontentloaded' });
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        await page.fill('input[type="email"]', 'info@tcswoodwork.com');
        await page.fill('input[type="password"]', 'Lola2024!');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        await page.waitForTimeout(3000);

        // Navigate to project
        await page.goto('http://aureuserp.test/admin/project/projects/1/edit', { waitUntil: 'domcontentloaded', timeout: 15000 });
        await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
        await page.waitForTimeout(3000);

        // Check footer state
        const footerState = await page.evaluate(() => {
            const footer = document.querySelector('[x-data="contextFooterGlobal()"]');
            if (!footer) return { found: false };

            const toggleBar = footer.querySelector('[\\@click]');
            const computedStyle = window.getComputedStyle(footer);
            const toggleStyle = window.getComputedStyle(toggleBar);

            return {
                found: true,
                transform: computedStyle.transform,
                footerHeight: footer.offsetHeight,
                toggleBarHeight: toggleBar.offsetHeight,
                toggleBarPaddingTop: toggleStyle.paddingTop,
                toggleBarPaddingBottom: toggleStyle.paddingBottom
            };
        });
        console.log('Footer state:', footerState);

        // Capture minimized footer
        const footer = page.locator('[x-data="contextFooterGlobal()"]');
        await footer.screenshot({ path: `footer-${viewport.name}-minimized-fixed.png` });
        console.log(`Screenshot saved: footer-${viewport.name}-minimized-fixed.png`);

        await context.close();
    }

    console.log('\n✅ All screenshots captured!');
    await browser.close();
})();
