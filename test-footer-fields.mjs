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

    console.log('\n=== Check Footer Data ===');
    const footerData = await page.evaluate(() => {
        const footer = Alpine.$data(document.querySelector('[x-data="contextFooterGlobal()"]'));
        return {
            contextType: footer.contextType,
            preferencesLoaded: footer.preferencesLoaded,
            userPreferences: footer.userPreferences,
            isMinimized: footer.isMinimized,
            fieldsForDisplay: footer.getFieldsForDisplay(),
            field0: footer.getFieldValue(footer.getFieldsForDisplay()[0]),
            field1: footer.getFieldValue(footer.getFieldsForDisplay()[1]),
        };
    });

    console.log('Footer Data:', JSON.stringify(footerData, null, 2));

    console.log('\nKeeping browser open for 10 seconds...');
    await page.waitForTimeout(10000);

    await browser.close();
})();
