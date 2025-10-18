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

    console.log('\n=== Screenshot: Footer Bar Only ===');
    const footer = page.locator('[x-data="contextFooterGlobal()"]');
    await footer.screenshot({ path: 'footer-bar-only.png' });

    console.log('\n=== Get Footer Text ===');
    const footerText = await page.evaluate(() => {
        const footer = document.querySelector('[x-data="contextFooterGlobal()"]');
        const toggleBar = footer.querySelector('div.cursor-pointer');
        return toggleBar ? toggleBar.innerText : 'Footer not found';
    });

    console.log('Footer Bar Text:', footerText);

    console.log('\nKeeping browser open for 20 seconds...');
    await page.waitForTimeout(20000);

    await browser.close();
})();
