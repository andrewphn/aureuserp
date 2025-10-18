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

    console.log('\n=== Screenshot: Footer Minimized (Default) ===');
    await page.screenshot({
        path: 'footer-minimized.png',
        fullPage: false
    });

    console.log('\n=== Expand Footer ===');
    const footerToggle = page.locator('[x-data="contextFooterGlobal()"]').locator('div.cursor-pointer').first();
    await footerToggle.click();
    await page.waitForTimeout(500);

    console.log('=== Screenshot: Footer Expanded ===');
    await page.screenshot({
        path: 'footer-expanded.png',
        fullPage: false
    });

    console.log('\n=== Minimize Footer Again ===');
    await footerToggle.click();
    await page.waitForTimeout(500);

    console.log('=== Screenshot: Footer Re-Minimized ===');
    await page.screenshot({
        path: 'footer-reminimized.png',
        fullPage: false
    });

    // Get computed styles of the footer
    const footerStyles = await page.evaluate(() => {
        const footer = document.querySelector('[x-data="contextFooterGlobal()"]');
        const toggleBar = footer.querySelector('div.cursor-pointer');
        const contentDiv = footer.querySelector('.fi-section-content');

        const footerComputed = getComputedStyle(footer);
        const toggleComputed = getComputedStyle(toggleBar);

        return {
            footer: {
                transform: footerComputed.transform,
                height: footerComputed.height,
                bottom: footerComputed.bottom,
                overflow: footerComputed.overflow,
                zIndex: footerComputed.zIndex
            },
            toggleBar: {
                height: toggleComputed.height,
                padding: toggleComputed.padding,
                overflow: toggleComputed.overflow
            },
            contentDiv: {
                display: contentDiv ? getComputedStyle(contentDiv).display : 'N/A'
            }
        };
    });

    console.log('\nFooter Styles:', JSON.stringify(footerStyles, null, 2));

    console.log('\nScreenshots saved. Check footer-minimized.png, footer-expanded.png, footer-reminimized.png');
    console.log('Keeping browser open for 20 seconds...');
    await page.waitForTimeout(20000);

    await browser.close();
})();
