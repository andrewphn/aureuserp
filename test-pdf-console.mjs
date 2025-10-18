import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('❌ Error:', msg.text());
            errors.push(msg.text());
        }
    });

    console.log('📝 Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    console.log('📄 Navigating to PDF review...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/pdf-review?pdf=1');
    await page.waitForTimeout(2000);

    console.log('🖱️  Clicking Annotate button...');
    await page.locator('button:has-text("Annotate")').first().click();
    await page.waitForTimeout(3000);

    console.log('\n🔍 Extracting Alpine component data...');
    const data = await page.evaluate(() => {
        const modal = document.querySelector('[x-data*="annotationSystemV2"]');
        if (modal) {
            return modal.getAttribute('x-data');
        }
        return null;
    });

    console.log('📊 x-data attribute:', data);

    console.log('\n⏳ Waiting 30 seconds...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
