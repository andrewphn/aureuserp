import { chromium } from '@playwright/test';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Navigate to project view
        await page.goto('http://aureuserp.test/admin/projects/projects/1');
        await page.waitForLoadState('networkidle');
        
        console.log('✓ Navigated to project page');

        // Click "Annotate PDF V2" button
        const v2Button = page.locator('text=Annotate PDF V2').first();
        if (await v2Button.isVisible()) {
            await v2Button.click();
            await page.waitForLoadState('networkidle');
            
            console.log('✓ Clicked Annotate PDF V2 button');
            console.log('Current URL:', page.url());
            
            // Wait a bit for Alpine to initialize
            await page.waitForTimeout(3000);
            
            // Check for errors
            const errors = await page.evaluate(() => {
                const consoleErrors = [];
                return consoleErrors;
            });
            
            // Take screenshot
            await page.screenshot({ path: 'annotate-v2-test.png', fullPage: true });
            console.log('✓ Screenshot saved: annotate-v2-test.png');
            
            // Check if Alpine loaded
            const alpineLoaded = await page.evaluate(() => {
                return typeof window.Alpine !== 'undefined';
            });
            console.log('Alpine loaded:', alpineLoaded);
            
            // Check page content
            const content = await page.content();
            console.log('Page has x-data:', content.includes('x-data'));
            console.log('Page has x-cloak:', content.includes('x-cloak'));
            
        } else {
            console.log('❌ Annotate PDF V2 button not found');
        }

    } catch (error) {
        console.error('Error:', error.message);
        await page.screenshot({ path: 'annotate-v2-error.png' });
    } finally {
        await browser.close();
    }
})();
