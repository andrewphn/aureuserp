import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    console.log('📝 Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin', { timeout: 5000 });
    console.log('✅ Logged in');

    console.log('📄 Navigating to PDF review...');
    await page.goto('http://aureuserp.test/admin/project/projects/1/pdf-review?pdf=1');
    await page.waitForTimeout(2000);

    console.log('🖱️  Clicking Annotate button...');
    await page.locator('button:has-text("Annotate")').first().click();
    await page.waitForTimeout(2000);

    console.log('🔍 Checking PDF URL and console errors...');
    
    // Get Alpine component data
    const alpineData = await page.evaluate(() => {
        const modal = document.querySelector('[x-data*="annotationSystemV2"]');
        if (modal && window.Alpine) {
            const component = window.Alpine.$data(modal);
            return {
                pdfUrl: component.pdfUrl,
                pageNumber: component.pageNumber,
                pdfPageId: component.pdfPageId,
                projectId: component.projectId,
                error: component.error
            };
        }
        return null;
    });

    console.log('📊 Alpine Component Data:', JSON.stringify(alpineData, null, 2));

    // Check console errors
    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
        }
    });

    await page.waitForTimeout(3000);

    console.log('🐛 Console Errors:');
    errors.forEach(err => console.log('  ', err));

    console.log('\nKeeping browser open for 30 seconds...');
    await page.waitForTimeout(30000);

    await browser.close();
})();
