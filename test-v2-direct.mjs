import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Navigate directly to the V2 page
        await page.goto('http://aureuserp.test/admin/projects/projects/1/annotate-pdf-v2?pdf=1&page=1');
        await page.waitForLoadState('networkidle');
        
        console.log('✓ Navigated to Annotate PDF V2 page');
        console.log('Current URL:', page.url());
        
        // Wait for page to fully load
        await page.waitForTimeout(3000);
        
        // Check console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                console.log('Console error:', msg.text());
            }
        });
        
        // Check if Alpine loaded
        const alpineLoaded = await page.evaluate(() => {
            return typeof window.Alpine !== 'undefined';
        });
        console.log('Alpine loaded:', alpineLoaded);
        
        // Check if content is visible
        const hasContent = await page.locator('.pdf-viewer-container').count();
        console.log('PDF viewer container found:', hasContent > 0);
        
        // Check if x-cloak is present
        const xCloakCount = await page.locator('[x-cloak]').count();
        console.log('Elements with x-cloak:', xCloakCount);
        
        // Take screenshot
        await page.screenshot({ path: 'v2-direct-test.png', fullPage: true });
        console.log('✓ Screenshot saved: v2-direct-test.png');
        
        // Keep browser open for inspection
        await page.waitForTimeout(5000);

    } catch (error) {
        console.error('Error:', error.message);
        await page.screenshot({ path: 'v2-direct-error.png' });
    } finally {
        await browser.close();
    }
})();
